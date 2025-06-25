<?php

include('requiere_login.php'); 

include('conexion.php');


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$required_tables = ['equipos', 'materiales', 'historial_cambios', 'almacenistas'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        die("Error: La tabla '$table' no existe en la base de datos 'proyecto_almacen'. Por favor, verifica las tablas.");
    }
}


if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1; 
}
$id_usuario = (int)$_SESSION['id_usuario'];

// Configuración de paginación
$registros_por_pagina = 10;

// Manejar solicitud AJAX para actualizar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_equipo'])) {
    $response = ['success' => false, 'message' => ''];

    $id_equipo = (int)$_POST['id_equipo'];
    $marca = trim($_POST['marca'] ?? '');
    $custom_marca = trim($_POST['custom_marca'] ?? '');
    $serial = trim($_POST['serial'] ?? '');
    $estado = $_POST['estado'] ?? '';

    // --- Validaciones para actualizar equipo ---
    if (empty($marca)) {
        $response['message'] = "Error: La marca del equipo es obligatoria.";
    } elseif ($marca === 'Otra' && empty($custom_marca)) {
        $response['message'] = "Error: Debe especificar la marca si selecciona 'Otra'.";
    } elseif (empty($serial)) {
        $response['message'] = "Error: El serial del equipo es obligatorio.";
    } elseif (empty($estado)) {
        $response['message'] = "Error: El estado del equipo es obligatorio.";
    } elseif (!in_array($estado, ['disponible', 'prestado', 'deteriorado'])) {
        $response['message'] = "Error: Estado de equipo inválido.";
    } else {
        $final_marca = ($marca === 'Otra') ? $custom_marca : $marca;

        // Validar que el serial no exista en otro equipo
        $sql = "SELECT id_equipo FROM equipos WHERE serial = ? AND id_equipo != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $serial, $id_equipo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['message'] = "Error: Ya existe otro equipo con el serial '$serial'.";
        } else {
            $sql = "UPDATE equipos SET marca = ?, serial = ?, estado = ? WHERE id_equipo = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $final_marca, $serial, $estado, $id_equipo);
            if ($stmt->execute()) {
                // Registrar en historial_cambios
                $detalles = json_encode([
                    'marca' => $final_marca,
                    'serial' => $serial,
                    'estado' => $estado
                ]);
                $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'equipos', 'actualizar', ?, ?)";
                $stmt_historial = $conn->prepare($sql_historial);
                $stmt_historial->bind_param("iis", $id_usuario, $id_equipo, $detalles);
                $stmt_historial->execute();

                $response['success'] = true;
                $response['message'] = "Confirmación exitosa";
            } else {
                $response['message'] = "Error al actualizar equipo: " . $stmt->error;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para eliminar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_equipo'])) {
    $response = ['success' => false, 'message' => ''];

    $id_equipo = (int)$_POST['id_equipo'];

    $sql = "DELETE FROM equipos WHERE id_equipo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_equipo);
    if ($stmt->execute()) {
        $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro) VALUES (?, 'equipos', 'eliminar', ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("ii", $id_usuario, $id_equipo);
        $stmt_historial->execute();

        $response['success'] = true;
        $response['message'] = "Confirmación exitosa";
    } else {
        $response['message'] = "Error al eliminar equipo: " . $stmt->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para agregar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_equipo_ajax'])) {
    $response = ['success' => false, 'message' => '', 'id_equipo' => null];

    $marca = trim($_POST['marca'] ?? '');
    $custom_marca = trim($_POST['custom_marca'] ?? '');
    $serial = trim($_POST['serial'] ?? '');
    $estado = 'disponible'; // Siempre se agrega como disponible

    // --- Validaciones para agregar equipo ---
    if (empty($marca)) {
        $response['message'] = "Error: La marca del equipo es obligatoria.";
    } elseif ($marca === 'Otra' && empty($custom_marca)) {
        $response['message'] = "Error: Debe especificar la marca si selecciona 'Otra'.";
    } elseif (empty($serial)) {
        $response['message'] = "Error: El serial del equipo es obligatorio.";
    } else {
        $final_marca = ($marca === 'Otra') ? $custom_marca : $marca;

        $sql = "SELECT id_equipo FROM equipos WHERE serial = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serial);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['message'] = "Error: Ya existe un equipo con el serial '$serial'.";
        } else {
            $sql = "INSERT INTO equipos (marca, serial, estado, fecha_creacion) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $final_marca, $serial, $estado);
            if ($stmt->execute()) {
                $id_equipo = $conn->insert_id;

                $detalles = json_encode([
                    'marca' => $final_marca,
                    'serial' => $serial,
                    'estado' => $estado
                ]);
                $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'equipos', 'agregar', ?, ?)";
                $stmt_historial = $conn->prepare($sql_historial);
                $stmt_historial->bind_param("iis", $id_usuario, $id_equipo, $detalles);
                $stmt_historial->execute();

                $response['success'] = true;
                $response['message'] = "Confirmación exitosa";
                $response['id_equipo'] = $id_equipo;
            } else {
                $response['message'] = "Error al agregar equipo: " . $stmt->error;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para agregar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_material_ajax'])) {
    $response = ['success' => false, 'message' => '', 'id_material' => null];

    try {
        $nombre = trim($_POST['nombre_material'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : -1; // Usar -1 para distinguir de 0 si el campo no se envía

        // --- Validaciones para agregar material ---
        if (empty($nombre)) {
            throw new Exception("Error: El nombre del material es obligatorio.");
        }
        if (empty($tipo)) {
            throw new Exception("Error: El tipo de material es obligatorio.");
        }
        if (!in_array($tipo, ['consumible', 'no consumible'])) {
            throw new Exception("Error: Tipo de material inválido.");
        }
        if ($stock < 1) { // Stock debe ser al menos 1 al agregar
            throw new Exception("Error: El stock debe ser un número entero mayor o igual a 1.");
        }

        $sql = "INSERT INTO materiales (nombre, tipo, stock, fecha_creacion) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        $stmt->bind_param("ssi", $nombre, $tipo, $stock);
        if ($stmt->execute()) {
            $id_material = $conn->insert_id;

            $detalles = json_encode([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'stock' => $stock
            ]);
            $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'materiales', 'agregar', ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            if (!$stmt_historial) {
                throw new Exception("Error al preparar la consulta del historial: " . $conn->error);
            }
            $stmt_historial->bind_param("iis", $id_usuario, $id_material, $detalles);
            $stmt_historial->execute();

            $response['success'] = true;
            $response['message'] = "Confirmación exitosa";
            $response['id_material'] = $id_material;
        } else {
            throw new Exception("Error al agregar material: " . $stmt->error);
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para actualizar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_material_ajax'])) {
    $response = ['success' => false, 'message' => ''];

    $id_material = (int)$_POST['id_material'];
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : -1;

    // --- Validaciones para actualizar material ---
    if (empty($nombre)) {
        $response['message'] = "Error: El nombre del material es obligatorio.";
    } elseif (empty($tipo)) {
        $response['message'] = "Error: El tipo de material es obligatorio.";
    } elseif (!in_array($tipo, ['consumible', 'no consumible'])) {
        $response['message'] = "Error: Tipo de material inválido.";
    } elseif ($stock < 0) { // Stock puede ser 0 al actualizar
        $response['message'] = "Error: El stock no puede ser un número negativo.";
    } else {
        $sql = "UPDATE materiales SET nombre = ?, tipo = ?, stock = ? WHERE id_material = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nombre, $tipo, $stock, $id_material);
        if ($stmt->execute()) {
            $detalles = json_encode([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'stock' => $stock
            ]);
            $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'materiales', 'actualizar', ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iis", $id_usuario, $id_material, $detalles);
            $stmt_historial->execute();

            $response['success'] = true;
            $response['message'] = "Confirmación exitosa";
        } else {
            $response['message'] = "Error al actualizar material: " . $stmt->error;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para eliminar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_material_ajax'])) {
    $response = ['success' => false, 'message' => ''];

    $id_material = (int)$_POST['id_material'];

    $sql = "DELETE FROM materiales WHERE id_material = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_material);
    if ($stmt->execute()) {
        $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro) VALUES (?, 'materiales', 'eliminar', ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("ii", $id_usuario, $id_material);
        $stmt_historial->execute();

        $response['success'] = true;
        $response['message'] = "Confirmación exitosa";
    } else {
        $response['message'] = "Error al eliminar material: " . $stmt->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para eliminar un registro del historial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_historial'])) {
    $response = ['success' => false, 'message' => ''];

    $id_historial = (int)$_POST['id_historial'];

    $sql = "DELETE FROM historial_cambios WHERE id_historial = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_historial);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Confirmación exitosa";
    } else {
        $response['message'] = "Error al eliminar registro del historial: " . $stmt->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para obtener el historial actualizado
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_historial'])) {
    $response = ['success' => false, 'data' => [], 'message' => ''];

    $sql = "SELECT h.id_historial, h.id_usuario, a.nombres, a.apellidos, h.tabla_afectada, h.accion, h.id_registro, h.fecha_accion, h.detalles 
            FROM historial_cambios h 
            LEFT JOIN almacenistas a ON h.id_usuario = a.id_almacenista 
            ORDER BY h.fecha_accion DESC";
    $result = $conn->query($sql);

    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id_historial' => $row['id_historial'],
                'id_usuario' => $row['id_usuario'],
                'nombres' => $row['nombres'],
                'apellidos' => $row['apellidos'],
                'tabla_afectada' => $row['tabla_afectada'],
                'accion' => $row['accion'],
                'id_registro' => $row['id_registro'],
                'fecha_accion' => $row['fecha_accion'],
                'detalles' => $row['detalles']
            ];
        }
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['message'] = "Error al cargar el historial: " . $conn->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para obtener el conteo de equipos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_equipos_count'])) {
    $response = ['success' => false, 'count' => 0, 'debug' => []];

    $sql = "SELECT COUNT(*) FROM equipos";
    $conditions = [];
    $params = [];
    $types = '';

    $response['debug']['params'] = [
        'search' => $_GET['search'] ?? '',
        'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['fecha_fin'] ?? ''
    ];

    if (!empty($_GET['search'])) {
        $search = "%" . $conn->real_escape_string($_GET['search']) . "%";
        $conditions[] = "(LOWER(marca) LIKE LOWER(?) OR LOWER(serial) LIKE LOWER(?) OR LOWER(estado) LIKE LOWER(?))";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }

    if (!empty($_GET['fecha_inicio'])) {
        $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
        $conditions[] = "DATE(fecha_creacion) >= ?";
        $params[] = $fecha_inicio;
        $types .= "s";
    }
    if (!empty($_GET['fecha_fin'])) {
        $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
        $conditions[] = "DATE(fecha_creacion) <= ?";
        $params[] = $fecha_fin;
        $types .= "s";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $response['debug']['sql'] = $sql;
    $response['debug']['params_values'] = $params;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response['message'] = "Error al preparar la consulta: " . $conn->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $response['message'] = "Error al ejecutar la consulta: " . $stmt->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $total_equipos = $stmt->get_result()->fetch_row()[0];
    $response['success'] = true;
    $response['count'] = $total_equipos;

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para obtener los equipos con búsqueda y filtros
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_equipos'])) {
    $response = ['success' => false, 'data' => [], 'total' => 0, 'message' => '', 'debug' => []];

    $pagina = isset($_GET['pagina_equipos']) ? (int)$_GET['pagina_equipos'] : 1;
    $inicio = ($pagina - 1) * $registros_por_pagina;

    $response['debug']['params'] = [
        'search' => $_GET['search'] ?? '',
        'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['fecha_fin'] ?? '',
        'pagina' => $pagina,
        'inicio' => $inicio
    ];

    // Consulta para contar el total de equipos con filtros
    $sql_count = "SELECT COUNT(*) FROM equipos";
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($_GET['search'])) {
        $search = "%" . $conn->real_escape_string($_GET['search']) . "%";
        $conditions[] = "(LOWER(marca) LIKE LOWER(?) OR LOWER(serial) LIKE LOWER(?) OR LOWER(estado) LIKE LOWER(?))";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }

    if (!empty($_GET['fecha_inicio'])) {
        $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
        $conditions[] = "DATE(fecha_creacion) >= ?";
        $params[] = $fecha_inicio;
        $types .= "s";
    }
    if (!empty($_GET['fecha_fin'])) {
        $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
        $conditions[] = "DATE(fecha_creacion) <= ?";
        $params[] = $fecha_fin;
        $types .= "s";
    }

    if (!empty($conditions)) {
        $sql_count .= " WHERE " . implode(" AND ", $conditions);
    }

    $response['debug']['sql_count'] = $sql_count;
    $response['debug']['params_values'] = $params;

    $stmt = $conn->prepare($sql_count);
    if (!$stmt) {
        $response['message'] = "Error al preparar la consulta de conteo: " . $conn->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $response['message'] = "Error al ejecutar la consulta de conteo: " . $stmt->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $total_equipos = $stmt->get_result()->fetch_row()[0];
    $response['total'] = $total_equipos;

    // Consulta para obtener los datos con filtros
    $sql = "SELECT id_equipo, marca, serial, estado, fecha_creacion 
            FROM equipos";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY serial ASC 
              LIMIT $inicio, $registros_por_pagina";

    $response['debug']['sql_data'] = $sql;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response['message'] = "Error al preparar la consulta de datos: " . $conn->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $response['message'] = "Error al ejecutar la consulta de datos: " . $stmt->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $result = $stmt->get_result();
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id_equipo' => $row['id_equipo'],
                'marca' => $row['marca'] ?? 'N/A',
                'serial' => $row['serial'] ?? 'N/A',
                'estado' => $row['estado'] ?? 'N/A',
                'fecha_creacion' => $row['fecha_creacion'] ?? 'N/A'
            ];
        }
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['message'] = "Error al cargar los equipos: " . $conn->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para obtener los materiales con búsqueda y filtros
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_materiales'])) {
    $response = ['success' => false, 'data' => [], 'total' => 0, 'message' => '', 'debug' => []];

    $pagina = isset($_GET['pagina_materiales']) ? (int)$_GET['pagina_materiales'] : 1;
    $inicio = ($pagina - 1) * $registros_por_pagina;

    $response['debug']['params'] = [
        'search' => $_GET['search'] ?? '',
        'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['fecha_fin'] ?? '',
        'pagina' => $pagina,
        'inicio' => $inicio
    ];

    // Consulta para contar el total de materiales con filtros
    $sql_count = "SELECT COUNT(*) FROM materiales";
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($_GET['search'])) {
        $search = "%" . $conn->real_escape_string($_GET['search']) . "%";
        $conditions[] = "(LOWER(nombre) LIKE LOWER(?) OR LOWER(tipo) LIKE LOWER(?) OR CAST(stock AS CHAR) LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }

    if (!empty($_GET['fecha_inicio'])) {
        $fecha_inicio = $conn->real_escape_string($_GET['fecha_inicio']);
        $conditions[] = "DATE(fecha_creacion) >= ?";
        $params[] = $fecha_inicio;
        $types .= "s";
    }
    if (!empty($_GET['fecha_fin'])) {
        $fecha_fin = $conn->real_escape_string($_GET['fecha_fin']);
        $conditions[] = "DATE(fecha_creacion) <= ?";
        $params[] = $fecha_fin;
        $types .= "s";
    }

    if (!empty($conditions)) {
        $sql_count .= " WHERE " . implode(" AND ", $conditions);
    }

    $response['debug']['sql_count'] = $sql_count;
    $response['debug']['params_values'] = $params;

    $stmt = $conn->prepare($sql_count);
    if (!$stmt) {
        $response['message'] = "Error al preparar la consulta de conteo: " . $conn->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $response['message'] = "Error al ejecutar la consulta de conteo: " . $stmt->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $total_materiales = $stmt->get_result()->fetch_row()[0];
    $response['total'] = $total_materiales;

    // Consulta para obtener los datos con filtros
    $sql = "SELECT id_material, nombre, tipo, stock, fecha_creacion 
            FROM materiales";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY id_material DESC 
              LIMIT $inicio, $registros_por_pagina";

    $response['debug']['sql_data'] = $sql;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response['message'] = "Error al preparar la consulta de datos: " . $conn->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $response['message'] = "Error al ejecutar la consulta de datos: " . $stmt->error;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $result = $stmt->get_result();
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id_material' => $row['id_material'],
                'nombre' => $row['nombre'] ?? 'N/A',
                'tipo' => $row['tipo'] ?? 'N/A',
                'stock' => $row['stock'] ?? 'N/A',
                'fecha_creacion' => $row['fecha_creacion'] ?? 'N/A'
            ];
        }
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['message'] = "Error al cargar los materiales: " . $conn->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Exportar a CSV para equipos
if (isset($_GET['exportar_equipos_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="equipos_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Marca', 'Serial', 'Estado', 'Fecha Creación'], ';');
    
    $sql = "SELECT id_equipo, marca, serial, estado, fecha_creacion FROM equipos ORDER BY serial ASC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id_equipo'],
            $row['marca'],
            $row['serial'],
            $row['estado'],
            $row['fecha_creacion']
        ], ';');
    }
    
    fclose($output);
    exit;
}

// Exportar a CSV para materiales
if (isset($_GET['exportar_materiales_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="materiales_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nombre', 'Tipo', 'Stock', 'Fecha Creación'], ';');
    
    $sql = "SELECT id_material, nombre, tipo, stock, fecha_creacion FROM materiales ORDER BY id_material DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id_material'],
            $row['nombre'],
            $row['tipo'],
            $row['stock'],
            $row['fecha_creacion']
        ], ';');
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Css/inventario.css?v=<?php echo time(); ?>">
</head>
<body>
    <div id="notificationContainer"></div>
    <div class="container">
        <h2>Gestión de Inventario</h2>
        <button class="back-btn" onclick="window.location.href='index.php'">Volver al Inicio</button>

        <div class="tabs">
            <div class="tab active" onclick="showTab('equipos')">Equipos</div>
            <div class="tab" onclick="showTab('materiales')">Materiales</div>
            <div class="tab" onclick="showTab('historial')">Historial de Cambios</div>
        </div>

        <div id="equipos" class="tab-content active">
            <div class="form-group">
                <h3>Agregar Equipo</h3>
                <form id="addEquipoForm">
                    <label for="marca">Marca:</label>
                    <select name="marca" id="marca" required onchange="toggleCustomMarca()">
                        <option value="" disabled selected>Seleccione una marca</option>
                        <option value="HP">HP</option>
                        <option value="Dell">Dell</option>
                        <option value="Lenovo">Lenovo</option>
                        <option value="Asus">Asus</option>
                        <option value="Acer">Acer</option>
                        <option value="Apple">Apple</option>
                        <option value="Otra">Otra</option>
                    </select>
                    <input type="text" name="custom_marca" id="customMarca" placeholder="Ingrese la marca" style="display:none;">

                    <label for="serial">Serial:</label>
                    <input type="text" name="serial" id="serial" required>

                    <button type="button" onclick="addEquipo()">Agregar Equipo</button>
                </form>
            </div>

            <button class="green-btn" id="toggleEquiposBtn" onclick="toggleEquipos()">Mostrar Equipos en Inventario</button>

            <div id="equiposTableContainer"></div>
        </div>

        <div id="materiales" class="tab-content">
            <div class="form-group">
                <h3>Agregar Material</h3>
                <form id="addMaterialForm">
                    <label for="nombre_material">Nombre:</label>
                    <input type="text" name="nombre_material" id="nombre_material" required>

                    <label for="tipo">Tipo:</label>
                    <select name="tipo" id="tipo" required>
                        <option value="consumible">Consumible</option>
                        <option value="no consumible">No Consumible</option>
                    </select>

                    <label for="stock">Stock:</label>
                    <input type="number" name="stock" id="stock" min="1" required>

                    <button type="button" onclick="addMaterial()">Agregar Material</button>
                </form>
            </div>

            <button class="green-btn" id="toggleMaterialesBtn" onclick="toggleMateriales()">Mostrar Materiales en Inventario</button>

            <div id="materialesTableContainer"></div>
        </div>

        <div id="historial" class="tab-content">
            <h3>Historial de Cambios</h3>
            <button class="green-btn" id="toggleHistorialBtn" onclick="loadHistorial()">Mostrar Historial de Cambios</button>
            <div id="historialTableContainer"></div>
        </div>

        <div id="updateEquipoModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('updateEquipoModal')">×</span>
                <h3>Actualizar Equipo</h3>
                <form id="updateEquipoForm">
                    <input type="hidden" name="id_equipo" id="update_equipo_id">
                    <input type="hidden" name="actualizar_equipo" value="1">
                    <label for="update_marca">Marca:</label>
                    <select name="marca" id="update_marca" required onchange="toggleUpdateCustomMarca()">
                        <option value="" disabled>Seleccione una marca</option>
                        <option value="HP">HP</option>
                        <option value="Dell">Dell</option>
                        <option value="Lenovo">Lenovo</option>
                        <option value="Asus">Asus</option>
                        <option value="Acer">Acer</option>
                        <option value="Apple">Apple</option>
                        <option value="Otra">Otra</option>
                    </select>
                    <input type="text" name="custom_marca" id="update_customMarca" placeholder="Ingrese la marca" style="display:none;">

                    <label for="update_serial">Serial:</label>
                    <input type="text" name="serial" id="update_serial" required>

                    <label for="update_estado">Estado:</label>
                    <select name="estado" id="update_estado" required>
                        <option value="disponible">Disponible</option>
                        <option value="prestado">Prestado</option>
                        <option value="deteriorado">Deteriorado</option>
                    </select>

                    <button type="button" onclick="updateEquipo()">Actualizar Equipo</button>
                </form>
            </div>
        </div>

        <div id="updateMaterialModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('updateMaterialModal')">×</span>
                <h3>Actualizar Material</h3>
                <form id="updateMaterialForm">
                    <input type="hidden" name="id_material" id="update_material_id">
                    <input type="hidden" name="actualizar_material_ajax" value="1">
                    <label for="update_nombre_material">Nombre:</label>
                    <input type="text" name="nombre" id="update_nombre_material" required>

                    <label for="update_tipo">Tipo:</label>
                    <select name="tipo" id="update_tipo" required>
                        <option value="consumible">Consumible</option>
                        <option value="no consumible">No Consumible</option>
                    </select>

                    <label for="update_stock">Stock:</label>
                    <input type="number" name="stock" id="update_stock" min="0" required>

                    <button type="button" onclick="updateMaterial()">Actualizar Material</button>
                </form>
            </div>
        </div>

        <?php
        // Cerrar la conexión al final
        $conn->close();
        ?>
    </div>

    <script src="inventario.js"></script>
</body>
</html>
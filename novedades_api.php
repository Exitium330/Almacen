<?php
// novedades_api.php

// Descomentar para depuración exhaustiva si es necesario:
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include('requiere_login.php'); 
require_once('conexion.php');    

header('Content-Type: application/json'); 

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Error de autenticación: Sesión no iniciada.']);
    exit;
}
$id_almacenista_sesion = $_SESSION['id_usuario'];

$action = $_REQUEST['action'] ?? '';

define('UPLOAD_DIR_NOVEDADES', 'uploads/novedades_fotos/'); 

if (!file_exists(UPLOAD_DIR_NOVEDADES)) {
    if (!mkdir(UPLOAD_DIR_NOVEDADES, 0775, true)) {
        error_log("CRÍTICO: No se pudo crear el directorio de subidas " . UPLOAD_DIR_NOVEDADES);
        // Dependiendo de la acción, esto podría ser un error fatal.
    }
}

// Verificar conexión a BD globalmente para las acciones que la necesiten
if ($action !== '' && in_array($action, ['get_items_for_selection', 'get_novedades_for_item', 'add_novedad', 'update_novedad', 'delete_novedad', 'get_items_with_novedades_summary'])) {
    if (!$conn || $conn->connect_error) {
        error_log("API Novedades (" . $action . "): Error de conexión a BD: " . ($conn ? $conn->connect_error : 'No hay objeto conn'));
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
        exit;
    }
}


switch ($action) {
    case 'get_items_for_selection':
        $tipo_item_req = $_GET['tipo_item'] ?? '';
        $items = [];
        $sql = '';

        if (empty($tipo_item_req) || !in_array($tipo_item_req, ['equipo', 'material'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo de ítem no válido o no proporcionado.']);
            exit;
        }

        if ($tipo_item_req === 'equipo') {
            $sql = "SELECT id_equipo as id, CONCAT(marca, ' - ', serial, ' (ID: ', id_equipo, ')') as display_name FROM equipos ORDER BY marca, serial";
        } elseif ($tipo_item_req === 'material') {
            $sql = "SELECT id_material as id, CONCAT(nombre, ' (Tipo: ', tipo, ', ID: ', id_material, ')') as display_name FROM materiales WHERE tipo = 'no consumible' ORDER BY nombre";
        }

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            echo json_encode(['success' => true, 'items' => $items]);
        } else {
            error_log("API Novedades (get_items_for_selection): Error SQL para tipo '$tipo_item_req': " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error al consultar los ítems.']);
        }
        break;

    case 'get_novedades_for_item':
        $id_item_req = filter_input(INPUT_GET, 'id_item', FILTER_VALIDATE_INT);
        $tipo_item_req = $_GET['tipo_item'] ?? '';

        if (!$id_item_req || !in_array($tipo_item_req, ['equipo', 'material'])) {
            echo json_encode(['success' => false, 'message' => 'ID de ítem o tipo no válido o no proporcionado.']);
            exit;
        }

        $novedades = [];
        $sql_novedades = "SELECT n.*, COALESCE(a.nombres, 'Usuario Desconocido') AS nombre_almacenista_reporta 
                          FROM novedades n
                          LEFT JOIN almacenistas a ON n.id_almacenista_reporta = a.id_almacenista
                          WHERE n.id_item = ? AND n.tipo_item = ? 
                          ORDER BY n.fecha_novedad DESC";
        $stmt = $conn->prepare($sql_novedades);
        if ($stmt) {
            $stmt->bind_param("is", $id_item_req, $tipo_item_req);
            if ($stmt->execute()) {
                $result_novedades = $stmt->get_result();
                while ($row = $result_novedades->fetch_assoc()) {
                    $novedades[] = $row;
                }
                echo json_encode(['success' => true, 'novedades' => $novedades]);
            } else {
                error_log("API Novedades (get_novedades_for_item): Error al ejecutar: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Error al obtener novedades.']);
            }
            $stmt->close();
        } else {
            error_log("API Novedades (get_novedades_for_item): Error al preparar: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error interno al preparar consulta.']);
        }
        break;

    case 'add_novedad':
        $id_item_post = filter_input(INPUT_POST, 'id_item', FILTER_VALIDATE_INT);
        $tipo_item_post = $_POST['tipo_item'] ?? '';
        $id_almacenista_post = filter_var($_POST['id_almacenista'] ?? $id_almacenista_sesion, FILTER_VALIDATE_INT);
        $observacion_post_raw = $_POST['observacion'] ?? null;
        $observacion_post = '';

        if ($observacion_post_raw !== null) {
            $observacion_post = trim($observacion_post_raw);
        }

        if (!$id_item_post || !in_array($tipo_item_post, ['equipo', 'material']) || empty($observacion_post) || !$id_almacenista_post || $observacion_post === 'undefined') {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos. La observación es obligatoria y no puede ser "undefined".']);
            exit;
        }
        
        $ruta_foto_guardada = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['foto']['tmp_name'];
            $file_info = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $file_info->file($tmp_name);
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($mime_type, $allowed_mime_types)) {
                echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido (solo JPG, PNG, GIF).']);
                exit;
            }
            
            $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if(!in_array($file_extension, $valid_extensions)){
                $ext_from_mime = array_search($mime_type, array_combine($valid_extensions, $allowed_mime_types));
                if ($ext_from_mime !== false && in_array($ext_from_mime, $valid_extensions)) {
                    $file_extension = $ext_from_mime;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Extensión de archivo no válida o no reconocida.']);
                    exit;
                }
            }

            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) { // 5MB
                 echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (máximo 5MB).']);
                exit;
            }
            
            $safe_filename = uniqid('novedad_', true) . '.' . $file_extension;
            $destination_path = UPLOAD_DIR_NOVEDADES . $safe_filename;

            if (!move_uploaded_file($tmp_name, $destination_path)) {
                error_log("API Novedades (add_novedad): Error al mover archivo a " . $destination_path);
                echo json_encode(['success' => false, 'message' => 'Error interno al guardar la foto.']);
                exit;
            }
            $ruta_foto_guardada = $destination_path; 
        } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] != UPLOAD_ERR_NO_FILE) {
            echo json_encode(['success' => false, 'message' => 'Error al subir la foto: Código ' . $_FILES['foto']['error']]);
            exit;
        }

        $sql_insert = "INSERT INTO novedades (id_item, tipo_item, id_almacenista_reporta, observacion, ruta_foto, fecha_novedad) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert) {
            $stmt_insert->bind_param("isiss", $id_item_post, $tipo_item_post, $id_almacenista_post, $observacion_post, $ruta_foto_guardada);
            if ($stmt_insert->execute()) {
                echo json_encode(['success' => true, 'message' => 'Novedad añadida correctamente.']);
            } else {
                if($ruta_foto_guardada && file_exists($ruta_foto_guardada)) { unlink($ruta_foto_guardada); }
                error_log("API Novedades (add_novedad): Error al guardar en BD: " . $stmt_insert->error);
                echo json_encode(['success' => false, 'message' => 'Error al guardar la novedad.']);
            }
            $stmt_insert->close();
        } else {
            if($ruta_foto_guardada && file_exists($ruta_foto_guardada)) { unlink($ruta_foto_guardada); }
            error_log("API Novedades (add_novedad): Error al preparar: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error interno al preparar inserción.']);
        }
        break;

    case 'get_items_with_novedades_summary':
        $summary_items = [];
        $sql_summary = "SELECT n.id_item, n.tipo_item, COUNT(n.id_novedad) as cantidad_novedades,
                            CASE
                                WHEN n.tipo_item = 'equipo' THEN (SELECT CONCAT(e.marca, ' - ', e.serial, ' (ID: ', e.id_equipo, ')') FROM equipos e WHERE e.id_equipo = n.id_item)
                                WHEN n.tipo_item = 'material' THEN (SELECT CONCAT(m.nombre, ' (Tipo: ', m.tipo, ', ID: ', m.id_material, ')') FROM materiales m WHERE m.id_material = n.id_item AND m.tipo = 'no consumible')
                                ELSE NULL 
                            END AS display_name
                        FROM novedades n
                        LEFT JOIN equipos eq_check ON n.tipo_item = 'equipo' AND n.id_item = eq_check.id_equipo
                        LEFT JOIN materiales mat_check ON n.tipo_item = 'material' AND n.id_item = mat_check.id_material AND mat_check.tipo = 'no consumible'
                        WHERE (n.tipo_item = 'equipo' AND eq_check.id_equipo IS NOT NULL) 
                           OR (n.tipo_item = 'material' AND mat_check.id_material IS NOT NULL)
                        GROUP BY n.id_item, n.tipo_item HAVING COUNT(n.id_novedad) > 0
                        ORDER BY cantidad_novedades DESC, display_name ASC";
        
        $result_summary = $conn->query($sql_summary);
        if ($result_summary) {
            while ($row = $result_summary->fetch_assoc()) {
                if ($row['display_name'] !== null) { 
                    $summary_items[] = $row;
                }
            }
            echo json_encode(['success' => true, 'items_summary' => $summary_items]);
        } else {
            error_log("API Novedades (get_items_with_novedades_summary): Error SQL: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error al consultar resumen.']);
        }
        break;

    case 'delete_novedad':
        $id_novedad_del = filter_input(INPUT_POST, 'id_novedad', FILTER_VALIDATE_INT);
        if (!$id_novedad_del) {
            echo json_encode(['success' => false, 'message' => 'ID de novedad no válido o no proporcionado.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $ruta_foto_a_borrar = null;
            $stmt_get_foto = $conn->prepare("SELECT ruta_foto FROM novedades WHERE id_novedad = ?");
            if (!$stmt_get_foto) throw new Exception("Error al preparar consulta de foto: " . $conn->error);
            $stmt_get_foto->bind_param("i", $id_novedad_del); 
            $stmt_get_foto->execute();
            $result_foto = $stmt_get_foto->get_result();
            if ($row_foto = $result_foto->fetch_assoc()) { $ruta_foto_a_borrar = $row_foto['ruta_foto']; }
            $stmt_get_foto->close();

            $stmt_delete = $conn->prepare("DELETE FROM novedades WHERE id_novedad = ?");
            if (!$stmt_delete) throw new Exception("Error al preparar la eliminación: " . $conn->error);
            
            $stmt_delete->bind_param("i", $id_novedad_del);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    if ($ruta_foto_a_borrar && file_exists($ruta_foto_a_borrar)) {
                        if (!unlink($ruta_foto_a_borrar)) {
                             error_log("API Novedades (delete_novedad): No se pudo borrar archivo: " . $ruta_foto_a_borrar);
                        }
                    }
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Novedad eliminada correctamente.']);
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'No se encontró la novedad.']);
                }
            } else {
                throw new Exception("Error al ejecutar la eliminación: " . $stmt_delete->error);
            }
            $stmt_delete->close();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("API Novedades (delete_novedad): Excepción: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        break;

    case 'update_novedad':
        $id_novedad_upd = filter_input(INPUT_POST, 'id_novedad', FILTER_VALIDATE_INT);
        $observacion_upd_raw = $_POST['observacion'] ?? null; 
        $observacion_upd = ''; 

        if ($observacion_upd_raw !== null) {
            $observacion_upd = trim($observacion_upd_raw);
        }
        
        $delete_current_foto_flag = isset($_POST['delete_current_foto']) && $_POST['delete_current_foto'] == '1';

        if (!$id_novedad_upd) {
            echo json_encode(['success' => false, 'message' => 'ID de novedad no proporcionado.']);
            exit;
        }
        if ($observacion_upd === 'undefined' || $observacion_upd === '') {
            error_log("API Novedades (update_novedad): Observación vacía o 'undefined'. ID: " . $id_novedad_upd . ", Raw: '" . ($observacion_upd_raw ?? 'NO DATA') . "'");
            echo json_encode(['success' => false, 'message' => 'La observación es obligatoria y no puede ser "undefined".']);
            exit;
        }
        
        $conn->begin_transaction();
        try {
            $current_ruta_foto = null;
            $stmt_get_current = $conn->prepare("SELECT ruta_foto FROM novedades WHERE id_novedad = ?");
            if (!$stmt_get_current) throw new Exception("Error preparando consulta foto actual: " . $conn->error);
            $stmt_get_current->bind_param("i", $id_novedad_upd);
            $stmt_get_current->execute();
            $result_current = $stmt_get_current->get_result();
            if (!($row_current = $result_current->fetch_assoc())) {
                 throw new Exception("Novedad ID $id_novedad_upd no encontrada.");
            }
            $current_ruta_foto = $row_current['ruta_foto'];
            $stmt_get_current->close();

            $new_ruta_foto_for_db = $current_ruta_foto; 

            if ($delete_current_foto_flag && $current_ruta_foto) {
                if (file_exists($current_ruta_foto)) {
                    if(!unlink($current_ruta_foto)){ error_log("No se pudo borrar foto actual $current_ruta_foto al actualizar."); }
                }
                $new_ruta_foto_for_db = null; 
                $current_ruta_foto = null; 
            }

            $destination_path_new_for_cleanup = null; // Para limpieza en caso de error posterior
            if (isset($_FILES['new_foto']) && $_FILES['new_foto']['error'] == UPLOAD_ERR_OK) {
                if ($current_ruta_foto && file_exists($current_ruta_foto)) { // Si había foto y no se borró con el checkbox, bórrala ahora
                    unlink($current_ruta_foto);
                }

                $tmp_name = $_FILES['new_foto']['tmp_name'];
                $file_info = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $file_info->file($tmp_name);
                $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($mime_type, $allowed_mime_types)) throw new Exception('Tipo de archivo nuevo no permitido.');
                if ($_FILES['new_foto']['size'] > 5 * 1024 * 1024) throw new Exception('El archivo nuevo es demasiado grande (max 5MB).');
                
                $file_extension = strtolower(pathinfo($_FILES['new_foto']['name'], PATHINFO_EXTENSION));
                $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if(!in_array($file_extension, $valid_extensions)){
                     $ext_from_mime = array_search($mime_type, array_combine($valid_extensions, $allowed_mime_types));
                     if ($ext_from_mime !== false && in_array($ext_from_mime, $valid_extensions)) { $file_extension = $ext_from_mime;}
                     else { throw new Exception('Extensión de archivo nueva no válida.');}
                }

                $safe_filename_new = uniqid('novedad_upd_', true) . '.' . $file_extension;
                $destination_path_new = UPLOAD_DIR_NOVEDADES . $safe_filename_new;
                $destination_path_new_for_cleanup = $destination_path_new; // Guardar para posible limpieza

                if (!move_uploaded_file($tmp_name, $destination_path_new)) throw new Exception('Error al mover la nueva foto subida.');
                $new_ruta_foto_for_db = $destination_path_new;

            } elseif (isset($_FILES['new_foto']) && $_FILES['new_foto']['error'] != UPLOAD_ERR_NO_FILE) {
                 throw new Exception('Error al subir la nueva foto: Código ' . $_FILES['new_foto']['error']);
            }

            $sql_update = "UPDATE novedades SET observacion = ?, ruta_foto = ?, fecha_novedad = NOW() WHERE id_novedad = ?";
            $stmt_update = $conn->prepare($sql_update);
            if (!$stmt_update) throw new Exception("Error preparando actualización: " . $conn->error);

            $stmt_update->bind_param("ssi", $observacion_upd, $new_ruta_foto_for_db, $id_novedad_upd);
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Novedad actualizada correctamente.']);
                } else {
                    // No hubo error SQL, pero no se actualizó ninguna fila (podría ser que los datos eran idénticos)
                    $conn->commit(); // O rollback si se considera un error que no se afecten filas
                    echo json_encode(['success' => true, 'message' => 'Novedad actualizada (o sin cambios detectados).']);
                }
            } else {
                throw new Exception("Error al ejecutar la actualización: " . $stmt_update->error);
            }
            $stmt_update->close();

        } catch (Exception $e) {
            $conn->rollback();
            if (isset($destination_path_new_for_cleanup) && file_exists($destination_path_new_for_cleanup)) {
                unlink($destination_path_new_for_cleanup); // Limpiar foto subida si la transacción falló
            }
            error_log("API Novedades (update_novedad): Excepción: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . htmlspecialchars($action) ]);
        break;
}

if ($conn && property_exists($conn, 'connect_error') && !$conn->connect_error) { 
    $conn->close();
}
?>
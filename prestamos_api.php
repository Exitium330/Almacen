<?php
include('requiere_login.php');
require_once('conexion.php'); 

date_default_timezone_set('America/Bogota'); 

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Error de autenticación: Sesión no iniciada o expirada.']);
    exit;
}
$id_almacenista_actual = $_SESSION['id_usuario'];

function log_change($conn, $id_usuario, $tabla_afectada, $accion, $id_registro, $detalles) {
    $stmt = $conn->prepare("INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isiss", $id_usuario, $tabla_afectada, $accion, $id_registro, $detalles);
        if (!$stmt->execute()) {
             error_log("Error al ejecutar log_change: (" . $stmt->errno . ") " . $stmt->error . " - Query: INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES ($id_usuario, $tabla_afectada, $accion, $id_registro, $detalles)");
        }
        $stmt->close();
    } else {
        error_log("Error al preparar la sentencia para log_change: (" . $conn->errno . ") " . $conn->error);
    }
}

// --- FUNCIÓN AUXILIAR AÑADIDA ---
function isInstructorActive($conn, $id_instructor) {
    $stmt = $conn->prepare("SELECT activo FROM instructores WHERE id_instructor = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $id_instructor);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result && $result['activo'] == 1;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'prestar_equipo':
        // ... (código existente sin cambios)
        $id_equipos_array = $_POST['id_equipos'] ?? [];
        $id_instructor_prestamo = $_POST['id_instructor'] ?? '';
        $fecha_prestamo_actual = date('Y-m-d H:i:s');

        // --- VALIDACIÓN AÑADIDA ---
        if (!isInstructorActive($conn, $id_instructor_prestamo)) {
            echo json_encode(['success' => false, 'message' => 'El instructor seleccionado no está activo o no existe. No se puede realizar el préstamo.']);
            exit;
        }
        // --- FIN VALIDACIÓN ---

        if (empty($id_equipos_array) || !is_array($id_equipos_array) || empty($id_instructor_prestamo)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos: se requieren equipos e instructor.']);
            exit;
        }

        $conn->begin_transaction();
        $errores_prestamo = [];
        $prestamos_exitosos_count = 0;
        $fecha_lote_prestamo = $fecha_prestamo_actual; 

        foreach ($id_equipos_array as $id_equipo_individual) {
            try {
                $id_equipo_validado = filter_var($id_equipo_individual, FILTER_VALIDATE_INT);
                if ($id_equipo_validado === false) {
                    throw new Exception("ID de equipo no válido: " . htmlspecialchars($id_equipo_individual));
                }

                $stmt_verif_equipo = $conn->prepare("SELECT estado FROM equipos WHERE id_equipo = ?");
                $stmt_verif_equipo->bind_param("i", $id_equipo_validado);
                $stmt_verif_equipo->execute();
                $result_verif_equipo = $stmt_verif_equipo->get_result();
                $estado_equipo_actual = $result_verif_equipo->fetch_assoc();
                $stmt_verif_equipo->close();

                if (!$estado_equipo_actual || $estado_equipo_actual['estado'] !== 'disponible') {
                    throw new Exception('Equipo ID ' . $id_equipo_validado . ' no se encuentra disponible.');
                }

                $stmt_reg_prestamo = $conn->prepare("INSERT INTO prestamo_equipos (id_equipo, id_instructor, id_almacenista, fecha_prestamo, estado) VALUES (?, ?, ?, ?, 'pendiente')");
                $stmt_reg_prestamo->bind_param("iiis", $id_equipo_validado, $id_instructor_prestamo, $id_almacenista_actual, $fecha_lote_prestamo);
                if (!$stmt_reg_prestamo->execute()) {
                    throw new Exception('Fallo al registrar préstamo para equipo ID ' . $id_equipo_validado . ': ' . $stmt_reg_prestamo->error);
                }
                $id_prestamo_generado = $conn->insert_id;
                $stmt_reg_prestamo->close();

                $stmt_act_estado_equipo = $conn->prepare("UPDATE equipos SET estado = 'prestado' WHERE id_equipo = ?");
                $stmt_act_estado_equipo->bind_param("i", $id_equipo_validado);
                if (!$stmt_act_estado_equipo->execute()) {
                    throw new Exception('Fallo al actualizar estado para equipo ID ' . $id_equipo_validado . ': ' . $stmt_act_estado_equipo->error);
                }
                $stmt_act_estado_equipo->close();

                log_change($conn, $id_almacenista_actual, 'prestamo_equipos', 'crear', $id_prestamo_generado, 'Préstamo de equipo ID: ' . $id_equipo_validado . ' a instructor ID: ' . $id_instructor_prestamo . ' en lote con fecha: ' . $fecha_lote_prestamo);
                log_change($conn, $id_almacenista_actual, 'equipos', 'actualizar_estado', $id_equipo_validado, 'Estado cambiado a prestado.');
                $prestamos_exitosos_count++;
            } catch (Exception $e) {
                $errores_prestamo[] = $e->getMessage();
            }
        }

        if ($prestamos_exitosos_count > 0 && empty($errores_prestamo)) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => $prestamos_exitosos_count . ' equipo(s) prestado(s) correctamente.']);
        } elseif ($prestamos_exitosos_count > 0 && !empty($errores_prestamo)) {
            $conn->commit(); 
            echo json_encode(['success' => true, 'message' => $prestamos_exitosos_count . ' equipo(s) prestado(s). Errores en otros: ' . implode("; ", $errores_prestamo)]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'No se pudo prestar ningún equipo. Errores: ' . implode("; ", $errores_prestamo)]);
        }
        break;

    case 'prestar_material':
        // ... (código existente sin cambios)
        $id_material_prestamo = $_POST['id_material'] ?? '';
        $id_instructor_material = $_POST['id_instructor'] ?? '';
        $cantidad_material_prestamo = $_POST['cantidad'] ?? 0;
        $fecha_prestamo_material_sql = date('Y-m-d H:i:s'); 

        // --- VALIDACIÓN AÑADIDA ---
        if (!isInstructorActive($conn, $id_instructor_material)) {
            echo json_encode(['success' => false, 'message' => 'El instructor seleccionado no está activo o no existe. No se puede realizar el préstamo.']);
            exit;
        }
        // --- FIN VALIDACIÓN ---

        if (empty($id_material_prestamo) || empty($id_instructor_material) || !is_numeric($cantidad_material_prestamo) || $cantidad_material_prestamo <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o cantidad no válida.']);
            exit;
        }
        
        $conn->begin_transaction();
        try {
            $stmt_verif_material = $conn->prepare("SELECT stock, tipo FROM materiales WHERE id_material = ?");
            $stmt_verif_material->bind_param("i", $id_material_prestamo);
            $stmt_verif_material->execute();
            $result_verif_material = $stmt_verif_material->get_result();
            $info_material_actual = $result_verif_material->fetch_assoc();
            $stmt_verif_material->close();

            if (!$info_material_actual) { throw new Exception('Material no encontrado.'); }
            if ($info_material_actual['stock'] < $cantidad_material_prestamo) { throw new Exception('Stock insuficiente. Disponible: ' . $info_material_actual['stock']); }
            
            $tipo_material_db = $info_material_actual['tipo'];

            $stmt_act_stock_material = $conn->prepare("UPDATE materiales SET stock = stock - ? WHERE id_material = ?");
            $stmt_act_stock_material->bind_param("ii", $cantidad_material_prestamo, $id_material_prestamo);
            if (!$stmt_act_stock_material->execute()) {
                throw new Exception('Fallo al actualizar stock del material: ' . $stmt_act_stock_material->error);
            }
            $stmt_act_stock_material->close();
            log_change($conn, $id_almacenista_actual, 'materiales', 'actualizar_stock', $id_material_prestamo, 'Stock reducido en ' . $cantidad_material_prestamo . ' por salida/préstamo.');

            $mensaje_respuesta = '';
            if ($tipo_material_db === 'no consumible') {
                $stmt_reg_prestamo_material = $conn->prepare("INSERT INTO prestamo_materiales (id_material, id_instructor, id_almacenista, cantidad, fecha_prestamo, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
                $stmt_reg_prestamo_material->bind_param("iiiis", $id_material_prestamo, $id_instructor_material, $id_almacenista_actual, $cantidad_material_prestamo, $fecha_prestamo_material_sql);
                if (!$stmt_reg_prestamo_material->execute()) {
                    throw new Exception('Fallo al registrar préstamo de material no consumible: ' . $stmt_reg_prestamo_material->error);
                }
                $id_prestamo_material_gen = $conn->insert_id;
                $stmt_reg_prestamo_material->close();
                log_change($conn, $id_almacenista_actual, 'prestamo_materiales', 'crear', $id_prestamo_material_gen, 'Préstamo de material no consumible ID: ' . $id_material_prestamo . ' (Cant: ' . $cantidad_material_prestamo . ') a instructor ID: ' . $id_instructor_material);
                $mensaje_respuesta = 'Material no consumible prestado correctamente.';
            } else { 
                $mensaje_respuesta = 'Salida de material consumible registrada (stock actualizado).';
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => $mensaje_respuesta]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'list_active_loans': 
        // ... (código existente sin cambios)
        $equipos_pendientes_raw = [];
        $sql_equipos = "SELECT
                            pe.id_prestamo_equipo,
                            pe.id_equipo AS id_equipo_actual,
                            e.marca,
                            e.serial,
                            pe.id_instructor,
                            ins.nombre AS instructor_nombre,
                            ins.apellido AS instructor_apellido,
                            pe.fecha_prestamo,
                            pe.fecha_devolucion_esperada AS fecha_devolucion_esperada_individual,
                            pe.estado 
                        FROM prestamo_equipos pe
                        JOIN equipos e ON pe.id_equipo = e.id_equipo
                        JOIN instructores ins ON pe.id_instructor = ins.id_instructor
                        WHERE pe.estado = 'pendiente'
                        ORDER BY pe.id_instructor ASC, pe.fecha_prestamo ASC, e.serial ASC";
        
        $result_equipos = $conn->query($sql_equipos);
        if ($result_equipos) {
            while ($row = $result_equipos->fetch_assoc()) {
                $equipos_pendientes_raw[] = $row;
            }
        } else {
            error_log("Error en consulta de equipos activos (list_active_loans): " . $conn->error);
        }

        $loans_final_agrupados = [];
        $mapa_grupos_equipos = [];

        if (!empty($equipos_pendientes_raw)) {
            foreach ($equipos_pendientes_raw as $equipo_actual) {
                $clave_grupo_actual = $equipo_actual['id_instructor'] . '|' . $equipo_actual['fecha_prestamo'];

                if (!isset($mapa_grupos_equipos[$clave_grupo_actual])) {
                    $mapa_grupos_equipos[$clave_grupo_actual] = [
                        'tipo' => 'grupo_equipo', 
                        'id_grupo' => $clave_grupo_actual,
                        'id_instructor' => $equipo_actual['id_instructor'],
                        'instructor_nombre' => $equipo_actual['instructor_nombre'],
                        'instructor_apellido' => $equipo_actual['instructor_apellido'],
                        'fecha_prestamo_referencia' => $equipo_actual['fecha_prestamo'], 
                        'estado_grupo' => 'pendiente', 
                        'equipos_individuales' => [],
                        'fecha_devolucion_esperada_grupo' => $equipo_actual['fecha_devolucion_esperada_individual'] 
                    ];
                }
                $mapa_grupos_equipos[$clave_grupo_actual]['equipos_individuales'][] = [
                    'id_prestamo_equipo' => $equipo_actual['id_prestamo_equipo'],
                    'id_equipo_actual' => $equipo_actual['id_equipo_actual'],
                    'marca' => $equipo_actual['marca'],
                    'serial' => $equipo_actual['serial'],
                    'fecha_devolucion_esperada_individual' => $equipo_actual['fecha_devolucion_esperada_individual']
                ];

                if ($equipo_actual['fecha_devolucion_esperada_individual'] && 
                    (!isset($mapa_grupos_equipos[$clave_grupo_actual]['fecha_devolucion_esperada_grupo']) || 
                     strtotime($equipo_actual['fecha_devolucion_esperada_individual']) < strtotime($mapa_grupos_equipos[$clave_grupo_actual]['fecha_devolucion_esperada_grupo']))
                   ) {
                    $mapa_grupos_equipos[$clave_grupo_actual]['fecha_devolucion_esperada_grupo'] = $equipo_actual['fecha_devolucion_esperada_individual'];
                }
            }
        }
        
        foreach ($mapa_grupos_equipos as $grupo_formado) {
            $loans_final_agrupados[] = $grupo_formado;
        }

        $sql_materiales_activos = "SELECT
                                pm.id_prestamo_material AS id_prestamo, 
                                'material' AS tipo, 
                                m.id_material AS id_material_actual,
                                m.nombre AS material_nombre,
                                m.tipo AS material_tipo, 
                                pm.cantidad, 
                                pm.id_instructor,
                                ins.nombre AS instructor_nombre,
                                ins.apellido AS instructor_apellido,
                                pm.fecha_prestamo,
                                pm.fecha_devolucion_esperada,
                                pm.estado
                            FROM prestamo_materiales pm
                            JOIN materiales m ON pm.id_material = m.id_material
                            JOIN instructores ins ON pm.id_instructor = ins.id_instructor
                            WHERE pm.estado = 'pendiente' AND m.tipo = 'no consumible'
                            ORDER BY pm.fecha_prestamo ASC"; 
        $result_materiales_activos = $conn->query($sql_materiales_activos);
        if ($result_materiales_activos) {
            while ($row_material = $result_materiales_activos->fetch_assoc()) {
                $loans_final_agrupados[] = $row_material; 
            }
        } else {
            error_log("Error en consulta de materiales activos (list_active_loans): " . $conn->error);
        }
        
        if (!empty($loans_final_agrupados)) {
            usort($loans_final_agrupados, function($a, $b) {
                $dateA_ref = isset($a['fecha_prestamo_referencia']) ? $a['fecha_prestamo_referencia'] : $a['fecha_prestamo'];
                $dateB_ref = isset($b['fecha_prestamo_referencia']) ? $b['fecha_prestamo_referencia'] : $b['fecha_prestamo'];
                return strtotime($dateA_ref) - strtotime($dateB_ref);
            });
        }
        echo json_encode(['success' => true, 'loans' => $loans_final_agrupados]);
        break;

    case 'list_active_loans_for_return': 
        // ... (código existente sin cambios)
        $loans_para_devolucion = [];
        $sql_materiales_devolucion = "SELECT pm.id_prestamo_material AS id_prestamo, 'material' AS tipo, m.nombre AS material_nombre, pm.cantidad, i.nombre AS instructor_nombre, i.apellido AS instructor_apellido, pm.fecha_prestamo, m.tipo AS material_tipo_real
                            FROM prestamo_materiales pm
                            JOIN materiales m ON pm.id_material = m.id_material
                            JOIN instructores i ON pm.id_instructor = i.id_instructor
                            WHERE pm.estado = 'pendiente' AND m.tipo = 'no consumible'
                            ORDER BY i.nombre ASC, i.apellido ASC, pm.fecha_prestamo ASC";
        $result_materiales_devolucion = $conn->query($sql_materiales_devolucion);
        if ($result_materiales_devolucion) { 
            while ($row_mat_dev = $result_materiales_devolucion->fetch_assoc()) { 
                $loans_para_devolucion[] = $row_mat_dev; 
            } 
        }  else {
            error_log("Error en consulta de materiales para devolución: " . $conn->error);
        }
        echo json_encode(['success' => true, 'loans' => $loans_para_devolucion]);
        break;
    
    case 'list_grupos_equipos_para_devolucion': 
        // ... (código existente sin cambios)
        $sql_grupos = "SELECT
                            pe.id_instructor,
                            ins.nombre AS instructor_nombre,
                            ins.apellido AS instructor_apellido,
                            pe.fecha_prestamo AS fecha_prestamo_referencia, 
                            COUNT(pe.id_prestamo_equipo) as cantidad_en_grupo_pendiente
                        FROM prestamo_equipos pe
                        JOIN instructores ins ON pe.id_instructor = ins.id_instructor
                        JOIN equipos e ON pe.id_equipo = e.id_equipo
                        WHERE pe.estado = 'pendiente' 
                        GROUP BY pe.id_instructor, pe.fecha_prestamo 
                        ORDER BY ins.nombre ASC, ins.apellido ASC, pe.fecha_prestamo ASC";
        
        $result_grupos = $conn->query($sql_grupos);
        $grupos_para_devolucion = [];
        if ($result_grupos) {
            while ($grupo_info = $result_grupos->fetch_assoc()) {
                $stmt_items = $conn->prepare("SELECT pe.id_prestamo_equipo, e.id_equipo, e.marca, e.serial
                                              FROM prestamo_equipos pe
                                              JOIN equipos e ON pe.id_equipo = e.id_equipo
                                              WHERE pe.id_instructor = ? AND pe.fecha_prestamo = ? AND pe.estado = 'pendiente'
                                              ORDER BY e.serial ASC"); 
                if (!$stmt_items) {
                    error_log("Error al preparar statement para items de grupo (devolución): " . $conn->error);
                    continue; 
                }
                $stmt_items->bind_param("is", $grupo_info['id_instructor'], $grupo_info['fecha_prestamo_referencia']);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                $items_del_grupo = [];
                while($item_info = $result_items->fetch_assoc()){
                    $items_del_grupo[] = $item_info;
                }
                $stmt_items->close();
                
                if (!empty($items_del_grupo)) { 
                    $grupo_info['items_individuales'] = $items_del_grupo;
                    $grupos_para_devolucion[] = $grupo_info;
                }
            }
        } else {
             error_log("Error en consulta de list_grupos_equipos_para_devolucion: " . $conn->error);
        }
        echo json_encode(['success' => true, 'grupos' => $grupos_para_devolucion]);
        break;

    case 'devolver_equipo_individual':
        $id_prestamo_equipo_ind = $_POST['id_prestamo_equipo'] ?? null;
        $estado_devolucion_ind = $_POST['estado_devolucion'] ?? 'bueno';
        $observaciones_ind_raw = $_POST['observaciones'] ?? null; // Captura la observación cruda
        $observaciones_ind = is_string($observaciones_ind_raw) ? trim($observaciones_ind_raw) : null; // Limpia si es string
        $id_almacenista_dev_ind = $id_almacenista_actual; 
        $fecha_devolucion_ind = date('Y-m-d H:i:s');

        if (empty($id_prestamo_equipo_ind) || !filter_var($id_prestamo_equipo_ind, FILTER_VALIDATE_INT)) {
            echo json_encode(['success' => false, 'message' => 'ID de préstamo de equipo no válido.']);
            exit;
        }
        if (!in_array($estado_devolucion_ind, ['bueno', 'regular', 'malo'])) {
            $estado_devolucion_ind = 'bueno'; 
        }

        $conn->begin_transaction();
        try {
            $stmt_verif_prestamo_ind = $conn->prepare(
                "SELECT pe.id_equipo, pe.estado, pe.id_instructor, e.marca, e.serial 
                 FROM prestamo_equipos pe 
                 JOIN equipos e ON pe.id_equipo = e.id_equipo 
                 WHERE pe.id_prestamo_equipo = ?"
            );
            if (!$stmt_verif_prestamo_ind) throw new Exception("Error preparando verificación (individual): " . $conn->error);
            $stmt_verif_prestamo_ind->bind_param("i", $id_prestamo_equipo_ind);
            $stmt_verif_prestamo_ind->execute();
            $info_prestamo_ind = $stmt_verif_prestamo_ind->get_result()->fetch_assoc();
            $stmt_verif_prestamo_ind->close();

            if (!$info_prestamo_ind) {
                throw new Exception("Préstamo de equipo ID $id_prestamo_equipo_ind no encontrado.");
            }
            if ($info_prestamo_ind['estado'] !== 'pendiente') {
                throw new Exception("El préstamo de equipo ID $id_prestamo_equipo_ind ya no está pendiente (estado actual: {$info_prestamo_ind['estado']}).");
            }
            $id_equipo_devuelto_ind = $info_prestamo_ind['id_equipo'];
            $id_instructor_prestamo_ind = $info_prestamo_ind['id_instructor'];
            $nombre_equipo_novedad = $info_prestamo_ind['marca'] . " - " . $info_prestamo_ind['serial'];

            $stmt_reg_dev_ind = $conn->prepare("INSERT INTO devolucion_equipos (id_prestamo_equipo, estado_devolucion, fecha_devolucion, observaciones) VALUES (?, ?, ?, ?)");
            if (!$stmt_reg_dev_ind) throw new Exception("Error preparando inserción en devolucion_equipos (ind): " . $conn->error);
            // Usar $observaciones_ind (ya trimeada) para la tabla devolucion_equipos
            $stmt_reg_dev_ind->bind_param("isss", $id_prestamo_equipo_ind, $estado_devolucion_ind, $fecha_devolucion_ind, $observaciones_ind);
            if (!$stmt_reg_dev_ind->execute()) {
                throw new Exception("Fallo al registrar devolución (ind): " . $stmt_reg_dev_ind->error);
            }
            $id_devolucion_gen_ind = $conn->insert_id;
            $stmt_reg_dev_ind->close();

            $stmt_act_prestamo_ind = $conn->prepare("UPDATE prestamo_equipos SET estado = 'devuelto', fecha_devolucion = ? WHERE id_prestamo_equipo = ?");
            if (!$stmt_act_prestamo_ind) throw new Exception("Error preparando actualización prestamo_equipos (ind): " . $conn->error);
            $stmt_act_prestamo_ind->bind_param("si", $fecha_devolucion_ind, $id_prestamo_equipo_ind);
            if (!$stmt_act_prestamo_ind->execute()) {
                throw new Exception("Fallo al actualizar estado del préstamo (ind): " . $stmt_act_prestamo_ind->error);
            }
            $stmt_act_prestamo_ind->close();

            $nuevo_estado_fisico_eq_ind = ($estado_devolucion_ind === 'malo') ? 'deteriorado' : 'disponible';
            $stmt_act_estado_fisico_eq_ind = $conn->prepare("UPDATE equipos SET estado = ? WHERE id_equipo = ?");
            if (!$stmt_act_estado_fisico_eq_ind) throw new Exception("Error preparando actualización equipos (ind): " . $conn->error);
            $stmt_act_estado_fisico_eq_ind->bind_param("si", $nuevo_estado_fisico_eq_ind, $id_equipo_devuelto_ind);
            if (!$stmt_act_estado_fisico_eq_ind->execute()) {
                throw new Exception("Fallo al actualizar estado físico del equipo (ind): " . $stmt_act_estado_fisico_eq_ind->error);
            }
            $stmt_act_estado_fisico_eq_ind->close();

            // --- MODIFICACIÓN: Solo registrar novedad si hay observaciones ---
            if (!empty($observaciones_ind)) { // $observaciones_ind ya está trimeada
                $stmt_instructor_novedad = $conn->prepare("SELECT nombre, apellido FROM instructores WHERE id_instructor = ?");
                if(!$stmt_instructor_novedad) throw new Exception("Error preparando consulta instructor para novedad: " . $conn->error);
                $stmt_instructor_novedad->bind_param("i", $id_instructor_prestamo_ind);
                $stmt_instructor_novedad->execute();
                $instructor_info_novedad = $stmt_instructor_novedad->get_result()->fetch_assoc();
                $stmt_instructor_novedad->close();
                $nombre_instructor_novedad = $instructor_info_novedad ? htmlspecialchars($instructor_info_novedad['nombre'] . " " . $instructor_info_novedad['apellido']) : "Desconocido";

                $observacion_novedad_final = "Novedad en devolución de equipo '" . htmlspecialchars($nombre_equipo_novedad) . "' (ID Equipo: $id_equipo_devuelto_ind). ";
                $observacion_novedad_final .= "Instructor: " . $nombre_instructor_novedad . ". ";
                $observacion_novedad_final .= "Estado devolución: " . ucfirst($estado_devolucion_ind) . ". ";
                $observacion_novedad_final .= "Observación reportada: " . htmlspecialchars($observaciones_ind); 
                
                $stmt_insert_novedad = $conn->prepare("INSERT INTO novedades (id_item, tipo_item, id_almacenista_reporta, observacion, fecha_novedad) VALUES (?, 'equipo', ?, ?, NOW())");
                if (!$stmt_insert_novedad) {
                     error_log("Error preparando inserción de novedad (ind): " . $conn->error);
                } else {
                    $stmt_insert_novedad->bind_param("iis", $id_equipo_devuelto_ind, $id_almacenista_dev_ind, $observacion_novedad_final);
                    if (!$stmt_insert_novedad->execute()) {
                         error_log("Fallo al insertar novedad para devolución individual ID Prestamo Equipo: $id_prestamo_equipo_ind. Error: " . $stmt_insert_novedad->error);
                    }
                    $stmt_insert_novedad->close();
                }
            }
            // --- FIN MODIFICACIÓN ---

            log_change($conn, $id_almacenista_dev_ind, 'devolucion_equipos', 'crear', $id_devolucion_gen_ind, "Devolución individual préstamo ID $id_prestamo_equipo_ind, equipo ID $id_equipo_devuelto_ind, estado: $estado_devolucion_ind. Registrado por almacenista ID: $id_almacenista_dev_ind.");
            log_change($conn, $id_almacenista_dev_ind, 'prestamo_equipos', 'actualizar_estado', $id_prestamo_equipo_ind, 'Estado a devuelto (Individual)');
            log_change($conn, $id_almacenista_dev_ind, 'equipos', 'actualizar_estado', $id_equipo_devuelto_ind, "Estado a $nuevo_estado_fisico_eq_ind (Devolución Individual)");

            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Equipo '" . htmlspecialchars($nombre_equipo_novedad) . "' devuelto individualmente con éxito."]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Excepción (dev_ind): " . $e->getMessage()]);
        }
        break;

    case 'devolver_grupo_equipos': 
        $id_instructor_grupo = $_POST['id_instructor_grupo'] ?? null;
        $fecha_prestamo_ref_grupo = $_POST['fecha_prestamo_ref_grupo'] ?? null;
        $items_data = $_POST['items'] ?? []; 
        $id_almacenista_devolucion = $id_almacenista_actual; 
        $fecha_devolucion_lote = date('Y-m-d H:i:s');

        if (empty($id_instructor_grupo) || empty($fecha_prestamo_ref_grupo) || empty($items_data) || !is_array($items_data)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos para la devolución del grupo. Se requiere instructor, fecha de referencia e ítems.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $errores_devolucion = [];
            $devoluciones_exitosas_count = 0;

            foreach ($items_data as $item_devolucion_info) {
                $id_prestamo_equipo_dev = $item_devolucion_info['id_prestamo_equipo'] ?? null;
                $estado_devolucion_eq = $item_devolucion_info['estado_devolucion'] ?? 'bueno'; 
                if (!in_array($estado_devolucion_eq, ['bueno', 'regular', 'malo'])) {
                     $estado_devolucion_eq = 'bueno'; 
                }
                $observaciones_eq_dev_raw = $item_devolucion_info['observaciones'] ?? null; // Captura la observación cruda
                $observaciones_eq_dev = is_string($observaciones_eq_dev_raw) ? trim($observaciones_eq_dev_raw) : null; // Limpia si es string

                if (empty($id_prestamo_equipo_dev) || !filter_var($id_prestamo_equipo_dev, FILTER_VALIDATE_INT)) {
                    $errores_devolucion[] = "ID de préstamo de equipo no válido para un ítem.";
                    continue;
                }

                $stmt_verif_prestamo_eq_dev = $conn->prepare(
                    "SELECT pe.id_equipo, pe.estado 
                     FROM prestamo_equipos pe 
                     WHERE pe.id_prestamo_equipo = ? AND pe.id_instructor = ? AND pe.fecha_prestamo = ?"
                );
                if (!$stmt_verif_prestamo_eq_dev) throw new Exception("Error preparando verificación de préstamo: " . $conn->error);
                
                $id_instructor_val = filter_var($id_instructor_grupo, FILTER_VALIDATE_INT);

                $stmt_verif_prestamo_eq_dev->bind_param("iis", $id_prestamo_equipo_dev, $id_instructor_val, $fecha_prestamo_ref_grupo);
                $stmt_verif_prestamo_eq_dev->execute();
                $info_prestamo_eq_dev = $stmt_verif_prestamo_eq_dev->get_result()->fetch_assoc();
                $stmt_verif_prestamo_eq_dev->close();

                if (!$info_prestamo_eq_dev) {
                    $errores_devolucion[] = "Préstamo de equipo ID $id_prestamo_equipo_dev no encontrado para el instructor ID $id_instructor_val y fecha $fecha_prestamo_ref_grupo.";
                    continue;
                }
                if ($info_prestamo_eq_dev['estado'] !== 'pendiente') {
                    continue; 
                }
                $id_equipo_devuelto = $info_prestamo_eq_dev['id_equipo'];

                $stmt_reg_devolucion_eq = $conn->prepare("INSERT INTO devolucion_equipos (id_prestamo_equipo, estado_devolucion, fecha_devolucion, observaciones) VALUES (?, ?, ?, ?)");
                if (!$stmt_reg_devolucion_eq) throw new Exception("Error preparando inserción en devolucion_equipos: " . $conn->error);
                // Usar $observaciones_eq_dev (ya trimeada) para la tabla devolucion_equipos
                $stmt_reg_devolucion_eq->bind_param("isss", $id_prestamo_equipo_dev, $estado_devolucion_eq, $fecha_devolucion_lote, $observaciones_eq_dev);
                if (!$stmt_reg_devolucion_eq->execute()) {
                    throw new Exception("Fallo al registrar la devolución para préstamo ID $id_prestamo_equipo_dev: " . $stmt_reg_devolucion_eq->error);
                }
                $id_devolucion_eq_gen = $conn->insert_id;
                $stmt_reg_devolucion_eq->close();

                $stmt_act_prestamo_eq_dev = $conn->prepare("UPDATE prestamo_equipos SET estado = 'devuelto', fecha_devolucion = ? WHERE id_prestamo_equipo = ?");
                if (!$stmt_act_prestamo_eq_dev) throw new Exception("Error preparando actualización de prestamo_equipos: " . $conn->error);
                $stmt_act_prestamo_eq_dev->bind_param("si", $fecha_devolucion_lote, $id_prestamo_equipo_dev);
                if (!$stmt_act_prestamo_eq_dev->execute()) {
                    throw new Exception("Fallo al actualizar estado del préstamo ID $id_prestamo_equipo_dev: " . $stmt_act_prestamo_eq_dev->error);
                }
                $stmt_act_prestamo_eq_dev->close();

                $nuevo_estado_fisico_equipo = ($estado_devolucion_eq === 'malo') ? 'deteriorado' : 'disponible';
                $stmt_act_estado_fisico_eq = $conn->prepare("UPDATE equipos SET estado = ? WHERE id_equipo = ?");
                if (!$stmt_act_estado_fisico_eq) throw new Exception("Error preparando actualización de equipos: " . $conn->error);
                $stmt_act_estado_fisico_eq->bind_param("si", $nuevo_estado_fisico_equipo, $id_equipo_devuelto);
                if (!$stmt_act_estado_fisico_eq->execute()) {
                    throw new Exception("Fallo al actualizar estado físico del equipo ID $id_equipo_devuelto: " . $stmt_act_estado_fisico_eq->error);
                }
                $stmt_act_estado_fisico_eq->close();

                // --- MODIFICACIÓN: Solo registrar novedad si hay observaciones ---
                if (!empty($observaciones_eq_dev)) { // $observaciones_eq_dev ya está trimeada
                    $stmt_info_novedad_g = $conn->prepare(
                        "SELECT e.marca, e.serial, ins.nombre AS instructor_nombre, ins.apellido AS instructor_apellido 
                         FROM equipos e
                         JOIN prestamo_equipos pe ON e.id_equipo = pe.id_equipo AND pe.id_prestamo_equipo = ?
                         JOIN instructores ins ON pe.id_instructor = ins.id_instructor"
                    );
                    if (!$stmt_info_novedad_g) {
                        error_log("Error preparando info novedad (grupo): " . $conn->error . " para prestamo_equipo ID: " . $id_prestamo_equipo_dev);
                    } else {
                        $stmt_info_novedad_g->bind_param("i", $id_prestamo_equipo_dev);
                        $stmt_info_novedad_g->execute();
                        $info_novedad_item_g = $stmt_info_novedad_g->get_result()->fetch_assoc();
                        $stmt_info_novedad_g->close();

                        if ($info_novedad_item_g) {
                            $nombre_equipo_novedad_g = htmlspecialchars($info_novedad_item_g['marca'] . " - " . $info_novedad_item_g['serial']);
                            $nombre_instructor_novedad_g = htmlspecialchars($info_novedad_item_g['instructor_nombre'] . " " . $info_novedad_item_g['instructor_apellido']);

                            $observacion_novedad_g_final = "Novedad en devolución de equipo (grupo) '" . $nombre_equipo_novedad_g . "' (ID Equipo: $id_equipo_devuelto). ";
                            $observacion_novedad_g_final .= "Instructor: " . $nombre_instructor_novedad_g . ". ";
                            $observacion_novedad_g_final .= "Estado devolución: " . ucfirst($estado_devolucion_eq) . ". ";
                            $observacion_novedad_g_final .= "Observación reportada: " . htmlspecialchars($observaciones_eq_dev);

                            $stmt_insert_novedad_g = $conn->prepare("INSERT INTO novedades (id_item, tipo_item, id_almacenista_reporta, observacion, fecha_novedad) VALUES (?, 'equipo', ?, ?, NOW())");
                            if (!$stmt_insert_novedad_g) {
                                 error_log("Error preparando inserción de novedad (equipo en grupo): " . $conn->error);
                            } else {
                                $stmt_insert_novedad_g->bind_param("iis", $id_equipo_devuelto, $id_almacenista_devolucion, $observacion_novedad_g_final);
                                if (!$stmt_insert_novedad_g->execute()) {
                                    error_log("Fallo al insertar novedad para devolución de equipo en grupo. ID PrestamoEquipo: $id_prestamo_equipo_dev. Error: " . $stmt_insert_novedad_g->error);
                                }
                                $stmt_insert_novedad_g->close();
                            }
                        } else {
                             error_log("No se pudo obtener información para la novedad del equipo ID $id_equipo_devuelto en devolución grupal (ID PrestamoEquipo: $id_prestamo_equipo_dev).");
                        }
                    }
                }
                // --- FIN MODIFICACIÓN ---

                log_change($conn, $id_almacenista_devolucion, 'devolucion_equipos', 'crear', $id_devolucion_eq_gen, "Devolución (lote) préstamo ID $id_prestamo_equipo_dev, equipo ID $id_equipo_devuelto, estado: $estado_devolucion_eq");
                log_change($conn, $id_almacenista_devolucion, 'prestamo_equipos', 'actualizar_estado', $id_prestamo_equipo_dev, 'Estado a devuelto (Lote)');
                log_change($conn, $id_almacenista_devolucion, 'equipos', 'actualizar_estado', $id_equipo_devuelto, "Estado a $nuevo_estado_fisico_equipo (Devolución Lote)");

                $devoluciones_exitosas_count++;
            }

            if ($devoluciones_exitosas_count > 0 && empty($errores_devolucion)) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => "$devoluciones_exitosas_count equipo(s) del grupo devuelto(s) correctamente."]);
            } elseif ($devoluciones_exitosas_count > 0 && !empty($errores_devolucion)) {
                $conn->commit(); 
                echo json_encode(['success' => true, 'message' => "$devoluciones_exitosas_count equipo(s) devuelto(s). Errores parciales no críticos: " . implode("; ", $errores_devolucion)]);
            } elseif (empty($errores_devolucion) && $devoluciones_exitosas_count == 0 && count($items_data) > 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'No se procesaron nuevos equipos. Los ítems enviados podrían ya estar devueltos o no ser válidos para el grupo.']);
            } elseif (empty($items_data)){
                 $conn->rollback();
                 echo json_encode(['success' => false, 'message' => 'No se enviaron ítems para procesar.']);
            } else { 
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => "No se pudo devolver ningún equipo del grupo. Errores: " . implode("; ", $errores_devolucion)]);
            }

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Excepción general: " . $e->getMessage()]);
        }
        break;

    case 'list_devoluciones':
        // INICIO: MODIFICACIÓN PARA PAGINACIÓN
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        // FIN: MODIFICACIÓN PARA PAGINACIÓN

        error_log("API Action: list_devoluciones iniciada."); 

        $search_instructor = trim($_GET['search_instructor_nombre'] ?? '');
        $fecha_desde = $_GET['fecha_desde'] ?? null;
        $fecha_hasta = $_GET['fecha_hasta'] ?? null;

        $lista_historial_combinado = [];

        $sql_devoluciones_equipos = "SELECT 
                                        de.id_devolucion, 
                                        de.id_prestamo_equipo,
                                        pe.id_instructor,
                                        pe.fecha_prestamo, 
                                        de.fecha_devolucion, 
                                        de.estado_devolucion AS estado_devolucion_equipo, 
                                        de.observaciones, 
                                        e.marca, 
                                        e.serial, 
                                        ins.nombre AS instructor_nombre, 
                                        ins.apellido AS instructor_apellido
                                    FROM devolucion_equipos de
                                    JOIN prestamo_equipos pe ON de.id_prestamo_equipo = pe.id_prestamo_equipo
                                    JOIN equipos e ON pe.id_equipo = e.id_equipo
                                    JOIN instructores ins ON pe.id_instructor = ins.id_instructor
                                    WHERE 1=1"; 

        $params_eq = [];
        $types_eq = "";

        if (!empty($search_instructor)) {
            $sql_devoluciones_equipos .= " AND (CONCAT(ins.nombre, ' ', ins.apellido) LIKE ? OR ins.nombre LIKE ? OR ins.apellido LIKE ?)";
            $like_search = "%" . $search_instructor . "%";
            array_push($params_eq, $like_search, $like_search, $like_search);
            $types_eq .= "sss";
        }
        if (!empty($fecha_desde)) {
            $sql_devoluciones_equipos .= " AND DATE(de.fecha_devolucion) >= ?";
            $params_eq[] = $fecha_desde;
            $types_eq .= "s";
        }
        if (!empty($fecha_hasta)) {
            $sql_devoluciones_equipos .= " AND DATE(de.fecha_devolucion) <= ?";
            $params_eq[] = $fecha_hasta;
            $types_eq .= "s";
        }
        $sql_devoluciones_equipos .= " ORDER BY pe.id_instructor ASC, pe.fecha_prestamo ASC, de.fecha_devolucion DESC, e.serial ASC"; 
        
        $stmt_dev_eq = $conn->prepare($sql_devoluciones_equipos);
        if ($stmt_dev_eq) {
            if (!empty($types_eq)) {
                $stmt_dev_eq->bind_param($types_eq, ...$params_eq);
            }
            if (!$stmt_dev_eq->execute()) {
                error_log("Error ejecutando consulta de devoluciones de equipos: " . $stmt_dev_eq->error);
            } else {
                $result_dev_eq = $stmt_dev_eq->get_result();
                $devoluciones_equipos_temp = [];
                while ($row = $result_dev_eq->fetch_assoc()) {
                    $devoluciones_equipos_temp[] = $row;
                }
                
                $mapa_grupos_equipos_devueltos = [];
                if (!empty($devoluciones_equipos_temp)) {
                    foreach ($devoluciones_equipos_temp as $de) {
                        $clave_grupo_devolucion = $de['id_instructor'] . '|' . $de['fecha_prestamo'] . '|' . $de['fecha_devolucion'];
                        
                        if (!isset($mapa_grupos_equipos_devueltos[$clave_grupo_devolucion])) {
                            $mapa_grupos_equipos_devueltos[$clave_grupo_devolucion] = [
                                'instructor_nombre' => $de['instructor_nombre'],
                                'instructor_apellido' => $de['instructor_apellido'],
                                'fecha_devolucion' => $de['fecha_devolucion'], 
                                'items_devueltos' => [],
                            ];
                        }
                        $mapa_grupos_equipos_devueltos[$clave_grupo_devolucion]['items_devueltos'][] = [
                            'id_prestamo_equipo' => $de['id_prestamo_equipo'], 
                            'marca' => $de['marca'],
                            'serial' => $de['serial'],
                            'estado_devolucion_equipo' => $de['estado_devolucion_equipo'],
                            'observaciones' => $de['observaciones']
                        ];
                    }

                    foreach ($mapa_grupos_equipos_devueltos as $clave_grupo_devolucion => $grupo_data) {
                        if (count($grupo_data['items_devueltos']) > 1) {
                            $grupo_data['tipo_historial'] = 'grupo_equipos_devueltos';
                        } else { 
                            $item_unico = $grupo_data['items_devueltos'][0];
                            $grupo_data['tipo_historial'] = 'equipo_devuelto_individual';
                            $grupo_data['item_detalle'] = $item_unico['marca'] . ' - ' . $item_unico['serial'];
                            $grupo_data['estado_devolucion_equipo'] = $item_unico['estado_devolucion_equipo'];
                            $grupo_data['observaciones'] = $item_unico['observaciones'];
                            unset($grupo_data['items_devueltos']); 
                        }
                        $lista_historial_combinado[] = $grupo_data;
                    }
                }
            }
            $stmt_dev_eq->close();
        } else {
            error_log("Error preparando SQL para devoluciones de equipos (historial): " . $conn->error);
        }

        $sql_devoluciones_materiales = "SELECT 
                                            dm.id_devolucion, 
                                            dm.fecha_devolucion, 
                                            dm.observaciones, 
                                            m.nombre AS material_nombre, 
                                            ins.nombre AS instructor_nombre, 
                                            ins.apellido AS instructor_apellido,
                                            (SELECT hc.detalles FROM historial_cambios hc WHERE hc.tabla_afectada = 'devolucion_materiales' AND hc.accion = 'crear' AND hc.id_registro = dm.id_devolucion ORDER BY hc.id_historial DESC LIMIT 1) AS log_info_devolucion_material
                                        FROM devolucion_materiales dm
                                        JOIN prestamo_materiales pm ON dm.id_prestamo_material = pm.id_prestamo_material
                                        JOIN materiales m ON pm.id_material = m.id_material
                                        JOIN instructores ins ON pm.id_instructor = ins.id_instructor
                                        WHERE 1=1 AND m.tipo = 'no consumible'";
        $params_mat = [];
        $types_mat = "";

        if (!empty($search_instructor)) {
            $sql_devoluciones_materiales .= " AND (CONCAT(ins.nombre, ' ', ins.apellido) LIKE ? OR ins.nombre LIKE ? OR ins.apellido LIKE ?)";
            $like_search_mat = "%" . $search_instructor . "%";
            array_push($params_mat, $like_search_mat, $like_search_mat, $like_search_mat);
            $types_mat .= "sss";
        }
        if (!empty($fecha_desde)) {
            $sql_devoluciones_materiales .= " AND DATE(dm.fecha_devolucion) >= ?";
            $params_mat[] = $fecha_desde;
            $types_mat .= "s";
        }
        if (!empty($fecha_hasta)) {
            $sql_devoluciones_materiales .= " AND DATE(dm.fecha_devolucion) <= ?";
            $params_mat[] = $fecha_hasta;
            $types_mat .= "s";
        }
        $sql_devoluciones_materiales .= " ORDER BY dm.fecha_devolucion DESC";

        $stmt_dev_mat = $conn->prepare($sql_devoluciones_materiales);
        if ($stmt_dev_mat) {
             if (!empty($types_mat)) {
                $stmt_dev_mat->bind_param($types_mat, ...$params_mat);
            }
            if(!$stmt_dev_mat->execute()){
                error_log("Error ejecutando consulta de devoluciones de materiales: " . $stmt_dev_mat->error);
            } else {
                $result_dev_mat = $stmt_dev_mat->get_result();
                while ($row_dev_mat = $result_dev_mat->fetch_assoc()) {
                    $cantidad_devuelta = 'N/D'; 
                    if (!empty($row_dev_mat['log_info_devolucion_material'])) {
                        if (preg_match('/cantidad devuelta: (\d+)/', $row_dev_mat['log_info_devolucion_material'], $matches_cant_dev)) {
                            $cantidad_devuelta = $matches_cant_dev[1];
                        }
                    }
                    $lista_historial_combinado[] = [
                        'tipo_historial' => 'material_devuelto',
                        'item_detalle' => $row_dev_mat['material_nombre'],
                        'instructor_nombre' => $row_dev_mat['instructor_nombre'],
                        'instructor_apellido' => $row_dev_mat['instructor_apellido'],
                        'cantidad_devuelta_en_transaccion' => $cantidad_devuelta,
                        'fecha_devolucion' => $row_dev_mat['fecha_devolucion'], 
                        'estado_devolucion_equipo' => 'N/A', 
                        'observaciones' => $row_dev_mat['observaciones']
                    ];
                }
            }
            $stmt_dev_mat->close();
        } else {
             error_log("Error preparando SQL para devoluciones de materiales (historial): " . $conn->error);
        }

        if (!empty($lista_historial_combinado)) {
            usort($lista_historial_combinado, function($a, $b) {
                return strtotime($b['fecha_devolucion']) - strtotime($a['fecha_devolucion']); 
            });
        }
        
        // INICIO: MODIFICACIÓN PARA PAGINACIÓN
        $total_records = count($lista_historial_combinado);
        $paginated_results = array_slice($lista_historial_combinado, $offset, $limit);
        $total_pages = ceil($total_records / $limit);

        echo json_encode([
            'success' => true, 
            'returns' => $paginated_results,
            'pagination' => [
                'total_records' => $total_records,
                'current_page' => $page,
                'limit' => $limit,
                'total_pages' => $total_pages
            ]
        ]);
        // FIN: MODIFICACIÓN PARA PAGINACIÓN
        break;

    case 'update_loan': 
        // ... (código existente sin cambios)
        $id_prestamo_modificar = $_POST['id_loan'] ?? ''; 
        $tipo_prestamo_modificar = $_POST['loan_type'] ?? ''; 
        $nuevo_id_instructor = $_POST['new_id_instructor'] ?? '';
        $nueva_fecha_devolucion_esperada = $_POST['new_fecha_devolucion_esperada'] ?? '';
        $nuevo_id_equipo_str = $_POST['new_id_equipo'] ?? ''; 
        
        if (empty($id_prestamo_modificar) || empty($tipo_prestamo_modificar) || empty($nuevo_id_instructor) || empty($nueva_fecha_devolucion_esperada)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos para actualizar (instructor, fecha esperada).']);
            exit;
        }
        
        $nuevo_id_equipo_validado_update = filter_var($nuevo_id_equipo_str, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        
        $conn->begin_transaction();
        try {
            $tabla_prestamo_actualizar = '';
            $columna_id_prestamo_actualizar = '';
            $columna_item_id_en_prestamo = '';

            if ($tipo_prestamo_modificar === 'equipo') {
                $tabla_prestamo_actualizar = 'prestamo_equipos';
                $columna_id_prestamo_actualizar = 'id_prestamo_equipo';
                $columna_item_id_en_prestamo = 'id_equipo';
            } elseif ($tipo_prestamo_modificar === 'material') {
                $tabla_prestamo_actualizar = 'prestamo_materiales';
                $columna_id_prestamo_actualizar = 'id_prestamo_material';
            } else {
                throw new Exception('Tipo de préstamo no reconocido para actualización.');
            }

            $sql_verif_prestamo_actual = "SELECT estado, id_instructor, fecha_devolucion_esperada";
            if ($tipo_prestamo_modificar === 'equipo') {
                $sql_verif_prestamo_actual .= ", $columna_item_id_en_prestamo AS id_item_original_prestamo";
            }
            $sql_verif_prestamo_actual .= " FROM $tabla_prestamo_actualizar WHERE $columna_id_prestamo_actualizar = ?";

            $stmt_verif_prestamo = $conn->prepare($sql_verif_prestamo_actual);
            $stmt_verif_prestamo->bind_param("i", $id_prestamo_modificar);
            $stmt_verif_prestamo->execute();
            $info_prestamo_actual_mod = $stmt_verif_prestamo->get_result()->fetch_assoc();
            $stmt_verif_prestamo->close();

            if (!$info_prestamo_actual_mod || $info_prestamo_actual_mod['estado'] !== 'pendiente') {
                throw new Exception('El préstamo no existe, no está pendiente o no se puede editar.');
            }
            
            $id_item_original_log = $info_prestamo_actual_mod['id_item_original_prestamo'] ?? null;
            $detalles_log_modificacion = [];

            if ($info_prestamo_actual_mod['id_instructor'] != $nuevo_id_instructor) {
                $detalles_log_modificacion[] = "Instructor cambiado a ID $nuevo_id_instructor";
            }
           
            if ($info_prestamo_actual_mod['fecha_devolucion_esperada'] != $nueva_fecha_devolucion_esperada ) {
                 $detalles_log_modificacion[] = "Fecha Esperada cambiada a $nueva_fecha_devolucion_esperada";
            }

            if ($tipo_prestamo_modificar === 'equipo') {
                if ($nuevo_id_equipo_validado_update !== null && $nuevo_id_equipo_validado_update != $id_item_original_log) {
                    $stmt_verif_nuevo_eq = $conn->prepare("SELECT estado FROM equipos WHERE id_equipo = ?");
                    $stmt_verif_nuevo_eq->bind_param("i", $nuevo_id_equipo_validado_update);
                    $stmt_verif_nuevo_eq->execute();
                    $estado_nuevo_eq = $stmt_verif_nuevo_eq->get_result()->fetch_assoc();
                    $stmt_verif_nuevo_eq->close();
                    if (!$estado_nuevo_eq || $estado_nuevo_eq['estado'] !== 'disponible') {
                        throw new Exception('El nuevo equipo (ID: '.$nuevo_id_equipo_validado_update.') no está disponible.');
                    }

                    if ($id_item_original_log !== null) { 
                        $stmt_liberar_eq_viejo = $conn->prepare("UPDATE equipos SET estado = 'disponible' WHERE id_equipo = ?");
                        $stmt_liberar_eq_viejo->bind_param("i", $id_item_original_log);
                        $stmt_liberar_eq_viejo->execute(); $stmt_liberar_eq_viejo->close();
                        log_change($conn, $id_almacenista_actual, 'equipos', 'actualizar_estado', $id_item_original_log, 'Estado a disponible (préstamo ID '.$id_prestamo_modificar.' modificado)');
                    }

                    $stmt_asignar_nuevo_eq = $conn->prepare("UPDATE equipos SET estado = 'prestado' WHERE id_equipo = ?");
                    $stmt_asignar_nuevo_eq->bind_param("i", $nuevo_id_equipo_validado_update);
                    $stmt_asignar_nuevo_eq->execute(); $stmt_asignar_nuevo_eq->close();
                    log_change($conn, $id_almacenista_actual, 'equipos', 'actualizar_estado', $nuevo_id_equipo_validado_update, 'Estado a prestado (asignado a préstamo ID '.$id_prestamo_modificar.')');
                    
                    $detalles_log_modificacion[] = "Equipo cambiado de ID $id_item_original_log a ID $nuevo_id_equipo_validado_update";

                    $stmt_actualizar_prestamo = $conn->prepare("UPDATE $tabla_prestamo_actualizar SET $columna_item_id_en_prestamo = ?, id_instructor = ?, fecha_devolucion_esperada = ? WHERE $columna_id_prestamo_actualizar = ?");
                    $stmt_actualizar_prestamo->bind_param("iisi", $nuevo_id_equipo_validado_update, $nuevo_id_instructor, $nueva_fecha_devolucion_esperada, $id_prestamo_modificar);
                } else { 
                    $stmt_actualizar_prestamo = $conn->prepare("UPDATE $tabla_prestamo_actualizar SET id_instructor = ?, fecha_devolucion_esperada = ? WHERE $columna_id_prestamo_actualizar = ?");
                    $stmt_actualizar_prestamo->bind_param("isi", $nuevo_id_instructor, $nueva_fecha_devolucion_esperada, $id_prestamo_modificar);
                }
            } else { 
                $stmt_actualizar_prestamo = $conn->prepare("UPDATE $tabla_prestamo_actualizar SET id_instructor = ?, fecha_devolucion_esperada = ? WHERE $columna_id_prestamo_actualizar = ?");
                $stmt_actualizar_prestamo->bind_param("isi", $nuevo_id_instructor, $nueva_fecha_devolucion_esperada, $id_prestamo_modificar);
            }

            if (!$stmt_actualizar_prestamo->execute()) { throw new Exception('Fallo al actualizar el préstamo: ' . $stmt_actualizar_prestamo->error); }
            $stmt_actualizar_prestamo->close();
            
            if (!empty($detalles_log_modificacion)){
                $detalle_final_log = "Préstamo $tipo_prestamo_modificar ID $id_prestamo_modificar actualizado: " . implode(", ", $detalles_log_modificacion);
                log_change($conn, $id_almacenista_actual, $tabla_prestamo_actualizar, 'actualizar', $id_prestamo_modificar, $detalle_final_log);
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Préstamo actualizado con éxito.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_loan': 
        // ... (código existente sin cambios)
        $id_prestamo_eliminar = $_POST['id_loan'] ?? '';
        $tipo_prestamo_eliminar = $_POST['loan_type'] ?? '';

        if (empty($id_prestamo_eliminar) || empty($tipo_prestamo_eliminar)) {
            echo json_encode(['success' => false, 'message' => 'ID o tipo de préstamo no especificado para eliminar.']); exit;
        }

        $conn->begin_transaction();
        try {
            $tabla_prestamo_del = '';
            $columna_id_prestamo_del = '';
            $columna_item_id_del = '';
            $tabla_item_del = '';
            $columna_estado_item_del = 'estado'; 

            if ($tipo_prestamo_eliminar === 'equipo') {
                $tabla_prestamo_del = 'prestamo_equipos';
                $columna_id_prestamo_del = 'id_prestamo_equipo';
                $columna_item_id_del = 'id_equipo';
                $tabla_item_del = 'equipos';
            } elseif ($tipo_prestamo_eliminar === 'material') {
                $tabla_prestamo_del = 'prestamo_materiales';
                $columna_id_prestamo_del = 'id_prestamo_material';
                $columna_item_id_del = 'id_material';
                $tabla_item_del = 'materiales';
                $columna_estado_item_del = 'stock'; 
            } else {
                throw new Exception('Tipo de préstamo no válido para eliminación.');
            }
            
            $sql_info_item_prestado = "SELECT $columna_item_id_del";
            if ($tipo_prestamo_eliminar === 'material') $sql_info_item_prestado .= ", cantidad"; 
            $sql_info_item_prestado .= " FROM $tabla_prestamo_del WHERE $columna_id_prestamo_del = ? AND estado = 'pendiente'";

            $stmt_info_item = $conn->prepare($sql_info_item_prestado);
            $stmt_info_item->bind_param("i", $id_prestamo_eliminar); 
            $stmt_info_item->execute();
            $info_item_prestado_actual = $stmt_info_item->get_result()->fetch_assoc(); 
            $stmt_info_item->close();

            if (!$info_item_prestado_actual) { throw new Exception('Préstamo no encontrado, ya devuelto o no es del tipo esperado.'); }
            
            $id_item_a_restaurar = $info_item_prestado_actual[$columna_item_id_del];
            $cantidad_a_restaurar = $info_item_prestado_actual['cantidad'] ?? null; 

            $stmt_eliminar_prestamo = $conn->prepare("DELETE FROM $tabla_prestamo_del WHERE $columna_id_prestamo_del = ?");
            $stmt_eliminar_prestamo->bind_param("i", $id_prestamo_eliminar);
            if (!$stmt_eliminar_prestamo->execute()) { throw new Exception('Fallo al eliminar el registro de préstamo: ' . $stmt_eliminar_prestamo->error); }
            $stmt_eliminar_prestamo->close();

            if ($tipo_prestamo_eliminar === 'equipo') {
                $stmt_restaurar_item = $conn->prepare("UPDATE $tabla_item_del SET $columna_estado_item_del = 'disponible' WHERE $columna_item_id_del = ?");
                $stmt_restaurar_item->bind_param("i", $id_item_a_restaurar);
                log_change($conn, $id_almacenista_actual, $tabla_item_del, 'actualizar_estado', $id_item_a_restaurar, 'Estado a disponible (préstamo ID '.$id_prestamo_eliminar.' eliminado)');
            } elseif ($tipo_prestamo_eliminar === 'material') {
                $stmt_restaurar_item = $conn->prepare("UPDATE $tabla_item_del SET $columna_estado_item_del = $columna_estado_item_del + ? WHERE $columna_item_id_del = ?");
                $stmt_restaurar_item->bind_param("ii", $cantidad_a_restaurar, $id_item_a_restaurar);
                log_change($conn, $id_almacenista_actual, $tabla_item_del, 'actualizar_stock', $id_item_a_restaurar, 'Stock restaurado en ' . $cantidad_a_restaurar . ' (préstamo ID '.$id_prestamo_eliminar.' eliminado)');
            }
            if (!$stmt_restaurar_item->execute()) { throw new Exception('Fallo al restaurar el ítem en inventario: ' . $stmt_restaurar_item->error); }
            $stmt_restaurar_item->close();
            
            log_change($conn, $id_almacenista_actual, $tabla_prestamo_del, 'eliminar', $id_prestamo_eliminar, 'Préstamo ID '.$id_prestamo_eliminar.' (item ID '.$id_item_a_restaurar.') eliminado.');

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Préstamo eliminado y el ítem ha sido devuelto al inventario.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'devolver_material': 
        $id_prestamo_material_dev = $_POST['id_prestamo'] ?? ''; 
        $cantidad_material_a_devolver = (int)($_POST['cantidad_devolver'] ?? 0);
        $observaciones_mat_dev_raw = $_POST['observaciones'] ?? null; // Captura la observación cruda
        $observaciones_mat_dev = is_string($observaciones_mat_dev_raw) ? trim($observaciones_mat_dev_raw) : null; // Limpia si es string
        $fecha_devolucion_mat = date('Y-m-d H:i:s');

        if (empty($id_prestamo_material_dev) || $cantidad_material_a_devolver <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos no válidos para la devolución del material.']); exit;
        }
        $conn->begin_transaction();
        try {
            $stmt_info_prestamo_mat_dev = $conn->prepare("SELECT pm.id_material, pm.cantidad AS cantidad_prestada_actual, pm.id_instructor, m.tipo AS tipo_material_real, m.nombre as material_nombre FROM prestamo_materiales pm JOIN materiales m ON pm.id_material = m.id_material WHERE pm.id_prestamo_material = ? AND pm.estado = 'pendiente'");
            if(!$stmt_info_prestamo_mat_dev) throw new Exception("Error preparando consulta info préstamo material: " . $conn->error);
            $stmt_info_prestamo_mat_dev->bind_param("i", $id_prestamo_material_dev); 
            $stmt_info_prestamo_mat_dev->execute();
            $detalles_prestamo_mat_dev = $stmt_info_prestamo_mat_dev->get_result()->fetch_assoc(); 
            $stmt_info_prestamo_mat_dev->close();

            if (!$detalles_prestamo_mat_dev) { throw new Exception('El préstamo de material no está pendiente o no existe.'); }
            if ($detalles_prestamo_mat_dev['tipo_material_real'] !== 'no consumible') { throw new Exception('Este préstamo no corresponde a un material no consumible que requiera devolución formal y novedad.');}

            $id_material_devuelto = $detalles_prestamo_mat_dev['id_material'];
            $cantidad_pendiente_original_dev = (int)$detalles_prestamo_mat_dev['cantidad_prestada_actual'];
            $id_instructor_material_dev = $detalles_prestamo_mat_dev['id_instructor'];
            $nombre_material_novedad = $detalles_prestamo_mat_dev['material_nombre'];


            if ($cantidad_material_a_devolver > $cantidad_pendiente_original_dev) {
                throw new Exception('No se puede devolver más material del que está actualmente pendiente en el préstamo.');
            }

            $stmt_reg_devolucion_mat = $conn->prepare("INSERT INTO devolucion_materiales (id_prestamo_material, fecha_devolucion, observaciones) VALUES (?, ?, ?)"); 
            if(!$stmt_reg_devolucion_mat) throw new Exception("Error preparando inserción en devolucion_materiales: " . $conn->error);
           
            $stmt_reg_devolucion_mat->bind_param("iss", $id_prestamo_material_dev, $fecha_devolucion_mat, $observaciones_mat_dev);
            if (!$stmt_reg_devolucion_mat->execute()) { throw new Exception('Fallo al registrar la devolución del material: ' . $stmt_reg_devolucion_mat->error); }
            $id_devolucion_mat_gen = $conn->insert_id; 
            $stmt_reg_devolucion_mat->close();
            log_change($conn, $id_almacenista_actual, 'devolucion_materiales', 'crear', $id_devolucion_mat_gen, "Devolución préstamo ID $id_prestamo_material_dev, material ID $id_material_devuelto, cantidad devuelta: $cantidad_material_a_devolver");


            $stmt_restaurar_stock_mat = $conn->prepare("UPDATE materiales SET stock = stock + ? WHERE id_material = ?");
            if(!$stmt_restaurar_stock_mat) throw new Exception("Error preparando actualización de stock: " . $conn->error);
            $stmt_restaurar_stock_mat->bind_param("ii", $cantidad_material_a_devolver, $id_material_devuelto);
            if (!$stmt_restaurar_stock_mat->execute()) { throw new Exception('Fallo al actualizar el stock del material devuelto: ' . $stmt_restaurar_stock_mat->error); }
            $stmt_restaurar_stock_mat->close();
            log_change($conn, $id_almacenista_actual, 'materiales', 'actualizar_stock', $id_material_devuelto, 'Stock restaurado en ' . $cantidad_material_a_devolver . ' por devolución.');

            $nueva_cantidad_pendiente_prestamo = $cantidad_pendiente_original_dev - $cantidad_material_a_devolver;

            if ($nueva_cantidad_pendiente_prestamo == 0) { 
                $stmt_act_prestamo_mat_dev = $conn->prepare(
                    "UPDATE prestamo_materiales SET estado = 'devuelto', fecha_devolucion = ?, cantidad = 0 
                     WHERE id_prestamo_material = ?"
                ); 
                if(!$stmt_act_prestamo_mat_dev) throw new Exception("Error preparando actualización préstamo (devuelto): " . $conn->error);
                $stmt_act_prestamo_mat_dev->bind_param("si", $fecha_devolucion_mat, $id_prestamo_material_dev);
            } else { 
                $stmt_act_prestamo_mat_dev = $conn->prepare(
                    "UPDATE prestamo_materiales SET cantidad = ? 
                     WHERE id_prestamo_material = ?"
                );
                if(!$stmt_act_prestamo_mat_dev) throw new Exception("Error preparando actualización préstamo (parcial): " . $conn->error);
                $stmt_act_prestamo_mat_dev->bind_param("ii", $nueva_cantidad_pendiente_prestamo, $id_prestamo_material_dev);
            }
            
            if (!$stmt_act_prestamo_mat_dev->execute()) { throw new Exception('Fallo al actualizar el estado/cantidad del préstamo de material: ' . $stmt_act_prestamo_mat_dev->error); }
            $stmt_act_prestamo_mat_dev->close();
            
            $log_detalle_prestamo_mat = 'Cant. devuelta: ' . $cantidad_material_a_devolver . ', cant. pendiente ahora: ' . $nueva_cantidad_pendiente_prestamo . ($nueva_cantidad_pendiente_prestamo == 0 ? ', estado final: devuelto' : ', devolución parcial');
            log_change($conn, $id_almacenista_actual, 'prestamo_materiales', 'actualizar_devolucion', $id_prestamo_material_dev, $log_detalle_prestamo_mat);

            
            if ($detalles_prestamo_mat_dev['tipo_material_real'] === 'no consumible' && !empty($observaciones_mat_dev)) { // $observaciones_mat_dev ya está trimeada
                $stmt_instructor_novedad_mat = $conn->prepare("SELECT nombre, apellido FROM instructores WHERE id_instructor = ?");
                if(!$stmt_instructor_novedad_mat) throw new Exception("Error preparando consulta instructor para novedad material: " . $conn->error);
                $stmt_instructor_novedad_mat->bind_param("i", $id_instructor_material_dev);
                $stmt_instructor_novedad_mat->execute();
                $instructor_info_novedad_mat = $stmt_instructor_novedad_mat->get_result()->fetch_assoc();
                $stmt_instructor_novedad_mat->close();
                $nombre_instructor_novedad_mat = $instructor_info_novedad_mat ? htmlspecialchars($instructor_info_novedad_mat['nombre'] . " " . $instructor_info_novedad_mat['apellido']) : "Desconocido";

                $observacion_novedad_mat_final = "Novedad en devolución de material '" . htmlspecialchars($nombre_material_novedad) . "' (ID Material: $id_material_devuelto). ";
                $observacion_novedad_mat_final .= "Instructor: " . $nombre_instructor_novedad_mat . ". ";
                $observacion_novedad_mat_final .= "Cantidad devuelta: " . $cantidad_material_a_devolver . ". ";
                $observacion_novedad_mat_final .= "Observación reportada: " . htmlspecialchars($observaciones_mat_dev);

                $stmt_insert_novedad_mat = $conn->prepare("INSERT INTO novedades (id_item, tipo_item, id_almacenista_reporta, observacion, fecha_novedad) VALUES (?, 'material', ?, ?, NOW())");
                if (!$stmt_insert_novedad_mat) {
                     error_log("Error preparando inserción de novedad para material devuelto: " . $conn->error);
                } else {
                    $stmt_insert_novedad_mat->bind_param("iis", $id_material_devuelto, $id_almacenista_actual, $observacion_novedad_mat_final);
                    if (!$stmt_insert_novedad_mat->execute()) {
                        error_log("Fallo al insertar novedad para devolución de material. ID PrestamoMaterial: $id_prestamo_material_dev. Error: " . $stmt_insert_novedad_mat->error);
                    }
                    $stmt_insert_novedad_mat->close();
                }
            }
            // --- FIN MODIFICACIÓN ---

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Material devuelto con éxito.']);
        } catch (Exception $e) {
            $conn->rollback();
             echo json_encode(['success' => false, 'message' => "Excepción: " . $e->getMessage()]);
        }
        break;

    case 'get_available_equipment':
        
        $lista_equipos_disponibles = [];
        $sql_eq_disp = "SELECT id_equipo, marca, serial FROM equipos WHERE estado = 'disponible' ORDER BY marca ASC, serial ASC";
        $result_eq_disp = $conn->query($sql_eq_disp);
        if ($result_eq_disp) { while ($row_eq_disp = $result_eq_disp->fetch_assoc()) { $lista_equipos_disponibles[] = $row_eq_disp; } }
        echo json_encode(['success' => true, 'equipment' => $lista_equipos_disponibles]);
        break;

    case 'get_available_materials':
      
        $lista_materiales_disponibles = [];
        $sql_mat_disp = "SELECT id_material, nombre, stock, tipo FROM materiales WHERE stock > 0 ORDER BY nombre ASC";
        $result_mat_disp = $conn->query($sql_mat_disp);
        if ($result_mat_disp) { while ($row_mat_disp = $result_mat_disp->fetch_assoc()) { $lista_materiales_disponibles[] = $row_mat_disp; } }
        echo json_encode(['success' => true, 'materials' => $lista_materiales_disponibles]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción solicitada no es válida: ' . htmlspecialchars($action)]);
        break;
}

if ($conn && property_exists($conn, 'connect_error') && !$conn->connect_error && $conn->ping()) { 
    $conn->close();
}
?>
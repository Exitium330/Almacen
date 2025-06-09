<?php
include('requiere_login.php');
require_once 'conexion.php'; 

if (!isset($_SESSION['id_usuario'])) {
    // Manejo si no hay sesión
}
$id_almacenista = $_SESSION['id_usuario'] ?? null; 

date_default_timezone_set('America/Bogota');

function getAvailableEquipment($conn) {
    $sql = "SELECT id_equipo, marca, serial FROM equipos WHERE estado = 'disponible' ORDER BY id_equipo ASC";
    $result = $conn->query($sql);
    $equipment = [];
    if ($result) { while ($row = $result->fetch_assoc()) { $equipment[] = $row; } }
    return $equipment;
}

function getInstructors($conn) {
    $sql = "SELECT id_instructor, nombre, apellido FROM instructores ORDER BY nombre, apellido ASC";
    $result = $conn->query($sql);
    $instructors = [];
    if ($result) { while ($row = $result->fetch_assoc()) { $instructors[] = $row; } }
    return $instructors;
}

function getAvailableMaterials($conn) {
    $sql = "SELECT id_material, nombre, stock, tipo FROM materiales WHERE stock > 0 ORDER BY nombre ASC";
    $result = $conn->query($sql);
    $materials = [];
    if ($result) { while ($row = $result->fetch_assoc()) { $materials[] = $row; } }
    return $materials;
}

$availableEquipment = getAvailableEquipment($conn);
$instructors = getInstructors($conn);
$availableMaterials = getAvailableMaterials($conn);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Img/icono_proyecto.png">
    <title>Gestión de Préstamos y Devoluciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --pastel-green: #C8E6C9; 
            --pine-green: #0A6640;   
            --light-green: #E8F5E9;  
            --success-color: #28a745;
            --danger-color: #dc3545;
            --info-color: #0dcaf0; 
            --warning-color: #ffc107; /* Color para botones de advertencia/devolución individual */
            --light-bg: #f8f9fa;
            --white-bg: #ffffff;
            --border-color: #dee2e6;
            --shadow-color: rgba(0,0,0,0.1);
        }
        body { padding-top: 20px; background-color: var(--light-bg); font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .container {
            background-color: var(--white-bg);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px var(--shadow-color);
            position: relative; 
        }
        h1 { color: var(--pine-green); margin-bottom: 30px; text-align: center; font-weight: 600;}
        h2 { color: var(--pine-green); margin-bottom: 20px; font-weight: 500; border-bottom: 2px solid var(--pastel-green); padding-bottom: 10px;}
        .nav-tabs .nav-link { color: var(--pine-green); border-width: 1px 1px 0 1px; border-style: solid; border-color: var(--light-green); margin-right: 5px; border-radius: 5px 5px 0 0; transition: all 0.3s ease; font-weight: 500;}
        .nav-tabs .nav-link.active { color: var(--white-bg); background-color: var(--pine-green); border-color: var(--pine-green); font-weight: bold; }
        .nav-tabs .nav-link:hover:not(.active) { color: var(--pine-green); border-color: var(--pastel-green); }
        .tab-content { margin-top: -1px; border: 1px solid var(--pine-green); border-radius: 0 8px 8px 8px; padding: 25px; background-color: var(--white-bg); }
        .form-section, .table-section { margin-bottom: 30px; padding: 20px; border: 1px solid var(--light-green); border-radius: 8px; background-color: #fdfdfd; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .btn-primary { background-color: var(--pine-green); border-color: var(--pine-green); transition: background-color 0.3s ease, transform 0.2s ease; }
        .btn-primary:hover { background-color: #085533; border-color: #085533; transform: translateY(-2px); }
        .btn-success { background-color: var(--success-color); border-color: var(--success-color); transition: background-color 0.3s ease, transform 0.2s ease; }
        .btn-success:hover { background-color: #218838; border-color: #1e7e34; transform: translateY(-2px); }
        .btn-info { background-color: var(--info-color); border-color: var(--info-color); color: #000; }
        .btn-info:hover { background-color: #0a9cb5; border-color: #0a9cb5; color: #000;}
        .btn-warning { background-color: var(--warning-color); border-color: var(--warning-color); color: #000; } /* Estilo para botón de advertencia */
        .btn-warning:hover { background-color: #e0a800; border-color: #d39e00; color: #000; }
        .btn-outline-warning { border-color: var(--warning-color); color: var(--warning-color); } /* Estilo para botón de advertencia delineado */
        .btn-outline-warning:hover { background-color: var(--warning-color); color: #000; }


        .table thead { background-color: var(--pine-green); color: var(--white-bg); }
        .table tbody tr:hover { background-color: var(--light-green); }
        .notification { position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px; }
        .timer.overdue { color: var(--danger-color); font-weight: bold; }
        .timer { font-weight: bold; }
        .select2-container--bootstrap-5 .select2-selection {min-height: calc(1.5em + .75rem + 2px);}
        .select2-container--open { z-index: 9999 !important; }
        .list-unstyled li {font-size: 0.9rem;}
        .align-middle {vertical-align: middle !important;}

        .container .top-right-image {
            position: absolute;
            top: 15px; 
            right: 15px; 
            width: 80px; 
            height: auto;
            z-index: 1000;
        }
        .action-buttons-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px; 
            align-items: center; 
            justify-content: center;
        }
        .action-buttons-group .btn { 
            flex-grow: 0; 
        }

        #detalle_items_devolucion_grupo_container .item-devolucion-info h6 {
            font-size: 0.95rem;
            color: var(--pine-green);
            margin-bottom: 0.5rem;
        }
         #detalle_items_devolucion_grupo_container .item-devolucion-info {
            background-color: #f9f9f9; 
        }
        #detalle_items_devolucion_grupo_container .item-devolucion-info .header-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        #detalle_items_devolucion_grupo_container .item-devolucion-info .header-item h6 {
            margin-bottom: 0; /* Remover margen inferior del h6 */
        }


        .table-group-header {
            background-color: #e9ecef !important; 
            font-weight: bold;
        }
        .table-group-header td {
             border-bottom: 2px solid var(--pine-green) !important;
        }
        .table-group-item td { 
            font-size: 0.9em;
            background-color: #f8f9fa !important; 
        }
        .table-group-item td:first-child { 
             padding-left: 2.5rem !important; 
        }
        .collapse-container-row td { 
            padding: 0 !important;
            border-top: none !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="img/sena.png" alt="Logo Sena" class="top-right-image">

        <h1>📊 Gestión de Préstamos y Devoluciones</h1>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="prestar-tab" data-bs-toggle="tab" data-bs-target="#prestar" type="button" role="tab" aria-controls="prestar" aria-selected="true">🚀 Prestar</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activos-tab" data-bs-toggle="tab" data-bs-target="#activos" type="button" role="tab" aria-controls="activos" aria-selected="false">⏱️ Préstamos Activos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="devolver-tab" data-bs-toggle="tab" data-bs-target="#devolver" type="button" role="tab" aria-controls="devolver" aria-selected="false">🔄 Devolver</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-controls="historial" aria-selected="false">📜 Historial Devoluciones</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="prestar" role="tabpanel" aria-labelledby="prestar-tab">
                <div class="row mt-4">
                    <div class="col-md-6 form-section">
                        <h2>💻 Prestar Equipo(s)</h2>
                        <form id="prestarEquipoForm">
                            <div class="mb-3">
                                <label for="id_equipos" class="form-label">Equipo(s) a prestar:</label>
                                <select class="form-select select2" id="id_equipos" name="id_equipos[]" multiple required>
                                    <?php foreach ($availableEquipment as $equipo): ?>
                                        <option value="<?= htmlspecialchars($equipo['id_equipo']) ?>">
                                            <?= htmlspecialchars($equipo['marca'] . ' - ' . $equipo['serial']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_instructor_equipo" class="form-label">Instructor:</label>
                                <select class="form-select select2" id="id_instructor_equipo" name="id_instructor" required>
                                    <option value="">Seleccione un instructor</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?= htmlspecialchars($instructor['id_instructor']) ?>">
                                            <?= htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-success w-100" onclick="prestarEquipo()">Prestar Equipo(s)</button>
                        </form>
                    </div>

                    <div class="col-md-6 form-section">
                        <h2>📦 Prestar Material</h2>
                        <form id="prestarMaterialForm">
                            <div class="mb-3">
                                <label for="id_material" class="form-label">Material a prestar:</label>
                                <select class="form-select select2" id="id_material" name="id_material" required>
                                    <option value="">Seleccione un material</option>
                                    <?php foreach ($availableMaterials as $material): ?>
                                        <option value="<?= htmlspecialchars($material['id_material']) ?>" data-type="<?= htmlspecialchars($material['tipo']) ?>">
                                            <?= htmlspecialchars($material['nombre'] . ' (Stock: ' . $material['stock'] . ' - Tipo: ' . $material['tipo'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_instructor_material" class="form-label">Instructor:</label>
                                <select class="form-select select2" id="id_instructor_material" name="id_instructor" required>
                                    <option value="">Seleccione un instructor</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?= htmlspecialchars($instructor['id_instructor']) ?>">
                                            <?= htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cantidad_material" class="form-label">Cantidad:</label>
                                <input type="number" class="form-control" id="cantidad_material" name="cantidad" min="1" required>
                            </div>
                            <button type="button" class="btn btn-success w-100" onclick="prestarMaterial()">Prestar Material</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="activos" role="tabpanel" aria-labelledby="activos-tab">
                <div class="table-section mt-4">
                    <h2>⏱️ Préstamos Activos y Pendientes</h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="activeLoansTable">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Ítem(s)</th>
                                    <th>Instructor</th>
                                    <th>Fecha Préstamo</th>
                                    <th>Fecha Esperada</th>
                                    <th>Tiempo Restante/Atraso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="8" class="text-center">Cargando préstamos activos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="devolver" role="tabpanel" aria-labelledby="devolver-tab">
                <div class="row mt-4">
                    <div class="col-md-6 form-section"> 
                        <h2><i class="fas fa-users"></i> Devolver Grupo de Equipos</h2>
                        <form id="devolverGrupoEquiposForm">
                            <div class="mb-3">
                                <label for="devolver_id_grupo_prestamo" class="form-label">Grupo de Préstamo a devolver:</label>
                                <select class="form-select select2" id="devolver_id_grupo_prestamo" name="id_grupo_prestamo_selector" required>
                                    <option value="">Seleccione un grupo de préstamo</option>
                                </select>
                            </div>
                            
                            <div id="detalle_items_devolucion_grupo_container" class="mt-3 mb-3" style="max-height: 350px; overflow-y: auto; padding: 10px; border: 1px solid #eee; border-radius: 5px;">
                                <p class="text-muted text-center small" id="msg_seleccione_grupo_devolucion">Seleccione un grupo para ver los equipos a devolver.</p>
                            </div>

                            <button type="button" class="btn btn-success w-100 mt-2" id="btnDevolverGrupoCompleto" style="display:none;" onclick="devolverGrupoCompleto()">Devolver Todos los Equipos Listados del Grupo</button>
                        </form>
                    </div>

                    <div class="col-md-6 form-section">
                        <h2><i class="fas fa-box"></i> Devolver Material (No Consumible)</h2>
                        <form id="devolverMaterialForm">
                            <div class="mb-3">
                                <label for="devolver_id_material_prestado" class="form-label">Préstamo de Material a devolver:</label>
                                <select class="form-select select2" id="devolver_id_material_prestado" name="id_prestamo_material" required> 
                                    <option value="">Seleccione un préstamo de material</option>
                                </select>
                            </div>
                             <div class="mb-3">
                                <label for="devolver_cantidad_material" class="form-label">Cantidad a Devolver:</label>
                                <input type="number" class="form-control" id="devolver_cantidad_material" name="cantidad_devolver" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="devolver_observaciones_material" class="form-label">Observaciones:</label>
                                <textarea class="form-control" id="devolver_observaciones_material" name="observaciones_material" rows="3"></textarea>
                            </div>
                            <button type="button" class="btn btn-success w-100" onclick="devolverMaterial()">Devolver Material</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
                 <div class="table-section mt-4">
                    <h2>📜 Historial de Devoluciones</h2>
                    
                    <div class="row mb-3 gy-2 gx-3 align-items-center bg-light p-3 rounded shadow-sm">
                        <div class="col-md-4">
                            <label for="search_instructor_historial" class="form-label">Buscar por Instructor:</label>
                            <input type="text" class="form-control form-control-sm" id="search_instructor_historial" placeholder="Nombre o apellido">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_desde_historial" class="form-label">Desde:</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_desde_historial">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_hasta_historial" class="form-label">Hasta:</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_hasta_historial">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-info" id="filterReturnHistoryBtn" title="Aplicar Filtros"><i class="fas fa-search"></i> Filtrar</button>
                                <button type="button" class="btn btn-sm btn-secondary" id="resetReturnHistoryFilterBtn" title="Limpiar Filtros"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3"> 
                         <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleIndividualReturnsBtn" style="display: none;"><i class="fas fa-eye"></i> Mostrar Devoluciones Individuales</button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover"> 
                            <thead>
                                <tr>
                                    <th>Tipo Devolución</th>
                                    <th>Ítem(s) / Detalles</th>
                                    <th>Instructor</th>
                                    <th>Cantidad (Material)</th>
                                    <th>Fecha Devolución</th>
                                    <th>Estado Devolución (Equipo)</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody id="returnList">
                                <tr><td colspan="7" class="text-center">Utilice los filtros para cargar el historial de devoluciones.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="notification-container" class="notification"></div>

    <div class="modal fade" id="editLoanModal" tabindex="-1" aria-labelledby="editLoanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLoanModalLabel">✏️ Editar Préstamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editLoanForm">
                        <input type="hidden" id="edit_loan_id" name="id_loan">
                        <input type="hidden" id="edit_loan_type" name="loan_type">
                        <input type="hidden" id="edit_current_item_id" name="current_item_id"> 
                        <div class="mb-3" id="edit_equipo_section" style="display:none;">
                            <label for="edit_id_equipo" class="form-label">Cambiar Equipo (Opcional):</label>
                            <select class="form-select select2-modal" id="edit_id_equipo" name="new_id_equipo" style="width: 100%;">
                                <option value="">Mantener equipo actual</option>
                                <?php foreach ($availableEquipment as $equipo): ?>
                                    <option value="<?= htmlspecialchars($equipo['id_equipo']) ?>">
                                        <?= htmlspecialchars($equipo['marca'] . ' - ' . $equipo['serial']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Si selecciona un nuevo equipo, el actual será devuelto a disponible y este préstamo se asignará al nuevo equipo.</small>
                        </div>

                        <div class="mb-3">
                            <label for="edit_id_instructor" class="form-label">Instructor:</label>
                            <select class="form-select select2-modal" id="edit_id_instructor" name="new_id_instructor" style="width: 100%;" required>
                                <option value="">Seleccione un instructor</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?= htmlspecialchars($instructor['id_instructor']) ?>">
                                        <?= htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="edit_fecha_devolucion_section">
                            <label for="edit_fecha_devolucion_esperada" class="form-label">Fecha Devolución Esperada:</label>
                            <input type="datetime-local" class="form-control" id="edit_fecha_devolucion_esperada" name="new_fecha_devolucion_esperada">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="updateLoan()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageGroupItemsModal" tabindex="-1" aria-labelledby="manageGroupItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageGroupItemsModalLabel">🔧 Gestionar Equipos del Préstamo Grupal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Instructor:</strong> <span id="modal_group_instructor_name"></span></p>
                    <p><strong>Referencia del Préstamo:</strong> <span id="modal_group_id_display"></span></p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Equipo (Marca - Serial)</th>
                                    <th>Fecha Devolución Esperada Individual</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="manageGroupItemsList">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmarDevolucionIndividualModal" tabindex="-1" aria-labelledby="confirmarDevolucionIndividualModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmarDevolucionIndividualModalLabel">Confirmar Devolución Individual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Devolviendo equipo: <strong id="nombreEquipoDevIndividual"></strong></p>
                    <input type="hidden" id="idPrestamoEquipoDevIndividual">
                    <div class="mb-3">
                        <label for="estadoDevolucionIndividual" class="form-label">Estado de Devolución:</label>
                        <select class="form-select" id="estadoDevolucionIndividual">
                            <option value="bueno" selected>Bueno 👍</option>
                            <option value="regular">Regular 😐</option>
                            <option value="malo">Malo 👎</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observacionesDevolucionIndividual" class="form-label">Observaciones (opcional):</label>
                        <textarea class="form-control" id="observacionesDevolucionIndividual" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarDevolucionIndividual" onclick="procesarDevolucionIndividual()">Confirmar Devolución</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const API_URL = 'prestamos_api.php';
        let globalAllAvailableEquipment = <?= json_encode($availableEquipment) ?>; 
        const ID_ALMACENISTA_LOGUEADO = <?= $id_almacenista ?? 'null' ?>;

        // --- INICIO: Nueva función auxiliar y modificación de listeners ---
        function refrescarDetallesGrupoDevolucion() {
            const selectedOption = $('#devolver_id_grupo_prestamo').find('option:selected');
            const container = $('#detalle_items_devolucion_grupo_container');
            container.empty(); 

            if (selectedOption.val() && selectedOption.data('items_individuales')) { 
                const itemsIndividuales = selectedOption.data('items_individuales'); 
                // console.log("Refrescando detalles con items:", itemsIndividuales); 

                if (itemsIndividuales && itemsIndividuales.length > 0) {
                    itemsIndividuales.forEach(function(item, index) {
                        const marca = item.marca || 'N/D';
                        const serial = item.serial || 'N/D';
                        const nombreCompletoEquipo = htmlspecialchars(marca + ' - ' + serial);

                        const devolverSoloEsteBtnHtml = `
                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                    title="Devolver solo este equipo: ${nombreCompletoEquipo}" 
                                    onclick="abrirModalConfirmarDevolucionIndividual(${item.id_prestamo_equipo}, '${nombreCompletoEquipo.replace(/'/g, "\\'")}')">
                                <i class="fas fa-undo-alt"></i> Devolver solo este
                            </button>
                        `;

                        const itemHtml = `
                            <div class="mb-3 border p-3 rounded item-devolucion-info" data-id-prestamo-equipo="${item.id_prestamo_equipo}">
                                <div class="header-item">
                                    <h6>${index + 1}. Equipo: ${nombreCompletoEquipo}</h6>
                                    ${devolverSoloEsteBtnHtml}
                                </div>
                                <input type="hidden" name="items_devolucion[${index}][id_prestamo_equipo]" value="${item.id_prestamo_equipo}">
                                <div class="row mt-2">
                                    <div class="col-md-6 mb-2">
                                        <label for="estado_devolucion_item_${index}" class="form-label fw-normal small">Estado (si devuelve todo el grupo):</label>
                                        <select class="form-select form-select-sm" name="items_devolucion[${index}][estado_devolucion]" id="estado_devolucion_item_${index}" required>
                                            <option value="bueno" selected>Bueno 👍</option>
                                            <option value="regular">Regular 😐</option>
                                            <option value="malo">Malo 👎</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="observaciones_item_${index}" class="form-label fw-normal small">Observaciones (si devuelve todo el grupo):</label>
                                        <textarea class="form-control form-control-sm" name="items_devolucion[${index}][observaciones]" id="observaciones_item_${index}" rows="1"></textarea>
                                    </div>
                                </div>
                            </div>`;
                        container.append(itemHtml);
                    });
                    $('#btnDevolverGrupoCompleto').show();
                } else {
                    container.html('<p class="text-muted text-center small">No hay más equipos pendientes en este grupo.</p>');
                    $('#btnDevolverGrupoCompleto').hide();
                }
            } else {
                 container.html('<p class="text-muted text-center small" id="msg_seleccione_grupo_devolucion">Seleccione un grupo para ver los equipos a devolver.</p>'); 
                 $('#btnDevolverGrupoCompleto').hide();
            }
        }
        // --- FIN: Nueva función auxiliar ---


        $(document).ready(function() {
            if (ID_ALMACENISTA_LOGUEADO === null) {
                showNotification("Error: No se pudo identificar al almacenista. Por favor, inicie sesión nuevamente.", "danger");
            }
            $('.select2').each(function() {
                let placeholderText = $(this).find('option[value=""]').length > 0 ? $(this).find('option[value=""]').text() : 'Seleccione una opción';
                if ($(this).prop('multiple')) {
                    placeholderText = 'Seleccione uno o más equipos';
                }
                $(this).select2({
                    theme: "bootstrap-5",
                    width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
                    placeholder: placeholderText,
                });
            });

             $('.select2-modal').each(function() {
                 let placeholderText = $(this).find('option[value=""]').length > 0 ? $(this).find('option[value=""]').text() : 'Seleccione una opción';
                 $(this).select2({
                    theme: "bootstrap-5",
                    width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
                    placeholder: placeholderText,
                    dropdownParent: $(this).closest('.modal')
                });
            });


            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                console.log("Tab changed to: ", e.target.id);
                if (e.target.id === 'activos-tab') {
                    loadActiveLoans();
                } else if (e.target.id === 'devolver-tab') {
                    loadReturnDropdowns(); 
                } else if (e.target.id === 'prestar-tab') {
                     updateAvailableEquipmentDropdowns(); 
                     updateAvailableMaterialsDropdown();
                } else if (e.target.id === 'historial-tab') {
                    fetchAndDisplayReturnHistory(); 
                } else {
                    if (window.timerInterval) {
                        clearInterval(window.timerInterval);
                    }
                }
            });

            const activeTabId = $('.nav-tabs .nav-link.active').attr('id');
            if (activeTabId === 'activos-tab') {
                loadActiveLoans();
            } else if (activeTabId === 'devolver-tab') {
                loadReturnDropdowns();
            } else if (activeTabId === 'historial-tab') {
                 fetchAndDisplayReturnHistory();
            }


            $('#devolver_id_material_prestado').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const loanedQuantity = selectedOption.data('loaned-quantity');
                if (loanedQuantity) {
                     $('#devolver_cantidad_material').val(loanedQuantity).prop('max', loanedQuantity).prop('placeholder', `Máx: ${loanedQuantity}`);
                } else {
                    $('#devolver_cantidad_material').val('').prop('max', '').prop('placeholder', 'Seleccione préstamo primero');
                }
            });

            // Modificado para usar la nueva función de refresco
            $('#devolver_id_grupo_prestamo').on('change', function() {
                refrescarDetallesGrupoDevolucion();
            });

             $('#filterReturnHistoryBtn').on('click', function() {
                const params = {
                    search_instructor_nombre: $('#search_instructor_historial').val().trim(),
                    fecha_desde: $('#fecha_desde_historial').val(),
                    fecha_hasta: $('#fecha_hasta_historial').val()
                };
                fetchAndDisplayReturnHistory(params);
            });

            $('#resetReturnHistoryFilterBtn').on('click', function() {
                $('#search_instructor_historial').val('');
                $('#fecha_desde_historial').val('');
                $('#fecha_hasta_historial').val('');
                fetchAndDisplayReturnHistory(); 
            });

            $('#returnList').on('show.bs.collapse', '.collapse', function () {
                $(`tr[data-bs-target="#${$(this).attr('id')}"]`).find('.indicator-icon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });

            $('#returnList').on('hide.bs.collapse', '.collapse', function () {
                $(`tr[data-bs-target="#${$(this).attr('id')}"]`).find('.indicator-icon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });
            
            $('#toggleIndividualReturnsBtn').on('click', function() {
                const $individualRows = $('#returnList .individual-return-row');
                const areVisible = $individualRows.first().is(':visible') && !$individualRows.first().hasClass('d-none');

                if (areVisible) {
                    $individualRows.addClass('d-none'); 
                    $(this).html('<i class="fas fa-eye"></i> Mostrar Devoluciones Individuales');
                } else {
                    $individualRows.removeClass('d-none'); 
                    $(this).html('<i class="fas fa-eye-slash"></i> Ocultar Devoluciones Individuales');
                }
            });


        }); // Fin $(document).ready

        function showNotification(message, type) {
            const container = $('#notification-container');
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert"><strong>${type === 'success' ? 'Éxito:' : (type === 'warning' ? 'Atención:' : 'Error:')}</strong> ${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
            const alertElement = $(alertHtml).appendTo(container);
            setTimeout(() => { alertElement.alert('close'); }, 5000);
        }

        window.prestarEquipo = function() {
             const form = $('#prestarEquipoForm');
            const idEquipos = $('#id_equipos').val();
            const idInstructor = form.find('select[name="id_instructor"]').val();

            if (!idEquipos || idEquipos.length === 0 || !idInstructor) {
                showNotification("Por favor, seleccione al menos un equipo y un instructor.", "warning");
                return;
            }
            const formData = { action: "prestar_equipo", id_equipos: idEquipos, id_instructor: idInstructor, id_almacenista: ID_ALMACENISTA_LOGUEADO };
            $.ajax({
                url: API_URL, type: 'POST', data: formData, dataType: 'json',
                success: function(response) {
                    showNotification(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        form[0].reset();
                        $('#id_equipos').val(null).trigger('change');
                        $('#id_instructor_equipo').val('').trigger('change');
                        updateAvailableEquipmentDropdowns();
                        if ($('#activos-tab').hasClass('active')) loadActiveLoans();
                    }
                },
                error: function(xhr) { showNotification("Error en la solicitud: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger"); }
            });
        };

        window.prestarMaterial = function() {
            const form = $('#prestarMaterialForm');
            const idMaterial = form.find('select[name="id_material"]').val();
            const idInstructor = form.find('select[name="id_instructor"]').val();
            const cantidad = parseInt(form.find('input[name="cantidad"]').val());
            const selectedMaterialOption = form.find('select[name="id_material"] option:selected');
            const materialType = selectedMaterialOption.data('type');

            if (!idMaterial || !idInstructor || !cantidad || cantidad <= 0) {
                showNotification("Por favor, complete todos los campos y asegúrese que la cantidad sea válida.", "warning"); return;
            }
            const stockText = selectedMaterialOption.text().match(/\(Stock: (\d+)/);
            const currentStock = stockText && stockText.length > 1 ? parseInt(stockText[1]) : 0;
            if (cantidad > currentStock) {
                showNotification(`No hay suficiente stock. Stock disponible: ${currentStock}.`, "danger"); return;
            }

            const formData = { action: "prestar_material", id_material: idMaterial, id_instructor: idInstructor, cantidad: cantidad, id_almacenista: ID_ALMACENISTA_LOGUEADO };
            $.ajax({
                url: API_URL, type: 'POST', data: formData, dataType: 'json',
                success: function(response) {
                    showNotification(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        form[0].reset();
                        $('#id_material').val('').trigger('change');
                        $('#id_instructor_material').val('').trigger('change');
                        updateAvailableMaterialsDropdown();
                        if ($('#activos-tab').hasClass('active') && materialType === 'no consumible') loadActiveLoans();
                    }
                },
                error: function(xhr) { showNotification("Error en la solicitud: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger"); }
            });
        };

        window.editLoan = function(loanId, loanType, currentInstructorId, currentExpectedReturnDate, currentItemId = null, currentItemName = null) {
            $('#editLoanForm')[0].reset();
            $('#edit_loan_id').val(loanId);
            $('#edit_loan_type').val(loanType);
            $('#edit_current_item_id').val(currentItemId); 

            $('#edit_id_instructor').val(currentInstructorId).trigger('change');

            const editEquipoDropdown = $('#edit_id_equipo');
            if (loanType === 'equipo') {
                $('#edit_equipo_section').show();
                editEquipoDropdown.empty().append(`<option value="">Mantener: ${htmlspecialchars(currentItemName)}</option>`);
                globalAllAvailableEquipment.forEach(function(eq) {
                    if (String(eq.id_equipo) !== String(currentItemId)) {
                         editEquipoDropdown.append(`<option value="${htmlspecialchars(eq.id_equipo)}">${htmlspecialchars(eq.marca + ' - ' + eq.serial)}</option>`);
                    }
                });
                editEquipoDropdown.val('').trigger('change.select2'); 

                $('#edit_fecha_devolucion_section').show();
                $('#edit_fecha_devolucion_esperada').prop('required', true);
            } else if (loanType === 'material') { 
                $('#edit_equipo_section').hide();
                editEquipoDropdown.val('').trigger('change'); 
                $('#edit_fecha_devolucion_section').show();
                $('#edit_fecha_devolucion_esperada').prop('required', true);
            }

            let formattedDate = '';
            if (currentExpectedReturnDate && currentExpectedReturnDate !== 'NULL' && currentExpectedReturnDate !== '0000-00-00 00:00:00') {
                try {
                    const dateObj = new Date(currentExpectedReturnDate.replace(/-/g, '/').replace(' ', 'T'));
                    if (!isNaN(dateObj)) {
                         formattedDate = dateObj.toISOString().slice(0, 16);
                    } else {
                         console.warn("Fecha inválida para edición:", currentExpectedReturnDate);
                    }
                } catch(e) { console.error("Error formatting date for edit modal:", e, currentExpectedReturnDate); formattedDate = '';}
            }
            $('#edit_fecha_devolucion_esperada').val(formattedDate);

            $('#editLoanModal').modal('show');
        };

        window.updateLoan = function() {
            const form = $('#editLoanForm');
            const loanType = $('#edit_loan_type').val();
            const idLoan = $('#edit_loan_id').val(); 

            let formDataObj = {
                action: "update_loan",
                id_loan: idLoan,
                loan_type: loanType,
                new_id_instructor: $('#edit_id_instructor').val(),
                new_fecha_devolucion_esperada: $('#edit_fecha_devolucion_esperada').val()
            };

            if (loanType === 'equipo') {
                formDataObj.new_id_equipo = $('#edit_id_equipo').val() || ''; 
            }

            if (!formDataObj.new_id_instructor || !formDataObj.new_fecha_devolucion_esperada) {
                 showNotification("Complete el instructor y la fecha de devolución esperada.", "warning"); return;
            }

            $.ajax({
                url: API_URL, type: 'POST', data: formDataObj, dataType: 'json',
                success: function(response) {
                    showNotification(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        $('#editLoanModal').modal('hide');
                        loadActiveLoans(); 
                        if (loanType === 'equipo') { 
                             updateAvailableEquipmentDropdowns(); 
                             loadReturnDropdowns(); 
                        }
                    }
                },
                error: function(xhr) { showNotification("Error en la solicitud de actualización: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");}
            });
        };

        window.deleteLoan = function(loanId, loanType) { 
            if (confirm('¿Está seguro de que quiere eliminar este préstamo? El ítem será devuelto al inventario.')) {
                $.ajax({
                    url: API_URL, type: 'POST', data: { action: "delete_loan", id_loan: loanId, loan_type: loanType }, dataType: 'json',
                    success: function(response) {
                        showNotification(response.message, response.success ? 'success' : 'danger');
                        if (response.success) {
                            loadActiveLoans();
                            loadReturnDropdowns(); 
                            if (loanType === 'equipo') updateAvailableEquipmentDropdowns();
                            else if (loanType === 'material') updateAvailableMaterialsDropdown();
                        }
                    },
                    error: function(xhr) { showNotification("Error en la solicitud de eliminación: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");}
                });
            }
        };
        
        window.deleteLoanAndRefreshActive = function(loanId, loanType) {
            deleteLoan(loanId, loanType); 
            $('#manageGroupItemsModal').modal('hide'); 
        };

        window.openManageGroupModal = function(buttonElement) {
            const equiposDataString = $(buttonElement).data('equipos');
            const groupId = $(buttonElement).data('groupId');
            const instructorId = String($(buttonElement).data('instructorId')); 
            const instructorFullName = $(buttonElement).data('instructorFullName');

            let equiposIndividuales;
            try {
                if (typeof equiposDataString === 'string') {
                    equiposIndividuales = JSON.parse(equiposDataString);
                } else {
                    equiposIndividuales = equiposDataString; 
                }
            } catch (e) {
                console.error("Error al parsear datos de equipos para el modal:", e, equiposDataString);
                showNotification("Error al cargar datos de equipos para gestionar.", "danger");
                return;
            }

            const modalListBody = $('#manageGroupItemsList');
            modalListBody.empty(); 

            $('#modal_group_instructor_name').text(instructorFullName);
            $('#modal_group_id_display').text(groupId); 

            if (equiposIndividuales && equiposIndividuales.length > 0) {
                equiposIndividuales.forEach(function(equipo, index) {
                    const itemNombreCompletoForDisplay = htmlspecialchars(equipo.marca + ' - ' + equipo.serial);
                    const rawItemNameForEditLoan = (equipo.marca + ' - ' + equipo.serial); 
                    
                    const equipoFechaEsperadaRaw = equipo.fecha_devolucion_esperada_individual;
                    const equipoFechaEsperadaDisplay = (equipoFechaEsperadaRaw && equipoFechaEsperadaRaw !== 'NULL' && equipoFechaEsperadaRaw !== '0000-00-00 00:00:00') ? 
                                                      new Date(equipoFechaEsperadaRaw.replace(' ', 'T')).toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : 'N/A';

                    const editButtonOnclick = `editLoan(${equipo.id_prestamo_equipo}, 'equipo', '${instructorId}', '${equipo.fecha_devolucion_esperada_individual || ''}', '${equipo.id_equipo_actual}', '${rawItemNameForEditLoan.replace(/'/g, "\\'").replace(/"/g, "&quot;")}'); $('#manageGroupItemsModal').modal('hide');`;
                    const deleteButtonOnclick = `deleteLoanAndRefreshActive(${equipo.id_prestamo_equipo}, 'equipo');`;
                    
                    const rowHtml = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${itemNombreCompletoForDisplay}</td>
                            <td>${equipoFechaEsperadaDisplay}</td>
                            <td>
                                <div class="btn-group" role="group" aria-label="Acciones de equipo individual">
                                    <button type="button" class="btn btn-sm btn-info" title="Editar este equipo" onclick="${editButtonOnclick}">✏️</button>
                                    <button type="button" class="btn btn-sm btn-danger" title="Eliminar este equipo del préstamo" onclick="${deleteButtonOnclick}">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    `;
                    modalListBody.append(rowHtml);
                });
            } else {
                modalListBody.append('<tr><td colspan="4" class="text-center">No hay equipos en este grupo, o ya fueron devueltos/eliminados.</td></tr>');
            }
            $('#manageGroupItemsModal').modal('show');
        };

        window.abrirModalConfirmarDevolucionIndividual = function(idPrestamoEquipo, nombreEquipo) {
            $('#idPrestamoEquipoDevIndividual').val(idPrestamoEquipo);
            $('#nombreEquipoDevIndividual').text(nombreEquipo);
            $('#estadoDevolucionIndividual').val('bueno'); 
            $('#observacionesDevolucionIndividual').val(''); 
            
            var modalDevInd = document.getElementById('confirmarDevolucionIndividualModal');
            var bootstrapModalDevInd = bootstrap.Modal.getInstance(modalDevInd);
            if (!bootstrapModalDevInd) {
                bootstrapModalDevInd = new bootstrap.Modal(modalDevInd);
            }
            bootstrapModalDevInd.show();
        }

        window.procesarDevolucionIndividual = function() {
            const idPrestamoEquipo = $('#idPrestamoEquipoDevIndividual').val();
            const estado = $('#estadoDevolucionIndividual').val();
            const observaciones = $('#observacionesDevolucionIndividual').val();
            const submitButton = $('#btnConfirmarDevolucionIndividual');
            const originalButtonText = submitButton.html();

            if (!idPrestamoEquipo) {
                showNotification("Error: No se encontró el ID del préstamo del equipo.", "danger");
                return;
            }
            submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            $.ajax({
                url: API_URL,
                type: 'POST',
                data: {
                    action: 'devolver_equipo_individual',
                    id_prestamo_equipo: idPrestamoEquipo,
                    estado_devolucion: estado,
                    observaciones: observaciones
                },
                dataType: 'json',
                success: function(response) {
                    showNotification(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        var modalInstance = bootstrap.Modal.getInstance(document.getElementById('confirmarDevolucionIndividualModal'));
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        loadActiveLoans(); 
                        updateAvailableEquipmentDropdowns(); 
                        
                        // --- INICIO: Lógica de refresco mejorada ---
                        loadDevolucionGroupDropdown(function() {
                            // Este callback se ejecuta después de que el dropdown de grupos se haya recargado.
                            // La función refrescarDetallesGrupoDevolucion usará la data actualizada del option seleccionado.
                            refrescarDetallesGrupoDevolucion();
                        });
                        // --- FIN: Lógica de refresco mejorada ---
                        
                        var manageModalInstance = bootstrap.Modal.getInstance(document.getElementById('manageGroupItemsModal'));
                        if(manageModalInstance && $(manageModalInstance._element).is(':visible')) {
                             manageModalInstance.hide(); 
                        }
                    }
                },
                error: function(xhr) {
                    showNotification("Error en la solicitud de devolución individual: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");
                },
                complete: function() {
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            });
        }


        window.devolverMaterial = function() { 
            const form = $('#devolverMaterialForm');
            const idPrestamoMaterial = form.find('select[name="id_prestamo_material"]').val(); 
            const cantidadDevolver = parseInt(form.find('input[name="cantidad_devolver"]').val());
            const maxQuantity = parseInt($('#devolver_cantidad_material').attr('max'));

            if (!idPrestamoMaterial || !cantidadDevolver || cantidadDevolver <= 0) {
                showNotification("Seleccione préstamo de material y cantidad válida.", "warning"); return;
            }
            if (cantidadDevolver > maxQuantity) {
                 showNotification(`No puede devolver más material del que está prestado (${maxQuantity}).`, "danger"); return;
            }
            const formData = {
                action: "devolver_material",
                id_prestamo: idPrestamoMaterial, 
                cantidad_devolver: cantidadDevolver,
                observaciones: form.find('textarea[name="observaciones_material"]').val(),
                id_almacenista: ID_ALMACENISTA_LOGUEADO
            };
            
            $.ajax({
                url: API_URL, type: 'POST', data: formData, dataType: 'json',
                success: function(response) {
                    showNotification(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        form[0].reset();
                        $('#devolver_id_material_prestado').val('').trigger('change');
                        $('#devolver_cantidad_material').val('').prop('max', '').prop('placeholder', 'Seleccione préstamo primero');
                        loadReturnDropdowns(); 
                        loadActiveLoans(); 
                        updateAvailableMaterialsDropdown();
                    }
                },
                error: function(xhr) { showNotification("Error en la solicitud de devolución de material: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");}
            });
        };

        window.devolverGrupoCompleto = function() {
            const selectedGroupOption = $('#devolver_id_grupo_prestamo').find('option:selected');
            if (!selectedGroupOption.val()) {
                showNotification("Por favor, seleccione un grupo de préstamo.", "warning");
                return;
            }
            const groupValueParts = selectedGroupOption.val().split('|');
            const idInstructorGrupo = groupValueParts[0];
            const fechaPrestamoRefGrupo = groupValueParts[1];

            const itemsPayload = [];
            let formIsValid = true;
            
            $('#detalle_items_devolucion_grupo_container .item-devolucion-info').each(function(index) {
                const itemDiv = $(this);
                const idPrestamoEquipo = itemDiv.find(`input[name="items_devolucion[${index}][id_prestamo_equipo]"]`).val();
                const estadoDevolucion = itemDiv.find(`select[name="items_devolucion[${index}][estado_devolucion]"]`).val();
                const observaciones = itemDiv.find(`textarea[name="items_devolucion[${index}][observaciones]"]`).val();

                if (!estadoDevolucion) { 
                    formIsValid = false;
                    const equipoNombre = itemDiv.find('h6').text() || `el ítem ${index + 1}`;
                    showNotification(`Por favor, seleccione el estado de devolución para ${equipoNombre}.`, "warning");
                    itemDiv.find(`select[name="items_devolucion[${index}][estado_devolucion]"]`).focus();
                    return false; 
                }

                itemsPayload.push({
                    id_prestamo_equipo: idPrestamoEquipo,
                    estado_devolucion: estadoDevolucion,
                    observaciones: observaciones
                });
            });

            if (!formIsValid) {
                return;
            }
            if (itemsPayload.length === 0) {
                 showNotification("No hay equipos para procesar en este grupo.", "info");
                return;
            }

            const formData = {
                action: "devolver_grupo_equipos",
                id_instructor_grupo: idInstructorGrupo,
                fecha_prestamo_ref_grupo: fechaPrestamoRefGrupo,
                items: itemsPayload 
            };

            console.log("Enviando para devolución de grupo:", formData);

            $.ajax({
                url: API_URL,
                type: 'POST',
                data: formData, 
                dataType: 'json',
                success: function(response) {
                    showNotification(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        $('#detalle_items_devolucion_grupo_container').empty().html('<p class="text-muted text-center small" id="msg_seleccione_grupo_devolucion">Seleccione un grupo para ver los equipos a devolver.</p>');
                        $('#btnDevolverGrupoCompleto').hide();
                        loadReturnDropdowns(); 
                        loadActiveLoans(); 
                        updateAvailableEquipmentDropdowns(); 
                    }
                },
                error: function(xhr) { 
                    showNotification("Error en la solicitud de devolución de grupo: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");
                    console.error("Error en devolverGrupoCompleto:", xhr.responseText);
                }
            });
        }


        function loadActiveLoans() {
            console.log("loadActiveLoans called");
            if (window.timerInterval) { clearInterval(window.timerInterval); }
            $.ajax({
                url: API_URL + '?action=list_active_loans', type: 'GET', dataType: 'json',
                success: function(response) {
                    console.log("API Response for list_active_loans:", response);
                    const tableBody = $('#activeLoansTable tbody');
                    tableBody.empty();
                    if (response.success && response.loans && response.loans.length > 0) {
                        response.loans.forEach(function(loan_item) {
                            if (loan_item.tipo === 'grupo_equipo') {
                                const grupo = loan_item;
                                const fechaPrestamoGrupo = htmlspecialchars(new Date(grupo.fecha_prestamo_referencia.replace(' ', 'T')).toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }));
                                const fechaEsperadaGrupoRaw = grupo.fecha_devolucion_esperada_grupo;
                                const fechaEsperadaGrupoDisplay = (fechaEsperadaGrupoRaw && fechaEsperadaGrupoRaw !== 'NULL' && fechaEsperadaGrupoRaw !== '0000-00-00 00:00:00') ? new Date(fechaEsperadaGrupoRaw.replace(' ', 'T')).toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : 'N/A';

                                let equiposHtml = '<ul class="list-unstyled mb-0 ps-3">';
                                grupo.equipos_individuales.forEach(function(equipo, index) {
                                    const itemNombreCompleto = htmlspecialchars(equipo.marca + ' - ' + equipo.serial);
                                    const timerId = `timer_eq_${equipo.id_prestamo_equipo}`;
                                    const fechaDevIndividual = equipo.fecha_devolucion_esperada_individual || '';

                                    equiposHtml += `
                                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom" data-end-time-individual="${htmlspecialchars(fechaDevIndividual)}">
                                            <span style="font-size: 0.9em;">${index + 1}. ${itemNombreCompleto}</span>
                                            <small class="timer-individual me-2" id="${timerId}" style="font-size: 0.8em; min-width: 90px; text-align: right;"></small>
                                        </li>`;
                                });
                                equiposHtml += '</ul>';
                                
                                const equiposJsonForDataAttr = htmlspecialchars(JSON.stringify(grupo.equipos_individuales));
                                const instructorFullNameForDataAttr = htmlspecialchars(grupo.instructor_nombre + ' ' + grupo.instructor_apellido);
                                const groupIdForDataAttr = htmlspecialchars(grupo.id_grupo); 
                                const instructorIdForDataAttr = htmlspecialchars(String(grupo.id_instructor));

                                const manageButtonHtml = `
                                    <button type="button" class="btn btn-sm btn-primary" 
                                        data-equipos='${equiposJsonForDataAttr}'
                                        data-group-id='${groupIdForDataAttr}'
                                        data-instructor-id='${instructorIdForDataAttr}'
                                        data-instructor-full-name='${instructorFullNameForDataAttr}'
                                        onclick="openManageGroupModal(this)" 
                                        title="Gestionar Equipos de este Préstamo">
                                    🔧 Gestionar (${grupo.equipos_individuales.length})
                                    </button>
                                `;

                                const groupRowHtml = `
                                    <tr data-loan-id="${htmlspecialchars(grupo.id_grupo)}"
                                        data-loan-type="grupo_equipo"
                                        data-end-time="${fechaEsperadaGrupoRaw || ''}"
                                        data-instructor-id="${htmlspecialchars(grupo.id_instructor)}">
                                        <td class="align-middle">💻 Equipos (Grupo)</td>
                                        <td>${equiposHtml}</td>
                                        <td class="align-middle">${htmlspecialchars(grupo.instructor_nombre + ' ' + grupo.instructor_apellido)}</td>
                                        <td class="align-middle">${fechaPrestamoGrupo}</td>
                                        <td class="align-middle">${fechaEsperadaGrupoDisplay}</td>
                                        <td class="timer align-middle"></td>
                                        <td class="align-middle">${htmlspecialchars(grupo.estado_grupo)}</td>
                                        <td class="align-middle">
                                            <div class="action-buttons-group">
                                                ${manageButtonHtml}
                                            </div>
                                        </td>
                                    </tr>`;
                                tableBody.append(groupRowHtml);

                            } else if (loan_item.tipo === 'material') {
                                const material = loan_item;
                                const itemDetails = htmlspecialchars(material.material_nombre + ' (Cant: ' + material.cantidad + ')');
                                const currentItemName = htmlspecialchars(material.material_nombre);
                                const currentItemId = htmlspecialchars(material.id_material_actual); 
                                const fechaPrestamo = htmlspecialchars(new Date(material.fecha_prestamo.replace(' ', 'T')).toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }));
                                const fechaEsperadaRaw = material.fecha_devolucion_esperada;
                                const fechaEsperadaDisplay = (fechaEsperadaRaw && fechaEsperadaRaw !== 'NULL' && fechaEsperadaRaw !== '0000-00-00 00:00:00') ? new Date(fechaEsperadaRaw.replace(' ', 'T')).toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : 'N/A';
                                const materialLoanId = material.id_prestamo; 

                                const materialRowHtml = `
                                    <tr data-loan-id="${htmlspecialchars(materialLoanId)}" data-loan-type="${htmlspecialchars(material.material_tipo)}" data-end-time="${fechaEsperadaRaw || ''}"
                                        data-instructor-id="${htmlspecialchars(material.id_instructor)}"
                                        data-item-id="${currentItemId}" data-item-name="${currentItemName.replace(/'/g, "\\'").replace(/"/g, "&quot;")}">
                                        <td class="align-middle">📦 ${htmlspecialchars(material.material_tipo.charAt(0).toUpperCase() + material.material_tipo.slice(1))}</td>
                                        <td class="align-middle">${itemDetails}</td>
                                        <td class="align-middle">${htmlspecialchars(material.instructor_nombre + ' ' + material.instructor_apellido)}</td>
                                        <td class="align-middle">${fechaPrestamo}</td>
                                        <td class="align-middle">${fechaEsperadaDisplay}</td>
                                        <td class="timer align-middle"></td>
                                        <td class="align-middle">${htmlspecialchars(material.estado)}</td>
                                        <td class="align-middle">
                                            <div class="action-buttons-group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="editLoan(
                                                    ${htmlspecialchars(materialLoanId)},
                                                    'material', 
                                                    '${htmlspecialchars(material.id_instructor)}',
                                                    '${fechaEsperadaRaw || ''}',
                                                    '${currentItemId}',
                                                    '${currentItemName.replace(/'/g, "\\'").replace(/"/g, "&quot;")}'
                                                )">✏️ Editar</button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteLoan(${htmlspecialchars(materialLoanId)}, 'material')">🗑️ Eliminar</button>
                                            </div>
                                        </td>
                                    </tr>`;
                                tableBody.append(materialRowHtml);
                            }
                        });
                        startTimers();
                    } else {
                        tableBody.append('<tr><td colspan="8" class="text-center">🎉 No hay préstamos activos o pendientes en este momento.</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("loadActiveLoans Error:", status, error, xhr.responseText);
                    tableBody.html('<tr><td colspan="8" class="text-center">⚠️ Error al cargar préstamos activos. Verifique la consola.</td></tr>');
                    showNotification("Error al cargar préstamos activos: " + (xhr.responseJSON ? xhr.responseJSON.message : error), "danger");
                }
            });
        }
        
        function loadReturnDropdowns() { 
            console.log("loadReturnDropdowns llamada");
            // Modificado para que loadDevolucionGroupDropdown llame a refrescarDetallesGrupoDevolucion al completarse
            loadDevolucionGroupDropdown(function(){
                // Si hay un grupo seleccionado después de cargar el dropdown, refrescar sus detalles.
                if ($('#devolver_id_grupo_prestamo').val()) {
                    refrescarDetallesGrupoDevolucion();
                } else {
                     $('#detalle_items_devolucion_grupo_container').empty().html('<p class="text-muted text-center small" id="msg_seleccione_grupo_devolucion">Seleccione un grupo para ver los equipos a devolver.</p>');
                     $('#btnDevolverGrupoCompleto').hide();
                }
            }); 

            $.ajax({
                url: API_URL + '?action=list_active_loans_for_return', 
                type: 'GET', dataType: 'json',
                success: function(response) {
                    const materialDropdown = $('#devolver_id_material_prestado');
                    const currentMaterialVal = materialDropdown.val(); 
                    materialDropdown.empty().append('<option value="">Seleccione un préstamo de material</option>');

                    if (response.success && response.loans && response.loans.length > 0) {
                        response.loans.forEach(function(loan) {
                             if (loan.tipo === 'material') { 
                                const fechaPrestamoFormateada = new Date(loan.fecha_prestamo.replace(' ', 'T')).toLocaleDateString('es-CO', {day: '2-digit', month: '2-digit', year: 'numeric'});
                                const displayText = `${htmlspecialchars(loan.material_nombre)} (Cant: ${loan.cantidad}) (Inst: ${htmlspecialchars(loan.instructor_nombre + ' ' + loan.instructor_apellido)} - ${fechaPrestamoFormateada})`;
                                materialDropdown.append(`<option value="${htmlspecialchars(loan.id_prestamo)}" data-loaned-quantity="${htmlspecialchars(loan.cantidad)}">${displayText}</option>`);
                            }
                        });
                    }
                    materialDropdown.val(currentMaterialVal);
                    materialDropdown.trigger('change.select2'); 
                    if (materialDropdown.val()) { 
                        materialDropdown.trigger('change'); 
                    }
                },
                error: function(xhr) {showNotification("Error al cargar préstamos de material para devolución: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");}
            });
        }
        
        // Modificado para aceptar un callback
        function loadDevolucionGroupDropdown(callback) {
            console.log("loadDevolucionGroupDropdown called con callback");
            const grupoDropdown = $('#devolver_id_grupo_prestamo');
            const currentSelectedValue = grupoDropdown.val();

            $.ajax({
                url: API_URL + '?action=list_grupos_equipos_para_devolucion',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    grupoDropdown.empty().append('<option value="">Seleccione un grupo de préstamo</option>');
                    if (response.success && response.grupos && response.grupos.length > 0) {
                        response.grupos.forEach(function(grupo) {
                            const fechaPrestamoFormateada = new Date(grupo.fecha_prestamo_referencia.replace(' ', 'T')).toLocaleDateString('es-CO', {day: '2-digit', month: '2-digit', year: 'numeric'});
                            const displayText = `Grupo: ${htmlspecialchars(grupo.instructor_nombre + ' ' + grupo.instructor_apellido)} (${fechaPrestamoFormateada}) - ${grupo.cantidad_en_grupo_pendiente} equipo(s)`;
                            const optionValue = `${grupo.id_instructor}|${grupo.fecha_prestamo_referencia}`;
                            
                            const optionElement = $(`<option value="${optionValue}">${displayText}</option>`);
                            optionElement.data('items_individuales', grupo.items_individuales); 
                            grupoDropdown.append(optionElement);
                        });
                        
                        if (currentSelectedValue && grupoDropdown.find(`option[value="${currentSelectedValue}"]`).length > 0) {
                            grupoDropdown.val(currentSelectedValue);
                        }
                    }
                    grupoDropdown.trigger('change.select2'); // Actualizar UI de Select2

                    if (typeof callback === 'function') {
                        callback(); // Ejecutar el callback aquí, después de poblar y seleccionar
                    } else {
                        // Si no hay callback, igual intentar refrescar los detalles por si acaso (comportamiento anterior)
                        // pero es mejor manejarlo explícitamente con el callback.
                        if(grupoDropdown.val()){
                            refrescarDetallesGrupoDevolucion();
                        } else {
                             $('#detalle_items_devolucion_grupo_container').empty().html('<p class="text-muted text-center small" id="msg_seleccione_grupo_devolucion">No hay grupos con equipos pendientes de devolución.</p>');
                             $('#btnDevolverGrupoCompleto').hide();
                        }
                    }
                },
                error: function(xhr) {
                    showNotification("Error al cargar grupos para devolución: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");
                    console.error("Error en loadDevolucionGroupDropdown:", xhr.responseText);
                     $('#detalle_items_devolucion_grupo_container').empty().html('<p class="text-danger text-center small">Error al cargar grupos.</p>');
                     $('#btnDevolverGrupoCompleto').hide();
                    if (typeof callback === 'function') {
                        callback(); // Ejecutar callback para limpiar UI si es necesario
                    }
                }
            });
        }


        function updateLoanTimers() {
            $('#activeLoansTable tbody tr').each(function() {
                const row = $(this);
                const timerElement = row.children('td.timer');
                if (timerElement.length > 0) {
                    const endTimeStr = row.data('end-time');
                    if (!endTimeStr || endTimeStr === 'NULL' || endTimeStr === '0000-00-00 00:00:00') {
                        timerElement.text('N/A').removeClass('overdue').css('color', '');
                    } else {
                        const endTime = new Date(endTimeStr.replace(' ', 'T')); 
                        const now = new Date();
                        if (isNaN(endTime.getTime())) {
                            timerElement.text('Fecha Inv.').removeClass('overdue').css('color', 'orange');
                        } else {
                            const diffMs = endTime.getTime() - now.getTime();
                            const s_total = Math.abs(diffMs) / 1000;
                            const days = Math.floor(s_total / 86400);
                            const hours = Math.floor((s_total % 86400) / 3600);
                            const minutes = Math.floor((s_total % 3600) / 60);
                            const seconds = Math.floor(s_total % 60);
                            let timerText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                            if (days === 0) timerText = `${hours}h ${minutes}m ${seconds}s`;
                            if (days === 0 && hours === 0) timerText = `${minutes}m ${seconds}s`;

                            if (diffMs <= 0) {
                                timerElement.html(`Vencido:<br>${timerText}`).addClass('overdue').css('color', 'var(--danger-color)');
                            } else {
                                timerElement.html(`${timerText}`).removeClass('overdue').css('color', 'var(--success-color)');
                            }
                        }
                    }
                }
            });

            $('#activeLoansTable tbody ul li').each(function() {
                const listItem = $(this);
                const endTimeStr = listItem.data('end-time-individual');
                const timerElement = listItem.find('.timer-individual');
                if (!timerElement.length) return;

                if (!endTimeStr || endTimeStr === 'NULL' || endTimeStr === '0000-00-00 00:00:00') {
                    timerElement.text('N/A').removeClass('overdue').css('color', '');
                } else {
                    const endTime = new Date(endTimeStr.replace(' ', 'T'));
                    const now = new Date();
                    if (isNaN(endTime.getTime())) {
                        timerElement.text('Fecha Inv.').removeClass('overdue').css('color', 'orange');
                    } else {
                        const diffMs = endTime.getTime() - now.getTime();
                        const s_total = Math.abs(diffMs) / 1000;
                        const days = Math.floor(s_total / 86400);
                        const hours = Math.floor((s_total % 86400) / 3600);
                        let timerTextInd = `${days}d ${hours}h`;
                        if (days === 0) timerTextInd = `${hours}h ${Math.floor((s_total % 3600) / 60)}m`;

                        if (diffMs <= 0) {
                            timerElement.text(`V: ${timerTextInd}`).addClass('overdue').css('color', 'var(--danger-color)');
                        } else {
                            timerElement.text(`${timerTextInd}`).removeClass('overdue').css('color', 'var(--success-color)');
                        }
                    }
                }
            });
        }

        let timerInterval;
        function startTimers() {
            if (timerInterval) clearInterval(timerInterval);
            updateLoanTimers(); 
            timerInterval = setInterval(updateLoanTimers, 1000);
        }

        function fetchAndDisplayReturnHistory(params = {}) {
            console.log("Fetching history with params:", params);
            $.ajax({
                url: API_URL + '?action=list_devoluciones',
                type: 'GET',
                data: params,
                dataType: 'json',
                beforeSend: function() {
                    $('#returnList').empty().html('<tr><td colspan="7" class="text-center">Cargando historial... <i class="fas fa-spinner fa-spin"></i></td></tr>');
                },
                success: function(response) {
                    console.log("API Response for list_devoluciones:", JSON.stringify(response, null, 2));
                    const listBody = $('#returnList');
                    listBody.empty();
                    let hasIndividualReturns = false;
                    let hasAnyReturns = false;

                    if (response.success && response.returns && response.returns.length > 0) {
                        hasAnyReturns = true;
                        let groupIndex = 0;
                        response.returns.forEach(function(item) {
                            let rowsHtml = '';
                            const instructorFullName = htmlspecialchars(item.instructor_nombre + ' ' + item.instructor_apellido);
                            const fechaDevolucionPrincipal = htmlspecialchars(new Date(item.fecha_devolucion.replace(' ', 'T')).toLocaleString('es-CO', {dateStyle:'short', timeStyle:'short'}));
                            
                            if (item.tipo_historial === 'grupo_equipos_devueltos') {
                                groupIndex++;
                                const collapseId = `group-items-detail-${groupIndex}`;
                                
                                rowsHtml += `
                                    <tr class="table-group-header" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}" style="cursor: pointer;">
                                        <td><strong>Devolución Grupal (Equipos)</strong> <i class="fas fa-chevron-down indicator-icon ms-2"></i></td>
                                        <td>${item.items_devueltos.length} ítems</td>
                                        <td>${instructorFullName}</td>
                                        <td>N/A</td>
                                        <td>${fechaDevolucionPrincipal}</td>
                                        <td>Múltiples</td>
                                        <td>Clic para expandir/colapsar</td>
                                    </tr>`;
                                
                                rowsHtml += `<tr class="collapse-container-row"><td colspan="7" style="padding: 0 !important; border-top:none !important;">`;
                                rowsHtml += `<div class="collapse" id="${collapseId}">`;
                                rowsHtml += `<table class="table table-sm mb-0 table-bordered"><thead class="table-light" style="font-size:0.85em;"><tr>
                                                <th class="ps-4" style="width: 20%;"><small># Equipo</small></th>
                                                <th style="width: 25%;"><small>Marca - Serial</small></th>
                                                <th style="width: 15%;"></th><th style="width: 10%;"></th><th style="width: 15%;"></th>
                                                <th style="width: 15%;"><small>Estado</small></th>
                                                <th><small>Observaciones</small></th>
                                            </tr></thead><tbody>`;

                                if (item.items_devueltos && item.items_devueltos.length > 0) {
                                    item.items_devueltos.forEach(function(subItem, index){
                                        rowsHtml += `
                                        <tr class="table-group-item">
                                            <td class="ps-4"><small>└─ Equipo ${index + 1}</small></td>
                                            <td><small>${htmlspecialchars(subItem.marca + ' - ' + subItem.serial)}</small></td>
                                            <td></td> 
                                            <td></td>
                                            <td></td>
                                            <td><small>${htmlspecialchars(subItem.estado_devolucion_equipo || 'N/A')}</small></td>
                                            <td><small>${htmlspecialchars(subItem.observaciones || 'Sin observaciones')}</small></td>
                                        </tr>`;
                                    });
                                }
                                rowsHtml += `</tbody></table></div></td></tr>`;

                            } else if (item.tipo_historial === 'equipo_devuelto_individual') {
                                 hasIndividualReturns = true;
                                 rowsHtml = `<tr class="individual-return-row d-none">
                                    <td>Equipo (Individual)</td>
                                    <td>${htmlspecialchars(item.item_detalle)}</td>
                                    <td>${instructorFullName}</td>
                                    <td>N/A</td>
                                    <td>${fechaDevolucionPrincipal}</td>
                                    <td>${htmlspecialchars(item.estado_devolucion_equipo || 'N/A')}</td>
                                    <td>${htmlspecialchars(item.observaciones || 'Sin observaciones')}</td>
                                </tr>`;
                            } else if (item.tipo_historial === 'material_devuelto') {
                                hasIndividualReturns = true;
                                rowsHtml = `<tr class="individual-return-row d-none">
                                    <td>Material</td>
                                    <td>${htmlspecialchars(item.item_detalle)}</td>
                                    <td>${instructorFullName}</td>
                                    <td>${htmlspecialchars(item.cantidad_devuelta_en_transaccion)}</td>
                                    <td>${fechaDevolucionPrincipal}</td>
                                    <td>N/A</td>
                                    <td>${htmlspecialchars(item.observaciones || 'Sin observaciones')}</td>
                                </tr>`;
                            }
                            listBody.append(rowsHtml);
                        });
                    }
                    
                    if (!hasAnyReturns) { 
                        listBody.append('<tr><td colspan="7" class="text-center">No hay devoluciones registradas que coincidan con los filtros.</td></tr>');
                    }

                    const $toggleBtn = $('#toggleIndividualReturnsBtn');
                    if (hasIndividualReturns) {
                        $toggleBtn.show();
                        if ($('#returnList .individual-return-row').first().hasClass('d-none')) {
                            $toggleBtn.html('<i class="fas fa-eye"></i> Mostrar Devoluciones Individuales');
                        } else {
                             $toggleBtn.html('<i class="fas fa-eye-slash"></i> Ocultar Devoluciones Individuales');
                        }
                    } else {
                        $toggleBtn.hide();
                    }

                },
                error: function(xhr) {
                    showNotification("Error al cargar historial: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), "danger");
                    $('#returnList').empty().append('<tr><td colspan="7" class="text-center">Error al cargar historial.</td></tr>');
                    console.error("Error en fetchAndDisplayReturnHistory:", xhr.responseText);
                    $('#toggleIndividualReturnsBtn').hide();
                }
            });
        }


        function updateAvailableEquipmentDropdowns() {
             $.ajax({
                url: API_URL + '?action=get_available_equipment', type: 'GET', dataType: 'json',
                success: function(response) {
                    globalAllAvailableEquipment = response.success ? response.equipment : [];
                    const mainDropdown = $('#id_equipos'); 
                    const editDropdown = $('#edit_id_equipo'); 
                    
                    const currentMainValues = mainDropdown.val();
                    const currentEditOption = editDropdown.find('option[value=""]'); 
                    const currentEditPlaceholderText = currentEditOption.length ? currentEditOption.text() : 'Mantener equipo actual';


                    mainDropdown.empty(); 
                    editDropdown.empty().append(`<option value="">${currentEditPlaceholderText}</option>`);


                    if (response.success && response.equipment && response.equipment.length > 0) {
                        response.equipment.forEach(function(eq) {
                            const optionHtml = `<option value="${htmlspecialchars(eq.id_equipo)}">${htmlspecialchars(eq.marca + ' - ' + eq.serial)}</option>`;
                            mainDropdown.append(optionHtml);
                             if ($('#edit_current_item_id').val() !== String(eq.id_equipo)) { 
                                editDropdown.append(optionHtml);
                             }
                        });
                    }
                    
                    if(mainDropdown.prop('multiple') && currentMainValues && currentMainValues.length > 0) {
                        mainDropdown.val(currentMainValues);
                    }
                    mainDropdown.trigger('change.select2');
                    
                     if ($('#editLoanModal').is(':visible')) { 
                        editDropdown.select2({ 
                            theme: "bootstrap-5",
                            width: '100%',
                            placeholder: currentEditPlaceholderText,
                            dropdownParent: $('#editLoanModal')
                        });
                    }


                },
                error: function() { showNotification("Error al actualizar lista de equipos disponibles.", "danger"); }
            });
        }

        function updateAvailableMaterialsDropdown() {
            $.ajax({
                url: API_URL + '?action=get_available_materials', type: 'GET', dataType: 'json',
                success: function(response) {
                    const dropdown = $('#id_material');
                    const currentValue = dropdown.val();
                    dropdown.empty().append('<option value="">Seleccione un material</option>');
                    if (response.success && response.materials && response.materials.length > 0) {
                        response.materials.forEach(function(mat) {
                            dropdown.append(`<option value="${htmlspecialchars(mat.id_material)}" data-type="${htmlspecialchars(mat.tipo)}">${htmlspecialchars(mat.nombre + ' (Stock: ' + mat.stock + ' - Tipo: ' + mat.tipo + ')')}</option>`);
                        });
                    }
                    dropdown.val(currentValue).trigger('change.select2');
                },
                error: function() { showNotification("Error al actualizar lista de materiales disponibles.", "danger"); }
            });
        }

        function htmlspecialchars(str) {
            if (typeof str === 'undefined' || str === null) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(str).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>
</body>
</html>
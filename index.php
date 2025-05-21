<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error, 3, "error_log.txt");
    die("❌ Error de conexión, intente más tarde.");
}

$id_usuario = $_SESSION['id_usuario'];
$sql_admin = "SELECT es_admin, nombres FROM almacenistas WHERE id_almacenista = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $id_usuario);
$stmt_admin->execute();
$resultado_admin = $stmt_admin->get_result();
$usuario = $resultado_admin->fetch_assoc();

$es_admin = ($usuario && $usuario['es_admin'] == 1);
$_SESSION['es_admin'] = $es_admin;
$_SESSION['nombre'] = $usuario ? $usuario['nombres'] : 'Usuario Desconocido';

$stmt_admin->close();

// --- CÓDIGO PARA OBTENER DATOS DE INVENTARIO ---

$sql_total_material = "SELECT SUM(stock) AS total_material_stock FROM materiales";
$resultado_total_material = $conn->query($sql_total_material);
$total_material_stock = $resultado_total_material->fetch_assoc()['total_material_stock'] ?? 0;

$sql_total_equipo = "SELECT COUNT(*) AS total_equipo_disponible FROM equipos WHERE estado = 'disponible'";
$resultado_total_equipo = $conn->query($sql_total_equipo);
$total_equipo_disponible = $resultado_total_equipo->fetch_assoc()['total_equipo_disponible'] ?? 0;

$total_stock = $total_material_stock + $total_equipo_disponible;

$sql_count_materials = "SELECT COUNT(*) AS total_materials FROM materiales";
$result_count_materials = $conn->query($sql_count_materials);
$total_materials_count = $result_count_materials->fetch_assoc()['total_materials'] ?? 0;

$sql_count_equipment = "SELECT COUNT(*) AS total_equipment FROM equipos";
$result_count_equipment = $conn->query($sql_count_equipment);
$total_equipment_count = $result_count_equipment->fetch_assoc()['total_equipment'] ?? 0;


// --- CÓDIGO PARA NOTIFICACIONES ---
$notifications = []; // Array para almacenar todas las notificaciones

// 1. Notificación de Stock Bajo (Materiales)
$umbral_stock_bajo_material = 10;
$sql_stock_bajo_count = "SELECT COUNT(*) AS stock_bajo_count FROM materiales WHERE stock <= ?";
$stmt_stock_bajo_count = $conn->prepare($sql_stock_bajo_count);
$stmt_stock_bajo_count->bind_param("i", $umbral_stock_bajo_material);
$stmt_stock_bajo_count->execute();
$resultado_stock_bajo_count = $stmt_stock_bajo_count->get_result();
$stock_bajo_count = $resultado_stock_bajo_count->fetch_assoc()['stock_bajo_count'] ?? 0;
$stmt_stock_bajo_count->close();

if ($stock_bajo_count > 0) {
    $sql_nombres_stock_bajo = "SELECT nombre FROM materiales WHERE stock <= ?";
    $stmt_nombres_stock_bajo = $conn->prepare($sql_nombres_stock_bajo);
    $stmt_nombres_stock_bajo->bind_param("i", $umbral_stock_bajo_material);
    $stmt_nombres_stock_bajo->execute();
    $resultado_nombres_stock_bajo = $stmt_nombres_stock_bajo->get_result();

    $nombres_materiales = [];
    while($fila = $resultado_nombres_stock_bajo->fetch_assoc()) {
        $nombres_materiales[] = htmlspecialchars($fila['nombre']);
    }
    $stmt_nombres_stock_bajo->close();

    $notifications[] = [
        'type' => 'warning',
        'message' => "⚠️ **Stock bajo en Materiales:** " . implode(", ", $nombres_materiales) . ".",
        'link' => 'inventario.php?filter=low_stock_material'
    ];
}

// Se elimina la sección de Notificaciones de Préstamos Vencidos por solicitud.

// Convertir el array de notificaciones a JSON para JavaScript
$notifications_json = json_encode($notifications);

// --- FIN CÓDIGO DE NOTIFICACIONES ---

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal</title>
    <link rel="stylesheet" href="Css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Modo oscuro
            if (localStorage.getItem("modoOscuro") === "enabled") {
                document.body.classList.add("dark-mode");
            } else {
                document.body.classList.remove("dark-mode");
            }

            // --- LÓGICA DE NOTIFICACIONES ---
            const notificationBell = document.getElementById('notification-bell');
            const notificationCount = document.getElementById('notification-count');
            const notificationDropdown = document.getElementById('notification-dropdown');
            const notificationList = document.getElementById('notification-list');

            const notifications = JSON.parse('<?php echo $notifications_json; ?>');

            // Cargar notificaciones en el dropdown
            if (notifications.length > 0) {
                notificationCount.textContent = notifications.length;
                notificationCount.style.display = 'flex'; // Mostrar el contador

                notifications.forEach(notif => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('notification-item', notif.type); // Añadir clase de tipo (warning, danger, info)
                    if (notif.link) {
                        listItem.innerHTML = `<a href="${notif.link}">${notif.message}</a>`;
                    } else {
                        listItem.innerHTML = notif.message;
                    }
                    notificationList.appendChild(listItem);
                });
            } else {
                notificationCount.style.display = 'none'; // Ocultar el contador si no hay notificaciones
                const noNotifItem = document.createElement('li');
                noNotifItem.classList.add('notification-item');
                noNotifItem.textContent = 'No hay notificaciones pendientes.';
                notificationList.appendChild(noNotifItem);
            }

            // Mostrar/Ocultar el dropdown al hacer clic en la campanita
            notificationBell.addEventListener('click', function(event) {
                event.stopPropagation(); // Evita que el clic se propague al document y cierre el dropdown
                notificationDropdown.classList.toggle('show-dropdown');
            });

            // Cerrar el dropdown si se hace clic fuera de él
            document.addEventListener('click', function(event) {
                if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
                    notificationDropdown.classList.remove('show-dropdown');
                }
            });

            // --- ELIMINAR EL POPUP DE NOTIFICACIÓN ANTIGUO ---
            const oldNotificationPopup = document.getElementById('stock-bajo-notification');
            if (oldNotificationPopup) {
                oldNotificationPopup.remove();
            }
        });
    </script>
</head>
<body>

    <?php if ($_SESSION['es_admin']): ?>
        <div class="sesiones-container">
            <a href="admin_almacenistas.php" class="gestion-link">🛠️ Gestión Almacenistas</a>
        </div>
    <?php endif; ?>

    <div class="sidebar">
        <div class="user-info">
            🔑 Usuario logeado: <span id="username"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        </div>
        <h2>📌 Menú</h2>
        <ul>
            <li><a href="prestamos.php">📚 Préstamos y devoluciones</a></li>
            <li><a href="inventario.php">📦 Inventario</a></li>
            <li><a href="registro.html">👥 Registro de instructores</a></li>
            <li><a href="">📝 Novedades</a></li>
            <li><a href="mostrar_registros.php">🗒️ Listado de instructores</a></li>
            <li><a class="ajuste" href="ajustes.php">⚙️ Ajustes</a></li>
        </ul>
        <a href="logout.php" class="logout-btn">🚪 Cerrar sesión</a>
    </div>

    <div class="content">
        <div class="notification-area">
            <div id="notification-bell" class="notification-bell">
                <i class="fas fa-bell"></i>
                <span id="notification-count" class="notification-count">0</span>
            </div>
            <div id="notification-dropdown" class="notification-dropdown">
                <h4>Notificaciones</h4>
                <ul id="notification-list">
                    </ul>
            </div>
        </div>


        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h1>
        <p>Este software ayuda en la gestión de inventarios y préstamos.</p>

        <div class="cards-container">
            <div class="card total-stock-card">
                <div class="card-icon">
                     <i class="fas fa-boxes"></i> </div>
                <div class="card-info">
                    <div class="card-title">Total Ítems en Stock</div>
                    <div class="card-value"><?php echo $total_stock; ?></div>
                </div>
            </div>

            <div class="card low-stock-card">
                <div class="card-icon">
                    <i class="fas fa-exclamation-triangle"></i> </div>
                <div class="card-info">
                    <div class="card-title">Materiales con Stock Bajo</div>
                    <div class="card-value"><?php echo $stock_bajo_count; ?></div>
                </div>
                <a href="inventario.php?filter=low_stock_material" class="card-link">Ver materiales</a>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Total Equipos</div>
                    <div class="card-value"><?php echo $total_equipment_count; ?></div>
                </div>
                <a href="inventario.php?type=equipment" class="card-link">Ver equipos</a>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-dolly-flatbed"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Total Materiales</div>
                    <div class="card-value"><?php echo $total_materials_count; ?></div>
                </div>
                <a href="inventario.php?type=material" class="card-link">Ver materiales</a>
            </div>

            </div>
        </div>

    <footer class="pie">
        © 2025 Almacén SENA. Todos los derechos reservados.
    </footer>

</body>
</html>

<?php
if (isset($conn) && $conn) {
    $conn->close();
}
?>
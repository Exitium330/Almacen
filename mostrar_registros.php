<?php
include('requiere_login.php');


if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Conectar a la base de datos
include("conexion.php");

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Asegurar que la conexión use UTF-8
$conn->set_charset("utf8mb4");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros de Instructores</title>
    <link rel="stylesheet" href="Css/mostrar_registro.css?v=<?php echo time(); ?>"> 
     
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
        }

        // Resto de tu JavaScript sin cambios...
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.style.display = 'none', 500);
            }, 3000);
        });

        const searchInput = document.getElementById('searchInput');
        const editForm = document.querySelector('.edit-popup form');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchText = this.value.toLowerCase();
                const table = document.getElementById('instructorsTable');
                const rows = table.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    // En la vista de tarjetas, las filas no tienen 'td' directos de la misma manera
                    // Esta búsqueda seguirá funcionando en vista de tabla, pero podría no ser ideal para la de tarjetas
                    const nameCell = rows[i].querySelector('td[data-label="Nombre"]');
                    if (nameCell) {
                        const name = nameCell.textContent.toLowerCase();
                        rows[i].style.display = name.includes(searchText) ? '' : 'none';
                    }
                }
            });
        }
    });
    </script>
</head>
<body>
<?php
// PHP para actualizar y editar... (sin cambios)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id_instructor = $_POST['id_instructor'];
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    
    // ... tu lógica de validación y actualización ...
    $sql_update = "UPDATE instructores SET cedula = ?, nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id_instructor = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sssssi", $cedula, $nombre, $apellido, $correo, $telefono, $id_instructor);
    if ($stmt->execute()) {
        echo "<div class='notification success'>✅ Instructor actualizado correctamente.</div>";
    } else {
        echo "<div class='notification error'>❌ Error al actualizar el instructor: " . $conn->error . "</div>";
    }
    $stmt->close();
}
if (isset($_GET['editar'])) {
    $id_instructor = $_GET['editar'];
    $sql_edit = "SELECT id_instructor, cedula, nombre, apellido, correo, telefono FROM instructores WHERE id_instructor = ?";
    $stmt = $conn->prepare($sql_edit);
    $stmt->bind_param("i", $id_instructor);
    $stmt->execute();
    $resultado_edit = $stmt->get_result();
    if ($resultado_edit->num_rows > 0) {
        $instructor = $resultado_edit->fetch_assoc();
        ?>
        <div class="overlay"></div>
        <div class="edit-popup">
            <h2>✏️ Editar Instructor</h2>
            <form action="mostrar_registros.php" method="POST">
                <input type="hidden" name="id_instructor" value="<?php echo htmlspecialchars($instructor['id_instructor']); ?>">
                <label for="cedula">Cédula:</label>
                <input type="text" name="cedula" value="<?php echo htmlspecialchars($instructor['cedula']); ?>" required>
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($instructor['nombre']); ?>" required>
                <label for="apellido">Apellido:</label>
                <input type="text" name="apellido" value="<?php echo htmlspecialchars($instructor['apellido']); ?>" required>
                <label for="correo">Correo:</label>
                <input type="email" name="correo" value="<?php echo htmlspecialchars($instructor['correo']); ?>" required>
                <label for="telefono">Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($instructor['telefono'] ?? ''); ?>">
                <button type="submit" name="actualizar">💾 Guardar Cambios</button>
                <a href="mostrar_registros.php">❌ Cancelar</a>
            </form>
        </div>
        <?php
    }
    $stmt->close();
}

$sql = "SELECT id_instructor, cedula, nombre, apellido, correo, telefono FROM instructores";
$resultado = $conn->query($sql);

echo "<h2>📋 Lista de Instructores</h2>";

echo "<div class='search-container'>";
echo "<input type='text' id='searchInput' placeholder='Buscar por nombre, apellido, cédula...'>";
echo "<div class='action-buttons'>"; 
echo "<a href='registro.html' class='add-btn'>➕ Agregar Instructor</a>";
echo "<a href='index.php' class='back-btn'>Volver al inicio</a>";
echo "</div>";
echo "</div>";

if ($resultado->num_rows > 0) {
    echo "<div class='table-container'>";
    echo "<table id='instructorsTable'>";
    echo "<thead><tr>
            <th>ID</th>
            <th>Cédula</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Acciones</th>
          </tr></thead>";
    
    echo "<tbody>"; // tbody añadido para semántica correcta
    while ($fila = $resultado->fetch_assoc()) {
        // --- INICIO DE LA MODIFICACIÓN CRÍTICA ---
        // Se añade el atributo data-label a cada <td> para el diseño responsivo
        echo "<tr>
                <td data-label='ID'>" . htmlspecialchars($fila['id_instructor'] ?? 'N/A') . "</td>
                <td data-label='Cédula'>" . htmlspecialchars($fila['cedula']) . "</td>
                <td data-label='Nombre'>" . htmlspecialchars($fila['nombre']) . "</td>
                <td data-label='Apellido'>" . htmlspecialchars($fila['apellido']) . "</td>
                <td data-label='Correo'>" . htmlspecialchars($fila['correo']) . "</td>
                <td data-label='Teléfono'>" . (!empty($fila['telefono']) ? htmlspecialchars($fila['telefono']) : "No registrado") . "</td>
                <td data-label='Acciones' class='actions-cell'>
                    <a href='mostrar_registros.php?editar=" . htmlspecialchars($fila['id_instructor']) . "'><button>✏️ Editar</button></a>
                    <form action='eliminar.php' method='POST' style='display:inline;'>
                        <input type='hidden' name='id_instructor' value='" . htmlspecialchars($fila['id_instructor']) . "'>
                        <button type='submit' onclick='return confirm(\"¿Seguro que quieres eliminar este instructor?\")'>
                            🗑️ Eliminar
                        </button>
                    </form>
                </td>
              </tr>";
        // --- FIN DE LA MODIFICACIÓN CRÍTICA ---
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<p>⚠ No hay instructores registrados.</p>";
}

$conn->close();
?>
</body>
</html>
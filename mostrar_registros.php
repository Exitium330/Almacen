<?php
session_start();

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros</title>
    <link rel="stylesheet" href="Css/mostrar_registro.css?v=<?php echo time(); ?>">
     
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
            console.log("Modo oscuro activado");
        } else {
            document.body.classList.remove("dark-mode");
            console.log("Modo oscuro desactivado");
        }

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
                    const nameCell = rows[i].getElementsByTagName('td')[2];
                    if (nameCell) {
                        const name = nameCell.textContent.toLowerCase();
                        rows[i].style.display = name.includes(searchText) ? '' : 'none';
                    }
                }
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]*$/.test(this.value.trim()) && this.value.trim()) {
                    showNotification('La búsqueda debe contener solo letras.', 'error');
                    this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                }
            });
        }

        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                const inputs = editForm.querySelectorAll('input[required]');
                let isValid = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        showNotification(`El campo "${input.name}" es obligatorio.`, 'error');
                        e.preventDefault();
                        isValid = false;
                        return;
                    }
                    if ((input.name === 'nombre' || input.name === 'apellido') && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(input.value.trim())) {
                        showNotification(`El campo "${input.name}" debe contener solo letras y espacios (se permiten acentos y ñ).`, 'error');
                        e.preventDefault();
                        isValid = false;
                        return;
                    }
                    if (input.name === 'telefono' && input.value.trim() && !/^[0-9]+$/.test(input.value.trim())) {
                        showNotification('El teléfono debe contener solo números.', 'error');
                        e.preventDefault();
                        isValid = false;
                        return;
                    }
                    if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim())) {
                        showNotification('El correo no es válido.', 'error');
                        e.preventDefault();
                        isValid = false;
                        return;
                    }
                });
                if (isValid) {
                    console.log("Formulario válido, enviando...");
                }
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    });
    </script>
   
    <link rel="stylesheet" href="Css/mostrar_registro.css?v=<?php echo time(); ?>">
</head>
<body>
<?php
// Procesar la actualización si se envía el formulario de edición
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id_instructor = $_POST['id_instructor'];
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];

    // Validar que nombre y apellido contengan solo letras, espacios, acentos y ñ
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
        echo "<div class='notification error'>❌ Error: El nombre debe contener solo letras y espacios (se permiten acentos y ñ).</div>";
        exit;
    }
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
        echo "<div class='notification error'>❌ Error: El apellido debe contener solo letras y espacios (se permiten acentos y ñ).</div>";
        exit;
    }

    // Sanitizar datos manualmente
    $cedula = htmlspecialchars(trim($cedula), ENT_QUOTES, 'UTF-8');
    $nombre = htmlspecialchars(trim($nombre), ENT_QUOTES, 'UTF-8');
    $apellido = htmlspecialchars(trim($apellido), ENT_QUOTES, 'UTF-8');
    $telefono = htmlspecialchars(trim($telefono), ENT_QUOTES, 'UTF-8');

    // Validar y sanitizar el correo
    $correo = trim($correo);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='notification error'>❌ Error: Correo inválido.</div>";
        exit;
    }
    $correo = htmlspecialchars($correo, ENT_QUOTES, 'UTF-8');

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

// Mostrar el formulario de edición si se selecciona un instructor
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
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($instructor['telefono'] ?? ''); ?>" class="validate-numeric" pattern="[0-9]*">
                <button type="submit" name="actualizar">💾 Guardar Cambios</button>
                <a href="mostrar_registros.php">❌ Cancelar</a>
            </form>
        </div>
        <?php
    } else {
        echo "<div class='notification error'>❌ Instructor no encontrado.</div>";
    }
    $stmt->close();
}

// Mostrar la lista de instructores
$sql = "SELECT id_instructor, cedula, nombre, apellido, correo, telefono FROM instructores";
$resultado = $conn->query($sql);

echo "<h2>📋 Lista de Instructores</h2>";

echo "<div class='search-container'>";
echo "<input type='text' id='searchInput' class='validate-required' placeholder='Buscar por nombre...'>";
echo "<a href='index.php' class='back-btn'>⬅️ Volver al Menú</a>";
echo "</div>";

if ($resultado->num_rows > 0) {
    echo "<div class='table-container'>";
    echo "<table border='1' id='instructorsTable'>";
    echo "<tr>
            <th>ID</th>
            <th>Cédula</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Acciones</th>
          </tr>";

    while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($fila['id_instructor'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($fila['cedula']) . "</td>
                <td>" . htmlspecialchars($fila['nombre']) . "</td>
                <td>" . htmlspecialchars($fila['apellido']) . "</td>
                <td>" . htmlspecialchars($fila['correo']) . "</td>
                <td>" . (!empty($fila['telefono']) ? htmlspecialchars($fila['telefono']) : "No registrado") . "</td>
                <td>
                    <a href='mostrar_registros.php?editar=" . htmlspecialchars($fila['id_instructor']) . "'><button>✏️ Editar</button></a>
                    <form action='eliminar.php' method='POST' style='display:inline;'>
                        <input type='hidden' name='id_instructor' value='" . htmlspecialchars($fila['id_instructor']) . "'>
                        <button type='submit' onclick='return confirm(\"¿Seguro que quieres eliminar este instructor?\")'>
                            🗑 Eliminar
                        </button>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";
    echo "</div>";
} else {
    echo "⚠ No hay instructores registrados.";
}

$conn->close();
?>
</body>
</html>
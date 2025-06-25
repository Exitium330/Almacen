<?php
include('requiere_login.php');

// === Seguridad: Verificar autenticación y rol de administrador ===
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: index.php");
    exit();
}

// Incluir la conexión a la base de datos
include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión en admin_almacenistas.php: " . $conn->connect_error, 3, "error_log.txt");
    die("❌ Error de conexión a la base de datos, intente más tarde.");
}

// --- Lógica para obtener mensajes de estado ---
$mensaje_estado = "";
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    switch ($status) {
        case 'password_success':
            $mensaje_estado = "<div class='message success'>✅ Contraseña actualizada con éxito.</div>";
            break;
        case 'password_error_match':
            $mensaje_estado = "<div class='message error'>❌ La nueva contraseña y la confirmación no coinciden.</div>";
            break;
        case 'password_error_current':
            $mensaje_estado = "<div class='message error'>❌ La contraseña actual es incorrecta.</div>";
            break;
        case 'password_error_db':
            $mensaje_estado = "<div class='message error'>❌ Error al actualizar la contraseña en la base de datos.</div>";
            break;
        case 'added':
            $mensaje_estado = "<div class='message success'>✅ Almacenista agregado con éxito.</div>";
            break;
        case 'updated':
            $mensaje_estado = "<div class='message success'>✅ Almacenista actualizado con éxito.</div>";
            break;
        case 'deleted':
            $mensaje_estado = "<div class='message success'>✅ Almacenista eliminado con éxito.</div>";
            break;
        case 'error':
            $mensaje_estado = "<div class='message error'>❌ Ocurrió un error durante la operación.</div>";
            if(isset($_GET['msg']) && $_GET['msg'] == 'self_delete') {
                $mensaje_estado = "<div class='message error'>❌ No puedes eliminar tu propia cuenta de administrador.</div>";
            } else if (isset($_GET['msg']) && $_GET['msg'] == 'invalid_id') {
                $mensaje_estado = "<div class='message error'>❌ ID de usuario inválido.</div>";
            }
            break;
    }
}

// --- Lógica para Visualizar TODOS los Almacenistas ---
$almacenistas = [];
$sql_todos_almacenistas = "SELECT id_almacenista, nombres, apellidos, correo, estado, es_admin, hora_ingreso
                            FROM almacenistas
                            ORDER BY apellidos, nombres";
$resultado_todos = $conn->query($sql_todos_almacenistas);
if ($resultado_todos) {
    if ($resultado_todos->num_rows > 0) {
        while ($fila = $resultado_todos->fetch_assoc()) {
            $almacenistas[] = $fila;
        }
    }
} else {
    error_log("Error al obtener todos los almacenistas: " . $conn->error, 3, "error_log.txt");
    $mensaje_estado = "<div class='message error'>❌ Error al cargar la lista de almacenistas.</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Almacenistas</title>
    <link rel="stylesheet" href="Css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
        }
        .admin-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            position: relative;
        }
        .top-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        .admin-container h1, .admin-container h2 {
            color:rgb(37, 43, 49);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .admin-container h1 {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .crud-links a { margin-right: 10px; text-decoration: none; color:rgb(23, 109, 49); }
        .crud-links a:hover { text-decoration: underline; }
        .add-button { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .add-button:hover { background-color: #218838; }
        .password-form { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; max-width: 400px; }
        .password-form label { display: block; margin-bottom: 5px; font-weight: bold; }
        .password-form input[type="password"] { width: calc(100% - 16px); padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .password-form button { padding: 10px 15px; background-color:rgb(20, 145, 62); color: white; border: none; border-radius: 5px; cursor: pointer; }
        .password-form button:hover { background-color:rgb(11, 105, 58); }
        .back-link { padding: 8px 12px; background-color: #4caf50; color: #ffffff; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: 500; transition: background-color 0.3s; }
        .back-link:hover { background-color:hsl(123, 42.90%, 28.80%); }
    </style>
    
    <style>
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .admin-container { background: #1e1e1e; box-shadow: 0 0 15px rgba(0, 0, 0, 0.5); border: 1px solid #333; }
        body.dark-mode .top-controls { border-bottom-color: #333; }
        body.dark-mode h1, body.dark-mode h2 { color: #4CAF50; border-bottom-color: #333; }
        body.dark-mode .message.success { background-color: #1a3a24; color: #a7d7b8; border: 1px solid #2a5c3d; }
        body.dark-mode .message.error { background-color: #441c22; color: #f5c6cb; border: 1px solid #721c24; }
        body.dark-mode table, body.dark-mode th, body.dark-mode td { border-color: #444; }
        body.dark-mode th { background-color: #2c2c2c; }
        body.dark-mode tr:nth-child(even) { background-color: #232323; }
        body.dark-mode .crud-links a { color: #66b3ff; }
        body.dark-mode .password-form { background-color: #2a2a2a; border-color: #444; }
        body.dark-mode .password-form input[type="password"] { background-color: #333; border-color: #555; color: #e0e0e0; }
        body.dark-mode footer.pie { background-color: #1e1e1e; color: #aaa; border-top: 1px solid #333; }
    </style>
</head>
<body>

    <div class="admin-container">
        <div class="top-controls">
            <h1>Panel de Administración de Almacenistas</h1>
            <a href="index.php" class="back-link">← Volver al Menú</a>
        </div>
        
        <?php echo $mensaje_estado; ?>

        <h2>Cambiar mi Contraseña</h2>
        <form class="password-form" method="POST" action="cambiar_contrasena_admin_process.php">
            <div><label for="contrasena_actual">Contraseña Actual:</label><input type="password" id="contrasena_actual" name="contrasena_actual" required></div>
            <div><label for="nueva_contrasena">Nueva Contraseña:</label><input type="password" id="nueva_contrasena" name="nueva_contrasena" required></div>
            <div><label for="confirmar_contrasena">Confirmar Nueva Contraseña:</label><input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required></div>
            <button type="submit">Actualizar Contraseña</button>
        </form>

        <h2>Listado de Todos los Almacenistas</h2>
        <a href="crear_almacenista.php" class="add-button">➕ Agregar Nuevo Almacenista</a>

        <?php if (count($almacenistas) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Nombres</th><th>Apellidos</th><th>Usuario</th><th>Estado</th><th>Es Admin</th><th>Hora Ingreso</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($almacenistas as $alma): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alma['id_almacenista']); ?></td>
                            <td><?php echo htmlspecialchars($alma['nombres']); ?></td>
                            <td><?php echo htmlspecialchars($alma['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($alma['correo']); ?></td>
                            <td><?php echo htmlspecialchars($alma['estado']); ?></td>
                            <td><?php echo $alma['es_admin'] ? 'Sí' : 'No'; ?></td>
                            <td><?php echo $alma['hora_ingreso'] ? date('d/m/Y H:i', strtotime($alma['hora_ingreso'])) : 'N/A'; ?></td>
                            <td class="crud-links">
                                <a href="editar_almacenista.php?id=<?php echo htmlspecialchars($alma['id_almacenista']); ?>">Editar</a> |
                                <?php if ($alma['id_almacenista'] != $_SESSION['id_usuario']): ?>
                                    <a href="eliminar_almacenista.php?id=<?php echo htmlspecialchars($alma['id_almacenista']); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar a este almacenista (ID: <?php echo $alma['id_almacenista']; ?>)? Esta acción es irreversible.');">Eliminar</a>
                                <?php else: ?>
                                    <span style="color: #999;">Eliminar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay almacenistas registrados en la base de datos.</p>
        <?php endif; ?>
    </div>

    <footer class="pie">
        © 2025 Almacén SENA. Todos los derechos reservados.
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Al cargar la página, simplemente aplica la clase 'dark-mode' al cuerpo
        // si la configuración global está activada en localStorage.
        if (localStorage.getItem('modoOscuro') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    });
    </script>

</body>
</html>

<?php $conn->close(); ?>
<?php
require_once "auth.php"; 
?>


<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['es_admin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <title>Registrar usuario</title>
</head>
<body>
    <h2>Registrar nuevo usuario</h2>
    <form action="procesar_registro.php" method="POST">
        <label for="nombres">Nombres:</label>
        <input type="text" name="nombres" required>
        <label for="correo">Correo:</label>
        <input type="email" name="correo" required>
        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>
        <label for="es_admin">¿Es administrador?</label>
        <select name="es_admin">
            <option value="0">No</option>
            <option value="1">Sí</option>
        </select>
        <button type="submit">Registrar</button>
    </form>
</body>
</html>

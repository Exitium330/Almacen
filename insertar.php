<?php
require_once "auth.php"; 
require_once "conexion.php";

if (!isset($conn)) {
    die("❌ Error: No se pudo establecer conexión con la base de datos.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y limpiar los datos
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $correo = trim($_POST["correo"]);
    $telefono = empty(trim($_POST["telefono"])) ? NULL : trim($_POST["telefono"]);
    $cedula = trim($_POST["cedula"]);

    // Validaciones para evitar campos vacíos o solo espacios
    if (empty($nombre) || preg_match("/^\s+$/", $nombre)) {
        echo "<script>alert('Error: El nombre no puede estar vacío o contener solo espacios'); window.history.back();</script>";
        exit();
    }
    if (empty($apellido) || preg_match("/^\s+$/", $apellido)) {
        echo "<script>alert('Error: El apellido no puede estar vacío o contener solo espacios'); window.history.back();</script>";
        exit();
    }
    if (empty($correo) || preg_match("/^\s+$/", $correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Error: El correo no puede estar vacío, contener solo espacios o ser inválido'); window.history.back();</script>";
        exit();
    }
    // Teléfono es opcional, pero si se proporciona, no debe ser solo espacios
    if ($telefono !== NULL && preg_match("/^\s+$/", $telefono)) {
        echo "<script>alert('Error: El teléfono no puede contener solo espacios'); window.history.back();</script>";
        exit();
    }
    if (empty($cedula) || preg_match("/^\s+$/", $cedula)) {
        echo "<script>alert('Error: La cédula no puede estar vacía o contener solo espacios'); window.history.back();</script>";
        exit();
    }

    // Preparar la consulta
    $sql = "INSERT INTO instructores (nombre, apellido, correo, telefono, cedula) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("sssss", $nombre, $apellido, $correo, $telefono, $cedula);

    if ($stmt->execute()) {
        header("Location: mostrar_registros.php");
        exit();
    } else {
        echo "<script>alert('❌ Error al agregar el instructor: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método no permitido.";
}
?>
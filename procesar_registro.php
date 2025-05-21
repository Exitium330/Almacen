<?php
require_once "auth.php"; 
?>

<?php
session_start();
include 'conexion.php';

if ($_SESSION['es_admin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres = $_POST['nombres'];
    $correo = $_POST['correo'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $es_admin = $_POST['es_admin'];

    $sql = "INSERT INTO almacenistas (nombres, correo, password, es_admin) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombres, $correo, $password, $es_admin);

    if ($stmt->execute()) {
        echo "Usuario registrado con éxito.";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

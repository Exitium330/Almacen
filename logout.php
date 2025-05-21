<?php
session_start();
include('conexion.php'); 

if (isset($_SESSION['id_usuario'])) {
    $id_usuario = $_SESSION['id_usuario'];

    
    $conn->query("UPDATE almacenistas SET hora_salida = NOW() WHERE id_almacenista = $id_usuario");
}

session_destroy();
header("Location: login.php");
exit();
?>

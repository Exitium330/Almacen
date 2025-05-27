<?php
// require_login.php - ESTE ES EL CÓDIGO PARA VERIFICAR LA SESIÓN REAL Y PROTEGER LA PÁGINA

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
    $_SESSION['login_error_message'] = "Debes iniciar sesión para acceder a esta página.";
    header("Location: login.php");
    exit();
}
?>
<?php
session_start();


if (!isset($_SESSION['id_usuario'])) {
   
    header("Location: login.php");
    exit();
}


if (isset($requerir_admin) && $requerir_admin === true) {
    if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
        echo "⚠️ Acceso denegado. No tienes permisos para ver esta página.";
        exit();
    }
}
?>

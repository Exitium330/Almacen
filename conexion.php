<?php
// Este es el único código que necesitas. Funciona en local y en Railway.

// Comprueba si una variable de entorno de Railway existe para saber dónde estamos.
if (getenv('RAILWAY_ENVIRONMENT')) {
    // --- ESTAMOS EN LA NUBE (RAILWAY) ---
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $password = getenv('MYSQLPASSWORD');
    $db = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT');
} else {
    // --- ESTAMOS EN LOCAL (TU COMPUTADORA) ---
    $host = 'localhost'; 
    $user = 'root';        
    $password = '1027802491'; // Tu contraseña local que usabas antes
    $db = 'proyecto_almacen'; // El nombre de tu base de datos local
    $port = 3306; // El puerto por defecto de MySQL en local
}

// El resto del código es el mismo para ambos casos.
// Se conecta usando las variables que se hayan definido arriba.
$conn = new mysqli($host, $user, $password, $db, $port);

// Comprobar la conexión
if ($conn->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    die("❌ Error: No se pudo establecer conexión con el servicio.");
}

$conn->set_charset("utf8mb4");

?>
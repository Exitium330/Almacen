<?php

// Variable para detectar el entorno
$is_local_environment = false;

// Lista de hosts que consideramos como entorno de desarrollo local
// Puedes añadir más si usas otros, como 'proyecto.dev', etc.
$local_hosts = ['localhost', '127.0.0.1', 'almacen.test'];

// Verificamos si el host actual está en nuestra lista de hosts locales
if (isset($_SERVER['HTTP_HOST'])) {
    foreach ($local_hosts as $host) {
        // strpos() busca la primera ocurrencia de un string dentro de otro.
        // Si 'localhost' se encuentra en 'localhost:8080', la condición es verdadera.
        if (strpos($_SERVER['HTTP_HOST'], $host) !== false) {
            $is_local_environment = true;
            break; // Salimos del bucle en cuanto encontramos una coincidencia
        }
    }
}

if ($is_local_environment) {
    // --- CONFIGURACIÓN PARA LOCAL (LARAGON / XAMPP) ---
    $servername = "localhost";
    $username = "root";
    $password = "1027802491"; 
    $dbname = "proyecto_almacen";
    $port = 3306;

} else {
    // --- CONFIGURACIÓN PARA PRODUCCIÓN (INFINITYFREE) ---
    $servername = "sql202.infinityfree.com";
    $username = "if0_39273771";
    $password = "1027802491"; 
    $dbname = "if0_39273771_almacen";
    $port = 3306;
}

// El resto del código de conexión es el mismo para ambos entornos
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Comprobar la conexión
if ($conn->connect_error) {
    // Guardamos el error en un log para nosotros, pero no lo mostramos al usuario
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    // Mostramos un mensaje genérico al usuario
    die("❌ Error: No se pudo establecer conexión con el servicio.");
}

// Aseguramos que la conexión use el charset correcto
$conn->set_charset("utf8mb4");

?>
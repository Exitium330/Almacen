<?php
// PASO 1: Forzar la visualización de todos los errores posibles
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba de Diagnóstico de Railway</h1>";
echo "<p>Versión de PHP: " . phpversion() . "</p>";

// PASO 2: Probar la conexión a la base de datos
echo "<h2>Intentando conectar a la base de datos...</h2>";

$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$db = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT');

if (empty($host) || empty($user) || empty($db) || empty($port)) {
    die("<p style='color:red;'><strong>Error Crítico:</strong> Una o más variables de entorno de la base de datos (HOST, USER, DB, PORT) no están definidas. Asegúrate de que la base de datos y la aplicación estén en el mismo proyecto de Railway.</p>");
}

echo "<p><strong>Host:</strong> " . $host . "</p>";
echo "<p><strong>Usuario:</strong> " . $user . "</p>";
echo "<p><strong>Base de Datos:</strong> " . $db . "</p>";
echo "<p><strong>Puerto:</strong> " . $port . "</p>";

// Ocultar la contraseña por seguridad
if (getenv('MYSQLPASSWORD') !== false) {
    echo "<p><strong>Contraseña:</strong> (Definida)</p>";
} else {
    // La contraseña puede estar vacía, lo cual es válido en algunos casos de Railway
    echo "<p><strong>Contraseña:</strong> (No definida o vacía)</p>";
    $password = ''; // Usar una cadena vacía si no está definida
}

// Intentar la conexión
// La @ suprime el warning por defecto de PHP para que podamos mostrar nuestro propio mensaje de error más claro.
$conn = @mysqli_connect($host, $user, $password, $db, $port);

// Comprobar el resultado
if ($conn) {
    echo "<h2 style='color:green;'>¡Conexión exitosa a la base de datos!</h2>";
    mysqli_close($conn);
} else {
    echo "<h2 style='color:red;'>FALLÓ LA CONEXIÓN</h2>";
    // mysqli_connect_error() nos da el mensaje de error específico del intento de conexión
    echo "<p><strong>Error de MySQLi:</strong> " . mysqli_connect_error() . "</p>";
}

?>
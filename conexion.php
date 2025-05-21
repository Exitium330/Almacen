<?php
$servername = "localhost"; 
$username = "root";        
$password = "1027802491";            
$dbname = "proyecto_almacen"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("❌ Error: No se pudo establecer conexión con la base de datos. " . $conn->connect_error);
} else {
    echo "";
}
?>

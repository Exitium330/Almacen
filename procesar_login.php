<?php
session_start();
include 'conexion.php'; /*Conexión a la base de datos*/

if ($_SERVER["REQUEST_METHOD"] == "POST") { /*Verifica si el método de la solicitud es POST*/
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);/*Se obtiene el correo ingresado por el usuario y se sanitiza*/
    $password = $_POST['password'];/*Se obtiene la contraseña ingresada por el usuario*/

    $sql = "SELECT id_almacenista, nombres, password, es_admin FROM almacenistas WHERE correo=?";
    $stmt = $conn->prepare($sql);/*Se prepara la consulta para buscar un usuario en la base de datos, segun los datos ingresados*/ 
    $stmt->bind_param("s", $correo); /*Evitar inyección de código*/ 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) { /*Verifica si la contraseña ingresada coincide con la almacenada en la base de datos*/
            $_SESSION['id_usuario'] = $row['id_almacenista'];
            $_SESSION['nombre'] = $row['nombres'];/*Se almacena el nombre del usuario en la sesión*/
            $_SESSION['es_admin'] = $row['es_admin'];/*valida si es admin o almacenista*/
            $_SESSION['hora_ingreso'] = date("Y-m-d H:i:s");/*Se almacena la hora de ingreso en la sesión*/

            // Actualizar la hora de ingreso en la base de datos
            $stmt_update = $conn->prepare("UPDATE almacenistas SET hora_ingreso = NOW() WHERE id_almacenista = ?"); 
            $stmt_update->bind_param("i", $_SESSION['id_usuario']);
            $stmt_update->execute();
            $stmt_update->close();

            header("Location: index.php");
            exit();
        }
    }
    
    echo "❌ Usuario o contraseña incorrectos.";

    $stmt->close();
    $conn->close();
}
?>


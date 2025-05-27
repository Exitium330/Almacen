<?php
require_once "auth.php";
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y limpiar los datos
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password']);
    
    // --- Validaciones Mejoradas ---

    // Función auxiliar para validar que el string no esté vacío o contenga solo espacios/símbolos
    function isValidText($str) {
        return !empty($str) && preg_match('/[a-zA-Z0-9]/', $str);
    }

    if (!isValidText($nombres)) {
        echo "<script>alert('Error: Los nombres no pueden estar vacíos o contener solo espacios/símbolos.'); window.history.back();</script>";
        exit();
    }
    
    if (!isValidText($apellidos)) {
        echo "<script>alert('Error: Los apellidos no pueden estar vacíos o contener solo espacios/símbolos.'); window.history.back();</script>";
        exit();
    }
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Error: El correo electrónico no es válido.'); window.history.back();</script>";
        exit();
    }
    
    // Validación para el teléfono: no vacío y solo dígitos
    if (empty($telefono) || !preg_match("/^[0-9]+$/", $telefono)) {
        echo "<script>alert('Error: El teléfono solo puede contener números y no puede estar vacío.'); window.history.back();</script>";
        exit();
    }

    if (empty($password) || strlen($password) < 6) { // Ejemplo: mínimo 6 caracteres para la contraseña
        echo "<script>alert('Error: La contraseña no puede estar vacía y debe tener al menos 6 caracteres.'); window.history.back();</script>";
        exit();
    }
    // Puedes añadir más validaciones para la contraseña (caracteres especiales, mayúsculas, etc.)
    
    // --- Fin de Validaciones ---

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $hora_ingreso = date('Y-m-d H:i:s');
    $hora_salida = date('Y-m-d 17:00:00');
    $estado = 'activo';

    $sql = "INSERT INTO almacenistas (nombres, apellidos, correo, telefono, password, hora_ingreso, hora_salida, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nombres, $apellidos, $correo, $telefono, $password_hash, $hora_ingreso, $hora_salida, $estado);

    if ($stmt->execute()) {
        echo "<script>alert('Usuario agregado correctamente'); window.location.href='crear_usuario_form.php';</script>";
    } else {
        echo "<script>alert('Error al agregar el usuario'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método no permitido.";
}
?>
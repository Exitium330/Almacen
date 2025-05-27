<?php
require_once "auth.php"; 
require_once "conexion.php";


if (!isset($conn)) {
    die("❌ Error: No se pudo establecer conexión con la base de datos.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y limpiar los datos
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $correo = trim($_POST["correo"]);
    $telefono = empty(trim($_POST["telefono"])) ? NULL : trim($_POST["telefono"]); // Teléfono sigue siendo opcional
    $cedula = trim($_POST["cedula"]);

    // --- Validaciones Mejoradas ---

    // Función auxiliar para validar que el string no esté vacío y contenga letras, números y espacios (pero no solo espacios o símbolos)
    function isValidNameText($str) {
        // Verifica que no esté vacío después de trim
        if (empty($str)) {
            return false;
        }
        // Verifica que contenga al menos una letra o número (para evitar solo espacios o símbolos)
        if (!preg_match('/[a-zA-Z0-9]/', $str)) {
            return false;
        }
        // Verifica que solo contenga letras, números y espacios
        return preg_match('/^[a-zA-Z0-9\s]+$/', $str);
    }

    if (!isValidNameText($nombre)) {
        echo "<script>alert('Error: El nombre no puede estar vacío y solo debe contener letras, números y espacios (sin puntos u otros símbolos).'); window.history.back();</script>";
        exit();
    }
    
    if (!isValidNameText($apellido)) {
        echo "<script>alert('Error: El apellido no puede estar vacío y solo debe contener letras, números y espacios (sin puntos u otros símbolos).'); window.history.back();</script>";
        exit();
    }
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Error: El correo no puede estar vacío, contener solo espacios o ser inválido.'); window.history.back();</script>";
        exit();
    }
    
    // Teléfono es opcional, pero si se proporciona, debe ser solo números
    if ($telefono !== NULL && !preg_match("/^[0-9]+$/", $telefono)) {
        echo "<script>alert('Error: El teléfono solo puede contener números.'); window.history.back();</script>";
        exit();
    }

    // Validación para CÉDULA: solo números, entre 6 y 10 dígitos
    if (empty($cedula) || !preg_match("/^[0-9]{6,10}$/", $cedula)) {
        echo "<script>alert('Error: La cédula debe contener entre 6 y 10 números, sin espacios ni otros caracteres.'); window.history.back();</script>";
        exit();
    }

    // --- Fin de Validaciones ---

    // Preparar la consulta
    $sql = "INSERT INTO instructores (nombre, apellido, correo, telefono, cedula) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("sssss", $nombre, $apellido, $correo, $telefono, $cedula);

    if ($stmt->execute()) {
        header("Location: mostrar_registros.php");
        exit();
    } else {
        echo "<script>alert('❌ Error al agregar el instructor: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método no permitido.";
}
?>
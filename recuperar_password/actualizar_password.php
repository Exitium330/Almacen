<?php
include '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validaciones básicas de que todos los campos esperados llegaron
    if (!isset($_POST['token'], $_POST['password'], $_POST['password_confirm'])) {
        die("Datos del formulario incompletos.");
    }

    // El token ahora viene directamente del campo de texto 'token' del formulario
    $token = $_POST['token'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($token)) {
        die("El campo del código de recuperación no puede estar vacío.");
    }

    if ($password !== $password_confirm) {
        echo "<script>alert('❌ Las contraseñas no coinciden.'); window.history.back();</script>";
        exit();
    }
    
    // VALIDACIÓN DE REGLAS DE CONTRASEÑA EN EL SERVIDOR (MUY IMPORTANTE)
    $errors = [];
    if (strlen($password) < 8) { $errors[] = "La contraseña debe tener al menos 8 caracteres."; }
    if (!preg_match('/[A-Z]/', $password)) { $errors[] = "La contraseña debe contener al menos una mayúscula."; }
    if (!preg_match('/[0-9]/', $password)) { $errors[] = "La contraseña debe contener al menos un número."; }
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.]/', $password)) { $errors[] = "La contraseña debe contener al menos un caracter especial."; }
    if (preg_match('/[<>&\/]/', $password)) { $errors[] = "La contraseña no puede contener los caracteres < > & /"; }

    if (!empty($errors)) {
        echo "<script>alert('Error:\\n" . implode("\\n", $errors) . "'); window.history.back();</script>";
        exit();
    }
    
    // Volver a verificar el token antes de actualizar
    $token_hash = hash("sha256", $token);
    $stmt = $conn->prepare("SELECT id_almacenista FROM almacenistas WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        die("Código de recuperación inválido o expirado. Por favor, solicita uno nuevo.");
    }

    // El token es válido, hashear la nueva contraseña y actualizar
    $row = $result->fetch_assoc();
    $id_usuario = $row['id_almacenista'];
    $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Actualizar la contraseña y anular el token para que no se pueda reutilizar
    $stmt_update = $conn->prepare("UPDATE almacenistas SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id_almacenista = ?");
    $stmt_update->bind_param("si", $new_password_hash, $id_usuario);
    
    if ($stmt_update->execute()) {
        echo "<script>alert('✔️ Contraseña actualizada correctamente. Ahora puedes iniciar sesión.'); window.location.href='../login.php';</script>";
    } else {
        echo "<script>alert('❌ Hubo un error al actualizar tu contraseña.'); window.history.back();</script>";
    }

    $stmt_update->close();
    $stmt->close();
    $conn->close();
}
?>
<?php
session_start();

// === Seguridad: Verificar autenticación y rol de administrador ===
// Crucial para proteger este script
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: index.php"); // Redirigir si no es admin
    exit();
}

// Solo procesar si se recibe un ID por GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // ID no válido, redirigir con error
    header("Location: admin_almacenistas.php?status=error&msg=invalid_id");
    exit();
}

include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión en eliminar_almacenista.php: " . $conn->connect_error, 3, "error_log.txt");
    header("Location: admin_almacenistas.php?status=error"); // Error de DB
    exit();
}

$id_a_eliminar = $_GET['id'];
$id_admin_actual = $_SESSION['id_usuario'];

// === Seguridad: Evitar que un administrador se elimine a sí mismo ===
if ($id_a_eliminar == $id_admin_actual) {
    header("Location: admin_almacenistas.php?status=error&msg=self_delete");
    exit();
}

// --- Eliminar el registro de la base de datos ---
$sql_delete = "DELETE FROM almacenistas WHERE id_almacenista = ?";
$stmt_delete = $conn->prepare($sql_delete);

if ($stmt_delete) {
    $stmt_delete->bind_param("i", $id_a_eliminar);

    if ($stmt_delete->execute()) {
        // Éxito
        $stmt_delete->close();
        $conn->close();
        header("Location: admin_almacenistas.php?status=deleted");
        exit();
    } else {
        // Error al ejecutar la eliminación
        error_log("Error al eliminar almacenista ID " . $id_a_eliminar . ": " . $stmt_delete->error, 3, "error_log.txt");
        $stmt_delete->close();
        $conn->close();
        header("Location: admin_almacenistas.php?status=error"); // Error de DB
        exit();
    }
} else {
    // Error al preparar la consulta
     error_log("Error al preparar consulta DELETE en eliminar_almacenista.php: " . $conn->error, 3, "error_log.txt");
     $conn->close();
    header("Location: admin_almacenistas.php?status=error"); // Error interno
    exit();
}

// Nota: La confirmación al usuario se maneja en el enlace HTML con `onclick="return confirm(...)"`
?>
<?php
include('requiere_login.php');


// === Seguridad: Verificar autenticación y rol de administrador ===
// Crucial para proteger este script
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: index.php"); // Redirigir si no es admin
    exit();
}

// Solo procesar si es una petición POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: admin_almacenistas.php"); // Redirigir si no es POST
    exit();
}

// Incluir la conexión a la base de datos
include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión en cambiar_contrasena_admin_process.php: " . $conn->connect_error, 3, "error_log.txt");
    // Redirigir con un estado de error genérico y cerrar conexión
    if ($conn) $conn->close(); // Asegurar que si la conexión falló pero el objeto existe, intentar cerrarlo
    header("Location: admin_almacenistas.php?status=error&msg=db_connect_error"); // Añadir mensaje para diferenciar
    exit();
}

// Obtener datos del formulario - Usando isset() y operador ternario para compatibilidad con PHP < 7
$id_admin = $_SESSION['id_usuario'];
$contrasena_actual_form = isset($_POST['contrasena_actual']) ? $_POST['contrasena_actual'] : ''; // Nombre del input del formulario
$nueva_contrasena_form = isset($_POST['nueva_contrasena']) ? $_POST['nueva_contrasena'] : ''; // Nombre del input del formulario
$confirmar_contrasena_form = isset($_POST['confirmar_contrasena']) ? $_POST['confirmar_contrasena'] : ''; // Nombre del input del formulario


// --- Validaciones ---
if (empty($contrasena_actual_form) || empty($nueva_contrasena_form) || empty($confirmar_contrasena_form)) {
     $conn->close(); // Cerrar conexión antes de redirigir
     header("Location: admin_almacenistas.php?status=error&msg=empty_fields");
     exit();
}

if ($nueva_contrasena_form !== $confirmar_contrasena_form) {
    $conn->close(); // Cerrar conexión antes de redirigir
    header("Location: admin_almacenistas.php?status=password_error_match");
    exit();
}

// --- Verificar la contraseña actual (usando el campo 'password' de la DB) ---
$sql_get_pass = "SELECT password FROM almacenistas WHERE id_almacenista = ?"; // Usar 'password'
$stmt_get_pass = $conn->prepare($sql_get_pass);

if ($stmt_get_pass === false) {
     error_log("Error al preparar consulta SELECT password para admin ID " . $id_admin . ": " . $conn->error, 3, "error_log.txt");
     $conn->close(); // Cerrar conexión antes de redirigir
     header("Location: admin_almacenistas.php?status=error&msg=prepare_error"); // Error al preparar la consulta
     exit();
}

$stmt_get_pass->bind_param("i", $id_admin);

if ($stmt_get_pass->execute()) {
    $resultado_pass = $stmt_get_pass->get_result();
    $fila_pass = $resultado_pass->fetch_assoc();
    $stmt_get_pass->close(); // Cerrar statement después de usar

    // Verificar si el usuario existe y la contraseña actual es correcta (comparando con el hash de la DB)
    if (!$fila_pass || !password_verify($contrasena_actual_form, $fila_pass['password'])) { // Usar 'password' de $fila_pass
        $conn->close(); // Cerrar conexión antes de redirigir
        header("Location: admin_almacenistas.php?status=password_error_current");
        exit();
    }

} else {
     error_log("Error al ejecutar consulta SELECT password para admin ID " . $id_admin . ": " . $stmt_get_pass->error, 3, "error_log.txt");
     $stmt_get_pass->close(); // Asegurar cierre del statement
     $conn->close(); // Cerrar conexión antes de redirigir
     header("Location: admin_almacenistas.php?status=error&msg=execute_error"); // Error de DB al obtener pass
     exit();
}

// --- Actualizar la contraseña en la base de datos (usando el campo 'password' de la DB) ---
// Encriptar la nueva contraseña antes de guardarla
$hash_nueva_contrasena = password_hash($nueva_contrasena_form, PASSWORD_DEFAULT); // Encriptar la nueva del formulario

$sql_update_pass = "UPDATE almacenistas SET password = ? WHERE id_almacenista = ?"; // Usar 'password' en UPDATE
$stmt_update_pass = $conn->prepare($sql_update_pass);

if ($stmt_update_pass === false) {
    error_log("Error al preparar consulta UPDATE password para admin ID " . $id_admin . ": " . $conn->error, 3, "error_log.txt");
    $conn->close(); // Cerrar conexión antes de redirigir
    header("Location: admin_almacenistas.php?status=error&msg=prepare_update_error"); // Error al preparar la consulta
    exit();
}

$stmt_update_pass->bind_param("si", $hash_nueva_contrasena, $id_admin); // Enlazar hash y ID

if ($stmt_update_pass->execute()) {
    // Éxito
    $stmt_update_pass->close(); // Cerrar statement
    $conn->close(); // Cerrar conexión
    header("Location: admin_almacenistas.php?status=password_success");
    exit();
} else {
    // Error al ejecutar la actualización
    error_log("Error al ejecutar consulta UPDATE password para admin ID " . $id_admin . ": " . $stmt_update_pass->error, 3, "error_log.txt");
    $stmt_update_pass->close(); // Asegurar cierre del statement
    $conn->close(); // Cerrar conexión
    header("Location: admin_almacenistas.php?status=password_error_db");
    exit();
}

// La conexión $conn y los statements $stmt ya deberían estar cerrados por los bloques anteriores.
// Si por alguna razón se llega aquí (lo cual no debería pasar), PHP los limpiará al finalizar el script.

?>
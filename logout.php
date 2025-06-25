<?php
session_start();

// PREVENIR CACHÉ DEL NAVEGADOR EN LA PÁGINA DE LOGOUT TAMBIÉN
// Esto asegura que incluso la página de logout no se cachee,
// aunque la redirección inmediata suele ser suficiente.
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

include('conexion.php'); // Incluye tu archivo de conexión a la base de datos

// Es mejor usar un prepared statement para la actualización de la base de datos
// para prevenir inyección SQL, incluso si el ID de usuario viene de una sesión.
if (isset($_SESSION['id_usuario'])) {
    $id_usuario = (int)$_SESSION['id_usuario']; // Asegurarse de que sea un entero

    // Verificar que la conexión a la base de datos sea válida antes de usarla
    if ($conn && $conn->ping()) { // Comprueba si la conexión está activa
        $stmt = $conn->prepare("UPDATE almacenistas SET hora_salida = NOW() WHERE id_almacenista = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();
        } else {
            // Manejar error si la preparación de la consulta falla
            error_log("Error al preparar la consulta de actualización de hora_salida: " . $conn->error);
        }
    } else {
        error_log("Error: La conexión a la base de datos no es válida en logout.php");
    }
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también.
// Nota: ¡Esto destruirá la sesión, y no solo los datos de sesión!
// Es importante para asegurar que el ID de sesión en el navegador también se elimine.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión en el servidor
session_destroy();

// Cerrar la conexión a la base de datos después de usarla
if ($conn && $conn->ping()) {
    $conn->close();
}


// Redirigir al usuario a la página de inicio de sesión
// CAMBIO AQUÍ: Añadimos el parámetro para que login.php sepa que debe mostrar la alerta.
header("Location: login.php?logout=1"); 
exit();
?>
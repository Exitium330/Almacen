<?php
// Incluye tu script de seguridad/autenticación si es necesario
require_once "auth.php"; 

include("conexion.php");

// Verificamos que la solicitud sea por POST y que se haya enviado el ID del instructor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_instructor'])) {
    
    $id_instructor_a_reactivar = $_POST['id_instructor'];

    // Preparamos la consulta para ACTUALIZAR el estado del instructor a activo (activo = 1)
    $sql = "UPDATE instructores SET activo = 1 WHERE id_instructor = ?";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_instructor_a_reactivar);
        $stmt->execute();
        $stmt->close();
    } else {
        // En caso de un error en la preparación, es bueno registrarlo para depuración
        error_log("Error al preparar consulta UPDATE en reactivar.php: " . $conn->error);
    }

    $conn->close();

    // Una vez reactivado, redirigimos de vuelta a la lista de inactivos.
    // El instructor recién reactivado ya no aparecerá en esta lista.
    header("Location: mostrar_registros.php?vista=inactivos");
    exit();
    
} else {
    // Si alguien intenta acceder a este script directamente o sin un ID, lo redirigimos a la vista principal.
    header("Location: mostrar_registros.php");
    exit();
}
?>
<?php
require_once "auth.php"; 

include("conexion.php");

// Verificamos que la solicitud sea por POST y que se haya enviado el ID del instructor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_instructor"])) {
    
    $id_instructor_a_desactivar = $_POST["id_instructor"];

    // --- ¡AQUÍ ESTÁ LA MAGIA! ---
    // En lugar de DELETE, usamos un UPDATE para cambiar el estado a inactivo (activo = 0).
    $sql = "UPDATE instructores SET activo = 0 WHERE id_instructor = ?";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_instructor_a_desactivar);

        // Ejecutamos la actualización
        if ($stmt->execute()) {
            // La desactivación fue exitosa.
            // Opcional: Puedes crear un mensaje de éxito para mostrar en la página siguiente.
        } else {
            // Hubo un error, sería bueno registrarlo.
            error_log("Error al desactivar instructor ID " . $id_instructor_a_desactivar . ": " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Error en la preparación de la consulta
        error_log("Error al preparar consulta UPDATE en eliminar.php: " . $conn->error);
    }

    $conn->close();

    // Sin importar el resultado, redirigimos de vuelta a la lista de registros.
    header("Location: mostrar_registros.php");
    exit();
    
} else {
    // Si alguien intenta acceder a este script directamente o sin un ID, lo redirigimos.
    header("Location: mostrar_registros.php?status=error");
    exit();
}
?>
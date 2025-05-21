<?php
require_once "auth.php"; 
?>

<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_instructor"])) {
    $id = $_POST["id_instructor"];

   
    $sql = "DELETE FROM instructores WHERE id_instructor = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "✅ Instructor eliminado con éxito.";
    } else {
        echo "❌ Error al eliminar el instructor: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    
    header("Location: mostrar_registros.php");
    exit();
} else {
    echo "⚠ Error: ID de instructor no válido.";
}
?>

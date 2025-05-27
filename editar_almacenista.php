<?php
include('requiere_login.php');


// === Seguridad: Verificar autenticación y rol de administrador usando $_SESSION['id_usuario'] ===
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: index.php");
    exit();
}

include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión en editar_almacenista.php: " . $conn->connect_error, 3, "error_log.txt");
    die("❌ Error de conexión a la base de datos, intente más tarde.");
}

$mensaje_estado = ""; // Para mostrar mensajes de validación o error
$almacenista_data = null; // Variable para almacenar los datos del almacenista a editar

// --- Lógica para obtener los datos del almacenista (GET o si venimos de un POST fallido) ---
// Si la solicitud es GET O si es POST pero hubo un error de validación/DB y necesitamos volver a mostrar el formulario con datos originales
if ($_SERVER["REQUEST_METHOD"] != "POST" || (isset($mensaje_estado) && $mensaje_estado != "")) {

     $id_almacenista_a_editar = isset($_GET['id']) ? $_GET['id'] : null; // Usando isset() para compatibilidad PHP < 7, variable para el ID del usuario A editar

     // Si venimos de un POST con error y el ID ya está en POST (campo oculto), usar ese ID
     if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_almacenista'])) {
          $id_almacenista_a_editar = $_POST['id_almacenista'];
     }



     // Validar que el ID sea un número válido
     if (!$id_almacenista_a_editar || !is_numeric($id_almacenista_a_editar)) {
         $conn->close(); // Cerrar conexión antes de redirigir
         header("Location: admin_almacenistas.php?status=error&msg=invalid_id");
         exit();
     }

     // Consultar los datos actuales del almacenista (usando id_almacenista, correo, telefono, etc.)
     // === CORRECCIÓN: Incluir 'telefono' en la consulta SELECT ===
     $sql_get_almacenista = "SELECT id_almacenista, nombres, apellidos, correo, telefono, estado, es_admin FROM almacenistas WHERE id_almacenista = ?"; // AGREGADO telefono
     $stmt_get = $conn->prepare($sql_get_almacenista);

     if ($stmt_get === false) {
         // Error al preparar la consulta GET
         error_log("Error al preparar consulta GET en editar_almacenista.php: " . $conn->error, 3, "error_log.txt");
         $conn->close();
         die("❌ Error interno del sistema al cargar los datos para edición."); // Aquí es donde muere si falla el prepare
     }

     $stmt_get->bind_param("i", $id_almacenista_a_editar); // Usar el ID para editar

     // === DEBUG === Verificar ejecución de la consulta SELECT ===
     $execute_success = $stmt_get->execute();
    
     if ($execute_success) {
         $resultado_get = $stmt_get->get_result();

        

         if ($resultado_get->num_rows == 1) {
             $almacenista_data = $resultado_get->fetch_assoc();
            

         } else {
             // No se encontró el almacenista con ese ID
             $stmt_get->close();
             $conn->close();
             header("Location: admin_almacenistas.php?status=error&msg=not_found"); // Redirige
             exit();
         }
         $stmt_get->close(); // Cerrar statement después de usar
     } else {
          // Error al ejecutar la consulta GET
         error_log("Error al ejecutar consulta GET en editar_almacenista.php: " . $stmt_get->error, 3, "error_log.txt");
         $stmt_get->close();
         $conn->close();
         header("Location: admin_almacenistas.php?status=error&msg=execute_select_error"); // Redirige
         exit();
     }
}


// --- Lógica para procesar el formulario POST (actualización) ---
// Esta sección solo se ejecuta si la solicitud es POST y no hubo un error en la validación inicial GET/POST ID
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($mensaje_estado) || $mensaje_estado == "")) {

     // Obtener ID y datos del formulario POST
    $id_almacenista_post = isset($_POST['id_almacenista']) ? $_POST['id_almacenista'] : null; // El ID del campo oculto (de la base de datos)
    $nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
    $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    // === CORRECCIÓN: Usar 'correo' y 'telefono' del formulario ===
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : ''; // Usar 'correo' del formulario
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : ''; // USAR 'telefono' del formulario
    // === CORRECCIÓN: Usar 'password' del formulario para la nueva contraseña ===
    $password_nuevo = isset($_POST['password']) ? $_POST['password'] : ''; // Usar 'password' del formulario (nueva contraseña, puede estar vacío)

    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'activo';
    $es_admin = isset($_POST['es_admin']) ? 1 : 0;

     // Validar que el ID del POST sea un número
    if (!$id_almacenista_post || !is_numeric($id_almacenista_post)) {
         $mensaje_estado = "<div class='message error'>❌ Error en los datos del formulario (ID de usuario inválido en POST).</div>";
          // Si hay error POST, $almacenista_data *no* está cargado aquí.
          // La lógica de arriba ($mensaje_estado != "") se encargará de recargarlo.
    }
     // === Validaciones de entrada de los campos ===
     // === CORRECCIÓN: Validar que 'correo' no esté vacío ===
     elseif (empty($nombres) || empty($apellidos) || empty($correo)) { // Ajusta campos obligatorios
        $mensaje_estado = "<div class='message error'>❌ Los campos Nombres, Apellidos y Correo son obligatorios.</div>";
     }
      // === VALIDACIÓN ADICIONAL PARA EL CAMPO ESTADO (ENUM) EN POST ===
    elseif ($estado != 'activo' && $estado != 'inactivo') {
         $mensaje_estado = "<div class='message error'>❌ El valor seleccionado para el estado no es válido.</div>";
         // Si hay error, la lógica de arriba recargará los datos originales.
    }
    // =========================================================
    else {
        // Validar que el correo no exista ya para OTRO usuario (usando el ID del post)
        // === CORRECCIÓN: Consultar por 'correo', no por 'usuario' ===
        $sql_check_user = "SELECT id_almacenista FROM almacenistas WHERE correo = ? AND id_almacenista != ?"; // Usar 'id_almacenista', 'correo'
        $stmt_check_user = $conn->prepare($sql_check_user);
         if ($stmt_check_user === false) {
              error_log("Error al preparar consulta check user POST en editar_almacenista.php: " . $conn->error, 3, "error_log.txt");
             $mensaje_estado = "<div class='message error'>❌ Error interno del sistema al verificar el correo (POST).</div>";
         } else {
            $stmt_check_user->bind_param("si", $correo, $id_almacenista_post); // Usar $correo y el ID del POST
            $stmt_check_user->execute();
            $stmt_check_user->store_result();

            if ($stmt_check_user->num_rows > 0) {
                $mensaje_estado = "<div class='message error'>❌ El correo '" . htmlspecialchars($correo) . "' ya está siendo usado por otro usuario.</div>"; // Mostrar correo
                $stmt_check_user->close();
            } else {
                $stmt_check_user->close();

                // --- Construir la consulta UPDATE dinámicamente (con o sin password) ---
                // === CORRECCIÓN: Incluir 'correo' y 'telefono' en el SET ===
                $sql_update = "UPDATE almacenistas SET nombres = ?, apellidos = ?, correo = ?, telefono = ?, estado = ?, es_admin = ? "; // AGREGADO correo, telefono, estado, es_admin
                $types = "sssssi"; // Tipos: nombres, apellidos, correo, telefono, estado, es_admin
                $params = [$nombres, $apellidos, $correo, $telefono, $estado, $es_admin]; // Variables

                // === CORRECCIÓN: Si se proporciona nueva password, usar el campo 'password' ===
                if (!empty($password_nuevo)) { // Si se proporcionó una nueva contraseña
                    $hash_nuevo_password = password_hash($password_nuevo, PASSWORD_DEFAULT);
                    $sql_update .= ", password = ? "; // Usar 'password'
                    $types .= "s"; // Añadir tipo string
                    $params[] = $hash_nuevo_password; // Añadir el hash
                }

                // === CORRECCIÓN: WHERE clause usa 'id_almacenista' ===
                $sql_update .= "WHERE id_almacenista = ?"; // Usar 'id_almacenista'
                $types .= "i"; // Añadir tipo integer para ID
                $params[] = $id_almacenista_post; // Añadir el ID del POST

                $stmt_update = $conn->prepare($sql_update);

                 if ($stmt_update) {
                     // Enlazar los parámetros dinámicamente
                     // Usa ...$params para desempaquetar el array. Requiere PHP 5.6+ para array en bind_param, o PHP 7+ para ...$params
                     $stmt_update->bind_param($types, ...$params);

                     if ($stmt_update->execute()) {
                         // Éxito - redirigir
                         $stmt_update->close();
                         $conn->close();
                         header("Location: admin_almacenistas.php?status=updated");
                         exit();
                     } else {
                         // Error de base de datos al actualizar
                         error_log("Error al ejecutar UPDATE almacenista ID " . $id_almacenista_post . ": " . $stmt_update->error, 3, "error_log.txt");
                         $mensaje_estado = "<div class='message error'>❌ Error al actualizar el almacenista en la base de datos.</div>";
                         $stmt_update->close();
                         // Si hay error, la lógica de arriba se encargará de recargar los datos originales
                     }
                } else {
                     // Error en la preparación de la consulta UPDATE
                     error_log("Error al preparar consulta UPDATE en editar_almacenista.php: " . $conn->error, 3, "error_log.txt");
                     $mensaje_estado = "<div class='message error'>❌ Error interno del sistema al preparar la actualización.</div>";
                      // Si hay error, la lógica de arriba se encargará de recargar los datos originales
                }
            }
         }
     }
}

// Si la solicitud es GET o si el POST falló la validación/DB, mostrar el formulario
// Asegúrate de que $almacenista_data tiene los datos correctos para llenar el formulario.
// Si $almacenista_data sigue siendo null aquí, significa que hubo un error grave al cargar los datos iniciales en el bloque GET/POST error.
if ($almacenista_data === null) {
   
     $conn->close();
     die("❌ No se pudieron cargar los datos del almacenista para editar."); // <-- Este es el mensaje
}

// Si llegamos aquí, $almacenista_data tiene datos y mostramos el formulario.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Almacenista</title>
    <link rel="stylesheet" href="Css/style.css?v=<?php echo time(); ?>">
     <style>
         body { font-family: sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; color: #333;}
         .container { max-width: 600px; margin: 20px auto; padding: 20px; background: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); border-radius: 8px; }
         h1 { color: #0056b3; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
          .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
         .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
         label { display: block; margin-bottom: 5px; font-weight: bold; }
         input[type="text"], input[type="password"], select { width: calc(100% - 22px); padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
         input[type="checkbox"] { margin-right: 5px;}
         button { padding: 10px 20px; background-color: #ffc107; color: #212529; border: none; border-radius: 5px; cursor: pointer; font-size: 1em;}
         button:hover { background-color: #e0a800; }
         .back-link { display: block; margin-top: 20px; text-align: center; text-decoration: none; color: #007bff; }
          .back-link:hover { text-decoration: underline; }
     </style>
</head>
<body>

    <div class="container">
        <h1>Editar Almacenista (ID: <?php echo htmlspecialchars($almacenista_data['id_almacenista']); ?>)</h1>

         <?php echo $mensaje_estado; // Mostrar mensajes ?>

        <form method="POST" action="">
            <input type="hidden" name="id_almacenista" value="<?php echo htmlspecialchars($almacenista_data['id_almacenista']); ?>">

            <div>
                <label for="nombres">Nombres:</label>
                <input type="text" id="nombres" name="nombres" required value="<?php echo htmlspecialchars($almacenista_data['nombres']); ?>">
            </div>
            <div>
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($almacenista_data['apellidos']); ?>">
            </div>
            <div>
                <label for="correo">Correo (para Login):</label>
                <input type="text" id="correo" name="correo" required value="<?php echo htmlspecialchars($almacenista_data['correo']); ?>">
            </div>
             <div>
                 <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($almacenista_data['telefono'] ?? ''); ?>">
            </div>
            <div>
                <label for="password">Nueva Contraseña (dejar vacío para no cambiar):</label>
                <input type="password" id="password" name="password">
            </div>
             <div>
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                     <option value="activo" <?php echo ($almacenista_data['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($almacenista_data['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
            </div>
            <div>
                 <label>
                    <input type="checkbox" name="es_admin" value="1" <?php echo ($almacenista_data['es_admin'] == 1) ? 'checked' : ''; ?>> Es Administrador
                </label>
            </div>
            <button type="submit">Actualizar Almacenista</button>
        </form>

         <p><a href="admin_almacenistas.php" class="back-link">← Volver al Panel de Administración</a></p>

    </div>

</body>
</html>

<?php // La conexión debe estar cerrada por los bloques de lógica ?>
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

     $id_almacenista_a_editar = isset($_GET['id']) ? $_GET['id'] : null; 

     // Si venimos de un POST con error y el ID ya está en POST (campo oculto), usar ese ID
     if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_almacenista'])) {
          $id_almacenista_a_editar = $_POST['id_almacenista'];
     }


     // Validar que el ID sea un número válido
     if (!$id_almacenista_a_editar || !is_numeric($id_almacenista_a_editar)) {
         $conn->close(); 
         header("Location: admin_almacenistas.php?status=error&msg=invalid_id");
         exit();
     }

     $sql_get_almacenista = "SELECT id_almacenista, nombres, apellidos, correo, telefono, estado, es_admin FROM almacenistas WHERE id_almacenista = ?";
     $stmt_get = $conn->prepare($sql_get_almacenista);

     if ($stmt_get === false) {
         error_log("Error al preparar consulta GET en editar_almacenista.php: " . $conn->error, 3, "error_log.txt");
         $conn->close();
         die("❌ Error interno del sistema al cargar los datos para edición."); 
     }

     $stmt_get->bind_param("i", $id_almacenista_a_editar); 

     $execute_success = $stmt_get->execute();
    
     if ($execute_success) {
         $resultado_get = $stmt_get->get_result();
        
         if ($resultado_get->num_rows == 1) {
             $almacenista_data = $resultado_get->fetch_assoc();
         } else {
             $stmt_get->close();
             $conn->close();
             header("Location: admin_almacenistas.php?status=error&msg=not_found"); 
             exit();
         }
         $stmt_get->close(); 
     } else {
         error_log("Error al ejecutar consulta GET en editar_almacenista.php: " . $stmt_get->error, 3, "error_log.txt");
         $stmt_get->close();
         $conn->close();
         header("Location: admin_almacenistas.php?status=error&msg=execute_select_error"); 
         exit();
     }
}


// --- Lógica para procesar el formulario POST (actualización) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($mensaje_estado) || $mensaje_estado == "")) {

    $id_almacenista_post = isset($_POST['id_almacenista']) ? $_POST['id_almacenista'] : null; 
    $nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
    $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : ''; 
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : ''; 
    $password_nuevo = isset($_POST['password']) ? $_POST['password'] : ''; 

    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'activo';
    $es_admin_form = isset($_POST['es_admin']) ? 1 : 0; // Valor de 'es_admin' proveniente del formulario


    if (!$id_almacenista_post || !is_numeric($id_almacenista_post)) {
         $mensaje_estado = "<div class='message error'>❌ Error en los datos del formulario (ID de usuario inválido en POST).</div>";
    }
     elseif (empty($nombres) || empty($apellidos) || empty($correo)) { 
        $mensaje_estado = "<div class='message error'>❌ Los campos Nombres, Apellidos y Correo son obligatorios.</div>";
     }
    elseif ($estado != 'activo' && $estado != 'inactivo') {
         $mensaje_estado = "<div class='message error'>❌ El valor seleccionado para el estado no es válido.</div>";
    }
    else {
        // ***** INICIO DE LA MODIFICACIÓN *****
        // Verificar si el administrador está editando su propia cuenta
        $es_admin_final = $es_admin_form; // Por defecto, tomar el valor del formulario
        if ($id_almacenista_post == $_SESSION['id_usuario']) {
            $es_admin_final = 1; // Forzar a que siga siendo administrador
            if ($es_admin_form == 0) { 
                // Opcional: Añadir un mensaje para informar al usuario, aunque la acción se fuerza igualmente.
                // $mensaje_estado .= "<div class='message warning'>⚠️ No puedes quitarte el rol de administrador a ti mismo. El cambio fue ignorado.</div>";
            }
        }
        // ***** FIN DE LA MODIFICACIÓN *****

        $sql_check_user = "SELECT id_almacenista FROM almacenistas WHERE correo = ? AND id_almacenista != ?"; 
        $stmt_check_user = $conn->prepare($sql_check_user);
         if ($stmt_check_user === false) {
              error_log("Error al preparar consulta check user POST en editar_almacenista.php: " . $conn->error, 3, "error_log.txt");
             $mensaje_estado = "<div class='message error'>❌ Error interno del sistema al verificar el correo (POST).</div>";
         } else {
            $stmt_check_user->bind_param("si", $correo, $id_almacenista_post); 
            $stmt_check_user->execute();
            $stmt_check_user->store_result();

            if ($stmt_check_user->num_rows > 0) {
                $mensaje_estado = "<div class='message error'>❌ El correo '" . htmlspecialchars($correo) . "' ya está siendo usado por otro usuario.</div>"; 
                $stmt_check_user->close();
            } else {
                $stmt_check_user->close();

                $sql_update = "UPDATE almacenistas SET nombres = ?, apellidos = ?, correo = ?, telefono = ?, estado = ?, es_admin = ? "; 
                // Usar $es_admin_final que contiene la lógica de protección
                $types = "sssssi"; 
                $params = [$nombres, $apellidos, $correo, $telefono, $estado, $es_admin_final]; 

                if (!empty($password_nuevo)) { 
                    $hash_nuevo_password = password_hash($password_nuevo, PASSWORD_DEFAULT);
                    $sql_update .= ", password = ? "; 
                    $types .= "s"; 
                    $params[] = $hash_nuevo_password; 
                }

                $sql_update .= "WHERE id_almacenista = ?"; 
                $types .= "i"; 
                $params[] = $id_almacenista_post; 

                $stmt_update = $conn->prepare($sql_update);

                 if ($stmt_update) {
                     $stmt_update->bind_param($types, ...$params);

                     if ($stmt_update->execute()) {
                         $stmt_update->close();
                         $conn->close();
                         header("Location: admin_almacenistas.php?status=updated");
                         exit();
                     } else {
                         error_log("Error al ejecutar UPDATE almacenista ID " . $id_almacenista_post . ": " . $stmt_update->error, 3, "error_log.txt");
                         $mensaje_estado = "<div class='message error'>❌ Error al actualizar el almacenista en la base de datos.</div>";
                         $stmt_update->close();
                     }
                } else {
                     error_log("Error al preparar consulta UPDATE en editar_almacenista.php: " . $conn->error, 3, "error_log.txt");
                     $mensaje_estado = "<div class='message error'>❌ Error interno del sistema al preparar la actualización.</div>";
                }
            }
         }
     }
}

if ($almacenista_data === null && empty($mensaje_estado)) {
    // Esto podría pasar si hubo un error no manejado antes de cargar datos y no se generó mensaje de estado
    // O si se accede directamente al script sin ID y el bloque GET no pudo obtenerlo y redirigir.
    // Para asegurar que $almacenista_data no sea null si $mensaje_estado está vacío, 
    // se podría forzar la recarga aquí si es necesario, pero la lógica actual debería cubrirlo.
    // Si $mensaje_estado NO está vacío, significa que un error ya ocurrió y se va a mostrar,
    // y el bloque GET/POST error se encargó de (intentar) cargar $almacenista_data para repoblar el form.
}

// Si $almacenista_data es null DESPUÉS de que el bloque POST intentó y falló (y $mensaje_estado está lleno),
// necesitamos asegurarnos de que los datos originales se carguen para repoblar el formulario.
// La lógica al inicio del script (if ($_SERVER["REQUEST_METHOD"] != "POST" || ...)) ya debería manejar esto.
// Si $almacenista_data es null aquí y $mensaje_estado está lleno, indica que el bloque if/else anidado
// dentro del procesamiento POST no recargó $almacenista_data. Sin embargo, el flujo actual
// hace que el bloque superior que carga $almacenista_data se ejecute si $mensaje_estado no está vacío.

if ($almacenista_data === null) {
     // Este die() es un último recurso si todas las cargas anteriores fallaron.
     // Con la lógica actual, esto no debería ocurrir si el ID es válido.
     $conn->close();
     die("❌ No se pudieron cargar los datos del almacenista para editar. Verifique el ID y la conexión.");
}

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
         .message.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; } /* Para mensajes de advertencia */
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

        <form method="POST" action="editar_almacenista.php?id=<?php echo htmlspecialchars($almacenista_data['id_almacenista']); ?>">
            <input type="hidden" name="id_almacenista" value="<?php echo htmlspecialchars($almacenista_data['id_almacenista']); ?>">

            <div>
                <label for="nombres">Nombres:</label>
                <input type="text" id="nombres" name="nombres" required value="<?php echo htmlspecialchars(isset($_POST['nombres']) && $mensaje_estado ? $_POST['nombres'] : $almacenista_data['nombres']); ?>">
            </div>
            <div>
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars(isset($_POST['apellidos']) && $mensaje_estado ? $_POST['apellidos'] : $almacenista_data['apellidos']); ?>">
            </div>
            <div>
                <label for="correo">Correo (para Login):</label>
                <input type="text" id="correo" name="correo" required value="<?php echo htmlspecialchars(isset($_POST['correo']) && $mensaje_estado ? $_POST['correo'] : $almacenista_data['correo']); ?>">
            </div>
             <div>
                 <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars(isset($_POST['telefono']) && $mensaje_estado ? $_POST['telefono'] : ($almacenista_data['telefono'] ?? '')); ?>">
            </div>
            <div>
                <label for="password">Nueva Contraseña (dejar vacío para no cambiar):</label>
                <input type="password" id="password" name="password">
            </div>
             <div>
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                     <option value="activo" <?php 
                        $estado_seleccionado = isset($_POST['estado']) && $mensaje_estado ? $_POST['estado'] : $almacenista_data['estado'];
                        echo ($estado_seleccionado == 'activo') ? 'selected' : ''; 
                     ?>>Activo</option>
                    <option value="inactivo" <?php 
                        echo ($estado_seleccionado == 'inactivo') ? 'selected' : ''; 
                    ?>>Inactivo</option>
                    </select>
            </div>
            <div>
                 <label>
                    <?php
                        $es_admin_actual = isset($_POST['es_admin']) && $mensaje_estado ? (bool)$_POST['es_admin'] : (bool)$almacenista_data['es_admin'];
                        // Si es el admin actual editándose a sí mismo, el checkbox debería aparecer marcado y deshabilitado,
                        // o simplemente marcado y la lógica del servidor se encarga.
                        // Para una mejor UX, podrías deshabilitarlo si es el admin actual.
                        $disabled_checkbox = ($almacenista_data['id_almacenista'] == $_SESSION['id_usuario']) ? 'disabled' : '';
                        if ($almacenista_data['id_almacenista'] == $_SESSION['id_usuario']) {
                            $es_admin_actual = true; // Asegurar que esté marcado si es el admin mismo
                        }
                    ?>
                    <input type="checkbox" name="es_admin" value="1" <?php echo $es_admin_actual ? 'checked' : ''; ?> <?php echo $disabled_checkbox; ?>> Es Administrador
                    <?php if ($disabled_checkbox): ?>
                        <input type="hidden" name="es_admin" value="1" /> 
                        <small>(No puedes remover tu propio rol de administrador)</small>
                    <?php endif; ?>
                </label>
            </div>
            <button type="submit">Actualizar Almacenista</button>
        </form>

         <p><a href="admin_almacenistas.php" class="back-link">← Volver al Panel de Administración</a></p>

    </div>

</body>
</html>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>
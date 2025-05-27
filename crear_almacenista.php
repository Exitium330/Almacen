<?php
include('requiere_login.php');


// === Seguridad: Verificar autenticación y rol de administrador ===
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: index.php"); // Redirigir si no es admin
    exit();
}

include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión en crear_almacenista.php: " . $conn->connect_error, 3, "error_log.txt");
    die("❌ Error de conexión a la base de datos, intente más tarde.");
}

$mensaje_estado = ""; // Para mostrar mensajes de validación o error

// Inicializar variables para mantener valores en el formulario en caso de error de validación
// Usando isset() para compatibilidad PHP < 7
$nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
$apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
$correo = isset($_POST['correo']) ? trim($_POST['correo']) : ''; // Usar 'correo'
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : ''; // Usar 'telefono'
$password_form = isset($_POST['password']) ? $_POST['password'] : ''; // Usar 'password' del formulario (sin trim)
$estado = isset($_POST['estado']) ? $_POST['estado'] : 'activo'; // Usar 'estado', valor por defecto 'activo'
$es_admin = isset($_POST['es_admin']) ? 1 : 0; // Usar 'es_admin'

// --- Lógica para procesar el formulario POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // === Validaciones de entrada ===
    if (empty($nombres) || empty($apellidos) || empty($correo) || empty($password_form)) {
        $mensaje_estado = "<div class='message error'>❌ Los campos Nombres, Apellidos, Correo y Contraseña son obligatorios.</div>";
    }
    // === VALIDACIÓN PARA EL CAMPO ESTADO (ENUM) ===
    elseif ($estado != 'activo' && $estado != 'inactivo') {
         $mensaje_estado = "<div class='message error'>❌ El valor seleccionado para el estado no es válido.</div>";
         $estado = 'activo'; // Asegurar que $estado tenga un valor válido conocido si es inválido
    }
    // =========================================================
    else {
        // Validar que el correo (usado como usuario de login) no exista ya
        $sql_check_user = "SELECT id_almacenista FROM almacenistas WHERE correo = ?"; // Usar 'correo' en la consulta
        $stmt_check_user = $conn->prepare($sql_check_user);

        if ($stmt_check_user === false) {
            error_log("Error al preparar consulta check user en crear_almacenista.php: " . $conn->error, 3, "error_log.txt");
            $mensaje_estado = "<div class='message error'>❌ Error interno del sistema al verificar el correo.</div>";
        } else {
             $stmt_check_user->bind_param("s", $correo); // Usar $correo en bind_param
             $stmt_check_user->execute();
             $stmt_check_user->store_result();

             if ($stmt_check_user->num_rows > 0) {
                 $mensaje_estado = "<div class='message error'>❌ El correo '" . htmlspecialchars($correo) . "' ya está registrado.</div>"; // Mostrar correo
             } else {
                 // --- Insertar en la base de datos ---
                 // ¡Encriptar la contraseña!
                 $hash_password = password_hash($password_form, PASSWORD_DEFAULT); // Encriptar la variable $password_form

                 // === OBTENER EL TIMESTAMP ACTUAL PARA HORA_INGRESO ===
                 $current_time = date('Y-m-d H:i:s'); // Obtiene la fecha y hora actual en formato de base de datos

                 // === CORRECCIÓN: INCLUIR HORA_INGRESO EN LA CONSULTA INSERT ===
                 // Ajustar la consulta INSERT para incluir 'correo', 'telefono', 'password', HORA_INGRESO y 'es_admin'
                 // El orden debe coincidir con los valores y bind_param
                 $sql_insert = "INSERT INTO almacenistas (nombres, apellidos, correo, telefono, password, hora_ingreso, estado, es_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // AGREGADO hora_ingreso y un ?
                 $stmt_insert = $conn->prepare($sql_insert);

                 if ($stmt_insert) {
                      // === CORRECCIÓN: AJUSTAR BIND_PARAM PARA INCLUIR HORA_INGRESO ===
                      // Ajustar bind_param para que coincida con los tipos y orden de las variables (AHORA SON 8 VARIABLES)
                      // ssssss si -> 6 strings, 1 string ($hora_ingreso), 1 integer ($es_admin)
                      // s (nombres), s (apellidos), s (correo), s (telefono), s (password hash), s (hora_ingreso), s (estado), i (es_admin)
                      // El orden de columnas es nombres, apellidos, correo, telefono, password, **hora_ingreso**, estado, es_admin
                      // La cadena de tipos debe coincidir: 5 strings (primeros 5 vars), 1 string ($current_time), 1 string ($estado), 1 integer ($es_admin)
                      // sssss + s + s + i = sssssssi
                      $stmt_insert->bind_param("sssssssi", $nombres, $apellidos, $correo, $telefono, $hash_password, $current_time, $estado, $es_admin); // AGREGADO $current_time y ajustada la cadena de tipos

                      if ($stmt_insert->execute()) { // <--- Esta es la línea 73/77 donde fallaba
                          // Éxito - redirigir de vuelta al panel admin con mensaje
                          $stmt_insert->close();
                          $conn->close();
                          header("Location: admin_almacenistas.php?status=added");
                          exit();
                      } else {
                          // Error de base de datos al insertar
                          error_log("Error al insertar nuevo almacenista: " . $stmt_insert->error, 3, "error_log.txt");
                          $mensaje_estado = "<div class='message error'>❌ Error al guardar el nuevo almacenista en la base de datos.</div>";
                          $stmt_insert->close();
                      }
                 } else {
                      // Error en la preparación de la consulta INSERT
                      error_log("Error al preparar consulta INSERT en crear_almacenista.php: " . $conn->error, 3, "error_log.txt");
                      $mensaje_estado = "<div class='message error'>❌ Error interno del sistema al preparar la inserción.</div>";
                 }
             }
              $stmt_check_user->close(); // Asegurar que el statement check_user se cierra
        }
    }
}

// Si no es POST o hubo un error de validación/DB, se mostrará el formulario
// Las variables ($nombres, $apellidos, $correo, etc.) conservan los valores POST si hubo error.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Almacenista</title>
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
         button { padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em;}
         button:hover { background-color: #218838; }
         .back-link { display: block; margin-top: 20px; text-align: center; text-decoration: none; color: #007bff; }
          .back-link:hover { text-decoration: underline; }
     </style>
</head>
<body>

    <div class="container">
        <h1>Agregar Nuevo Almacenista</h1>

        <?php echo $mensaje_estado; // Mostrar mensajes ?>

        <form method="POST" action="">
            <div>
                <label for="nombres">Nombres:</label>
                <input type="text" id="nombres" name="nombres" required value="<?php echo htmlspecialchars($nombres); ?>"> </div>
            <div>
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($apellidos); ?>"> </div>
            <div>
                <label for="correo">Correo (para Login):</label> <input type="text" id="correo" name="correo" required value="<?php echo htmlspecialchars($correo); ?>"> </div>
             <div>
                <label for="telefono">Teléfono:</label> <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>"> </div>
            <div>
                <label for="password">Contraseña:</label> <input type="password" id="password" name="password" required>
            </div>
             <div>
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                    <option value="activo" <?php echo ($estado == 'activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($estado == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
            </div>
            <div>
                 <label>
                    <input type="checkbox" name="es_admin" value="1" <?php echo ($es_admin == 1) ? 'checked' : ''; ?>> Es Administrador </label>
            </div>
            <button type="submit">Guardar Almacenista</button>
        </form>

         <p><a href="admin_almacenistas.php" class="back-link">← Volver al Panel de Administración</a></p>

    </div>

</body>
</html>

<?php $conn->close(); ?>
<?php
// Usar los namespaces de PHPMailer para poder usar sus clases
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer desde el directorio padre
require '../vendor/autoload.php';

// Incluir la conexión a la base de datos desde el directorio padre
include '../conexion.php';

// El script solo se ejecuta si se envían datos por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);

    // 1. Verificar si el correo del usuario existe en la base de datos
    $stmt = $conn->prepare("SELECT id_almacenista FROM almacenistas WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si se encontró el usuario, procedemos
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_usuario = $row['id_almacenista'];

        // 2. Generar un token seguro y su fecha de expiración
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // 3. Guardar el token hasheado en la base de datos para ese usuario
        $stmt_update = $conn->prepare("UPDATE almacenistas SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id_almacenista = ?");
        $stmt_update->bind_param("ssi", $token_hash, $expires_at, $id_usuario);
        $stmt_update->execute();

        // 4. Generar el enlace de recuperación (ahora sin el token)
        $host = $_SERVER['HTTP_HOST'];
        $projectBasePath = dirname(dirname($_SERVER['PHP_SELF']));
        $projectBasePath = str_replace('\\', '/', $projectBasePath); // Asegura barras correctas
        $reset_link = "http://{$host}" . rtrim($projectBasePath, '/') . "/recuperar_password/reset_password.php";

        // 5. Enviar el correo real usando Brevo
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP de Brevo
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = '8fd586001@smtp-brevo.com'; // usuario SMTP de Brevo
            $mail->Password   = '6cq0UgnJhIRxsYm8';         // Clave SMTP de Brevo
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

            // Remitente y destinatario
            $mail->setFrom('no-reply@mialmacensena.online', 'Gestión de Almacén');
            $mail->addAddress($correo);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Tu Código de Recuperación de Contraseña';

            // Nuevo cuerpo del correo con el código visible
            $mail->Body = "
                <div style='font-family: sans-serif; line-height: 1.6;'>
                    <h2>Recuperación de Contraseña</h2>
                    <p>Hola,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña. Por favor, usa el siguiente código de un solo uso para continuar.</p>
                    <p>Tu código de recuperación es:</p>
                    <p style='font-size: 24px; font-weight: bold; letter-spacing: 2px; background-color: #f2f2f2; padding: 15px; text-align: center; border-radius: 5px;'>
                        {$token}
                    </p>
                    <p>Ve a la página de restablecimiento haciendo clic en el botón de abajo y pega este código en el campo correspondiente. El código expirará en 1 hora.</p>
                    <p style='margin: 25px 0;'>
                        <a href='{$reset_link}' brevo-disable-tracking='true' style='background-color:rgb(12, 99, 41); color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>Ir a la Página de Restablecimiento</a>
                    </p>
                    <p>Si no solicitaste esto, puedes ignorar este correo de forma segura.</p>
                </div>
            ";
            
            $mail->AltBody = "Usa el siguiente código para restablecer tu contraseña: {$token}. Ve a este enlace: {$reset_link}";

            $mail->send();
            
            echo "<script>alert('✔️ Se ha enviado un código de recuperación a tu correo.'); window.location.href='../login.php';</script>";

        } catch (Exception $e) {
            echo "El mensaje no se pudo enviar. Error de Mailer: {$mail->ErrorInfo}";
        }
    } else {
        echo "<script>alert('✔️ Si existe una cuenta con ese correo, se ha iniciado el proceso de recuperación.'); window.location.href='../login.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../login.php");
    exit();
}
?>
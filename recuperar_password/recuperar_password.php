<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../Css/recuperar.css?v=<?php echo time(); ?>">
    
    <link rel="icon" href="../Img/icono_proyecto.png">
</head>
<body class="recovery-page-body">

    <h1 class="main-title">Gestión de Almacén</h1>
    
    <div class="recovery-container">
        <h2>Recuperar Contraseña</h2>
        <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
        
        <form action="solicitar_recuperacion.php" method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="correo" required placeholder="Ingresa tu correo electrónico">
            </div>
            <button type="submit">Enviar Enlace de Recuperación</button>
        </form>
        
        <a href="../login.php" class="back-to-login">Volver a Iniciar Sesión</a>
    </div>

<script>
    // Seleccionamos el formulario y el botón por sus nuevos IDs
    const recoveryForm = document.getElementById('recovery-form');
    const submitButton = document.getElementById('submit-btn');

    // Añadimos un "escuchador" para el evento de envío del formulario
    recoveryForm.addEventListener('submit', function() {
        // 1. Desactivamos el botón para evitar que se envíe varias veces
        submitButton.disabled = true;

        // 2. Cambiamos el contenido del botón para mostrar el estado de carga
        // Usamos innerHTML para poder incluir el icono de Font Awesome
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    });
</script>

</body>
</html>
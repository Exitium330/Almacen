<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Gestión de Almacén</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="Css/login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="split-container">
        <div class="branding-panel">
            <div class="branding-content">
                <i class="fas fa-warehouse logo-icon"></i>
                <h1>Gestión de Almacén</h1>
                <p>Control total sobre tu inventario. Eficiencia y precisión en cada movimiento.</p>
            </div>
        </div>

        <div class="login-panel">
            <div class="login-container">
                <h2>Bienvenido de Nuevo</h2>
                <form action="procesar_login.php" method="POST">
                    
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="correo" required placeholder="Ingresa tu correo">
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" required placeholder="Ingresa tu contraseña" id="login-password">
                        <i class="fas fa-eye toggle-password" data-target="login-password"></i>
                    </div>

                    <button type="submit">Ingresar</button>
                </form>
                <a href="recuperar_password/recuperar_password.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <script>
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;

            // Verificamos si hay un error de login
            if (params.has('error')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Autenticación',
                    text: 'El correo o la contraseña que ingresaste son incorrectos. Por favor, inténtalo de nuevo.',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Entendido',
                    heightAuto: false
                });
                // Limpiamos la URL para que no se repita al recargar
                window.history.replaceState({}, document.title, cleanUrl);
            }

            // Verificamos si se cerró sesión
            if (params.has('logout')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sesión Cerrada',
                    text: 'Has cerrado sesión exitosamente.',
                    timer: 2000,
                    showConfirmButton: false,
                    heightAuto: false
                });
                // Limpiamos la URL aquí también
                window.history.replaceState({}, document.title, cleanUrl);
            }
        });

        // Tu código existente para el ojo de la contraseña
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function (e) {
                const targetInput = document.getElementById(e.target.getAttribute('data-target'));
                if (targetInput) {
                    const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    targetInput.setAttribute('type', type);
                    e.target.classList.toggle('fa-eye');
                    e.target.classList.toggle('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>
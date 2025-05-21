<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Usuario</title>
    <link rel="stylesheet" href="Css/crear_usuario_form.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (localStorage.getItem("modoOscuro") === "enabled") {
                document.body.classList.add("dark-mode");
            }

            $('form').on('submit', function(e) {
                let isValid = true;
                $('input[required], select[required]').each(function() {
                    if (!$(this).val().trim()) {
                        alert('Por favor, complete todos los campos obligatorios.');
                        e.preventDefault();
                        isValid = false;
                        return false;
                    }
                    if ($(this).hasClass('validate-numeric') && !/^[0-9]+$/.test($(this).val().trim())) {
                        alert('El campo debe contener solo números.');
                        e.preventDefault();
                        isValid = false;
                        return false;
                    }
                    if ($(this).hasClass('validate-email') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($(this).val().trim())) {
                        alert('El correo no es válido.');
                        e.preventDefault();
                        isValid = false;
                        return false;
                    }
                });
                return isValid;
            });
        });
    </script>
</head>
<body>
    <h2>Crear Nuevo Usuario</h2>
    <form action="crear_usuario.php" method="POST">
        <div class="form-group">
            <label for="nombres">Nombres:</label>
            <input type="text" id="nombres" name="nombres" class="validate-required" required pattern="[a-zA-Z0-9]+" placeholder="Ingrese nombres">
        </div>
        <div class="form-group">
            <label for="apellidos">Apellidos:</label>
            <input type="text" id="apellidos" name="apellidos" class="validate-required" required pattern="[a-zA-Z0-9]+" placeholder="Ingrese apellidos">
        </div>
        <div class="form-group">
            <label for="correo">Correo:</label>
            <input type="email" id="correo" name="correo" class="validate-required validate-email" required placeholder="Ingrese correo">
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" class="validate-numeric" pattern="[0-9]+" placeholder="Ingrese teléfono (opcional)">
        </div>
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" class="validate-required" required placeholder="Ingrese contraseña">
        </div>
        <div class="form-group">
            <label for="es_admin">Rol:</label>
            <select id="es_admin" name="es_admin" class="validate-required" required>
                <option value="">Seleccione rol</option>
                <option value="0">Usuario</option>
                <option value="1">Administrador</option>
            </select>
        </div>
        <button type="submit" class="green-btn">Crear Usuario</button>
    </form>
    <a href="ajustes.php">Volver</a>
</body>
</html>
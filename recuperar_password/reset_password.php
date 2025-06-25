<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="icon" href="../Img/icono_proyecto.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #357018, #09ac2c); display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .main-title { font-size: 2.5rem; font-weight: 700; color: white; margin-bottom: 40px; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); }
        .form-container { background-color: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); width: 100%; max-width: 450px; text-align: center; box-sizing: border-box; animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .form-container h2 { color: #333; font-size: 26px; font-weight: 600; margin-top: 0; margin-bottom: 25px; }
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; transition: color 0.3s ease; }
        .input-group .toggle-password { left: auto; right: 15px; cursor: pointer; }
        .form-container input { width: 100%; padding: 12px 15px 12px 45px; border: 2px solid #e9e9e9; border-radius: 8px; font-size: 16px; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; box-sizing: border-box; }
        .form-container input:focus { outline: none; border-color: #09ac2c; box-shadow: 0 0 0 4px rgba(9, 172, 44, 0.1); }
        .input-group:focus-within i:not(.toggle-password) { color: #09ac2c; }
        .form-container button { width: 100%; padding: 14px; border: none; border-radius: 8px; background: linear-gradient(90deg, #357018, #09ac2c); color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; box-shadow: 0 4px 15px rgba(9, 172, 44, 0.3); }
        #password-requirements { list-style-type: none; padding: 0; margin: -10px 0 20px 0; text-align: left; font-size: 13px; display: none; }
        #password-requirements li { margin-bottom: 6px; color: #a0a0a0; transition: all 0.3s ease; }
        #password-requirements li.valid { color: #09ac2c; text-decoration: line-through; }
        #password-requirements li i { margin-right: 8px; width: 15px; }
        #password-requirements li.valid i::before { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
        #password-requirements li:not(.valid) i::before { content: '\f111'; font-family: 'Font Awesome 6 Free'; font-weight: 400; }
    </style>
</head>
<body>

    <h1 class="main-title">Gestión de Almacén</h1>
    <div class="form-container">
        <h2>Establecer Nueva Contraseña</h2>
        <form action="actualizar_password.php" method="POST" id="reset-form">
            
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="token" required placeholder="Pega aquí el código del correo">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" required placeholder="Ingresa tu nueva contraseña">
                <i class="fas fa-eye toggle-password" data-target="password"></i>
            </div>
            
            <ul id="password-requirements">
                <li id="length"><i></i>Al menos 8 caracteres</li>
                <li id="upper"><i></i>Al menos una mayúscula</li>
                <li id="number"><i></i>Al menos un número</li>
                <li id="special"><i></i>Al menos un caracter especial (ej: !@#$*)</li>
            </ul>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="Confirma tu nueva contraseña">
                <i class="fas fa-eye toggle-password" data-target="password_confirm"></i>
            </div>

            <button type="submit" id="submit-btn">Guardar Contraseña</button>
        </form>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const requirementsList = document.getElementById('password-requirements');
        const req = { length: document.getElementById('length'), upper: document.getElementById('upper'), number: document.getElementById('number'), special: document.getElementById('special') };
        const validators = {
            length: val => val.length >= 8,
            upper: val => /[A-Z]/.test(val),
            number: val => /[0-9]/.test(val),
            special: val => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.]/.test(val) && !/[<>&/]/.test(val)
        };
        passwordInput.addEventListener('focus', () => { requirementsList.style.display = 'block'; });
        passwordInput.addEventListener('keyup', () => {
            const password = passwordInput.value;
            for (const [key, validator] of Object.entries(validators)) {
                req[key].classList.toggle('valid', validator(password));
            }
        });
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                const targetInput = document.getElementById(e.target.getAttribute('data-target'));
                const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                targetInput.setAttribute('type', type);
                e.target.classList.toggle('fa-eye');
                e.target.classList.toggle('fa-eye-slash');
            });
        });
    </script>

</body>
</html>
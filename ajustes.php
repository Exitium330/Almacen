<?php
include('requiere_login.php');


if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['es_admin'])) {
    echo "⚠️ Error: No se detectó el rol de administrador en la sesión.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones</title>
    <link rel="stylesheet" href="Css/ajustes.css?v=<?php echo time(); ?>">

    <script>
document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;
    const toggleModo = document.getElementById("modoOscuroBtn");

    
    if (localStorage.getItem("modoOscuro") === "enabled") {
        body.classList.add("dark-mode");
        toggleModo.innerHTML = "☀️ Desactivar Modo Oscuro";
    } else {
        body.classList.remove("dark-mode");
        toggleModo.innerHTML = "🌙 Activar Modo Oscuro";
    }

    
    toggleModo.addEventListener("click", function () {
        body.classList.toggle("dark-mode");
        const modoActivado = body.classList.contains("dark-mode");

        
        if (modoActivado) {
            localStorage.setItem("modoOscuro", "enabled");
            toggleModo.innerHTML = "☀️ Desactivar Modo Oscuro";
        } else {
            localStorage.setItem("modoOscuro", "disabled");
            toggleModo.innerHTML = "🌙 Activar Modo Oscuro";
        }

        
        document.cookie = "modoOscuro=" + modoActivado + "; path=/";

        
        toggleModo.classList.add("animacion-boton");
        setTimeout(() => {
            toggleModo.classList.remove("animacion-boton");
        }, 300);
    });
});
</script>
</head>
<body>

    <div class="settings-container">
        <h1>Ajustes</h1>
        <div class="settings">
        <button id="modoOscuroBtn">🌙 Modo Oscuro</button> 
        </div>
    </div>
    <button class="add-btn" onclick="window.location.href='index.php'">Volver al Inicio</button>

    <script>
    
    document.addEventListener("DOMContentLoaded", function () {
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
        } else {
            document.body.classList.remove("dark-mode");
        }
    });
    </script>

</body>
</html>


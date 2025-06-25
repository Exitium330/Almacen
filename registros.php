<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Instructores</title>
    <link rel="stylesheet" href="Css/registro.css">

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (localStorage.getItem("modoOscuro") === "enabled") {
                document.body.classList.add("dark-mode");
            } else {
                document.body.classList.remove("dark-mode");
            }
        });
        </script>
        

</head>
<body>
    <h2>Registro de Instructor</h2>
    
    <form action="insertar.php" method="POST">
        
        <label for="nombre">Nombres:</label>
        <input type="text" id="nombre" name="nombre" placeholder="Inserte Nombres" required><br><br>

        <label for="apellido">Apellidos:</label>
        <input type="text" id="apellido" name="apellido" placeholder="Inserte Apellidos"><br><br>

        <label for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" placeholder="Inserte correo electrónico" required><br><br>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" placeholder="Inserte Teléfono" maxlength="15"><br><br>

        <label for="ambiente">Cédula:</label>
        <input type="text" id="cedula" name="cedula" placeholder="Inserte Cédula" required><br><br>

        <button type="submit" >Guardar</button>

    </form>

    

   
</body>
</html>

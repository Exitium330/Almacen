<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Img/icono_proyecto.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Préstamos</title>
    <link rel="stylesheet" href="Css/prestamos.css?v=<?php echo time(); ?>">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (localStorage.getItem("modoOscuro") === "enabled") {
                document.body.classList.add("dark-mode");
            } else {
                document.body.classList.remove("dark-mode");
            }
        });
        </script>
        
    <style>
        
        .prestamo-item {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrar Préstamo</h2>
        <input type="text" id="busqueda" placeholder="Buscar por cédula" onkeyup="filtrarPrestamos()">
        
        <label for="equipo">Equipo:</label>
        <select id="equipo">
            <option value="Portátil HP">Portátil HP</option>
            <option value="Portatil lenovo">Portátil Lenovo</option>
            <option value="Portatil DELL">Portátil Dell</option>
        </select>
        
        <label for="instructor">Instructor:</label>
        <input type="text" id="instructor" placeholder="Nombre del instructor">
        
        <label for="almacenista">Almacenista:</label>
        <input type="text" id="almacenista" placeholder="Nombre del almacenista">
        
        <label for="fechaPrestamo">Fecha de Préstamo:</label>
        <input type="date" id="fechaPrestamo">
        
        <label for="fechaDevolucion">Fecha de Devolución:</label>
        <input type="date" id="fechaDevolucion">
        
        <label for="estado">Estado:</label>
        <select id="estado">
            <option value="Pendiente">Pendiente</option>
            <option value="Devuelto">Devuelto</option>
        </select>

        <button onclick="registrarPrestamo()">Realizar Préstamo</button>
        
        <h3>Préstamos Activos</h3>
        <div id="prestamos"></div>
    </div>

    <script>
        let listaPrestamos = [];

        function registrarPrestamo() {
            let equipo = document.getElementById('equipo').value;
            let instructor = document.getElementById('instructor').value.trim();
            let almacenista = document.getElementById('almacenista').value.trim();
            let fechaPrestamo = document.getElementById('fechaPrestamo').value;
            let fechaDevolucion = document.getElementById('fechaDevolucion').value;
            let estado = document.getElementById('estado').value;

            if (instructor === "" || almacenista === "" || fechaPrestamo === "" || fechaDevolucion === "") {
                alert("Por favor, completa todos los campos.");
                return;
            }

            let prestamo = {
                equipo,
                instructor,
                almacenista,
                fechaPrestamo,
                fechaDevolucion,
                estado
            };

            listaPrestamos.push(prestamo);
            mostrarPrestamos(listaPrestamos);
            limpiarCampos();
        }

        function mostrarPrestamos(prestamos) {
            let contenedor = document.getElementById('prestamos');
            contenedor.innerHTML = "";

            if (prestamos.length === 0) {
                contenedor.innerHTML = "<p>No hay préstamos registrados.</p>";
                return;
            }

            prestamos.forEach((p, index) => {
                contenedor.innerHTML += `
                    <div class="prestamo-item">
                        <strong>Equipo:</strong> ${p.equipo}<br>
                        <strong>Instructor:</strong> ${p.instructor}<br>
                        <strong>Almacenista:</strong> ${p.almacenista}<br>
                        <strong>Fecha Préstamo:</strong> ${p.fechaPrestamo}<br>
                        <strong>Fecha Devolución:</strong> ${p.fechaDevolucion}<br>
                        <strong>Estado:</strong> ${p.estado}<br>
                    </div>
                `;
            });
        }

        function limpiarCampos() {
            document.getElementById('instructor').value = "";
            document.getElementById('almacenista').value = "";
            document.getElementById('fechaPrestamo').value = "";
            document.getElementById('fechaDevolucion').value = "";
            document.getElementById('estado').value = "Pendiente";
        }

        function filtrarPrestamos() {
            let filtro = document.getElementById('busqueda').value.toLowerCase();
            let prestamosFiltrados = listaPrestamos.filter(p => 
                p.instructor.toLowerCase().includes(filtro)
            );
            mostrarPrestamos(prestamosFiltrados);
        }
    </script>
</body>
</html>

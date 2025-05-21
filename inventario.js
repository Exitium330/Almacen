console.log("inventario.js cargado correctamente");

/* Variables globales para rastrear el estado de visibilidad */
let equiposVisible = false;
let materialesVisible = false;



function applyStyles() {
    console.log("applyStyles ejecutado");
    // Forzar recarga de estilos evitando problemas de caché
    const link = document.querySelector('link[href="css/inventario.css"]');
    if (link) {
        const newLink = document.createElement('link');
        newLink.rel = 'stylesheet';
        newLink.href = `css/inventario.css?ts=${new Date().getTime()}`; // Añade timestamp
        link.parentNode.replaceChild(newLink, link);
    }
}

function showTab(tabName) {
    console.log("showTab ejecutado para: " + tabName);
    const tabs = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }

    const tabButtons = document.getElementsByClassName('tab');
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }

    document.getElementById(tabName).classList.add('active');
    const tabButton = document.querySelector(`.tab[onclick="showTab('${tabName}')"]`);
    if (tabButton) {
        tabButton.classList.add('active');
    }

    const equiposContainer = document.getElementById('equiposTableContainer');
    const materialesContainer = document.getElementById('materialesTableContainer');
    const historialContainer = document.getElementById('historialTableContainer');
    const toggleEquiposBtn = document.getElementById('toggleEquiposBtn');
    const toggleMaterialesBtn = document.getElementById('toggleMaterialesBtn');
    const toggleHistorialBtn = document.getElementById('toggleHistorialBtn');

    equiposContainer.innerHTML = '';
    materialesContainer.innerHTML = '';
    historialContainer.innerHTML = '';
    toggleEquiposBtn.textContent = 'Mostrar Equipos en Inventario';
    toggleMaterialesBtn.textContent = 'Mostrar Materiales en Inventario';
    toggleHistorialBtn.textContent = 'Mostrar Historial de Cambios';

    equiposVisible = false;
    materialesVisible = false;
}

function showNotification(message, type) {
    console.log("showNotification ejecutado: " + message);
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    container.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 500);
    }, 3000);
}

function toggleCustomMarca() {
    console.log("toggleCustomMarca ejecutado");
    const marca = document.getElementById('marca');
    const customMarca = document.getElementById('customMarca');
    customMarca.style.display = marca.value === 'Otra' ? 'block' : 'none';
}

function toggleUpdateCustomMarca() {
    console.log("toggleUpdateCustomMarca ejecutado");
    const updateMarca = document.getElementById('update_marca');
    const updateCustomMarca = document.getElementById('update_customMarca');
    updateCustomMarca.style.display = updateMarca.value === 'Otra' ? 'block' : 'none';
}

function openUpdateEquipoModal(id, marca, serial, estado) {
    console.log("openUpdateEquipoModal ejecutado para ID: " + id);
    document.getElementById('update_equipo_id').value = decodeURIComponent(id); // Decodificar
    document.getElementById('update_marca').value = decodeURIComponent(marca); // Decodificar
    document.getElementById('update_serial').value = decodeURIComponent(serial); // Decodificar
    document.getElementById('update_estado').value = decodeURIComponent(estado); // Decodificar
    document.getElementById('updateEquipoModal').style.display = 'block';
    toggleUpdateCustomMarca();
}

function openUpdateMaterialModal(id, nombre, tipo, stock) {
    console.log("openUpdateMaterialModal ejecutado para ID: " + id);
    document.getElementById('update_material_id').value = decodeURIComponent(id); // Decodificar
    document.getElementById('update_nombre_material').value = decodeURIComponent(nombre); // Decodificar
    document.getElementById('update_tipo').value = decodeURIComponent(tipo); // Decodificar
    document.getElementById('update_stock').value = decodeURIComponent(stock); // Decodificar
    document.getElementById('updateMaterialModal').style.display = 'block';
}

function closeModal(modalId) {
    console.log("closeModal ejecutado para: " + modalId);
    document.getElementById(modalId).style.display = 'none';
}

function sortTable(tableId, column) {
    console.log(`Ordenando tabla ${tableId} por columna ${column} (función no implementada)`);
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Función para actualizar el conteo de equipos en tiempo real
function updateEquiposCount() {
    console.log("updateEquiposCount ejecutado");
    const search = document.getElementById('searchEquipos')?.value || '';
    const fechaInicio = document.getElementById('fechaInicioEquipos')?.value || '';
    const fechaFin = document.getElementById('fechaFinEquipos')?.value || '';

    fetch(`inventario.php?get_equipos_count=1&search=${encodeURIComponent(search)}&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const totalEquiposCount = document.getElementById('totalEquiposCount');
                if (totalEquiposCount) {
                    totalEquiposCount.innerHTML = `Total de equipos: <strong>${data.count}</strong>`;
                }
            } else {
                console.error('Error al obtener el conteo de equipos:', data.message);
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error al actualizar conteo:', error);
            showNotification('Error al actualizar conteo: ' + error, 'error');
        });
}

// Función auxiliar para manejar eventos de búsqueda y fechas
function attachSearchEvents(inputId, fechaInicioId, fechaFinId, loadFunction) {
    console.log("attachSearchEvents ejecutado para: " + inputId);
    const input = document.getElementById(inputId);
    const fechaInicio = document.getElementById(fechaInicioId);
    const fechaFin = document.getElementById(fechaFinId);

    if (input) {
        const debouncedSearch = debounce(() => loadFunction(1), 300);
        input.oninput = debouncedSearch; // Usar oninput para compatibilidad
        input.onkeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                debouncedSearch();
            }
        };
    }

    if (fechaInicio) {
        fechaInicio.onchange = () => loadFunction(1); // Usar onchange
        fechaInicio.onkeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadFunction(1);
            }
        };
    }

    if (fechaFin) {
        fechaFin.onchange = () => loadFunction(1); // Usar onchange
        fechaFin.onkeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadFunction(1);
            }
        };
    }
}

// Función para cargar los equipos desde el servidor
function loadEquipos(page = 1) {
    console.log("loadEquipos ejecutado para página: " + page);
    const container = document.getElementById('equiposTableContainer');
    const toggleBtn = document.getElementById('toggleEquiposBtn');

    if (!container || !toggleBtn) {
        console.error('No se encontraron los elementos #equiposTableContainer o #toggleEquiposBtn');
        showNotification('Error: No se encontraron los elementos necesarios en la página.', 'error');
        return;
    }

    const searchInput = document.getElementById('searchEquipos');
    const search = searchInput ? searchInput.value : '';
    const fechaInicio = document.getElementById('fechaInicioEquipos')?.value || '';
    const fechaFin = document.getElementById('fechaFinEquipos')?.value || '';

    const hasFocus = searchInput && document.activeElement === searchInput;
    const cursorPosition = hasFocus ? searchInput.selectionStart : null;

    const url = `inventario.php?obtener_equipos=1&pagina_equipos=${page}&search=${encodeURIComponent(search)}&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error en la solicitud: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="search-container">
                        <div>
                            <label for="searchEquipos">Buscar Equipos:</label>
                            <input type="text" id="searchEquipos" placeholder="Buscar por marca, serial o estado..." value="${search}">
                        </div>
                        <div>
                            <label for="fechaInicioEquipos">Desde:</label>
                            <input type="date" id="fechaInicioEquipos" value="${fechaInicio}">
                        </div>
                        <div>
                            <label for="fechaFinEquipos">Hasta:</label>
                            <input type="date" id="fechaFinEquipos" value="${fechaFin}">
                        </div>
                        <button class="export-btn" onclick="window.location.href='inventario.php?exportar_equipos_csv=1'">Exportar a CSV</button>
                    </div>
                    <p class="total-count" id="totalEquiposCount">Total de equipos: <strong>${data.total}</strong></p>
                    <table id="equiposTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable('equiposTable', 0)">Marca <span class="sort-icon"></span></th>
                                <th onclick="sortTable('equiposTable', 1)">Serial <span class="sort-icon"></span></th>
                                <th onclick="sortTable('equiposTable', 2)">Estado <span class="sort-icon"></span></th>
                                <th onclick="sortTable('equiposTable', 3)">Fecha Creación <span class="sort-icon"></span></th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                if (data.data.length === 0) {
                    html += `<tr><td colspan="5">No hay equipos registrados.</td></tr>`;
                } else {
                    data.data.forEach(equipo => {
                        html += `
                            <tr data-id="${encodeURIComponent(equipo.id_equipo)}" data-fecha-creacion="${encodeURIComponent(equipo.fecha_creacion)}">
                                <td class="marca">${equipo.marca}</td> <td class="serial">${equipo.serial}</td> <td class="estado">${equipo.estado}</td> <td class="fecha-creacion">${decodeURIComponent(equipo.fecha_creacion)}</td> <td class="action-buttons">
                                    <button class="edit-btn" onclick="openUpdateEquipoModal('${encodeURIComponent(equipo.id_equipo)}', '${encodeURIComponent(equipo.marca)}', '${encodeURIComponent(equipo.serial)}', '${encodeURIComponent(equipo.estado)}')">Editar</button>
                                    <button class="delete-btn" onclick="deleteEquipo('${encodeURIComponent(equipo.id_equipo)}')">Eliminar</button>
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `</tbody></table>`;

                const totalPaginas = Math.ceil(data.total / 10);
                if (totalPaginas > 1) {
                    html += `<div class="pagination">`;
                    for (let i = 1; i <= totalPaginas; i++) {
                        const active = i === page ? 'active' : '';
                        html += `<a href="#" class="${active}" onclick="loadEquipos(${i}); return false;">${i}</a>`;
                    }
                    html += `</div>`;
                }

                container.innerHTML = html;
                toggleBtn.textContent = 'Ocultar Equipos en Inventario';
                equiposVisible = true;

                const newSearchInput = document.getElementById('searchEquipos');
                if (hasFocus && newSearchInput) {
                    newSearchInput.focus();
                    if (cursorPosition !== null) {
                        newSearchInput.setSelectionRange(cursorPosition, cursorPosition);
                    }
                }

                // Agregar eventos a los campos de búsqueda y fechas
                attachSearchEvents('searchEquipos', 'fechaInicioEquipos', 'fechaFinEquipos', loadEquipos);
                applyStyles(); // Forzar la aplicación de estilos
            } else {
                showNotification(data.message, 'error');
                container.innerHTML = '';
                toggleBtn.textContent = 'Mostrar Equipos en Inventario';
                equiposVisible = false;
            }
        })
        .catch(error => {
            showNotification('Error al cargar equipos: ' + error, 'error');
            container.innerHTML = '';
            toggleBtn.textContent = 'Mostrar Equipos en Inventario';
            equiposVisible = false;
        });
}

function toggleEquipos() {
    console.log("toggleEquipos ejecutado");
    if (equiposVisible) {
        document.getElementById('equiposTableContainer').innerHTML = '';
        document.getElementById('toggleEquiposBtn').textContent = 'Mostrar Equipos en Inventario';
        equiposVisible = false;
    } else {
        loadEquipos();
    }
}

function loadMateriales(page = 1) {
    console.log("loadMateriales ejecutado para página: " + page);
    const container = document.getElementById('materialesTableContainer');
    const toggleBtn = document.getElementById('toggleMaterialesBtn');

    if (!container || !toggleBtn) {
        console.error('No se encontraron los elementos #materialesTableContainer o #toggleMaterialesBtn');
        showNotification('Error: No se encontraron los elementos necesarios en la página.', 'error');
        return;
    }

    const searchInput = document.getElementById('searchMateriales');
    const search = searchInput ? searchInput.value : '';
    const fechaInicio = document.getElementById('fechaInicioMateriales')?.value || '';
    const fechaFin = document.getElementById('fechaFinMateriales')?.value || '';

    const hasFocus = searchInput && document.activeElement === searchInput;
    const cursorPosition = hasFocus ? searchInput.selectionStart : null;

    const url = `inventario.php?obtener_materiales=1&pagina_materiales=${page}&search=${encodeURIComponent(search)}&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error en la solicitud: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="search-container">
                        <div>
                            <label for="searchMateriales">Buscar Materiales:</label>
                            <input type="text" id="searchMateriales" placeholder="Buscar por nombre, tipo o stock..." value="${search}">
                        </div>
                        <div>
                            <label for="fechaInicioMateriales">Desde:</label>
                            <input type="date" id="fechaInicioMateriales" value="${fechaInicio}">
                        </div>
                        <div>
                            <label for="fechaFinMateriales">Hasta:</label>
                            <input type="date" id="fechaFinMateriales" value="${fechaFin}">
                        </div>
                        <button class="export-btn" onclick="window.location.href='inventario.php?exportar_materiales_csv=1'">Exportar a CSV</button>
                    </div>
                    <table id="materialesTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable('materialesTable', 0)">Nombre <span class="sort-icon"></span></th>
                                <th onclick="sortTable('materialesTable', 1)">Tipo <span class="sort-icon"></span></th>
                                <th onclick="sortTable('materialesTable', 2)">Stock <span class="sort-icon"></span></th>
                                <th onclick="sortTable('materialesTable', 3)">Fecha Creación <span class="sort-icon"></span></th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                if (data.data.length === 0) {
                    html += `<tr><td colspan="5">No hay materiales registrados.</td></tr>`;
                } else {
                    data.data.forEach(material => {
                        html += `
                            <tr data-id="${encodeURIComponent(material.id_material)}" data-fecha-creacion="${encodeURIComponent(material.fecha_creacion)}">
                                <td class="nombre">${material.nombre}</td> <td class="tipo">${material.tipo}</td> <td class="stock">${material.stock}</td> <td class="fecha-creacion">${decodeURIComponent(material.fecha_creacion)}</td> <td class="action-buttons">
                                    <button class="edit-btn" onclick="openUpdateMaterialModal('${encodeURIComponent(material.id_material)}', '${encodeURIComponent(material.nombre)}', '${encodeURIComponent(material.tipo)}', '${encodeURIComponent(material.stock)}')">Editar</button>
                                    <button class="delete-btn" onclick="deleteMaterial('${encodeURIComponent(material.id_material)}')">Eliminar</button>
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `</tbody></table>`;

                const totalPaginas = Math.ceil(data.total / 10);
                if (totalPaginas > 1) {
                    html += `<div class="pagination">`;
                    for (let i = 1; i <= totalPaginas; i++) {
                        const active = i === page ? 'active' : '';
                        html += `<a href="#" class="${active}" onclick="loadMateriales(${i}); return false;">${i}</a>`;
                    }
                    html += `</div>`;
                }

                container.innerHTML = html;
                toggleBtn.textContent = 'Ocultar Materiales en Inventario';
                materialesVisible = true;

                const newSearchInput = document.getElementById('searchMateriales');
                if (hasFocus && newSearchInput) {
                    newSearchInput.focus();
                    if (cursorPosition !== null) {
                        newSearchInput.setSelectionRange(cursorPosition, cursorPosition);
                    }
                }

                // Agregar eventos a los campos de búsqueda y fechas
                attachSearchEvents('searchMateriales', 'fechaInicioMateriales', 'fechaFinMateriales', loadMateriales);
                applyStyles(); // Forzar la aplicación de estilos
            } else {
                showNotification(data.message, 'error');
                container.innerHTML = '';
                toggleBtn.textContent = 'Mostrar Materiales en Inventario';
                materialesVisible = false;
            }
        })
        .catch(error => {
            showNotification('Error al cargar materiales: ' + error, 'error');
            container.innerHTML = '';
            toggleBtn.textContent = 'Mostrar Materiales en Inventario';
            materialesVisible = false;
        });
}

function toggleMateriales() {
    console.log("toggleMateriales ejecutado");
    if (materialesVisible) {
        document.getElementById('materialesTableContainer').innerHTML = '';
        document.getElementById('toggleMaterialesBtn').textContent = 'Mostrar Materiales en Inventario';
        materialesVisible = false;
    } else {
        loadMateriales();
    }
}

function loadHistorial() {
    console.log("loadHistorial ejecutado");
    const container = document.getElementById('historialTableContainer');
    const toggleBtn = document.getElementById('toggleHistorialBtn');

    if (container.innerHTML.trim() !== '') {
        container.innerHTML = '';
        toggleBtn.textContent = 'Mostrar Historial de Cambios';
        return;
    }

    fetch('inventario.php?obtener_historial=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <table id="historialTable">
                        <thead>
                            <tr>
                                <th>Almacenista</th>
                                <th>Tabla</th>
                                <th>Acción</th>
                                <th>ID Registro</th>
                                <th>Fecha</th>
                                <th>Detalles</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                if (data.data.length === 0) {
                    html += `<tr><td colspan="7">No hay cambios registrados.</td></tr>`;
                } else {
                    data.data.forEach(row => {
                        let detallesFormateados = 'N/A';
                        if (row.detalles) {
                            try {
                                // Primero decodificar la cadena URL
                                const decodedDetails = decodeURIComponent(row.detalles);
                                // Luego intentar parsear como JSON
                                const jsonDetails = JSON.parse(decodedDetails);
                                // Formatear para mostrar de manera legible (ej: en lista)
                                detallesFormateados = '<ul>';
                                for (const key in jsonDetails) {
                                    // Asegurarse de que los valores se muestren correctamente
                                    detallesFormateados += `<li><strong>${key}:</strong> ${jsonDetails[key]}</li>`; // Eliminado encodeURIComponent
                                }
                                detallesFormateados += '</ul>';
                            } catch (e) {
                                // Si falla la decodificación o el parseo, mostrar la cadena original decodificada
                                try {
                                     detallesFormateados = decodeURIComponent(row.detalles);
                                } catch (eDecode) {
                                     detallesFormateados = row.detalles; // Fallback a la cadena original si la decodificación falla
                                }
                                console.error('Error parsing historial details:', e, 'Original encoded:', row.detalles);
                            }
                        }

                        html += `
                            <tr data-id="${encodeURIComponent(row.id_historial)}">
                                <td>${row.nombres ? row.nombres + ' ' + row.apellidos : 'N/A'}</td> <td>${row.tabla_afectada ? row.tabla_afectada : 'N/A'}</td> <td>${row.accion ? row.accion : 'N/A'}</td> <td>${row.id_registro ? row.id_registro : 'N/A'}</td> <td>${row.fecha_accion ? decodeURIComponent(row.fecha_accion) : 'N/A'}</td> <td>${detallesFormateados}</td> <td class="action-buttons">
                                    <button class="delete-btn" onclick="deleteHistorial('${encodeURIComponent(row.id_historial)}')">Eliminar</button>
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `</tbody></table>`;

                container.innerHTML = html;
                toggleBtn.textContent = 'Ocultar Historial de Cambios';
                applyStyles(); // Forzar la aplicación de estilos
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error al cargar historial: ' + error, 'error');
        });
}

function deleteHistorial(id) {
    console.log("deleteHistorial ejecutado para ID: " + id);
    if (!confirm('¿Estás seguro de que deseas eliminar este registro del historial?')) return;

    const formData = new FormData();
    formData.append('eliminar_historial', '1');
    formData.append('id_historial', id);

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al eliminar registro del historial: ' + error, 'error');
    });
}

function addEquipo() {
    console.log("addEquipo ejecutado");
    const form = document.getElementById('addEquipoForm');
    const formData = new FormData(form);
    formData.append('agregar_equipo_ajax', '1');

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            form.reset();
            document.getElementById('customMarca').style.display = 'none';
            updateEquiposCount();
            if (equiposVisible) {
                loadEquipos();
            }
            const historialContainer = document.getElementById('historialTableContainer');
            if (historialContainer.innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al agregar equipo: ' + error, 'error');
    });
}

function deleteEquipo(id) {
    console.log("deleteEquipo ejecutado para ID: " + id);
    if (!confirm('¿Estás seguro de que deseas eliminar este equipo?')) return;

    const formData = new FormData();
    formData.append('eliminar_equipo', '1');
    formData.append('id_equipo', id);

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateEquiposCount();
            if (equiposVisible) {
                loadEquipos();
            }
            const historialContainer = document.getElementById('historialTableContainer');
            if (historialContainer.innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al eliminar equipo: ' + error, 'error');
    });
}

function addMaterial() {
    console.log("addMaterial ejecutado");
    const form = document.getElementById('addMaterialForm');
    const formData = new FormData(form);
    formData.append('agregar_material_ajax', '1');

    const nombre = form.querySelector('#nombre_material').value.trim();
    const tipo = form.querySelector('#tipo').value;
    const stock = parseInt(form.querySelector('#stock').value);

    if (!nombre) {
        showNotification('Error: El nombre del material es obligatorio.', 'error');
        return;
    }
    if (!tipo || !['consumible', 'no consumible'].includes(tipo)) {
        showNotification('Error: Selecciona un tipo de material válido.', 'error');
        return;
    }
    if (isNaN(stock) || stock < 1) {
        showNotification('Error: El stock debe ser mayor o igual a 1.', 'error');
        return;
    }

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('La respuesta del servidor no es JSON válido: ' + text);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            form.reset();
            if (materialesVisible) {
                loadMateriales();
            }
            const historialContainer = document.getElementById('historialTableContainer');
            if (historialContainer.innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al agregar material: ' + error.message, 'error');
    });
}

function deleteMaterial(id) {
    console.log("deleteMaterial ejecutado para ID: " + id);
    if (!confirm('¿Estás seguro de que deseas eliminar este material?')) return;

    const formData = new FormData();
    formData.append('eliminar_material_ajax', '1');
    formData.append('id_material', id);

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            if (materialesVisible) {
                loadMateriales();
            }
            const historialContainer = document.getElementById('historialTableContainer');
            if (historialContainer.innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al eliminar material: ' + error, 'error');
    });
}

function updateEquipo() {
    console.log("updateEquipo ejecutado");
    const form = document.getElementById('updateEquipoForm');
    const formData = new FormData(form);
    formData.append('actualizar_equipo', '1');

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('updateEquipoModal');
            updateEquiposCount();
            if (equiposVisible) {
                loadEquipos();
            }
            const historialContainer = document.getElementById('historialTableContainer');
            if (historialContainer.innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al actualizar equipo: ' + error, 'error');
    });
}

function updateMaterial() {
    console.log("updateMaterial ejecutado");
    const form = document.getElementById('updateMaterialForm');
    const formData = new FormData(form);
    formData.append('actualizar_material_ajax', '1');

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('updateMaterialModal');
            if (materialesVisible) {
                loadMateriales();
            }
            const historialContainer = document.getElementById('historialTableContainer');
            if (historialContainer.innerHTML.trim() !== '') {
                loadHistorial();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error al actualizar material: ' + error, 'error');
    });
}

// Inicializa la pestaña de equipos por defecto
document.addEventListener('DOMContentLoaded', () => {
    showTab('equipos');
});
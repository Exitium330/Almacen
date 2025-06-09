// ==================================================================
// INICIO: Lógica de Activación de Modo Oscuro (¡LA PARTE QUE FALTABA!)
// ==================================================================
document.addEventListener('DOMContentLoaded', () => {
    // Aplica el modo oscuro si está guardado en localStorage
    if (localStorage.getItem('modoOscuro') === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    // Inicializa la pestaña de equipos por defecto al cargar la página
    showTab('equipos');
});
// ==================================================================
// FIN: Lógica de Activación de Modo Oscuro
// ==================================================================


/* Variables globales para rastrear el estado de visibilidad */
let equiposVisible = false;
let materialesVisible = false;

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

    const tabElement = document.getElementById(tabName);
    if (tabElement) {
        tabElement.classList.add('active');
    }

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

    if (equiposContainer) equiposContainer.innerHTML = '';
    if (materialesContainer) materialesContainer.innerHTML = '';
    if (historialContainer) historialContainer.innerHTML = '';
    
    if (toggleEquiposBtn) toggleEquiposBtn.textContent = 'Mostrar Equipos en Inventario';
    if (toggleMaterialesBtn) toggleMaterialesBtn.textContent = 'Mostrar Materiales en Inventario';
    if (toggleHistorialBtn) toggleHistorialBtn.textContent = 'Mostrar Historial de Cambios';

    equiposVisible = false;
    materialesVisible = false;
}

function showNotification(message, type) {
    console.log("showNotification ejecutado: " + message);
    const container = document.getElementById('notificationContainer');
    if (!container) return;

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
    const marca = document.getElementById('marca');
    const customMarca = document.getElementById('customMarca');
    if (marca && customMarca) {
        customMarca.style.display = marca.value === 'Otra' ? 'block' : 'none';
    }
}

function toggleUpdateCustomMarca() {
    const updateMarca = document.getElementById('update_marca');
    const updateCustomMarca = document.getElementById('update_customMarca');
    if (updateMarca && updateCustomMarca) {
        updateCustomMarca.style.display = updateMarca.value === 'Otra' ? 'block' : 'none';
    }
}

function openUpdateEquipoModal(id, marca, serial, estado) {
    document.getElementById('update_equipo_id').value = decodeURIComponent(id);
    document.getElementById('update_marca').value = decodeURIComponent(marca);
    document.getElementById('update_serial').value = decodeURIComponent(serial);
    document.getElementById('update_estado').value = decodeURIComponent(estado);
    document.getElementById('updateEquipoModal').style.display = 'block';
    toggleUpdateCustomMarca();
}

function openUpdateMaterialModal(id, nombre, tipo, stock) {
    document.getElementById('update_material_id').value = decodeURIComponent(id);
    document.getElementById('update_nombre_material').value = decodeURIComponent(nombre);
    document.getElementById('update_tipo').value = decodeURIComponent(tipo);
    document.getElementById('update_stock').value = decodeURIComponent(stock);
    document.getElementById('updateMaterialModal').style.display = 'block';
}

function closeModal(modalId) {
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

function attachSearchEvents(inputId, fechaInicioId, fechaFinId, loadFunction) {
    const input = document.getElementById(inputId);
    const fechaInicio = document.getElementById(fechaInicioId);
    const fechaFin = document.getElementById(fechaFinId);

    const debouncedSearch = debounce(() => loadFunction(1), 300);

    if (input) input.addEventListener('input', debouncedSearch);
    if (fechaInicio) fechaInicio.addEventListener('change', debouncedSearch);
    if (fechaFin) fechaFin.addEventListener('change', debouncedSearch);
}

function loadEquipos(page = 1) {
    const container = document.getElementById('equiposTableContainer');
    const toggleBtn = document.getElementById('toggleEquiposBtn');
    if (!container || !toggleBtn) return;

    const search = document.getElementById('searchEquipos')?.value || '';
    const fechaInicio = document.getElementById('fechaInicioEquipos')?.value || '';
    const fechaFin = document.getElementById('fechaFinEquipos')?.value || '';

    const url = `inventario.php?obtener_equipos=1&pagina_equipos=${page}&search=${encodeURIComponent(search)}&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="search-container">
                        <div><label for="searchEquipos">Buscar Equipos:</label><input type="text" id="searchEquipos" placeholder="Buscar por marca, serial..." value="${search}"></div>
                        <div><label for="fechaInicioEquipos">Desde:</label><input type="date" id="fechaInicioEquipos" value="${fechaInicio}"></div>
                        <div><label for="fechaFinEquipos">Hasta:</label><input type="date" id="fechaFinEquipos" value="${fechaFin}"></div>
                        <button class="export-btn" onclick="window.location.href='inventario.php?exportar_equipos_csv=1'">Exportar a CSV</button>
                    </div>
                    <p class="total-count">Total de equipos: <strong>${data.total}</strong></p>
                    <table><thead><tr><th>Marca</th><th>Serial</th><th>Estado</th><th>Fecha Creación</th><th>Acción</th></tr></thead><tbody>`;

                if (data.data.length === 0) {
                    html += `<tr><td colspan="5" style="text-align:center;">No se encontraron equipos.</td></tr>`;
                } else {
                    data.data.forEach(equipo => {
                        html += `<tr>
                                    <td>${equipo.marca}</td><td>${equipo.serial}</td><td>${equipo.estado}</td><td>${equipo.fecha_creacion}</td>
                                    <td class="action-buttons">
                                        <button class="edit-btn" onclick="openUpdateEquipoModal('${encodeURIComponent(equipo.id_equipo)}', '${encodeURIComponent(equipo.marca)}', '${encodeURIComponent(equipo.serial)}', '${encodeURIComponent(equipo.estado)}')">Editar</button>
                                        <button class="delete-btn" onclick="deleteEquipo('${encodeURIComponent(equipo.id_equipo)}')">Eliminar</button>
                                    </td>
                                </tr>`;
                    });
                }
                html += `</tbody></table>`;

                const totalPaginas = Math.ceil(data.total / 10);
                if (totalPaginas > 1) {
                    html += `<div class="pagination">`;
                    for (let i = 1; i <= totalPaginas; i++) {
                        html += `<a href="#" class="${i === page ? 'active' : ''}" onclick="loadEquipos(${i}); return false;">${i}</a>`;
                    }
                    html += `</div>`;
                }
                container.innerHTML = html;
                toggleBtn.textContent = 'Ocultar Equipos en Inventario';
                equiposVisible = true;
                attachSearchEvents('searchEquipos', 'fechaInicioEquipos', 'fechaFinEquipos', loadEquipos);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => showNotification('Error al cargar equipos: ' + error, 'error'));
}

function toggleEquipos() {
    if (equiposVisible) {
        document.getElementById('equiposTableContainer').innerHTML = '';
        document.getElementById('toggleEquiposBtn').textContent = 'Mostrar Equipos en Inventario';
        equiposVisible = false;
    } else {
        loadEquipos();
    }
}

function loadMateriales(page = 1) {
    const container = document.getElementById('materialesTableContainer');
    const toggleBtn = document.getElementById('toggleMaterialesBtn');
    if (!container || !toggleBtn) return;

    const search = document.getElementById('searchMateriales')?.value || '';
    const fechaInicio = document.getElementById('fechaInicioMateriales')?.value || '';
    const fechaFin = document.getElementById('fechaFinMateriales')?.value || '';

    const url = `inventario.php?obtener_materiales=1&pagina_materiales=${page}&search=${encodeURIComponent(search)}&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="search-container">
                        <div><label for="searchMateriales">Buscar Materiales:</label><input type="text" id="searchMateriales" placeholder="Buscar por nombre, tipo..." value="${search}"></div>
                        <div><label for="fechaInicioMateriales">Desde:</label><input type="date" id="fechaInicioMateriales" value="${fechaInicio}"></div>
                        <div><label for="fechaFinMateriales">Hasta:</label><input type="date" id="fechaFinMateriales" value="${fechaFin}"></div>
                        <button class="export-btn" onclick="window.location.href='inventario.php?exportar_materiales_csv=1'">Exportar a CSV</button>
                    </div>
                    <p class="total-count">Total de materiales: <strong>${data.total}</strong></p>
                    <table><thead><tr><th>Nombre</th><th>Tipo</th><th>Stock</th><th>Fecha Creación</th><th>Acción</th></tr></thead><tbody>`;

                if (data.data.length === 0) {
                    html += `<tr><td colspan="5" style="text-align:center;">No se encontraron materiales.</td></tr>`;
                } else {
                    data.data.forEach(material => {
                        html += `<tr>
                                    <td>${material.nombre}</td><td>${material.tipo}</td><td>${material.stock}</td><td>${material.fecha_creacion}</td>
                                    <td class="action-buttons">
                                        <button class="edit-btn" onclick="openUpdateMaterialModal('${encodeURIComponent(material.id_material)}', '${encodeURIComponent(material.nombre)}', '${encodeURIComponent(material.tipo)}', '${encodeURIComponent(material.stock)}')">Editar</button>
                                        <button class="delete-btn" onclick="deleteMaterial('${encodeURIComponent(material.id_material)}')">Eliminar</button>
                                    </td>
                                </tr>`;
                    });
                }
                html += `</tbody></table>`;
                
                const totalPaginas = Math.ceil(data.total / 10);
                if (totalPaginas > 1) {
                    html += `<div class="pagination">`;
                    for (let i = 1; i <= totalPaginas; i++) {
                        html += `<a href="#" class="${i === page ? 'active' : ''}" onclick="loadMateriales(${i}); return false;">${i}</a>`;
                    }
                    html += `</div>`;
                }
                container.innerHTML = html;
                toggleBtn.textContent = 'Ocultar Materiales en Inventario';
                materialesVisible = true;
                attachSearchEvents('searchMateriales', 'fechaInicioMateriales', 'fechaFinMateriales', loadMateriales);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => showNotification('Error al cargar materiales: ' + error, 'error'));
}

function toggleMateriales() {
    if (materialesVisible) {
        document.getElementById('materialesTableContainer').innerHTML = '';
        document.getElementById('toggleMaterialesBtn').textContent = 'Mostrar Materiales en Inventario';
        materialesVisible = false;
    } else {
        loadMateriales();
    }
}

function loadHistorial() {
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
                let html = `<table><thead><tr><th>Almacenista</th><th>Tabla</th><th>Acción</th><th>ID Registro</th><th>Fecha</th><th>Detalles</th><th>Acción</th></tr></thead><tbody>`;
                if (data.data.length === 0) {
                    html += `<tr><td colspan="7" style="text-align:center;">No hay cambios registrados.</td></tr>`;
                } else {
                    data.data.forEach(row => {
                        html += `<tr>
                                    <td>${row.nombres ? row.nombres + ' ' + row.apellidos : 'N/A'}</td><td>${row.tabla_afectada}</td><td>${row.accion}</td><td>${row.id_registro}</td><td>${row.fecha_accion}</td><td>${row.detalles}</td>
                                    <td class="action-buttons"><button class="delete-btn" onclick="deleteHistorial('${row.id_historial}')">Eliminar</button></td>
                                </tr>`;
                    });
                }
                html += `</tbody></table>`;
                container.innerHTML = html;
                toggleBtn.textContent = 'Ocultar Historial de Cambios';
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => showNotification('Error al cargar historial: ' + error, 'error'));
}

function deleteHistorial(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este registro del historial?')) return;
    const formData = new FormData();
    formData.append('eliminar_historial', '1');
    formData.append('id_historial', id);

    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) loadHistorial();
    })
    .catch(error => showNotification('Error al eliminar registro: ' + error, 'error'));
}

function addEquipo() {
    const form = document.getElementById('addEquipoForm');
    const formData = new FormData(form);
    formData.append('agregar_equipo_ajax', '1');

    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            form.reset();
            document.getElementById('customMarca').style.display = 'none';
            if (equiposVisible) loadEquipos();
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') loadHistorial();
        }
    })
    .catch(error => showNotification('Error al agregar equipo: ' + error, 'error'));
}

function deleteEquipo(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este equipo?')) return;
    const formData = new FormData();
    formData.append('eliminar_equipo', '1');
    formData.append('id_equipo', id);

    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            if (equiposVisible) loadEquipos();
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') loadHistorial();
        }
    })
    .catch(error => showNotification('Error al eliminar equipo: ' + error, 'error'));
}

function addMaterial() {
    const form = document.getElementById('addMaterialForm');
    const formData = new FormData(form);
    formData.append('agregar_material_ajax', '1');

    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            form.reset();
            if (materialesVisible) loadMateriales();
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') loadHistorial();
        }
    })
    .catch(error => showNotification('Error al agregar material: ' + error, 'error'));
}

function deleteMaterial(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este material?')) return;
    const formData = new FormData();
    formData.append('eliminar_material_ajax', '1');
    formData.append('id_material', id);

    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            if (materialesVisible) loadMateriales();
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') loadHistorial();
        }
    })
    .catch(error => showNotification('Error al eliminar material: ' + error, 'error'));
}

function updateEquipo() {
    const form = document.getElementById('updateEquipoForm');
    const formData = new FormData(form);
    
    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            closeModal('updateEquipoModal');
            if (equiposVisible) loadEquipos();
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') loadHistorial();
        }
    })
    .catch(error => showNotification('Error al actualizar equipo: ' + error, 'error'));
}

function updateMaterial() {
    const form = document.getElementById('updateMaterialForm');
    const formData = new FormData(form);

    fetch('inventario.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            closeModal('updateMaterialModal');
            if (materialesVisible) loadMateriales();
            if (document.getElementById('historialTableContainer').innerHTML.trim() !== '') loadHistorial();
        }
    })
    .catch(error => showNotification('Error al actualizar material: ' + error, 'error'));
}
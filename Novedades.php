<?php
include('requiere_login.php'); 
include("conexion.php"); 

$id_almacenista_actual = $_SESSION['id_usuario'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Img/icono_proyecto.png">
    <title>Gestión de Novedades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="Css/style.css?v=<?php echo time(); ?>"> 
    <style>
        .novedades-page-wrapper { padding: 20px; max-width: 1000px; margin: 20px auto;  }
        .container-novedades-content { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .novedad-card { border: 1px solid #e0e0e0; border-left-width: 5px; margin-bottom: 15px; padding: 15px; padding-top: 45px; border-radius: 5px; background-color: #fdfdfd; position: relative; }
        .novedad-card.tipo-equipo { border-left-color: #007bff; }
        .novedad-card.tipo-material { border-left-color: #28a745; }
        .novedad-foto { max-width: 45%; height: auto; border-radius: 4px; margin-top: 10px; cursor: pointer; }
        .item-info-header { background-color: #e9ecef; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary-item-link { cursor: pointer; }
        .summary-item-link:hover { background-color:rgb(240, 240, 240); } 
        .summary-item-link.active { background-color:rgb(207, 255, 215) !important; border-color: #b6d4fe !important; color: #004085 !important; font-weight: bold; }
        .novedad-actions { position: absolute; top: 10px; right: 10px; z-index:5; }
        .novedad-actions .btn { margin-left: 5px; }
        
        
        body.dark-mode .container-novedades-content { background-color: #2d3748; color: #e2e8f0; box-shadow: 0 0 20px rgba(0,0,0,0.4); }
        body.dark-mode .novedad-card { border-color: #4a5568; background-color: #3e4a5a; }
        body.dark-mode .novedad-card.tipo-equipo { border-left-color: #58a6ff; }
        body.dark-mode .novedad-card.tipo-material { border-left-color: #52c074; }
        body.dark-mode .item-info-header { background-color: #3e4a5a; color: #e2e8f0; }
        body.dark-mode .form-select, body.dark-mode .form-control { background-color: #2d3748; color: #e2e8f0; border-color: #4a5568; }
        body.dark-mode .select2-container--bootstrap-5 .select2-selection { background-color: #2d3748 !important; border-color: #4a5568 !important; color: #e2e8f0 !important; }
        body.dark-mode .select2-container--bootstrap-5 .select2-selection .select2-selection__rendered { color: #e2e8f0 !important; }
        body.dark-mode .select2-container--bootstrap-5 .select2-dropdown { background-color: #2d3748; border-color: #4a5568; }
        body.dark-mode .select2-results__option { color: #e2e8f0; }
        body.dark-mode .select2-results__option--highlighted { background-color: #4a5568 !important; color: #ffffff !important; }
        body.dark-mode .btn-light { background-color: #4a5568; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode .btn-light:hover { background-color: #5a6578; border-color: #5a6578; }
        body.dark-mode .summary-item-link:hover { background-color: #374151; }
        body.dark-mode .list-group-item { background-color: #2d3748; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode .summary-item-link.active { background-color: #3b5998 !important; border-color: #3b5998 !important; color: #ffffff !important; }
        body.dark-mode .modal-content { background-color: #2d3748; color: #e2e8f0; } 
        body.dark-mode .modal-header, body.dark-mode .modal-footer { border-color: #4a5568; }
        body.dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%);}
    </style>
</head>
<body>
    <div class="novedades-page-wrapper">
        <a href="index.php" class="btn btn-light mb-4">
            <i class="fas fa-arrow-left"></i> Volver al Menú Principal
        </a>

        <div class="container-novedades-content">
            <h1 class="mb-4">📝 Gestión de Novedades</h1>
            
            <div id="items_with_novedades_summary_list" class="mb-4">
                <h4 class="mb-3">Resumen: Ítems con Novedades</h4>
                <div class="list-group"></div>
                <p id="summary_loading_message" class="text-center text-muted mt-2" style="display:none;">Cargando resumen...</p>
                <p id="summary_no_items_message" class="text-center text-muted mt-2" style="display:none;">No hay ítems con novedades actualmente.</p>
            </div>
            <hr class="my-4">

            <h4 class="mb-3">Seleccionar Ítem Manualmente:</h4>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="tipo_item_select" class="form-label">Tipo de Ítem:</label>
                    <select id="tipo_item_select" class="form-select">
                        <option value="">-- Seleccione Tipo --</option>
                        <option value="equipo">Equipo</option>
                        <option value="material">Material (No Consumible)</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="item_select" class="form-label">Ítem Específico:</label>
                    <select id="item_select" class="form-select select2" disabled>
                        <option value="">-- Seleccione un tipo primero --</option>
                    </select>
                </div>
            </div>

            <div id="item_info_display" class="item-info-header" style="display:none;">
                <h4 id="selected_item_name"></h4>
                <p id="selected_item_details" class="mb-0"></p>
            </div>

            <h3 class="mt-4 mb-3" id="novedades_header" style="display:none;">Historial de Novedades</h3>
            <div id="novedades_list_container" class="mb-4" style="max-height: 400px; overflow-y: auto;">
                <p class="text-center text-muted">Seleccione un ítem para ver sus novedades.</p>
            </div>

            <div id="add_novedad_form_container" style="display:none;">
                <h3 class="mt-4">Añadir Nueva Novedad para <span id="form_item_name_label"></span></h3>
                <form id="form_add_novedad" enctype="multipart/form-data">
                    <input type="hidden" id="novedad_id_item" name="id_item">
                    <input type="hidden" id="novedad_tipo_item" name="tipo_item">
                    <input type="hidden" id="novedad_id_almacenista" name="id_almacenista" value="<?php echo htmlspecialchars($id_almacenista_actual); ?>">
                    <div class="mb-3">
                        <label for="novedad_observacion" class="form-label">Observación:</label>
                        <textarea id="novedad_observacion" name="observacion" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="novedad_foto" class="form-label">Adjuntar Foto (Opcional):</label>
                        <input type="file" id="novedad_foto" name="foto" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Añadir Novedad</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editNovedadModal" tabindex="-1" aria-labelledby="editNovedadModalLabel" aria-hidden="true"> <div class="modal-dialog modal-lg"> <div class="modal-content"> <form id="form_edit_novedad" enctype="multipart/form-data"> <div class="modal-header"> <h5 class="modal-title" id="editNovedadModalLabel">Editar Novedad</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div> <div class="modal-body"> <input type="hidden" id="edit_novedad_id" name="id_novedad"> <div class="mb-3"> <label for="edit_novedad_observacion" class="form-label">Observación:</label> <textarea id="edit_novedad_observacion" name="observacion" class="form-control" rows="4" required></textarea> </div> <div class="mb-3"> <label class="form-label">Foto Actual:</label> <div id="current_novedad_photo_preview_container" class="mb-2"> <p id="no_current_photo_text" style="display:none;" class="text-muted">No hay foto actual.</p> </div> <div class="form-check" id="delete_photo_checkbox_container" style="display:none;"> <input class="form-check-input" type="checkbox" value="1" id="edit_delete_current_foto" name="delete_current_foto"> <label class="form-check-label" for="edit_delete_current_foto"> Eliminar foto actual </label> </div> </div> <div class="mb-3"> <label for="edit_novedad_new_foto" class="form-label">Cambiar/Adjuntar Foto Nueva (Opcional):</label> <input type="file" id="edit_novedad_new_foto" name="new_foto" class="form-control" accept="image/*"> </div> </div> <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button> <button type="submit" class="btn btn-primary">Guardar Cambios</button> </div> </form> </div> </div> </div>
    <div class="modal fade" id="imageDisplayModal" tabindex="-1" aria-labelledby="imageDisplayModalLabel" aria-hidden="true"> <div class="modal-dialog modal-lg modal-dialog-centered"> <div class="modal-content"> <div class="modal-header"> <h5 class="modal-title" id="imageDisplayModalLabel">Vista Previa de Imagen</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div> <div class="modal-body text-center"> <img src="" id="modalImage" class="img-fluid" alt="Imagen de Novedad"> </div> </div> </div> </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
                const API_NOVEDADES_URL = 'novedades_api.php';
        let activeSummaryItem = { id: null, type: null }; 
        let allNovedadesData = {}; 
        let editNovedadModalInstance = null; 

        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
        }

        function htmlspecialchars(str) {
            if (typeof str === 'undefined' || str === null) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(str).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function showNotification(message, type) {
            const containerId = 'notification-container-novedades-page';
            let notificationContainer = $('#' + containerId);
            if (!notificationContainer.length) {
                notificationContainer = $('<div id="' + containerId + '" style="position: fixed; top: 20px; right: 20px; z-index: 1055; min-width: 300px;"></div>');
                $('body').append(notificationContainer);
            }
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${htmlspecialchars(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
            if(notificationContainer.children().length >= 3) { notificationContainer.children().first().remove(); } // Quitar la más antigua si hay muchas
            notificationContainer.prepend(alertHtml); // Añadir la nueva al principio
            
            // Auto-cerrar la notificación recién añadida después de 5 segundos
             $(notificationContainer.find('.alert')[0]).delay(5000).fadeOut(500, function() { $(this).remove(); });
        }
        
        function showImageModal(imageSrc) {
            const modalImage = document.getElementById('modalImage');
            if(modalImage) {
                modalImage.src = imageSrc;
                const imageModal = bootstrap.Modal.getInstance(document.getElementById('imageDisplayModal')) || new bootstrap.Modal(document.getElementById('imageDisplayModal'));
                imageModal.show();
            }
        }

        function resetDetailedView(clearDropdowns = true) {
            $('#item_info_display').hide();
            $('#novedades_header').hide();
            $('#novedades_list_container').html('<p class="text-center text-muted">Seleccione un ítem para ver sus novedades.</p>');
            $('#add_novedad_form_container').hide();

            if (clearDropdowns) {
                $('#tipo_item_select').val('');
                $('#item_select').val(null).trigger('change.select2').empty().append('<option value="">-- Seleccione un tipo primero --</option>').prop('disabled', true);
            }
        }

        function highlightSummaryItem(itemId, itemType) {
            $('.summary-item-link').removeClass('active');
            if (itemId && itemType) {
                // console.log("Highlighting summary item:", itemId, itemType);
                $(`.summary-item-link[data-id="${itemId}"][data-type="${itemType}"]`).addClass('active');
            }
        }

        function loadItemsWithNovedadesSummary() {
            const summaryContainer = $('#items_with_novedades_summary_list .list-group');
            const loadingMsg = $('#summary_loading_message');
            const noItemsMsg = $('#summary_no_items_message');

            loadingMsg.show(); 
            noItemsMsg.hide();
            summaryContainer.empty();

            $.ajax({
                url: API_NOVEDADES_URL, 
                type: 'GET', data: { action: 'get_items_with_novedades_summary' }, dataType: 'json',
                success: function(response) { 
                    loadingMsg.hide();
                    if (response.success && response.items_summary && response.items_summary.length > 0) {
                        noItemsMsg.hide();
                        response.items_summary.forEach(function(item) {
                            const itemHtml = `
                                <a href="#" class="list-group-item list-group-item-action summary-item-link d-flex justify-content-between align-items-center" 
                                   data-id="${item.id_item}" 
                                   data-type="${item.tipo_item}"
                                   data-name="${htmlspecialchars(item.display_name)}">
                                    ${htmlspecialchars(item.display_name)}
                                    <span class="badge bg-warning rounded-pill">${item.cantidad_novedades}</span>
                                </a>`;
                            summaryContainer.append(itemHtml);
                        });
                    } else if (response.success) { 
                        summaryContainer.empty(); 
                        noItemsMsg.show(); 
                    } else { 
                        summaryContainer.empty(); 
                        noItemsMsg.hide();
                        showNotification(response.message || 'Error al cargar resumen.', 'danger');
                        summaryContainer.html('<p class="text-danger text-center">Error al cargar el resumen.</p>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) { 
                    loadingMsg.hide(); 
                    summaryContainer.empty(); 
                    noItemsMsg.hide();
                    showNotification('Error de conexión al cargar resumen. Verifique la consola.', 'danger');
                    summaryContainer.html('<p class="text-danger text-center">Error de conexión al cargar el resumen.</p>');
                    console.error("Error en AJAX para get_items_with_novedades_summary:", textStatus, errorThrown, jqXHR.responseText);
                }
            });
        }
        
        function loadNovedades(itemId, tipoItem) {
            const container = $('#novedades_list_container');
            container.html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando novedades...</p>');
            allNovedadesData = {}; 

            $.ajax({
                url: API_NOVEDADES_URL, type: 'GET', data: { action: 'get_novedades_for_item', id_item: itemId, tipo_item: tipoItem }, dataType: 'json',
                success: function(response) {
                    container.empty();
                    if (response.success && response.novedades && response.novedades.length > 0) {
                        response.novedades.forEach(function(novedad) {
                            allNovedadesData[novedad.id_novedad] = novedad; 
                            let fotoHtml = '';
                            if (novedad.ruta_foto) {
                                fotoHtml = `<img src="${htmlspecialchars(novedad.ruta_foto)}" alt="Foto Novedad" class="novedad-foto img-thumbnail mt-2" onclick="showImageModal('${htmlspecialchars(novedad.ruta_foto)}')">`;
                            }
                            const fechaFormateada = new Date(novedad.fecha_novedad.replace(' ', 'T')).toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
                            const cardHtml = `
                                <div class="novedad-card tipo-${htmlspecialchars(novedad.tipo_item)}" id="novedad-card-${novedad.id_novedad}">
                                    <div class="novedad-actions">
                                        <button class="btn btn-sm btn-outline-primary edit-novedad-btn" data-id_novedad="${novedad.id_novedad}" title="Editar Novedad"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger delete-novedad-btn" data-id_novedad="${novedad.id_novedad}" title="Eliminar Novedad"><i class="fas fa-trash"></i></button>
                                    </div>
                                    <p class="mb-1"><strong>Observación:</strong> ${htmlspecialchars(novedad.observacion)}</p>
                                    <small class="text-muted">Registrado por: ${htmlspecialchars(novedad.nombre_almacenista_reporta || 'Sistema')} el ${fechaFormateada}</small>
                                    ${fotoHtml}
                                </div>`;
                            container.append(cardHtml);
                        });
                    } else if (response.success) {
                        container.html('<p class="text-center text-muted">No hay novedades registradas para este ítem.</p>');
                    } else {
                        container.html('<p class="text-center text-danger">Error al cargar novedades.</p>');
                        showNotification(response.message || 'Error al cargar novedades.', 'danger');
                    }
                },
                error: function() { 
                    container.html('<p class="text-center text-danger">Error de conexión al cargar novedades.</p>');
                    showNotification('Error de conexión al cargar novedades.', 'danger');
                 }
            });
        }

        $(document).ready(function() {
            if(document.getElementById('editNovedadModal')) {
                editNovedadModalInstance = new bootstrap.Modal(document.getElementById('editNovedadModal'));
            }
            loadItemsWithNovedadesSummary(); 

            $('#item_select').select2({
                theme: "bootstrap-5",
                placeholder: "Seleccione un ítem",
                width: '100%',
                dropdownParent: $('#item_select').parent() 
            });

            $('#items_with_novedades_summary_list').on('click', '.summary-item-link', function(e) {
                e.preventDefault();
                const itemId = parseInt($(this).data('id')); 
                const itemType = $(this).data('type');

                if (activeSummaryItem.id === itemId && activeSummaryItem.type === itemType) {
                    resetDetailedView(true); 
                    activeSummaryItem = { id: null, type: null };
                    highlightSummaryItem(null, null);
                    sessionStorage.removeItem('preselectNovedadItemType');
                    sessionStorage.removeItem('preselectNovedadItemId');
                } else {
                    // No establecemos activeSummaryItem aquí directamente. Se hará cuando los detalles se carguen.
                    highlightSummaryItem(itemId, itemType); // Resaltar visualmente de inmediato el clickeado
                    sessionStorage.setItem('preselectNovedadItemType', itemType);
                    sessionStorage.setItem('preselectNovedadItemId', itemId.toString());
                    $('#tipo_item_select').val(itemType).trigger('change');
                    
                    $('html, body').animate({ scrollTop: $("#tipo_item_select").offset().top - 20 }, 300);
                }
            });

            $('#tipo_item_select').on('change', function() {
                const tipoItem = $(this).val();
                const itemSelect = $('#item_select');
                const isPreselectingViaSession = sessionStorage.getItem('preselectNovedadItemId') && sessionStorage.getItem('preselectNovedadItemType') === tipoItem;

                if (!isPreselectingViaSession && activeSummaryItem.id !== null) { 
                    activeSummaryItem = { id: null, type: null }; 
                    highlightSummaryItem(null, null); // Limpiar resaltado si el usuario cambia tipo manualmente
                }
                
                if (!tipoItem) { 
                    resetDetailedView(false); 
                    itemSelect.val(null).trigger('change.select2').empty().append('<option value="">-- Seleccione un tipo primero --</option>').prop('disabled', true);
                    // Si se limpia el tipo, también limpiar el activeSummaryItem si estaba fijado
                    if(activeSummaryItem.id !== null) { 
                         activeSummaryItem = { id: null, type: null };
                         highlightSummaryItem(null, null);
                    }
                    return; 
                }
                
                resetDetailedView(false); 
                itemSelect.empty().prop('disabled', true).append(isPreselectingViaSession ? '<option value="">Cargando para preselección...</option>' : '<option value="">Cargando...</option>');

                $.ajax({
                    url: API_NOVEDADES_URL, type: 'GET', data: { action: 'get_items_for_selection', tipo_item: tipoItem }, dataType: 'json',
                    success: function(response) { 
                        itemSelect.empty().append('<option value="">-- Seleccione Ítem --</option>');
                        if (response.success && response.items) {
                            response.items.forEach(function(item) { itemSelect.append(new Option(item.display_name, item.id)); });
                            itemSelect.prop('disabled', false);
                        } else { 
                            itemSelect.append('<option value="">No hay ítems disponibles</option>');
                            showNotification(response.message || 'Error al cargar ítems.', 'danger');
                        }
                        itemSelect.trigger('change.select2'); 

                        const preselectType = sessionStorage.getItem('preselectNovedadItemType');
                        const preselectIdStr = sessionStorage.getItem('preselectNovedadItemId');

                        if (preselectType === tipoItem && preselectIdStr) {
                            const preselectId = parseInt(preselectIdStr);
                            if (itemSelect.find(`option[value="${preselectId}"]`).length > 0) {
                                itemSelect.val(preselectId.toString()).trigger('change'); 
                            } else {
                                console.warn(`Ítem de preselección ID ${preselectId} no encontrado.`);
                                itemSelect.trigger('change'); 
                            }
                            // Limpiar sessionStorage aquí, después de que itemSelect.trigger('change') haya sido llamado
                            // para que el handler de item_select pueda confirmar que fue una preselección.
                            // Se usará otra variable para confirmar en item_select
                            sessionStorage.setItem('preselectConfirmId', preselectId.toString());
                            sessionStorage.setItem('preselectConfirmType', preselectType);
                            sessionStorage.removeItem('preselectNovedadItemType'); 
                            sessionStorage.removeItem('preselectNovedadItemId');
                        } else { 
                            if (!itemSelect.val()) { itemSelect.trigger('change'); }
                        }
                    },
                    error: function() { 
                        itemSelect.empty().append('<option value="">Error al cargar</option>').prop('disabled', false).trigger('change.select2');
                        showNotification('Error de conexión al cargar ítems.', 'danger');
                        sessionStorage.removeItem('preselectNovedadItemType'); sessionStorage.removeItem('preselectNovedadItemId');
                     }
                });
            });

            $('#item_select').on('change', function() {
                const itemId = $(this).val() ? parseInt($(this).val()) : null; 
                const tipoItem = $('#tipo_item_select').val();
                const selectedItemText = $(this).find('option:selected').text();

                if (itemId && tipoItem) {
                    const confirmId = sessionStorage.getItem('preselectConfirmId');
                    const confirmType = sessionStorage.getItem('preselectConfirmType');

                    if (confirmId === itemId.toString() && confirmType === tipoItem) {
                        // Es el final de un flujo de preselección desde el resumen
                        activeSummaryItem = { id: itemId, type: tipoItem };
                        // El resaltado ya debería estar hecho por el click en el resumen
                        sessionStorage.removeItem('preselectConfirmId');
                        sessionStorage.removeItem('preselectConfirmType');
                    } else {
                        // Es una selección manual o el flujo de preselección se interrumpió
                        if (activeSummaryItem.id !== itemId || activeSummaryItem.type !== tipoItem) {
                             activeSummaryItem = { id: null, type: null }; // Limpiar si es diferente
                             highlightSummaryItem(null, null); // Quitar resaltado del resumen
                             // Si el item seleccionado manualmente ESTÁ en el resumen, resaltarlo visualmente pero no hacerlo "activo para colapso"
                             const $correspondingSummaryItem = $(`.summary-item-link[data-id="${itemId}"][data-type="${tipoItem}"]`);
                             if($correspondingSummaryItem.length > 0) {
                                highlightSummaryItem(itemId, tipoItem);
                             }
                        }
                    }

                    $('#novedad_id_item').val(itemId); $('#novedad_tipo_item').val(tipoItem); $('#form_item_name_label').text(selectedItemText);
                    $('#selected_item_name').text(selectedItemText); $('#selected_item_details').text(`Tipo: ${tipoItem.charAt(0).toUpperCase() + tipoItem.slice(1)}, ID: ${itemId}`);
                    $('#item_info_display').show(); $('#novedades_header').text(`Historial de Novedades para: ${selectedItemText}`).show();
                    loadNovedades(itemId, tipoItem); $('#add_novedad_form_container').show();
                } else { 
                    resetDetailedView(false); 
                    if (activeSummaryItem.id !== null) {
                         activeSummaryItem = { id: null, type: null };
                         highlightSummaryItem(null, null);
                    }
                    sessionStorage.removeItem('preselectConfirmId'); // Limpiar por si acaso
                    sessionStorage.removeItem('preselectConfirmType');
                }
            });

            $('#form_add_novedad').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add_novedad');
                const submitButton = $(this).find('button[type="submit"]');
                const originalButtonText = submitButton.html();
                submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({ 
                    url: API_NOVEDADES_URL, type: 'POST', data: formData, dataType: 'json', contentType: false, processData: false,
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message, 'success');
                            $('#form_add_novedad')[0].reset(); 
                            loadNovedades($('#novedad_id_item').val(), $('#novedad_tipo_item').val()); 
                            loadItemsWithNovedadesSummary(); 
                        } else { showNotification(response.message || 'Error al añadir novedad.', 'danger');}
                    },
                    error: function(xhr) { showNotification('Error de conexión: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), 'danger'); },
                    complete: function() { submitButton.prop('disabled', false).html(originalButtonText); }
                });
            });

            $('#novedades_list_container').on('click', '.delete-novedad-btn', function() {
                const novedadId = $(this).data('id_novedad');
                if (!novedadId) { console.error("ID de novedad no encontrado para eliminar."); return; }
                if (confirm('¿Está seguro de que desea eliminar esta novedad? Esta acción no se puede deshacer.')) {
                    $.ajax({
                        url: API_NOVEDADES_URL, type: 'POST', 
                        data: { action: 'delete_novedad', id_novedad: novedadId }, 
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showNotification(response.message, 'success');
                                loadNovedades($('#novedad_id_item').val(), $('#novedad_tipo_item').val()); 
                                loadItemsWithNovedadesSummary(); 
                            } else { showNotification(response.message || 'Error al eliminar.', 'danger'); }
                        },
                        error: function() { showNotification('Error de conexión al intentar eliminar.', 'danger'); }
                    });
                }
            });

            $('#novedades_list_container').on('click', '.edit-novedad-btn', function() {
                const novedadId = $(this).data('id_novedad');
                if (!novedadId) { console.error("ID de novedad no encontrado para editar."); return; }
                const novedadData = allNovedadesData[novedadId]; 
                if (novedadData) {
                    $('#edit_novedad_id').val(novedadData.id_novedad);
                    $('#edit_novedad_observacion').val(novedadData.observacion); 
                    
                    const previewContainer = $('#current_novedad_photo_preview_container');
                    const deleteCheckboxContainer = $('#delete_photo_checkbox_container');
                    const noPhotoText = $('#no_current_photo_text');

                    previewContainer.empty(); 
                    $('#edit_delete_current_foto').prop('checked', false); 
                    $('#edit_novedad_new_foto').val(''); 

                    if (novedadData.ruta_foto) {
                        previewContainer.append(`<img src="${htmlspecialchars(novedadData.ruta_foto)}" class="img-thumbnail" style="max-height: 150px; margin-bottom:10px;" alt="Foto actual" onclick="showImageModal('${htmlspecialchars(novedadData.ruta_foto)}')">`);
                        deleteCheckboxContainer.show(); noPhotoText.hide();
                    } else {
                        noPhotoText.show(); deleteCheckboxContainer.hide();
                    }
                    if(editNovedadModalInstance) editNovedadModalInstance.show(); else console.error("Modal de edición no inicializado.");
                } else {
                    showNotification('No se encontraron datos para editar. Intente recargar.', 'warning');
                    console.error("Datos no encontrados en allNovedadesData para ID:", novedadId);
                }
            });

            $('#form_edit_novedad').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'update_novedad'); 
                
                const submitButton = $(this).find('button[type="submit"]');
                const originalButtonText = submitButton.html();
                submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: API_NOVEDADES_URL, type: 'POST', data: formData, dataType: 'json', contentType: false, processData: false,
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message, 'success');
                            if(editNovedadModalInstance) editNovedadModalInstance.hide();
                            loadNovedades($('#novedad_id_item').val(), $('#novedad_tipo_item').val()); 
                            loadItemsWithNovedadesSummary(); // Recargar resumen por si una edición cambia el estado/conteo
                        } else { 
                            showNotification(response.message || 'Error al actualizar.', 'danger'); 
                        }
                    },
                    error: function(xhr) { 
                        showNotification('Error de conexión al actualizar: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText), 'danger');
                    },
                    complete: function() { submitButton.prop('disabled', false).html(originalButtonText); }
                });
            });
        }); 
    </script>
</body>
</html>
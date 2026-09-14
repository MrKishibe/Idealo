$(document).ready(function () {
    let tablaDataTable = null;
    let todosLosMateriales = []; 
    let verEliminados = false;

    console.log("Inicialización: tipomaterial.js sincronizado con el controlador procedimental.");

    // Expresión regular para validar nombres (entre 3 y 50 caracteres) con / - . ( )
    const regexNombre = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s/\-\.\(\)]{3,50}$/;
    const mensajeErrorNombre = "El nombre debe tener entre 3 y 50 caracteres (letras, números o /-.()).";

    // Función auxiliar para reseteo completo de formularios y mensajes visuales
    function limpiarFormulario(formSelector, inputJQuery) {
        if (formSelector && $(formSelector).length > 0) {
            $(formSelector)[0].reset();
            $(formSelector).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
            $(formSelector).find(".feedback-validacion").remove();
        } else if (inputJQuery && inputJQuery.length > 0) {
            inputJQuery.removeClass("is-valid is-invalid");
            inputJQuery.siblings(".feedback-validacion").remove();
        }
    }

    // Validador de campos (Ejecutado solo al presionar el botón de envío)
    function validarCampo(input, regex, mensajeError) {
        if (!input || input.length === 0) return false;
        let domInput = input[0];
        
        // 1. Ocultar o eliminar mensajes estáticos que puedan venir desde el HTML
        $(domInput).siblings("small:not(.feedback-validacion), .invalid-feedback, .valid-feedback").hide();
        
        // 2. Buscar si ya existe el contenedor dinámico o crearlo si no existe
        let $feedback = $(domInput).siblings(".feedback-validacion");
        if ($feedback.length === 0) {
            $feedback = $('<small class="feedback-validacion form-text d-block"></small>');
            $(domInput).after($feedback);
        }

        let valor = input.val().trim();

        // Evaluar si el campo está vacío
        if (valor === "") {
            input.removeClass("is-valid").addClass("is-invalid");
            $feedback.text("Este campo no puede estar vacío.").css("color", "#dc3545").show();
            return false;
        }

        // Evaluar con la expresión regular
        if (regex.test(valor)) {
            input.removeClass("is-invalid").addClass("is-valid");
            $feedback.text("").hide();
            return true;
        } else {
            input.removeClass("is-valid").addClass("is-invalid");
            $feedback.text(mensajeError).css("color", "#dc3545").show();
            return false;
        }
    }

    // =========================================================================
    // CONTROL DE LIMPIEZA Y EVENTOS DE MODALES (BOOTSTRAP)
    // =========================================================================
    
    // 1. Modal Registrar: NO limpiar al cerrar (solo ocultar)
    $('#modalRegistrarMaterial').on('hide.bs.modal', function () {
        if (document.activeElement) document.activeElement.blur();
    });

    // Botón Cancelar explícito en Registrar -> SÍ limpia
    $('#modalRegistrarMaterial').find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', function() {
        limpiarFormulario("#formTipoMaterial");
    });

    // 2. Modal Editar Activo: NO limpiar al cerrar (solo ocultar)
    $('#modalEditarActivo').on('hide.bs.modal', function () {
        if (document.activeElement) document.activeElement.blur();
    });

    // Botón Cancelar explícito en Editar Activo -> SÍ limpia
    $('#modalEditarActivo').find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', function() {
        limpiarFormulario(null, $('#edit_activo_nombre, #edit_activo_descripcion'));
        $('#edit_activo_id').val('');
    });

    // 3. Modal Editar Inactivo: NO limpiar al cerrar (solo ocultar)
    $('#modalEditarInactivo').on('hide.bs.modal', function () {
        if (document.activeElement) document.activeElement.blur();
    });

    // Botón Cancelar explícito en Editar Inactivo -> SÍ limpia
    $('#modalEditarInactivo').find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', function() {
        limpiarFormulario("#formEditarMaterial");
        $('#edit_id_material').val('');
    });

    // Carga de datos asíncrona
    function cargarMateriales() {
        $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar&ajax=listar',
            type: 'GET',
            dataType: 'json', 
            success: function (data) {
                if (data && Array.isArray(data.materiales)) {
                    todosLosMateriales = data.materiales;
                } else {
                    todosLosMateriales = [];
                }
                renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
            },
            error: function (xhr, status, error) { 
                console.error("Error al obtener JSON, usando datos de respaldo de la vista estática.", error);
                extraerDatosDeTablaEstatica();
            }
        });
    }

    // Extracción de contingencia desde el HTML
    function extraerDatosDeTablaEstatica() {
        todosLosMateriales = [];
        $('#tablaTipoMaterial tbody tr').each(function() {
            const fila = $(this);
            const idAttr = fila.attr('id');
            if (!idAttr) return;
            const id = idAttr.replace('fila-', '');
            
            const btnEditar = fila.find('.btnEditarActivo, .btnEditarInactivo');
            
            let nombre = "";
            let descripcion = "";
            let estado = "Activo";

            if (btnEditar.length > 0) {
                nombre = btnEditar.data('nombre');
                descripcion = btnEditar.data('descripcion');
                estado = fila.find('.badge').text().trim() === 'Inactivo' ? 'Inactivo' : 'Activo';
            }

            if (nombre) {
                todosLosMateriales.push({
                    id_tipo_materia_prima: id,
                    nombre_de_material: nombre,
                    descripcion: descripcion === 'Sin especificaciones' ? '' : descripcion,
                    status_tipo_materia: estado 
                });
            }
        });
        renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
    }

    // Renderizador dinámico de filas con DataTables
    function renderizarTabla(estadoFiltro) {
        const tbody = $('#tablaTipoMaterial tbody');
        
        if ($.fn.DataTable.isDataTable('#tablaTipoMaterial')) {
            tablaDataTable.destroy();
        }
        tbody.empty();

        const filtrados = todosLosMateriales.filter(mat => {
            let estadoReal = mat.status_tipo_materia || mat.status_tipo_material;
            return estadoReal === estadoFiltro;
        });

        if (filtrados.length > 0) {
            filtrados.forEach(mat => {
                let nombreMat = mat.nombre_de_material || mat.nombre;
                let descMat = mat.descripcion || 'Sin especificaciones';
                let estadoReal = mat.status_tipo_materia || mat.status_tipo_material;

                let badge = estadoReal === 'Activo'
                    ? '<span class="badge bg-success">Activo</span>' 
                    : '<span class="badge bg-danger">Inactivo</span>';

                let acciones = '';
                if (estadoReal === 'Activo') {
                    acciones = `
                        <button class="btn btn-sm btn-outline-primary btnEditarActivo me-1" 
                                data-id="${mat.id_tipo_materia_prima}" 
                                data-nombre="${nombreMat}" 
                                data-descripcion="${descMat}"
                                data-estado="${estadoReal}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                                data-id="${mat.id_tipo_materia_prima}" 
                                data-nombre="${nombreMat}"
                                data-estado="Inactivo">
                            <i class="bi bi-trash3-fill"></i>
                        </button>`;
                } else {
                    acciones = `
                        <button class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                                data-id="${mat.id_tipo_materia_prima}" 
                                data-nombre="${nombreMat}"
                                data-descripcion="${descMat}"
                                data-estado="${estadoReal}">
                            <i class="bi bi-pencil-square"></i>
                        </button>`;
                }

                tbody.append(`<tr id="fila-${mat.id_tipo_materia_prima}">
                    <td class="fw-bold">${nombreMat}</td>
                    <td>${descMat}</td>
                    <td>${badge}</td>
                    <td><div class="text-center">${acciones}</div></td>
                </tr>`);
            });
        }

        tablaDataTable = $('#tablaTipoMaterial').DataTable({
            language: { 
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                emptyTable: "No hay registros en esta vista",
                zeroRecords: "No se encontraron coincidencias"
            },
            pageLength: 10, 
            responsive: true, 
            ordering: false
        });
    }

    // Alternar vistas entre Activos / Inhabilitados
    $('#btnAlternarEstado').on('click', function() {
        verEliminados = !verEliminados;
        if (verEliminados) {
            $(this).attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Tipos de Material Inhabilitados');
            renderizarTabla('Inactivo');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Tipo de Material');
            renderizarTabla('Activo');
        }
    });

    // =========================================================================
    // ACCIÓN: REGISTRAR
    // =========================================================================
    $("#btnEnvio").on("click", function (e) {
        e.preventDefault();

        let inputNombre = $("#nombre_tipo_material");
        let inputDesc = $("#descripcion_tipo_material");

        let vNom = validarCampo(inputNombre, regexNombre, mensajeErrorNombre);
        if (!vNom) return;

        $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar', 
            type: 'POST',
            data: {
                nombre: inputNombre.val().trim(),          
                descripcion: inputDesc.val().trim()       
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    if (document.activeElement) document.activeElement.blur();
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Completado', 
                        text: response.message, 
                        confirmButtonColor: '#10b981',
                        didClose: () => {
                            // Limpiar SOLO después de registro exitoso
                            limpiarFormulario("#formTipoMaterial");
                            bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMaterial')).hide();
                            cargarMateriales();
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: response.message, confirmButtonColor: '#dc3545' });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Sucedió un error inesperado de comunicación con el servidor.', confirmButtonColor: '#dc3545' });
            }
        });
    });

    // ACCIÓN: ABRIR MODAL EDICIÓN ACTIVO
    $(document).on('click', '.btnEditarActivo', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const descripcion = $(this).data('descripcion');
        const estado = $(this).data('estado') || 'Activo';

        $('#edit_activo_nombre, #edit_activo_descripcion').removeClass("is-invalid is-valid");
        $('#modalEditarActivo').find(".feedback-validacion").remove();

        $('#edit_activo_id').val(id);
        $('#edit_activo_nombre').val(nombre);
        $('#edit_activo_descripcion').val(descripcion);
        
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarActivo')).show();
    });

    // =========================================================================
    // ACCIÓN: GUARDAR CAMBIOS EDICIÓN ACTIVO
    // =========================================================================
    $('#formEditarActivo').on('submit', function(e) {
        e.preventDefault();
        
        let id = $('#edit_activo_id').val();
        let inputNombre = $('#edit_activo_nombre');
        let inputDesc = $('#edit_activo_descripcion');

        let vNom = validarCampo(inputNombre, regexNombre, mensajeErrorNombre);
        if (!vNom) return;

        $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar',
            type: 'POST',
            data: {
                id_accion: id,            
                nombre: inputNombre.val().trim(),
                descripcion: inputDesc.val().trim()
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    if (document.activeElement) document.activeElement.blur();
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Actualizado', 
                        text: response.message, 
                        confirmButtonColor: '#10b981',
                        didClose: () => {
                            // Limpiar SOLO después de edición exitosa
                            limpiarFormulario(null, $('#edit_activo_nombre, #edit_activo_descripcion'));
                            $('#edit_activo_id').val('');
                            bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                            cargarMateriales();
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                }
            }
        });
    });

    // ACCIÓN: INHABILITAR DIRECTO DESDE LA FILA ACTIVA
    $(document).on('click', '.btnCambiarEstado', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        if (document.activeElement) document.activeElement.blur();

        Swal.fire({
            title: '¿Inhabilitar Tipo de Material?',
            text: `El registro "${nombre}" pasará a la lista de archivados.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?controller=tipoMateriaPrima&action=listar',
                    type: 'POST',
                    data: {
                        id_accion: id,
                        nuevo_estado: 'Inactivo'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({ icon: 'success', title: 'Inhabilitado', text: response.message, confirmButtonColor: '#10b981' });
                            cargarMateriales();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                        }
                    }
                });
            }
        });
    });

    // =========================================================================
    // ACCIÓN: ABRIR MODAL EDICIÓN INACTIVO (NOMBRE + DESCRIPCIÓN + ESTADO)
    // =========================================================================
    $(document).on('click', '.btnEditarInactivo', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const descripcion = $(this).data('descripcion') || '';
        const estado = $(this).data('estado') || 'Inactivo';

        // Limpiar validaciones previas
        $('#edit_nombre_material, #edit_descripcion_material').removeClass("is-invalid is-valid");
        $('#modalEditarInactivo').find(".feedback-validacion").remove();

        // Llenar campos del modal
        $('#edit_id_material').val(id);
        $('#edit_nombre_material').val(nombre);
        $('#edit_descripcion_material').val(descripcion);

        if ($('#edit_status_material').length > 0) {
            $('#edit_status_material').val(estado); // Activo o Inactivo
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarInactivo')).show();
    });

    // =========================================================================
    // ACCIÓN: GUARDAR CAMBIOS DESDE MODAL INACTIVO (NOMBRE + DESCRIPCIÓN + ESTADO)
    // =========================================================================
    $('#formEditarMaterial').on('submit', function (e) {
        e.preventDefault();

        const id = $('#edit_id_material').val();
        const nombre = $('#edit_nombre_material').val().trim();
        const descripcion = $('#edit_descripcion_material').val().trim();
        const nuevoEstado = $('#edit_status_material').val();

        // Validación básica del nombre
        if (!nombre) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'El nombre no puede estar vacío.',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar',
            type: 'POST',
            data: {
                id_accion: id,
                nombre: nombre,
                descripcion: descripcion,
                nuevo_estado: nuevoEstado
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    if (document.activeElement) document.activeElement.blur();
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: response.message || 'El registro ha sido actualizado correctamente.',
                        confirmButtonColor: '#10b981',
                        didClose: () => {
                            // Limpiar SOLO después de edición exitosa
                            limpiarFormulario("#formEditarMaterial");
                            $('#edit_id_material').val('');
                            bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo')).hide();
                            cargarMateriales();
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la solicitud con el servidor.', confirmButtonColor: '#dc3545' });
            }
        });
    });

    // Cargar datos iniciales
    cargarMateriales();
});
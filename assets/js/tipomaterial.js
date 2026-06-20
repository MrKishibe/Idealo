$(document).ready(function () {
    let tablaDataTable = null;
    let todosLosMateriales = []; 
    let verEliminados = false;

    console.log("Inicialización: tipomaterial.js sincronizado con el controlador procedimental.");

    // Expresión regular para validar nombres (entre 3 y 50 caracteres)
    const regexNombre = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,50}$/;

    // Validador visual de campos en tiempo real (Corregido para evitar duplicados y vacíos)
    function validarCampo(input, regex, mensajeError) {
        if (!input || input.length === 0) return false;
        let domInput = input[0];
        
        // Limpiar cualquier mensaje de validación previo en este contenedor
        $(domInput).siblings(".feedback-validacion").remove();
        
        // Crear el contenedor limpio para el mensaje
        let feedback = document.createElement("small");
        feedback.classList.add("feedback-validacion", "form-text");
        domInput.parentNode.appendChild(feedback);

        let valor = input.val().trim();

        // Si el campo está vacío, es inválido inmediatamente
        if (valor === "") {
            input.removeClass("is-valid").addClass("is-invalid");
            feedback.textContent = "Este campo no puede estar vacío.";
            feedback.style.color = "#dc3545";
            return false;
        }

        // Evaluar la expresión regular una vez confirmado que tiene texto
        if (regex.test(valor)) {
            input.removeClass("is-invalid").addClass("is-valid");
            feedback.textContent = "Campo válido";
            feedback.style.color = "#198754";
            return true;
        } else {
            input.removeClass("is-valid").addClass("is-invalid");
            feedback.textContent = mensajeError;
            feedback.style.color = "#dc3545";
            return false;
        }
    }

    // Escuchadores en tiempo real para las entradas de texto (Input e Input-Edición)
    $("#nombre_tipo_material").on("input", function() {
        validarCampo($(this), regexNombre, "El nombre debe tener entre 3 y 50 caracteres.");
    });

    $("#edit_activo_nombre").on("input", function() {
        validarCampo($(this), regexNombre, "El nombre debe tener entre 3 y 50 caracteres.");
    });

    // Carga de datos asíncrona adaptada al parámetro ajax=listar de tu controlador
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

    // Extracción de contingencia directa desde el HTML si la petición AJAX principal falla
    function extraerDatosDeTablaEstatica() {
        todosLosMateriales = [];
        $('#tablaTipoMaterial tbody tr').each(function() {
            const fila = $(this);
            const idAttr = fila.attr('id');
            if (!idAttr) return;
            const id = idAttr.replace('fila-', '');
            
            const btnEditar = fila.find('.btnEditarActivo');
            const btnInactivo = fila.find('.btnEditarInactivo');
            
            let nombre = "";
            let descripcion = "";
            let estado = "Activo";

            if (btnEditar.length > 0) {
                nombre = btnEditar.data('nombre');
                descripcion = btnEditar.data('descripcion');
                estado = "Activo";
            } else if (btnInactivo.length > 0) {
                nombre = btnInactivo.data('nombre');
                descripcion = fila.find('td:eq(1)').text().trim();
                estado = "Inactivo";
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

    // Renderizador dinámico de filas gobernado por la matriz reactiva y DataTables
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
                                data-descripcion="${descMat}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                                data-id="${mat.id_tipo_materia_prima}" 
                                data-nombre="${nombreMat}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>`;
                } else {
                    acciones = `
                        <button class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                                data-id="${mat.id_tipo_materia_prima}" 
                                data-nombre="${nombreMat}">
                            <i class="bi bi-pencil-square"></i> Editar / Reactivar
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

    // ACCIÓN: REGISTRAR (Sincronizado con el CASO B de tu controlador procedimental)
    $("#btnEnvio").on("click", function (e) {
        e.preventDefault();

        let inputNombre = $("#nombre_tipo_material");
        let inputDesc = $("#descripcion_tipo_material");

        let vNom = validarCampo(inputNombre, regexNombre, "El nombre debe tener entre 3 y 50 caracteres.");
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
                    Swal.fire({ icon: 'success', title: '¡Completado!', text: response.message, confirmButtonColor: '#10b981' });
                    $("#formTipoMaterial")[0].reset();
                    $("#formTipoMaterial").find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    $("#formTipoMaterial").find(".feedback-validacion").remove(); // Limpiar feedbacks
                    bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMaterial')).hide();
                    cargarMateriales();
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

        $('#edit_activo_id').val(id);
        $('#edit_activo_nombre').val(nombre).removeClass("is-invalid is-valid");
        $('#edit_activo_descripcion').val(descripcion).removeClass("is-invalid is-valid");
        $('#modalEditarActivo').find(".feedback-validacion").remove(); 
        
        new bootstrap.Modal(document.getElementById('modalEditarActivo')).show();
    });

    // ACCIÓN: GUARDAR CAMBIOS EDICIÓN ACTIVO (Sincronizado con el CASO A de tu controlador)
    $('#btnGuardarEdicionActivo').on('click', function(e) {
        e.preventDefault();
        
        let id = $('#edit_activo_id').val();
        let inputNombre = $('#edit_activo_nombre');
        let inputDesc = $('#edit_activo_descripcion');

        let vNom = validarCampo(inputNombre, regexNombre, "El nombre debe tener entre 3 y 50 caracteres.");
        if (!vNom) return;

        $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar',
            type: 'POST',
            data: {
                id_accion: id,             
                nuevo_estado: 'Activo',    
                nombre: inputNombre.val().trim(),
                descripcion: inputDesc.val().trim()
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({ icon: 'success', title: 'Actualizado', text: response.message, confirmButtonColor: '#10b981' });
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                    cargarMateriales();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                }
            }
        });
    });

    // ACCIÓN: ABRIR MODAL EDICIÓN INACTIVO (REACTIVAR)
    $(document).on('click', '.btnEditarInactivo', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#edit_id_material').val(id);
        $('#edit_nombre_material').val(nombre);
        $('#edit_status_material').val('Inactivo'); 

        new bootstrap.Modal(document.getElementById('modalEditarInactivo')).show();
    });

    // ACCIÓN: GUARDAR CAMBIOS EDICIÓN INACTIVO / REACTIVACIÓN (CASO A - Mutación rápida)
    $('#btnGuardarEdicionInactivo').on('click', function(e) {
        e.preventDefault();
        let id = $('#edit_id_material').val();
        let nuevoEstado = $('#edit_status_material').val(); 

        $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar',
            type: 'POST',
            data: {
                id_accion: id,
                nuevo_estado: nuevoEstado
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({ icon: 'success', title: 'Estado Modificado', text: response.message, confirmButtonColor: '#10b981' });
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo')).hide();
                    cargarMateriales();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                }
            }
        });
    });

    // ACCIÓN: INHABILITAR DIRECTO DESDE LA FILA ACTIVA (CASO A - Flujo rápido)
    $(document).on('click', '.btnCambiarEstado', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
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

    // Inicializar cargando el listado dinámico al entrar
    cargarMateriales();
});
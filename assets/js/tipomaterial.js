$(document).ready(function () {
    let tablaDataTable = null;
    let todosLosMateriales = [];
    let verEliminados = false;

    // Configuración centralizada de idioma en español para DataTables
    const lenguajeEspanolDataTables = {
        processing:     "Procesando...",
        search:         "Buscar:",
        lengthMenu:     "Mostrar _MENU_ registros",
        info:           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        infoEmpty:      "Mostrando registros del 0 al 0 de un total de 0 registros",
        infoFiltered:   "(filtrado de un total de _MAX_ registros)",
        loadingRecords: "Cargando...",
        zeroRecords:    "No se encontraron coincidencias",
        emptyTable:     "No hay registros disponibles en esta vista",
        paginate: {
            first:      "Primero",
            previous:   "Anterior",
            next:       "Siguiente",
            last:       "Último"
        },
        aria: {
            sortAscending:  ": Activar para ordenar la columna de manera ascendente",
            sortDescending: ": Activar para ordenar la columna de manera descendente"
        }
    };

    function bloqEntradaNoPermitida(input, tipoPatron) {
        if (!window.jQuery || !$.expresionesRegulares) return;

        input.on("keypress", function (e) {
            if (e.ctrlKey || e.altKey || e.metaKey || e.which < 32) return;

            const char = String.fromCharCode(e.which);
            const valActual = $(this).val();
            const selStart = this.selectionStart ?? valActual.length;
            const selEnd = this.selectionEnd ?? valActual.length;
            const nuevoVal = valActual.slice(0, selStart) + char + valActual.slice(selEnd);

            try {
                const patron = $.expresionesRegulares.obtener(tipoPatron);
                const fuente = patron.source.replace(/^\^|\$$/g, "");
                let regexParcial;

                if (fuente.includes("{")) {
                    const baseSinRango = fuente.replace(/\{[^}]+\}/, "+");
                    regexParcial = new RegExp("^" + baseSinRango + "$");
                } else {
                    regexParcial = new RegExp("^" + fuente + "$");
                }

                if (!regexParcial.test(nuevoVal)) {
                    e.preventDefault();
                }
            } catch (err) {}
        });

        input.on("paste", function (e) {
            const pastedText = (e.originalEvent || e).clipboardData?.getData("text") || "";
            const valActual = $(this).val();
            const selStart = this.selectionStart ?? valActual.length;
            const selEnd = this.selectionEnd ?? valActual.length;
            const nuevoVal = valActual.slice(0, selStart) + pastedText + valActual.slice(selEnd);

            try {
                if (!$.expresionesRegulares.validar(tipoPatron, nuevoVal)) {
                    e.preventDefault();
                }
            } catch (err) {}
        });
    }

    function validarCampoEntrada(input, tipoPatron, esRequerido = true) {
        if (!input || input.length === 0) return false;

        const valor = input.val().trim();
        let $feedback = input.siblings(".feedback-validacion");

        if ($feedback.length === 0) {
            $feedback =$('<small class="feedback-validacion form-text d-block"></small>');
            input.after($feedback);
        }

        if (valor === "") {
            if (esRequerido) {
                input.removeClass("is-valid").addClass("is-invalid");
                $feedback.text("Este campo es obligatorio.").css("color", "#dc3545").show();
                return false;
            } else {
                input.removeClass("is-valid is-invalid");
                $feedback.text("").hide();
                return true;
            }
        }

        if (window.jQuery && $.expresionesRegulares) {
            try {
                const esValido = $.expresionesRegulares.validar(tipoPatron, valor);

                if (esValido) {
                    input.removeClass("is-invalid").addClass("is-valid");
                    $feedback.text("").hide();
                    return true;
                } else {
                    input.removeClass("is-valid").addClass("is-invalid");
                    $feedback.text("El formato ingresado no es válido.").css("color", "#dc3545").show();
                    return false;
                }
            } catch (error) {}
        }

        input.removeClass("is-invalid").addClass("is-valid");
        $feedback.text("").hide();
        return true;
    }

    function configurarCampo(selector, tipoPatron, esRequerido = true) {
        const $el =$(selector);
        bloqEntradaNoPermitida($el, tipoPatron);$(document).on("input change", selector, function () {
            validarCampoEntrada($(this), tipoPatron, esRequerido);
        });
    }

    configurarCampo("#nombre_tipo_material", "nombre", true);
    configurarCampo("#descripcion_tipo_material", "descripcion", false);

    configurarCampo("#edit_activo_nombre", "nombre", true);
    configurarCampo("#edit_activo_descripcion", "descripcion", false);

    configurarCampo("#edit_nombre_material", "nombre", true);
    configurarCampo("#edit_descripcion_material", "descripcion", false);

    function limpiarFormulario(formSelector, inputsJQuery = null) {
        if (formSelector && $(formSelector).length > 0) {$(formSelector)[0].reset();
            $(formSelector).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');$(formSelector).find(".feedback-validacion").remove();
        } else if (inputsJQuery && inputsJQuery.length > 0) {
            inputsJQuery.removeClass("is-valid is-invalid");
            inputsJQuery.siblings(".feedback-validacion").remove();
        }
    }

    async function cargarMateriales() {
        try {
            const response = await $.ajax({
                url: 'index.php?controller=tipoMateriaPrima&action=listar&ajax=listar',
                type: 'GET',
                dataType: 'json'
            });

            todosLosMateriales = (response && Array.isArray(response.materiales)) ? response.materiales : [];
            renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');

        } catch (error) {
            extraerDatosDeTablaEstatica();
        }
    }

    async function enviarPeticion(dataPayload) {
        return await $.ajax({
            url: 'index.php?controller=tipoMateriaPrima&action=listar',
            type: 'POST',
            data: dataPayload,
            dataType: 'json'
        });
    }

    function extraerDatosDeTablaEstatica() {
        todosLosMateriales = [];
        $('#tablaTipoMaterial tbody tr').each(function () {
            const fila = $(this);
            const idAttr = fila.attr('id');
            if (!idAttr) return;

            const id = idAttr.replace('fila-', '');
            const btnEditar = fila.find('.btnEditarActivo, .btnEditarInactivo');

            if (btnEditar.length > 0) {
                const nombre = btnEditar.data('nombre');
                const descripcion = btnEditar.data('descripcion');
                const estado = fila.find('.badge').text().trim() === 'Inactivo' ? 'Inactivo' : 'Activo';

                if (nombre) {
                    todosLosMateriales.push({
                        id_tipo_materia_prima: id,
                        nombre_de_material: nombre,
                        descripcion: descripcion === 'Sin especificaciones' ? '' : descripcion,
                        status_tipo_materia: estado
                    });
                }
            }
        });
        renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
    }

    function renderizarTabla(estadoFiltro) {
        const tbody = $('#tablaTipoMaterial tbody');

        if ($.fn.DataTable.isDataTable('#tablaTipoMaterial')) {
            $('#tablaTipoMaterial').DataTable().destroy();
        }
        tbody.empty();

        const filtrados = todosLosMateriales.filter(mat => {
            const estadoReal = mat.status_tipo_materia || mat.status_tipo_material;
            return estadoReal === estadoFiltro;
        });

        if (filtrados.length > 0) {
            const fragmento = $(document.createDocumentFragment());

            filtrados.forEach(mat => {
                const nombreMat = mat.nombre_de_material || mat.nombre;
                const descMat = mat.descripcion || 'Sin especificaciones';
                const estadoReal = mat.status_tipo_materia || mat.status_tipo_material;

                const badge = estadoReal === 'Activo'
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

                fragmento.append(`
                    <tr id="fila-${mat.id_tipo_materia_prima}">
                        <td class="fw-bold">${nombreMat}</td>
                        <td>${descMat}</td>
                        <td>${badge}</td>
                        <td><div class="text-center">${acciones}</div></td>
                    </tr>
                `);
            });

            tbody.append(fragmento);
        }

        // Inicialización del DataTable con idioma en español garantizado
        tablaDataTable = $('#tablaTipoMaterial').DataTable({
            language: lenguajeEspanolDataTables,
            pageLength: 10,
            responsive: true,
            ordering: false
        });
    }

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        const $btn =$(this);

        if (verEliminados) {
            $btn.attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');$('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Tipos de Material Inhabilitados');
            renderizarTabla('Inactivo');
        } else {
            $btn.attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');$('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Tipo de Material');
            renderizarTabla('Activo');
        }
    });

    $('#modalRegistrarMaterial').on('hide.bs.modal', () => document.activeElement?.blur());
    $('#modalRegistrarMaterial').find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', () => {
        limpiarFormulario("#formTipoMaterial");
    });

    $('#modalEditarActivo').on('hide.bs.modal', () => document.activeElement?.blur());
    $('#modalEditarActivo').find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', () => {
        limpiarFormulario(null, $('#edit_activo_nombre, #edit_activo_descripcion'));
        $('#edit_activo_id').val('');
    });

    $('#modalEditarInactivo').on('hide.bs.modal', () => document.activeElement?.blur());
    $('#modalEditarInactivo').find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', () => {
        limpiarFormulario("#formEditarMaterial");
        $('#edit_id_material').val('');
    });

    $("#btnEnvio").on("click", async function (e) {
        e.preventDefault();

        const inputNombre = $("#nombre_tipo_material");
        const inputDesc = $("#descripcion_tipo_material");

        const esNombreValido = validarCampoEntrada(inputNombre, "nombre", true);
        const esDescValida = validarCampoEntrada(inputDesc, "descripcion", false);

        if (!esNombreValido || !esDescValida) return;

        try {
            const response = await enviarPeticion({
                nombre: inputNombre.val().trim(),
                descripcion: inputDesc.val().trim()
            });

            if (response.success) {
                document.activeElement?.blur();
                Swal.fire({
                    icon: 'success',
                    title: 'Completado',
                    text: response.message,
                    confirmButtonColor: '#10b981',
                    didClose: () => {
                        limpiarFormulario("#formTipoMaterial");
                        bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMaterial')).hide();
                        cargarMateriales();
                    }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error de Validación', text: response.message, confirmButtonColor: '#dc3545' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error inesperado de comunicación con el servidor.', confirmButtonColor: '#dc3545' });
        }
    });

    $(document).on('click', '.btnEditarActivo', function () {
        const btn = $(this);
        limpiarFormulario(null, $('#edit_activo_nombre, #edit_activo_descripcion'));

        $('#edit_activo_id').val(btn.data('id'));
        $('#edit_activo_nombre').val(btn.data('nombre'));
        $('#edit_activo_descripcion').val(btn.data('descripcion'));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarActivo')).show();
    });

    $('#formEditarActivo').on('submit', async function (e) {
        e.preventDefault();

        const id = $('#edit_activo_id').val();
        const inputNombre = $('#edit_activo_nombre');
        const inputDesc = $('#edit_activo_descripcion');

        const esNombreValido = validarCampoEntrada(inputNombre, "nombre", true);
        const esDescValida = validarCampoEntrada(inputDesc, "descripcion", false);

        if (!esNombreValido || !esDescValida) return;

        try {
            const response = await enviarPeticion({
                id_accion: id,
                nombre: inputNombre.val().trim(),
                descripcion: inputDesc.val().trim()
            });

            if (response.success) {
                document.activeElement?.blur();
                Swal.fire({
                    icon: 'success',
                    title: 'Actualizado',
                    text: response.message,
                    confirmButtonColor: '#10b981',
                    didClose: () => {
                        limpiarFormulario(null, $('#edit_activo_nombre, #edit_activo_descripcion'));
                        $('#edit_activo_id').val('');
                        bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                        cargarMateriales();
                    }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la actualización.', confirmButtonColor: '#dc3545' });
        }
    });

    $(document).on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        document.activeElement?.blur();

        Swal.fire({
            title: '¿Inhabilitar Tipo de Material?',
            text: `El registro "${nombre}" pasará a la lista de archivados.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await enviarPeticion({
                        id_accion: id,
                        nuevo_estado: 'Inactivo'
                    });

                    if (response.success) {
                        Swal.fire({ icon: 'success', title: 'Inhabilitado', text: response.message, confirmButtonColor: '#10b981' });
                        cargarMateriales();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cambiar el estado del registro.', confirmButtonColor: '#dc3545' });
                }
            }
        });
    });

    $(document).on('click', '.btnEditarInactivo', function () {
        const btn = $(this);
        limpiarFormulario("#formEditarMaterial");

        $('#edit_id_material').val(btn.data('id'));
        $('#edit_nombre_material').val(btn.data('nombre'));
        $('#edit_descripcion_material').val(btn.data('descripcion') || '');

        if ($('#edit_status_material').length > 0) {
            $('#edit_status_material').val(btn.data('estado') || 'Inactivo');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarInactivo')).show();
    });

    $('#formEditarMaterial').on('submit', async function (e) {
        e.preventDefault();

        const id = $('#edit_id_material').val();
        const inputNombre = $('#edit_nombre_material');
        const inputDesc = $('#edit_descripcion_material');
        const nuevoEstado = $('#edit_status_material').val();

        const esNombreValido = validarCampoEntrada(inputNombre, "nombre", true);
        const esDescValida = validarCampoEntrada(inputDesc, "descripcion", false);

        if (!esNombreValido || !esDescValida) return;

        try {
            const response = await enviarPeticion({
                id_accion: id,
                nombre: inputNombre.val().trim(),
                descripcion: inputDesc.val().trim(),
                nuevo_estado: nuevoEstado
            });

            if (response.success) {
                document.activeElement?.blur();
                Swal.fire({
                    icon: 'success',
                    title: 'Actualizado',
                    text: response.message || 'El registro ha sido actualizado correctamente.',
                    confirmButtonColor: '#10b981',
                    didClose: () => {
                        limpiarFormulario("#formEditarMaterial");
                        $('#edit_id_material').val('');
                        bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo')).hide();
                        cargarMateriales();
                    }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la solicitud con el servidor.', confirmButtonColor: '#dc3545' });
        }
    });

    cargarMateriales();
});
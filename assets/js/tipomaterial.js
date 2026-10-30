import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { obtenerPatron, validar } from './helpers/expresiones.js';

document.addEventListener(
    'DOMContentLoaded',
    function () {
        let tablaDataTable = null;
        let verEliminados = false;

        function bloqEntradaNoPermitida(input, tipoPatron) {
            input.on("keypress", function (e) {
                if (e.ctrlKey || e.altKey || e.metaKey || e.which < 32) return;

                const char = String.fromCharCode(e.which);
                const valActual = $(this).val();
                const selStart = this.selectionStart ?? valActual.length;
                const selEnd = this.selectionEnd ?? valActual.length;
                const nuevoVal = valActual.slice(0, selStart) + char + valActual.slice(selEnd);

                try {
                    const patron = obtenerPatron(tipoPatron);
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
                    if (!validar(tipoPatron, nuevoVal)) {
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
                $feedback = $('<small class="feedback-validacion form-text d-block"></small>');
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

            const esValido = validar(tipoPatron, valor);
            if (esValido) {
                input.removeClass("is-invalid").addClass("is-valid");
                $feedback.text("").hide();
                return true;
            }

            input.removeClass("is-valid").addClass("is-invalid");
            $feedback.text("El formato ingresado no es válido.").css("color", "#dc3545").show();
            return false;
        }

        function configurarCampo(selector, tipoPatron, esRequerido = true) {
            const $el = $(selector);
            bloqEntradaNoPermitida($el, tipoPatron);
            $(document).on("input change", selector, function () {
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
            if (formSelector && $(formSelector).length > 0) {
                $(formSelector)[0].reset();
                $(formSelector).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                $(formSelector).find(".feedback-validacion").remove();
            } else if (inputsJQuery && inputsJQuery.length > 0) {
                inputsJQuery.removeClass("is-valid is-invalid");
                inputsJQuery.siblings(".feedback-validacion").remove();
            }
        }

        // ==========================================
        // ESTRUCTURA DATATABLE BASADA EN TU IMAGEN[cite: 2]
        // ==========================================
        function inicializarDataTable() {
            if ($.fn.DataTable.isDataTable('#tablaTipoMaterial')) {
                $('#tablaTipoMaterial').DataTable().destroy();
            }

            const estadoFiltro = verEliminados ? 'Inactivo' : 'Activo';

            tablaDataTable = $('#tablaTipoMaterial').DataTable({
                ajax: {
                    url: 'index.php?controller=tipoMateriaPrima&action=listar&ajax=listar',
                    method: 'GET',
                    data: { Consultar: 'yes', estado: estadoFiltro },
                    dataSrc: function (json) {
                        const lista = (json && Array.isArray(json.materiales)) ? json.materiales : [];
                        // Filtramos en el cliente según el estado activo/inactivo actual
                        return lista.filter(mat => {
                            const estadoReal = mat.status_tipo_materia || mat.status_tipo_material;
                            return estadoReal === estadoFiltro;
                        });
                    }
                },
                columns: [
                    {
                        data: 'nombre_de_material',
                        render: function (data, type, row) {
                            const nombre = data || row.nombre || '';
                            return `<span class="fw-bold">${nombre}</span>`;
                        }
                    },
                    {
                        data: 'descripcion',
                        render: function (data) {
                            return data && data.trim() !== '' ? data : 'Sin especificaciones';
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            const estadoReal = data.status_tipo_materia || data.status_tipo_material;
                            return estadoReal === 'Activo'
                                ? '<span class="badge bg-success">Activo</span>'
                                : '<span class="badge bg-danger">Inactivo</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data) {
                            const id = data.id_tipo_materia_prima;
                            const nombreMat = data.nombre_de_material || data.nombre;
                            const descMat = data.descripcion || 'Sin especificaciones';
                            const estadoReal = data.status_tipo_materia || data.status_tipo_material;

                            if (estadoReal === 'Activo') {
                                return `
                                    <button class="btn btn-sm btn-outline-primary btnEditarActivo me-1" 
                                            data-id="${id}" 
                                            data-nombre="${nombreMat}" 
                                            data-descripcion="${descMat}"
                                            data-estado="${estadoReal}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                                            data-id="${id}" 
                                            data-nombre="${nombreMat}"
                                            data-estado="Inactivo">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>`;
                            } else {
                                return `
                                    <button class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                                            data-id="${id}" 
                                            data-nombre="${nombreMat}"
                                            data-descripcion="${descMat}"
                                            data-estado="${estadoReal}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>`;
                            }
                        }
                    }
                ],
                processing: true,
                responsive: true,
                pageLength: 10,
                language: lenguajeDataTableEs,
                ordering: false
            });
        }

        async function enviarPeticion(dataPayload) {
            return await $.ajax({
                url: 'index.php?controller=tipoMateriaPrima&action=listar',
                type: 'POST',
                data: dataPayload,
                dataType: 'json'
            });
        }

        $('#btnAlternarEstado').on('click', function () {
            verEliminados = !verEliminados;
            const $btn = $(this);

            if (verEliminados) {
                $btn.attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');
                $('#txtBotonEstado').text('Ver Activos');
                $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                $('#tituloVista').text('Tipos de Material Inhabilitados');
            } else {
                $btn.attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
                $('#txtBotonEstado').text('Ver inhabilitados');
                $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                $('#tituloVista').text('Tipo de Material');
            }
            inicializarDataTable();
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
                    await successAlert('Completado', response.message);
                    limpiarFormulario("#formTipoMaterial");
                    const modalEl = document.getElementById('modalRegistrarMaterial');
                    if (modalEl) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    inicializarDataTable();
                } else {
                    await errorAlert('Error de Validación', response.message);
                }
            } catch (error) {
                await errorAlert('Error', 'Error inesperado de comunicación con el servidor.');
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
                    await successAlert('Actualizado', response.message);
                    limpiarFormulario(null, $('#edit_activo_nombre, #edit_activo_descripcion'));
                    $('#edit_activo_id').val('');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                    inicializarDataTable();
                } else {
                    await errorAlert('Error', response.message);
                }
            } catch (error) {
                await errorAlert('Error', 'Error al procesar la actualización.');
            }
        });

        $(document).on('click', '.btnCambiarEstado', function () {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            document.activeElement?.blur();

            confirmDeleteAlert(async () => {
                try {
                    const response = await enviarPeticion({
                        id_accion: id,
                        nuevo_estado: 'Inactivo'
                    });

                    if (response.success) {
                        await successAlert('Inhabilitado', response.message);
                        inicializarDataTable();
                    } else {
                        await errorAlert('Error', response.message);
                    }
                } catch (error) {
                    await errorAlert('Error', 'No se pudo cambiar el estado del registro.');
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
                    await successAlert('Actualizado', response.message || 'El registro ha sido actualizado correctamente.');
                    limpiarFormulario("#formEditarMaterial");
                    $('#edit_id_material').val('');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo')).hide();
                    inicializarDataTable();
                } else {
                    await errorAlert('Error', response.message);
                }
            } catch (error) {
                await errorAlert('Error', 'Error al procesar la solicitud con el servidor.');
            }
        });

        // Inicialización inicial al cargar el DOM
        inicializarDataTable();
    }
);
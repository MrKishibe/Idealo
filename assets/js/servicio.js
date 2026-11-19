import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';

//NO TOCAR LO QUE YA FUNCIONA, PORQUE LUEGO LO DAÑAN

$(document).ready(function () {
    const $txtNombre = $("#nombre_servicio");
    const $editNombre = $("#edit_nombre_servicio");
    const urlModulo = 'index.php?controller=servicio';

    let datatableInstancia = null;
    let verEliminados = false;

    const regexCaracteresPermitidos = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/;
    const regexNombreCompleto = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]{3,50}$/;

    function bloqEntradaNoPermitida($input) {
        $input.on("keypress", function (e) {
            if (e.ctrlKey || e.altKey || e.metaKey || e.which < 32) return;
            const char = String.fromCharCode(e.which);
            if (!regexCaracteresPermitidos.test(char)) {
                e.preventDefault();
            }
        });

        $input.on("paste", function (e) {
            const pasted = (e.originalEvent || e).clipboardData?.getData("text") || "";
            const valActual = $(this).val();
            const nuevoVal = valActual.slice(0, this.selectionStart ?? valActual.length) + pasted + valActual.slice(this.selectionEnd ?? valActual.length);

            if (!regexCaracteresPermitidos.test(nuevoVal) || nuevoVal.length > 50) {
                e.preventDefault();
            }
        });
    }

    function validarCampoEntrada($input, esRequerido = true) {
        if (!$input || !$input.length) return false;
        const valor = $input.val().trim();
        let $feedback = $input.siblings(".feedback-validacion");

        if (!$feedback.length) {
            $feedback = $('<small class="feedback-validacion form-text d-block"></small>').insertAfter($input);
        }

        if (!valor) {
            $input.toggleClass("is-invalid", esRequerido).toggleClass("is-valid", !esRequerido);
            $feedback.text(esRequerido ? "Este campo es obligatorio." : "").css("color", "#dc3545").toggle(esRequerido);
            return !esRequerido;
        }

        if (valor.length < 3 || valor.length > 50) {
            $input.removeClass("is-valid").addClass("is-invalid");
            $feedback.text("El nombre debe tener entre 3 y 50 caracteres.").css("color", "#dc3545").show();
            return false;
        }

        const esValido = regexNombreCompleto.test(valor);
        $input.toggleClass("is-valid", esValido).toggleClass("is-invalid", !esValido);
        $feedback.text(esValido ? "Nombre válido" : "Solo se permiten letras y números.")
                 .css("color", esValido ? "#198754" : "#dc3545").show();

        return esValido;
    }

    function configurarCampo(selector, esRequerido = true) {
        const $el = $(selector);
        bloqEntradaNoPermitida($el);
        $(document).on("input change", selector, function () {
            validarCampoEntrada($el, esRequerido);
        });
    }

    configurarCampo("#nombre_servicio", true);
    configurarCampo("#edit_nombre_servicio", true);

    function limpiarFormulario(formSelector, inputsJQuery = null) {
        const $target = formSelector ? $(formSelector) : inputsJQuery;
        if (!$target || !$target.length) return;
        if (formSelector && $target[0]) $target[0].reset();
        $target.find('.is-valid, .is-invalid').addBack('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $target.siblings(".feedback-validacion").add($target.find(".feedback-validacion")).remove();
    }

    function prepararModal(idModal, $input, selectorForm) {
        const $el = $(`#${idModal}`);
        if (!$el.length) return null;
        const bsModal = bootstrap.Modal.getOrCreateInstance($el[0]);
        
        $el.on('shown.bs.modal', () => $input.focus());
        $el.on('hide.bs.modal', () => { 
            $(document.activeElement).blur(); 
            limpiarFormulario(null, $input); 
        });
        
        $el.find('[data-bs-dismiss="modal"]:not(.btn-close)').on('click', () => limpiarFormulario(selectorForm));
        return bsModal;
    }

    const bsModalRegistrar = prepararModal('modalRegistrarServicio', $txtNombre, "#formRegistrarServicio");
    const bsModalEditar = prepararModal('modalEditarServicio', $editNombre, "#formEditarServicio");

    async function registrarServicioAjax(formData, formElement, bsModal) {
        try {
            const data = await $.ajax({
                url: `${urlModulo}&action=guardar`,
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false
            });

            $(document.activeElement).blur();
            
            if (data.success) {
                await successAlert('¡Registrado!', data.message);
                limpiarFormulario(formElement);
                bsModal?.hide();
                window.recargarTablaServicios();
            } else {
                await errorAlert('Error', data.message);
                bsModal?.show();
            }
        } catch (err) {
            await errorAlert('Error', 'Error de comunicación con el servidor.');
        }
    }

    $("#formRegistrarServicio").on("submit", async function (e) {
        e.preventDefault();
        if (!validarCampoEntrada($txtNombre, true)) return;
        await registrarServicioAjax(new FormData(this), this, bsModalRegistrar);
    });

    async function editarServicioAjax(formData, formElement, bsModal) {
        try {
            const data = await $.ajax({
                url: `${urlModulo}&action=editar`,
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false
            });

            $(document.activeElement).blur();
            
            if (data.success) {
                await successAlert('¡Actualizado!', data.message);
                limpiarFormulario(formElement);
                bsModal?.hide();
                window.recargarTablaServicios();
            } else {
                await errorAlert('Error', data.message);
                bsModal?.show();
            }
        } catch (err) {
            await errorAlert('Error', 'Error de comunicación con el servidor.');
        }
    }

    $("#formEditarServicio").on("submit", async function (e) {
        e.preventDefault();
        if (!validarCampoEntrada($editNombre, true)) return;
        await editarServicioAjax(new FormData(this), this, bsModalEditar);
    });

    window.abrirModalEditarServicio = function (data) {
        $('#edit_id_servicio').val(data.id_servicio);
        $('#edit_nombre_servicio').val(data.nombre_servicio);
        limpiarFormulario(null, $('#edit_nombre_servicio'));

        const esInactivo = data.status_servicio === 'inactivo';
        $('#edit_status_servicio').val(esInactivo ? 'inactivo' : 'activo');
        $('#contenedor_edit_estado').toggle(esInactivo);

        bsModalEditar?.show();
    };

    function inicializarDataTableServicios() {
        if ($.fn.DataTable.isDataTable('#tablaServicios')) {
            datatableInstancia.destroy();
        }

        const modo = verEliminados ? 'eliminados' : 'activos';

        datatableInstancia = $('#tablaServicios').DataTable({
            ajax: {
                url: `${urlModulo}&action=listarServiciosAjax`,
                type: 'GET',
                dataType: 'json',
                dataSrc: function (json) {
                    if (json && json.error) return [];
                    const lista = Array.isArray(json) ? json : [];
                    return lista.filter(s => modo === 'eliminados' ? s.status_servicio !== 'activo' : s.status_servicio === 'activo');
                },
                error: function () {
                    console.error('Error al cargar servicios por AJAX');
                }
            },
            columns: [
                {
                    data: 'nombre_servicio',
                    type: 'string',
                    className: 'px-4',
                    render: function (data) {
                        // Nombre en negrita idéntico al estilo de la referencia
                        return `<div class="fw-bold text-dark">${data || ''}</div>`;
                    }
                },
                {
                    data: 'status_servicio',
                    type: 'string',
                    className: 'px-4',
                    render: function (data) {
                        const esActivo = data === 'activo';
                        // Insignia (badge) con fondo suave, esquinas redondeadas y tipografía nítida idéntica a la imagen
                        return `<span class="badge bg-${esActivo ? 'success' : 'danger'} text-white" style="padding: 5px 10px; border-radius: 4px; font-weight: 500;">${esActivo ? 'Activo' : 'Inactivo'}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'px-4 text-center',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        const servicioJson = JSON.stringify(data).replace(/'/g, "&apos;");
                        let btnAcciones = `<button class="btn btn-sm btn-outline-primary btn-editar me-1" style="border-radius:6px;" data-servicio='${servicioJson}'><i class="bi bi-pencil-square"></i></button>`;

                        if (data.status_servicio === 'activo') {
                            btnAcciones += `<button class="btn btn-sm btn-outline-danger btn-eliminar" style="border-radius:6px;" data-id="${data.id_servicio}" data-nombre="${data.nombre_servicio}"><i class="bi bi-trash3"></i></button>`;
                        }
                        return btnAcciones;
                    }
                }
            ],
            processing: true,
            language: {
                ...lenguajeDataTableEs,
                processing: crearMarkupSkeletonDataTable($('#tablaServicios thead th').length)
            },
            pageLength: 10,
            responsive: true,
            ordering: true,
            order: [[0, 'asc']],
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    window.recargarTablaServicios = function () {
        if (datatableInstancia) {
            datatableInstancia.ajax.reload(null, false);
        } else {
            inicializarDataTableServicios();
        }
    };

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        $(this).attr('data-vista', verEliminados ? 'eliminados' : 'activos')
               .toggleClass('btn-outline-secondary', !verEliminados)
               .toggleClass('btn-secondary', verEliminados);
        $('#txtBotonEstado').text(verEliminados ? 'Ver Activos' : 'Ver Inhabilitados');
        $('#iconoEstado').toggleClass('bi-eye-slash-fill', !verEliminados).toggleClass('bi-eye-fill', verEliminados);
        $('#tituloVista').text(verEliminados ? 'Servicios (Inhabilitados)' : 'Servicios');
        
        inicializarDataTableServicios();
    });

    $(document).on('click', '.btn-editar', function () {
        const data = $(this).data('servicio');
        if (data) window.abrirModalEditarServicio(data);
    });

    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        $(document.activeElement).blur();

        confirmDeleteAlert(async () => {
            try {
                const data = await $.ajax({ 
                    url: `${urlModulo}&action=eliminar&id=${id}`, 
                    type: 'GET', 
                    dataType: 'json' 
                });
                
                if (data.success) {
                    await successAlert('Inhabilitado', data.message);
                    window.recargarTablaServicios();
                } else {
                    await errorAlert('Error de base de datos', data.message);
                }
            } catch (err) {
                await errorAlert('Error de comunicación', 'No se pudo completar la solicitud.');
            }
        });
    });

    // Carga inicial de la tabla por AJAX
    inicializarDataTableServicios();
});
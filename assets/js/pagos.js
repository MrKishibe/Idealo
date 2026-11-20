import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { validarCampo, formatearFechaLatinoamericana } from './helpers/expresiones.js';
import { limpiarValidaciones, soloNumerosTeclado } from './helpers/validaciones.js';
import {
    crearXhrProgresoCarga,
    mostrarProgresoCargaArchivo,
    mostrarSkeletonCargaArchivo
} from './helpers/carga-archivo-skeleton.js';

console.log("Módulo de Pagos conectado correctamente");

$(document).ready(function () {
    let verInhabilitados = false;
    let tablaPagos;

    function escaparHtml(valor) {
        return $('<div>').text(valor ?? '').html();
    }

    function normalizarTexto(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function estabilizarEspacioReferencia() {
        $('input[name="referencia"]').each(function () {
            const $input = $(this);
            const $columna = $input.closest('.col-md-6, .col-md-4, .mb-3, .form-group');
            if ($columna.length) {
                $columna.css({
                    'min-height': '84px',
                    'position': 'relative'
                });
            }
            const $feedback = $input.siblings('.feedback-validacion, .invalid-feedback');
            if ($feedback.length) {
                $feedback.css({
                    'display': 'block',
                    'min-height': '18px',
                    'line-height': '1.2',
                    'margin-top': '0.2rem'
                });
            }
        });
    }

    function transformarSelectConBuscadorNativo($selectOriginal, placeholder = 'Escriba para filtrar...') {
        if (!$selectOriginal.length) return;

        const idSelect = $selectOriginal.attr('id') || 'sel_' + Math.random().toString(36).substr(2, 9);
        $(`#wrapper_${idSelect}`).remove();
        $(document).off('click.cierreBuscador_' + idSelect);

        $selectOriginal.addClass('d-none').hide();

        const $wrapper = $(`<div id="wrapper_${idSelect}" class="position-relative w-100"></div>`);
        const $inputBusqueda = $(`<input type="text" class="form-control" placeholder="${placeholder}" autocomplete="off">`);
        const $listaSugerencias = $(`<div class="list-group position-absolute start-0 end-0 shadow bg-white border rounded" style="top: 100%; max-height: 220px; overflow-y: auto; z-index: 1060; display: none;"></div>`);

        $wrapper.append($inputBusqueda).append($listaSugerencias);
        $selectOriginal.after($wrapper);

        let sincronizandoDesdeSelect = false;

        function ocultarSugerencias() {
            $listaSugerencias.stop(true, true).hide().empty();
        }

        function actualizarSugerencias(filtro = '') {
            $listaSugerencias.empty();
            const terminoNorm = normalizarTexto(filtro);
            const palabrasClave = terminoNorm.split(/\s+/).filter(Boolean);

            let opcionesVisibles = 0;

            $selectOriginal.find('option').each(function () {
                const $opt = $(this);
                const valorOpt = $opt.val();
                const textoOpt = $opt.text().trim();

                if (!valorOpt) return;

                const textoNorm = normalizarTexto(textoOpt);
                const coincide = palabrasClave.length === 0 || palabrasClave.every(palabra => textoNorm.includes(palabra));

                if (coincide) {
                    opcionesVisibles++;
                    const $item = $(`<button type="button" class="list-group-item list-group-item-action py-2 px-3 small text-start">${escaparHtml(textoOpt)}</button>`);

                    if (String($selectOriginal.val()) === String(valorOpt)) {
                        $item.addClass('active text-white');
                    }

                    $item.on('mousedown', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $selectOriginal.val(valorOpt).trigger('change');
                        $inputBusqueda.val(textoOpt).removeClass('is-invalid');
                        ocultarSugerencias();
                    });

                    $listaSugerencias.append($item);
                }
            });

            if (opcionesVisibles === 0) {
                $listaSugerencias.append(`<div class="list-group-item py-2 px-3 small text-muted text-center fst-italic">No se encontró ningún pedido que coincida.</div>`);
            }

            $listaSugerencias.show();
        }

        $inputBusqueda.on('focus', function () {
            if (!sincronizandoDesdeSelect) {
                actualizarSugerencias($(this).val());
            }
        });

        $inputBusqueda.on('click', function (e) {
            e.stopPropagation();
            if ($listaSugerencias.is(':visible')) {
                return;
            }
            actualizarSugerencias($(this).val());
        });

        $inputBusqueda.on('input', function () {
            $selectOriginal.val('').removeClass('is-valid is-invalid').trigger('change');
            actualizarSugerencias($(this).val());
        });

        $inputBusqueda.on('keydown', function (e) {
            if (e.key === 'Escape' || e.key === 'Tab') {
                ocultarSugerencias();
            }
        });

        $selectOriginal.on('change', function () {
            sincronizandoDesdeSelect = true;
            const valorSeleccionado = $(this).val();
            const textoSeleccionado = $(this).find('option:selected').text().trim();

            if (!valorSeleccionado) {
                $inputBusqueda.val('');
            } else {
                $inputBusqueda.val(textoSeleccionado);
            }
            sincronizandoDesdeSelect = false;
        });

        $(document).on('click.cierreBuscador_' + idSelect, function (e) {
            if (!$(e.target).closest($wrapper).length) {
                ocultarSugerencias();
            }
        });
    }

    function obtenerFechaHoraActual() {
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        return ahora.toISOString().slice(0, 16);
    }

    function limpiarFormularioModal(formSelector) {
        const $form = $(formSelector);
        if ($form.length) {
            $form[0].reset();
            limpiarValidaciones($form);
            $form.find('select.form-select').each(function () {
                $(this).val('').trigger('change');
            });
            $form.find('input[name="referencia"]').prop('disabled', false).attr('required', true);
            estabilizarEspacioReferencia();
        }
    }

    estabilizarEspacioReferencia();

    if ($('#select_registrar_pedido').length) {
        transformarSelectConBuscadorNativo($('#select_registrar_pedido'), 'Buscar pedido por descripción o cliente...');
    }

    if ($('#edit_id_pedido').length) {
        transformarSelectConBuscadorNativo($('#edit_id_pedido'), 'Buscar pedido por descripción o cliente...');
    }

    $(document).on('change', 'select[name="id_metodo_de_pago"]', function () {
        const textoMetodo = $(this).find('option:selected').text().toLowerCase();
        const $form = $(this).closest('form');
        const $inputRef = $form.find('input[name="referencia"]');

        if (textoMetodo.includes('efectivo')) {
            $inputRef.val('').prop('disabled', true).removeAttr('required');
            $inputRef.removeClass('is-invalid is-valid');
            $inputRef.siblings('.feedback-validacion, .invalid-feedback').text('').hide();
        } else {
            $inputRef.prop('disabled', false).attr('required', true);
        }

        validarCampo(this, {
            requerido: true,
            mensajeVacio: 'campo vacio',
            mensaje: 'campo vacio'
        });
        estabilizarEspacioReferencia();
    });

    $(document).on('change', 'select[name="id_pedido"]', function () {
        validarCampo(this, {
            requerido: true,
            mensajeVacio: 'campo vacio',
            mensaje: 'campo vacio'
        });
    });

    $(document).on('input keypress', 'input[name="referencia"]', function (e) {
        if ($(this).is(':disabled')) return;
        if (e.type === 'keypress') {
            soloNumerosTeclado(e);
        }
        let valorLimpio = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        if (this.value !== valorLimpio) {
            this.value = valorLimpio;
        }

        const mensajeError = this.value.trim() === '' ? 'campo vacio' : '6 números obligatorio';
        validarCampo(this, {
            patron: 'referenciaPago',
            mensajeVacio: 'campo vacio',
            mensaje: mensajeError
        });
        estabilizarEspacioReferencia();
    });

    $(document).on('input keypress', 'input[name="monto_pago"]', function (e) {
        let valor = this.value;
        if (e.type === 'keypress') {
            const charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
                e.preventDefault();
            }
            if (charCode === 46 && valor.indexOf('.') !== -1) {
                e.preventDefault();
            }
        }

        const mensajeError = this.value.trim() === '' ? 'campo vacio' : 'Ingrese un monto válido mayor a 0.';
        validarCampo(this, {
            patron: 'moneda',
            mensajeVacio: 'campo vacio',
            mensaje: mensajeError
        });
    });

    $(document).on('change input', 'input[name="fecha_pago"]', function () {
        validarCampo(this, {
            requerido: true,
            mensajeVacio: 'campo vacio',
            mensaje: 'campo vacio'
        });
    });

    if ($('#tablaPagos').length > 0) {
        tablaPagos = $('#tablaPagos').DataTable({
            processing: true,
            ajax: {
                url: 'index.php?controller=Finanzas&action=pagos&ajax=listar',
                method: 'GET',
                dataSrc: function (json) {
                    let registros = [];
                    if (json && json.success === false) {
                        console.error("Error en respuesta:", json.message);
                    } else if (json && typeof json === 'object') {
                        registros = json.datos || json.pagos || [];
                    } else if (Array.isArray(json)) {
                        registros = json;
                    }

                    return registros.filter(item => {
                        let estadoItem = item.estado || 'procesado';
                        let esInhabilitado = estadoItem === 'inhabilitado' || estadoItem === 'inactivo';
                        return verInhabilitados ? esInhabilitado : !esInhabilitado;
                    });
                }
            },
            columns: [
                { data: 'referencia', render: d => `<strong>${d || 'N/A'}</strong>` },
                {
                    data: 'id_pedido',
                    render: (d, t, r) => `${escaparHtml(r.descripcion_pedido || 'Pedido #' + d)}<br><small class="text-muted">${escaparHtml((r.nombre_razon_social || '') + ' ' + (r.apellido || ''))}</small>`
                },
                { data: 'monto_pago', render: d => `<span class="text-success fw-bold">$${parseFloat(d || 0).toFixed(2)}</span>` },
                { data: 'nombre_metodo', render: d => escaparHtml(d || 'N/A') },
                { data: 'fecha_pago', render: d => d ? formatearFechaLatinoamericana(d) : '' },
                {
                    data: 'estado',
                    render: d => (d === 'inhabilitado')
                        ? '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inhabilitado</span>'
                        : '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Procesado</span>'
                },
                {
                    data: null,
                    className: "text-center",
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        let esInhabilitado = data.estado === 'inhabilitado';
                        let jsonStr = JSON.stringify(data).replace(/'/g, "&apos;");
                        let btn = '';

                        if (data.comprobante_de_pago) {
                            btn += `<button type="button" class="btn btn-sm btn-outline-secondary btn-ver-comprobante me-1" style="border-radius:8px;" data-ruta="${escaparHtml(data.comprobante_de_pago)}" title="Ver Comprobante"><i class="bi bi-file-earmark-text-fill"></i></button>`;
                        }

                        btn += `<button type="button" class="btn btn-sm btn-outline-primary btn-editar-pago me-1" style="border-radius:8px;" data-pago='${jsonStr}' title="Editar Pago"><i class="bi bi-pencil-square"></i></button>`;

                        if (!esInhabilitado) {
                            btn += `<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-pago" style="border-radius:8px;" data-id="${data.id_pago}" title="Inhabilitar Pago"><i class="bi bi-trash-fill"></i></button>`;
                        }
                        return btn;
                    }
                }
            ],
            language: {
                ...lenguajeDataTableEs,
                processing: crearMarkupSkeletonDataTable(7)
            },
            pageLength: 10,
            responsive: true,
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    window.recargarDatos = function () {
        if (tablaPagos) {
            tablaPagos.ajax.reload(null, false);
        }
    };

    $('#btnAlternarEstado').on('click', function () {
        verInhabilitados = !verInhabilitados;
        let nombreVistaActual = $('#tituloVista').text();
        if (verInhabilitados) {
            $(this).attr('data-vista', 'inhabilitados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text(nombreVistaActual.includes('Inhabilitados') ? nombreVistaActual : nombreVistaActual + ' Inhabilitados');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver Inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text(nombreVistaActual.replace(' Inhabilitados', ''));
        }
        window.recargarDatos();
    });

    $(document).on('click', '#btnGenerarReportePagos, .btn-generar-reportes', function (e) {
        e.preventDefault();
        const modalEl = document.getElementById('modalFiltroReporte');
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        } else {
            errorAlert('Aviso', 'No se encontró la ventana de filtros de reporte.');
        }
    });

    $(document).on('click', '.btn-ver-comprobante', function () {
        const ruta = $(this).data('ruta');
        if (!ruta) {
            errorAlert('Aviso', 'No se encontró el archivo de comprobante.');
            return;
        }

        const $contenedor = $('#contenedorComprobante');
        $contenedor.empty().css('min-height', '300px');
        const quitarSkeleton = mostrarSkeletonCargaArchivo($contenedor[0], 'Cargando comprobante');

        const modalEl = document.getElementById('modalVerComprobante');
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        } else {
            quitarSkeleton();
            window.open(ruta, '_blank');
            return;
        }

        const extension = ruta.split('.').pop().toLowerCase();

        if (extension === 'pdf') {
            setTimeout(() => {
                quitarSkeleton();
                $contenedor.html(`<iframe src="${ruta}" style="width:100%; height:500px; border:none; border-radius: 8px;"></iframe>`);
            }, 350);
        } else if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
            const img = new Image();
            img.src = ruta;
            img.onload = function () {
                quitarSkeleton();
                $contenedor.html(`<img src="${ruta}" alt="Comprobante de Pago" class="img-fluid rounded shadow-sm" style="max-height: 500px; object-fit: contain;">`);
            };
            img.onerror = function () {
                quitarSkeleton();
                $contenedor.html(`<div class="alert alert-danger w-100">No se pudo cargar la imagen del comprobante.</div>`);
            };
        } else {
            quitarSkeleton();
            $contenedor.html(`<div class="alert alert-warning w-100">Este formato no se puede previsualizar directamente, pero puedes descargarlo.</div>`);
        }

        $('#btnDescargarComprobante').attr('href', ruta);
    });

    $(document).on('click', '.btn-editar-pago', function () {
        const data = $(this).data('pago');
        if (!data) return;
        $('#edit_id_pago').val(data.id_pago);
        $('#edit_id_pedido').val(data.id_pedido).trigger('change');
        $('#edit_monto_pago').val(data.monto_pago);
        $('#edit_id_metodo').val(data.id_metodo_de_pago).trigger('change');
        $('#edit_referencia').val(data.referencia);
        $('#edit_fecha').val(data.fecha_pago ? data.fecha_pago.replace(' ', 'T') : obtenerFechaHoraActual());
        $('#edit_status_pago').val(data.estado || 'procesado');
        estabilizarEspacioReferencia();
        new bootstrap.Modal(document.getElementById('modalEditarPago')).show();
    });

    $(document).on('submit', '.finanzas-form', async function (e) {
        e.preventDefault();
        let $formActual = $(this);
        const formData = new FormData(this);
        const $archivo = $formActual.find('input[type="file"]').first();
        if ($archivo.length && !$archivo.parent().hasClass('carga-archivo-control')) {
            $archivo.wrap('<div class="carga-archivo-control"></div>');
        }
        const archivo = $archivo[0];
        const quitarSkeleton = archivo && archivo.files && archivo.files.length
            ? mostrarSkeletonCargaArchivo($archivo.parent()[0], 'Subiendo comprobante')
            : null;
        const progreso = archivo && archivo.files && archivo.files.length
            ? mostrarProgresoCargaArchivo()
            : null;

        try {
            const opciones = {
                url: $formActual.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            };
            if (progreso) opciones.xhr = crearXhrProgresoCarga(progreso);

            const data = await $.ajax(opciones);

            if (data.success) {
                $('.modal.show').each(function () {
                    let modalInstance = bootstrap.Modal.getInstance(this);
                    if (modalInstance) modalInstance.hide();
                });
                limpiarFormularioModal($formActual);
                await successAlert('¡Éxito!', data.message || 'Operación realizada correctamente.');
                window.recargarDatos();
            } else {
                await errorAlert('Error de Validación', data.message || 'No se pudo procesar la solicitud.');
            }
        } catch (error) {
            await errorAlert('Error de Conexión', 'Fallo de comunicación con el servidor.');
        } finally {
            if (quitarSkeleton) quitarSkeleton();
            if (progreso) progreso.cerrar();
        }
    });

    $(document).on('click', '.btn-eliminar-pago', function () {
        let idPago = $(this).data('id');
        confirmDeleteAlert(async () => {
            try {
                const data = await $.ajax({
                    url: 'index.php?controller=Finanzas',
                    method: 'POST',
                    data: { accion: 'cambiar_estado', entidad: 'pago', id: idPago, nuevo_estado: 'inhabilitado' },
                    dataType: 'json'
                });

                if (data.success) {
                    await successAlert('Inhabilitado', data.message || 'El registro ha sido inhabilitado.');
                    window.recargarDatos();
                } else {
                    await errorAlert('Error', data.message || 'No se pudo cambiar el estado.');
                }
            } catch (error) {
                await errorAlert('Error', 'No se pudo contactar al servidor.');
            }
        });
    });
});
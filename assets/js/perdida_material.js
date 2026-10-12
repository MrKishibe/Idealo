import $ from 'jquery';
import { confirmActionAlert, statusAlert } from './helpers/alerts.js';
import {
    validarCampo as validarCampoCompartido,
    marcarCampo,
    patrones,
    obtenerFechaLocalISO
} from './helpers/expresiones.js';

$(document).ready(function () {
    const $tablaBody = $('#tbodyPerdidaMaterial');
    const $formRegistrarPerdida = $('#formRegistrarPerdida');
    const $formEditarPerdida = $('#formEditarPerdida');

    let perdidas = [];
    let tablaPerdidas;

    function validarCampo($campo) {
        const valor = String($campo.val() || '').trim();
        if ($campo.attr('type') === 'date' && $campo.closest('#formRegistrarPerdida').length) {
            $campo.attr('min', obtenerFechaLocalISO());
        }
        let esValido = $campo[0].checkValidity();

        if (esValido && $campo.attr('name') === 'motivo') {
            esValido = validarCampoCompartido($campo, patrones.descripcion, {
                requerido: false,
                estilo: true,
                feedback: false
            });
        }

        marcarCampo($campo, esValido, {
            estilo: true,
            feedback: false
        });
        return esValido;
    }

    function validarFormulario($form) {
        let formularioValido = true;

        $form.find('input:not([type="hidden"]), select, textarea').each(function () {
            if (!validarCampo($(this))) formularioValido = false;
        });

        return formularioValido;
    }

    function limpiarValidaciones($form) {
        $form.find('.is-valid, .is-invalid')
            .removeClass('is-valid is-invalid')
            .removeAttr('style');
    }

    async function confirmarAccion(titulo, texto) {
        return await confirmActionAlert(titulo, texto);
    }

    async function mostrarAlertaInformativa(titulo, texto, icono) {
        return await statusAlert(titulo, texto, icono);
    }

    function enviarFormulario($form, $modal) {
        if (!$form.length) return;

        const $submitButton = $form.find('button[type="submit"]').first();
        const textoOriginal = $submitButton.text();
        const actionUrl = $form.attr('action');

        if (!actionUrl) {
            mostrarAlertaInformativa('Error', 'URL de acción del formulario no encontrada.', 'error');
            return;
        }

        $submitButton.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        }).done(async function (data) {
            if (data.success) {
                await mostrarAlertaInformativa('¡Éxito!', data.message || 'Guardado correctamente.', 'success');
                if ($modal.length) $modal.modal('hide');
                $form[0].reset();
                actualizarCostoPerdida($form);
                limpiarValidaciones($form);
                fetchPerdidas();
            } else {
                await mostrarAlertaInformativa('Error', data.message || 'No se pudo procesar la solicitud.', 'error');
            }
        }).fail(async function (xhr, estado, error) {
            console.error('Error al enviar el formulario:', error || estado);
            await mostrarAlertaInformativa('Error de red', 'No se pudo conectar con el servidor.', 'error');
        }).always(function () {
            $submitButton.prop('disabled', false).text(textoOriginal);
        });
    }

    function fetchPerdidas() {
        $.ajax({
            url: 'index.php?url=perdidaMaterial/listar&accion=listar',
            type: 'GET',
            dataType: 'json'
        }).done(function (data) {
            if (data.success && Array.isArray(data.data)) {
                perdidas = data.data;
                renderizarTabla();
            } else if (Array.isArray(data)) {
                perdidas = data;
                renderizarTabla();
            } else {
                console.warn('Respuesta inesperada al cargar las pérdidas de material:', data);
            }
        }).fail(async function (xhr, estado, error) {
            console.error('Error al cargar las pérdidas de material:', error || estado);
            await mostrarAlertaInformativa('Error', 'No se pudieron cargar las pérdidas de material.', 'error');
        });
    }

    function renderizarTabla() {
        if (!$tablaBody.length) return;

        if (tablaPerdidas) tablaPerdidas.destroy();
        $tablaBody.empty();

        if (!Array.isArray(perdidas)) perdidas = [];

        perdidas.forEach(function (perdida) {
            const materiaPrimaLabel = perdida.nombre_materia_prima
                ? perdida.nombre_materia_prima + (perdida.unidad_de_medida ? ' (' + perdida.unidad_de_medida + ')' : '')
                : 'Materia prima no disponible';
            let produccionLabel = 'Orden #' + (perdida.id_produccion || 'N/A');
            if (perdida.descripcion_pedido) produccionLabel += ' - ' + perdida.descripcion_pedido;

            const $fila = $('<tr>');
            $fila.append($('<td>').append($('<strong>').text('#' + (perdida.id_perdida_material || ''))));
            $fila.append($('<td>').text(perdida.cantidad_perdida || 0));
            $fila.append($('<td>').text(perdida.fecha_de_registro || 'N/A'));
            $fila.append($('<td>').text(perdida.motivo || 'Sin motivo especificado'));
            $fila.append($('<td>').text('$' + (Number(perdida.costo_total_perdida || 0).toFixed(2))));
            $fila.append($('<td>').text(materiaPrimaLabel));
            $fila.append($('<td>').text(produccionLabel));

            const $botonEditar = $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-primary btnEditarPerdida'
            }).attr({
                'data-id_perdida': perdida.id_perdida_material || '',
                'data-cantidad': perdida.cantidad_perdida || '',
                'data-fecha': perdida.fecha_de_registro || '',
                'data-costo-total': perdida.costo_total_perdida || '',
                'data-id_produccion': perdida.id_produccion || '',
                'data-id_materia_prima': perdida.id_materia_prima || '',
                'data-motivo': perdida.motivo || ''
            }).append($('<i>', { class: 'bi bi-pencil-square' }));

            $fila.append($('<td>', { class: 'text-center' }).append($botonEditar));
            $tablaBody.append($fila);
        });

        tablaPerdidas = $('#tablaPerdidasMaterial').DataTable({
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados',
                sEmptyTable: 'Ningún dato disponible en esta tabla',
                sInfo: 'Mostrando del _START_ al _END_ de _TOTAL_ registros',
                sInfoEmpty: 'Mostrando del 0 al 0 de 0 registros',
                sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
                sSearch: 'Buscar:',
                oPaginate: {
                    sFirst: 'Primero',
                    sLast: 'Último',
                    sNext: 'Siguiente',
                    sPrevious: 'Anterior'
                }
            },
            pageLength: 10,
            responsive: true
        });
    }

    const $fechaRegistro = $formRegistrarPerdida.find('[name="fecha_de_registro"]');
    $fechaRegistro.attr('min', obtenerFechaLocalISO());

    function actualizarCostoPerdida($form) {
        const idProduccion = String($form.find('[name="id_produccion"]').val() || '');
        const idMateriaPrima = String($form.find('[name="id_materia_prima"]').val() || '');
        const cantidadPerdida = Number($form.find('[name="cantidad_perdida"]').val());
        const $orden = $form.find('[name="id_produccion"]');
        const $materiaPrima = $form.find('[name="id_materia_prima"]');
        const $costoUnitario = $form.find('[data-role="costo-unitario"]');
        const $costoTotal = $form.find('[name="costo_total_perdida"]');

        $orden[0].setCustomValidity('');
        $materiaPrima[0].setCustomValidity('');
        $costoUnitario.val('');
        $costoTotal.val('');

        if (!idProduccion || !idMateriaPrima) return;

        const consumos = JSON.parse($form.attr('data-consumos') || '[]');
        const consumo = consumos.find(function (item) {
            return String(item.id_produccion) === idProduccion
                && String(item.id_materia_prima) === idMateriaPrima;
        });

        if (!consumo) {
            const mensaje = 'No hay consumo registrado para la orden y materia prima seleccionadas.';
            $orden[0].setCustomValidity(mensaje);
            $materiaPrima[0].setCustomValidity(mensaje);
            return;
        }

        const costoUnitario = Number(consumo.costo_unitario);
        if (!Number.isFinite(costoUnitario) || costoUnitario < 0) {
            $orden[0].setCustomValidity('El costo unitario del consumo no es válido.');
            $materiaPrima[0].setCustomValidity('El costo unitario del consumo no es válido.');
            return;
        }

        $costoUnitario.val(costoUnitario.toFixed(2));
        if (Number.isFinite(cantidadPerdida) && cantidadPerdida > 0) {
            $costoTotal.val((costoUnitario * cantidadPerdida).toFixed(2));
        }
    }

    [$formRegistrarPerdida, $formEditarPerdida].forEach(function ($form) {
        $form.on('change input', '[name="id_produccion"], [name="id_materia_prima"], [name="cantidad_perdida"]', function () {
            actualizarCostoPerdida($form);
            validarCampo($form.find('[name="id_produccion"]'));
            validarCampo($form.find('[name="id_materia_prima"]'));
        });
    });

    [$formRegistrarPerdida, $formEditarPerdida].forEach(function ($form) {
        $form.on('input change', 'input:not([type="hidden"]), select, textarea', function () {
            validarCampo($(this));
        });
    });

    $formRegistrarPerdida.on('submit', async function (event) {
        event.preventDefault();
        actualizarCostoPerdida($(this));
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Registrar pérdida?', 'Se registrará una nueva pérdida de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalRegistrarPerdida'));
    });

    $formEditarPerdida.on('submit', async function (event) {
        event.preventDefault();
        actualizarCostoPerdida($(this));
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Guardar cambios?', 'Se actualizarán los datos de la pérdida de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalEditarPerdida'));
    });

    $('#formFiltrosReportePerdida').on('submit', async function (event) {
        event.preventDefault();

        const fechaDesde = $('#reporteFechaDesde').val();
        const fechaHasta = $('#reporteFechaHasta').val();
        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            await mostrarAlertaInformativa('Rango de fechas inválido', 'La fecha inicial no puede ser posterior a la fecha final.', 'warning');
            return;
        }

        const parametros = new URLSearchParams({ accion: 'reporte' });
        if (fechaDesde) parametros.set('fecha_desde', fechaDesde);
        if (fechaHasta) parametros.set('fecha_hasta', fechaHasta);

        const estadoProduccion = $('#reporteEstadoProduccion').val();
        if (estadoProduccion) parametros.set('estado_produccion', estadoProduccion);

        window.open(`index.php?url=perdidaMaterial/reporte&${parametros.toString()}`, '_blank');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFiltrosReportePerdida')).hide();
    });

    $('#tablaPerdidasMaterial tbody').on('click', '.btnEditarPerdida', function () {
        const $boton = $(this);

        $('#edit_id_perdida_material').val($boton.attr('data-id_perdida') || '');
        $('#edit_cantidad_perdida').val($boton.attr('data-cantidad') || '');
        $('#edit_fecha_de_registro').val($boton.attr('data-fecha') || '');
        $('#edit_id_produccion').val($boton.attr('data-id_produccion') || '');
        $('#edit_id_materia_prima').val($boton.attr('data-id_materia_prima') || '');
        $('#edit_costo_total_perdida').val($boton.attr('data-costo-total') || '');
        actualizarCostoPerdida($formEditarPerdida);
        $('#edit_motivo').val($boton.attr('data-motivo') || '');

        validarFormulario($formEditarPerdida);
        $('#modalEditarPerdida').modal('show');
    });

    fetchPerdidas();
});

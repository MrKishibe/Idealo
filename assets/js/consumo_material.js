$(document).ready(function () {
    const $tablaBody = $('#tbodyConsumos');
    const $formRegistrarConsumo = $('#formRegistrarConsumo');
    const $formEditarConsumo = $('#formEditarConsumo');

    let consumos = [];
    let tablaConsumos;

    function marcarCampo($campo, esValido) {
        $campo.removeClass('is-valid is-invalid')
            .addClass(esValido ? 'is-valid' : 'is-invalid')
            .css({
                'border-color': esValido ? 'green' : 'red',
                'box-shadow': esValido ? '0 0 5px green' : '0 0 5px red'
            });
    }

    function validarCampo($campo) {
        const valor = String($campo.val() || '').trim();
        let esValido = $campo[0].checkValidity();

        if (esValido && $campo.attr('name') === 'descripcion_de_consumo' && valor !== '') {
            esValido = $.expresionesRegulares.validar('descripcion', valor);
        }

        marcarCampo($campo, esValido);
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
        return await Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });
    }

    async function mostrarAlertaInformativa(titulo, texto, icono) {
        return await Swal.fire({
            title: titulo,
            text: texto,
            icon: icono,
            timer: 2000,
            showConfirmButton: false
        });
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
                limpiarValidaciones($form);
                fetchConsumos();
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

    function fetchConsumos() {
        $.ajax({
            url: 'index.php?controller=consumoMaterial&action=listar&accion=listar',
            type: 'GET',
            dataType: 'json'
        }).done(function (data) {
            if (data.success && Array.isArray(data.data)) {
                consumos = data.data;
            } else if (Array.isArray(data)) {
                consumos = data;
            } else {
                consumos = [];
                console.warn('Respuesta inesperada al cargar los consumos:', data);
            }
            renderizarTabla();
        }).fail(async function (xhr, estado, error) {
            console.error('Error al cargar los consumos:', error || estado);
            consumos = [];
            renderizarTabla();
            await mostrarAlertaInformativa('Error', 'No se pudieron cargar los consumos de material.', 'error');
        });
    }

    function renderizarTabla() {
        if (!$tablaBody.length) return;

        if (tablaConsumos) tablaConsumos.destroy();
        $tablaBody.empty();

        if (!Array.isArray(consumos)) consumos = [];

        consumos.forEach(function (consumo) {
            const costoUnitario = Number(consumo.costo_unitario || 0);
            const cantidadUsada = Number(consumo.cantidad_usada || 0);
            const costoTotal = (costoUnitario * cantidadUsada).toFixed(2);

            const $fila = $('<tr>');
            $fila.append($('<td>', { class: 'fw-bold' }).text('#' + (consumo.id_consumo_material || '')));

            const $material = $('<td>')
                .append($('<div>', { class: 'fw-bold text-dark' }).text(consumo.nombre_materia_prima || 'Sin material'))
                .append(
                    $('<small>', { class: 'text-muted' })
                        .append($('<i>', { class: 'bi bi-info-circle' }))
                        .append(' ' + (consumo.descripcion_de_consumo || 'Sin descripción'))
                );
            $fila.append($material);

            $fila.append($('<td>', { class: 'fw-bold text-muted' }).text('$' + costoUnitario.toFixed(2)));
            $fila.append($('<td>', { class: 'fw-bold' }).text((consumo.cantidad_usada || 0) + ' ' + (consumo.unidad_de_medida || '')));
            $fila.append($('<td>', { class: 'text-success fw-bold' }).text('$' + costoTotal));
            $fila.append(
                $('<td>').append(
                    $('<span>', { class: 'badge bg-secondary' })
                        .text('OP-' + String(consumo.id_produccion || 0).padStart(4, '0'))
                )
            );

            const $botonEditar = $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-primary btnEditarConsumo'
            }).attr({
                'data-id_consumo_material': consumo.id_consumo_material || '',
                'data-id_materia_prima': consumo.id_materia_prima || '',
                'data-costo_unitario': consumo.costo_unitario || '',
                'data-cantidad_usada': consumo.cantidad_usada || '',
                'data-descripcion_de_consumo': consumo.descripcion_de_consumo || '',
                'data-id_produccion': consumo.id_produccion || ''
            }).append($('<i>', { class: 'bi bi-pencil-square' }));

            $fila.append(
                $('<td>').append(
                    $('<div>', { class: 'text-center d-flex justify-content-center gap-1' }).append($botonEditar)
                )
            );
            $tablaBody.append($fila);
        });

        tablaConsumos = $('#tablaConsumos').DataTable({
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

    [$formRegistrarConsumo, $formEditarConsumo].forEach(function ($form) {
        $form.on('input change', 'input:not([type="hidden"]), select, textarea', function () {
            validarCampo($(this));
        });
    });

    $formRegistrarConsumo.on('submit', async function (event) {
        event.preventDefault();
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Registrar consumo?', 'Se registrará el consumo de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalRegistrarConsumo'));
    });

    $formEditarConsumo.on('submit', async function (event) {
        event.preventDefault();
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Guardar cambios?', 'Se actualizarán los datos del consumo de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalEditarConsumo'));
    });

    $('#formFiltrosReporteConsumo').on('submit', async function (event) {
        event.preventDefault();

        const parametros = new URLSearchParams({ accion: 'reporte' });
        const idMateriaPrima = $('#reporteMateriaPrima').val();
        const estadoProduccion = $('#reporteEstadoProduccion').val();
        if (idMateriaPrima) parametros.set('id_materia_prima', idMateriaPrima);
        if (estadoProduccion) parametros.set('estado_produccion', estadoProduccion);

        window.open(`index.php?controller=consumoMaterial&action=listar&${parametros.toString()}`, '_blank');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFiltrosReporteConsumo')).hide();
    });

    $('#tablaConsumos tbody').on('click', '.btnEditarConsumo', function () {
        const $boton = $(this);

        $('#edit_id_consumo_material').val($boton.attr('data-id_consumo_material') || '');
        $('#edit_id_materia_prima').val($boton.attr('data-id_materia_prima') || '');
        $('#edit_id_produccion').val($boton.attr('data-id_produccion') || '');
        $('#edit_costo_unitario').val($boton.attr('data-costo_unitario') || '');
        $('#edit_cantidad_usada').val($boton.attr('data-cantidad_usada') || '');
        $('#edit_descripcion_de_consumo').val($boton.attr('data-descripcion_de_consumo') || '');

        validarFormulario($formEditarConsumo);
        $('#modalEditarConsumo').modal('show');
    });

    fetchConsumos();
});

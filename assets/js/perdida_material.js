$(document).ready(function () {
    const $tablaBody = $('#tbodyPerdidaMaterial');
    const $formRegistrarPerdida = $('#formRegistrarPerdida');
    const $formEditarPerdida = $('#formEditarPerdida');

    let perdidas = [];
    let tablaPerdidas;

    function obtenerFechaLocalISO() {
        const fecha = new Date();
        fecha.setHours(0, 0, 0, 0);
        const anio = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return anio + '-' + mes + '-' + dia;
    }

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
        if ($campo.attr('type') === 'date' && $campo.closest('#formRegistrarPerdida').length) {
            $campo.attr('min', obtenerFechaLocalISO());
        }
        let esValido = $campo[0].checkValidity();

        if (esValido && $campo.attr('name') === 'motivo') {
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
            let produccionLabel;
            if (perdida.descripcion_pedido) {
                produccionLabel = perdida.cantidad_detalle
                    ? 'Pedido de ' + perdida.cantidad_detalle + ' ' + perdida.descripcion_pedido
                    : perdida.descripcion_pedido;
            } else {
                produccionLabel = 'Orden #' + (perdida.id_produccion || 'N/A');
            }

            const $fila = $('<tr>');
            $fila.append($('<td>').append($('<strong>').text('#' + (perdida.id_perdida_material || ''))));
            $fila.append($('<td>').text(perdida.cantidad_perdida || 0));
            $fila.append($('<td>').text(perdida.fecha_de_registro || 'N/A'));
            $fila.append($('<td>').text(perdida.motivo || 'Sin motivo especificado'));
            $fila.append($('<td>').text('$' + (perdida.costo_unitario || '0.00')));
            $fila.append($('<td>').text(produccionLabel));

            const $botonEditar = $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-primary btnEditarPerdida'
            }).attr({
                'data-id_perdida': perdida.id_perdida_material || '',
                'data-cantidad': perdida.cantidad_perdida || '',
                'data-fecha': perdida.fecha_de_registro || '',
                'data-costo': perdida.costo_unitario || '',
                'data-id_produccion': perdida.id_produccion || '',
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

    [$formRegistrarPerdida, $formEditarPerdida].forEach(function ($form) {
        $form.on('input change', 'input:not([type="hidden"]), select, textarea', function () {
            validarCampo($(this));
        });
    });

    $formRegistrarPerdida.on('submit', async function (event) {
        event.preventDefault();
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
        $('#edit_costo_unitario').val($boton.attr('data-costo') || '');
        $('#edit_id_produccion').val($boton.attr('data-id_produccion') || '');
        $('#edit_motivo').val($boton.attr('data-motivo') || '');

        validarFormulario($formEditarPerdida);
        $('#modalEditarPerdida').modal('show');
    });

    fetchPerdidas();
});

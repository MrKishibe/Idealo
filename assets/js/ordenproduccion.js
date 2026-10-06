$(document).ready(function () {
    const urlControlador = 'index.php?url=ordenproduccion/listarordenproduccion';


    let tablaOrdenes = $('#tablaOrdenProduccion').DataTable({
        ajax: {
            url: urlControlador,
            type: 'GET',
            data: { accion: 'listar' },
            dataSrc: function (json) {
                return json.success ? json.data : [];
            }
        },
        columns: [
            { data: 'id_produccion' },
            { data: 'fecha_de_inicio' },
            { data: 'fecha_terminado', defaultContent: 'No definida' },
            { data: 'descripcion_pedido' },
            { data: 'estado_de_produccion' },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-sm btn-primary btn-editar" data-orden='${JSON.stringify(row)}'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-eliminar" data-id="${row.id_produccion}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            emptyTable: 'Ningún dato disponible en esta tabla',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
            infoFiltered: '(filtrado de un total de _MAX_ registros)',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron resultados',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        },
        responsive: true
    });

    
    function validarCampo(input, patron, obligatorio = true) {
        let valor = input.val().trim();
        
        
        if (!obligatorio && valor === '') {
            marcarCampo(input, true);
            return true;
        }

        
        let esValido = $.expresionesRegulares.validar(patron, valor);
        marcarCampo(input, esValido);
        return esValido;
    }

    function marcarCampo(input, esValido) {
        if (esValido) {
            input.removeClass('is-invalid').addClass('is-valid');
            input.css({ 'border-color': 'green', 'box-shadow': '0 0 5px green' });
        } else {
            input.removeClass('is-valid').addClass('is-invalid');
            input.css({ 'border-color': 'red', 'box-shadow': '0 0 5px red' });
        }
    }

    function obtenerFechaLocalISO(diasDesdeHoy = 0) {
        const fecha = new Date();
        fecha.setHours(0, 0, 0, 0);
        fecha.setDate(fecha.getDate() + diasDesdeHoy);
        const anio = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${anio}-${mes}-${dia}`;
    }

    function validarFecha(input, obligatoria, diasMinimosDesdeHoy) {
        const formatoValido = validarCampo(input, 'fechaISO', obligatoria);
        const valor = input.val().trim();

        if (!formatoValido || valor === '') {
            return formatoValido;
        }

        const esValida = valor >= obtenerFechaLocalISO(diasMinimosDesdeHoy);
        marcarCampo(input, esValida);
        return esValida;
    }

    
    $('#fecha_de_inicio').on('input change', function() { validarFecha($(this), true, 0); });
    $('#fecha_terminado').on('input change', function() { validarFecha($(this), false, 1); });
    $('#id_detalle_pedido').on('change', function() { validarCampo($(this), 'enteroPositivo'); });
    
    
    $('#edit_fecha_de_inicio').on('input change', function() { validarFecha($(this), true, 0); });
    $('#edit_fecha_terminado').on('input change', function() { validarFecha($(this), false, 1); });
    $('#edit_id_detalle_pedido').on('change', function() { validarCampo($(this), 'enteroPositivo'); });


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


    $('#formRegistrarOrden').on('submit', async function (e) {
        e.preventDefault();

        
        let v1 = validarFecha($('#fecha_de_inicio'), true, 0);
        let v2 = validarFecha($('#fecha_terminado'), false, 1);
        let v3 = validarCampo($('#id_detalle_pedido'), 'enteroPositivo');

        if (!v1 || !v2 || !v3) {
            await mostrarAlertaInformativa('Error', 'Por favor, corrija los campos en rojo.', 'error');
            return;
        }

        let confirmacion = await confirmarAccion('¿Registrar Orden?', 'Se creará una nueva orden de producción.');
        if (!confirmacion.isConfirmed) return;

        let formData = new FormData(this);

        try {
            let response = await fetch(urlControlador, {
                method: 'POST',
                body: formData
            });
            let data = await response.json();

            if (data.success) {
                await mostrarAlertaInformativa('¡Éxito!', data.message, 'success');
                $('#modalRegistrarOrden').modal('hide');
                $('#formRegistrarOrden')[0].reset();
                $('.is-valid, .is-invalid').removeClass('is-valid is-invalid').removeAttr('style');
                tablaOrdenes.ajax.reload();
            } else {
                await mostrarAlertaInformativa('Error', data.message, 'error');
            }
        } catch (error) {
            await mostrarAlertaInformativa('Error crítico', 'Fallo al procesar la solicitud.', 'error');
        }
    });


    $('#tablaOrdenProduccion tbody').on('click', '.btn-editar', async function () {
        let orden = $(this).data('orden');
        
        
        $('#edit_id_orden').val(orden.id_produccion);
        $('#edit_fecha_de_inicio').val(orden.fecha_de_inicio);
        $('#edit_fecha_terminado').val(orden.fecha_terminado || '');
        $('#edit_id_detalle_pedido').val(orden.id_detalle_pedido);
        $('#edit_estado_de_produccion').val(orden.estado_de_produccion);
        
        
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid').removeAttr('style');
        
        
        $('.check-edit-empleado').prop('checked', false);

        
        try {
            let response = await fetch(`${urlControlador}&accion=obtener_empleados&id=${orden.id_produccion}`);
            let data = await response.json();
            
            if (data.success && data.data.length > 0) {
                
                data.data.forEach(id_empleado => {
                    $(`#edit_emp_${id_empleado}`).prop('checked', true);
                });
            }
        } catch (error) {
            console.error('Error obteniendo trabajadores asignados:', error);
        }

        $('#modalEditarOrden').modal('show');
    });

    $('#formEditarOrden').on('submit', async function (e) {
        e.preventDefault();

        
        let v1 = validarFecha($('#edit_fecha_de_inicio'), true, 0);
        let v2 = validarFecha($('#edit_fecha_terminado'), false, 1);
        let v3 = validarCampo($('#edit_id_detalle_pedido'), 'enteroPositivo');

        if (!v1 || !v2 || !v3) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        let confirmacion = await confirmarAccion('¿Guardar Cambios?', 'Se actualizarán los datos de la orden de producción.');
        if (!confirmacion.isConfirmed) return;

        let formData = new FormData(this);

        try {
            let response = await fetch(urlControlador, {
                method: 'POST',
                body: formData
            });
            let data = await response.json();

            if (data.success) {
                await mostrarAlertaInformativa('Actualizado', data.message, 'success');
                $('#modalEditarOrden').modal('hide');
                tablaOrdenes.ajax.reload();
            } else {
                await mostrarAlertaInformativa('Error', data.message, 'error');
            }
        } catch (error) {
            await mostrarAlertaInformativa('Error de red', 'No se pudo contactar con el servidor.', 'error');
        }
    });


    $('#tablaOrdenProduccion tbody').on('click', '.btn-eliminar', async function () {
        let id = $(this).data('id');

        let confirmacion = await confirmarAccion('¿Inactivar orden?', 'Esta orden pasará a estado Inactivo.');
        
        if (confirmacion.isConfirmed) {
            try {
                let response = await fetch(`${urlControlador}&accion=eliminar&id=${id}`);
                let data = await response.json();

                if (data.success) {
                    await mostrarAlertaInformativa('Inactivada', data.message, 'success');
                    tablaOrdenes.ajax.reload();
                } else {
                    await mostrarAlertaInformativa('Error', data.message, 'error');
                }
            } catch (error) {
                await mostrarAlertaInformativa('Error', 'Fallo al procesar la solicitud.', 'error');
            }
        }
    });


    $('#btnAlternarEstado').on('click', function () {
        let estadoActual = $(this).attr('data-vista');
        
        if (estadoActual === 'activos') {
            
            tablaOrdenes.column(4).search('Inactiva').draw();
            $(this).attr('data-vista', 'inactivos');$('#txtBotonEstado').text('Ver activas');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
        } else {
            
            tablaOrdenes.column(4).search('').draw();
            $(this).attr('data-vista', 'activos');$('#txtBotonEstado').text('Ver inactivas');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
        }
    });

    $('#reporteSeleccionarTodos').on('change', function () {
        $('.check-estado-reporte').prop('checked', this.checked);
    });

    $('.check-estado-reporte').on('change', function () {
        $('#reporteSeleccionarTodos').prop(
            'checked',
            $('.check-estado-reporte').length === $('.check-estado-reporte:checked').length
        );
    });

    $('#formReporteOrdenes').on('submit', function (e) {
        e.preventDefault();

        const fechaDesde = $('#reporteFechaDesde').val();
        const fechaHasta = $('#reporteFechaHasta').val();
        const estados = $('.check-estado-reporte:checked').map(function () {
            return this.value;
        }).get();

        if (estados.length === 0) {
            mostrarAlertaInformativa('Seleccione un estado', 'Elija al menos un estado de producción para el reporte.', 'warning');
            return;
        }

        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            mostrarAlertaInformativa('Rango de fechas inválido', 'La fecha inicial no puede ser posterior a la fecha final.', 'warning');
            return;
        }

        const parametros = new URLSearchParams();
        parametros.set('accion', 'generar_reporte');
        estados.forEach(estado => parametros.append('estados[]', estado));
        if (fechaDesde) parametros.set('fecha_desde', fechaDesde);
        if (fechaHasta) parametros.set('fecha_hasta', fechaHasta);

        window.open(`${urlControlador}&${parametros.toString()}`, '_blank');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalReporteOrdenes')).hide();
    });
});
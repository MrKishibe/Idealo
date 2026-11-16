import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { confirmActionAlert, statusAlert } from './helpers/alerts.js';
import {
    validarCampo as validarCampoCompartido,
    patrones,
    obtenerPatron,
    obtenerFechaLocalISO,
    formatearFechaLatinoamericana
} from './helpers/expresiones.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';

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
            {
                data: 'fecha_de_inicio',
                render: function (data, type) {
                    if (type !== 'display' && type !== 'filter') return data;
                    return data ? formatearFechaLatinoamericana(data) : 'N/A';
                }
            },
            {
                data: 'fecha_terminado',
                defaultContent: 'No definida',
                render: function (data, type) {
                    if (type !== 'display' && type !== 'filter') return data;
                    return data ? formatearFechaLatinoamericana(data) : 'No definida';
                }
            },
            { data: 'descripcion_pedido' },
            { data: 'estado_de_produccion' },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-orden='${JSON.stringify(row)}' title="Editar orden">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${row.id_produccion}" title="Inactivar orden">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        processing: true,
        language: {
            ...lenguajeDataTableEs,
            processing: crearMarkupSkeletonDataTable(6)
        },
        pageLength: 10,
        responsive: true,
        ordering: true,
        dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
    });

    tablaOrdenes.column(4).search('^(?!Inactiva$).*$', true, false).draw();

    function configurarBusquedaPedido(inputSelector, idSelector, opcionesSelector) {
        const $busqueda = $(inputSelector);
        const $idPedido = $(idSelector);
        const $opciones = $(opcionesSelector);
        const $contenedor = $busqueda.closest('.pedido-combobox');
        const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();

        function ocultarOpciones() {
            $opciones.removeClass('show');
            $busqueda.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
            $opciones.find('.active').removeClass('active');
        }

        function filtrarOpciones(filtro = '') {
            const textoFiltro = normalizar(filtro);
            const $opcionesFiltradas = $opciones.find('[data-value]');
            $opcionesFiltradas.each(function () {
                $(this).toggle(normalizar(this.textContent).includes(textoFiltro));
            });
            $opciones.find('[data-empty-message]').prop('hidden', $opcionesFiltradas.filter(':visible').length > 0);
            $opciones.addClass('show');
            $busqueda.attr('aria-expanded', 'true');
            $opciones.find('.active').removeClass('active');
        }

        function seleccionarOpcion($opcion) {
            if (!$opcion.length || $opcion.prop('disabled')) {
                return;
            }
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            $opcion.attr('aria-selected', 'true');
            $busqueda.val($opcion.text().trim());
            $idPedido.val($opcion.data('value')).trigger('change');
            ocultarOpciones();
        }

        $busqueda.on('focus', function () {
            filtrarOpciones(this.value);
        }).on('input', function () {
            $idPedido.val('').removeClass('is-valid is-invalid');
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            $busqueda.removeClass('is-valid is-invalid').removeAttr('style');
            filtrarOpciones(this.value);
        }).on('keydown', function (event) {
            let $visibles = $opciones.find('[data-value]:visible:not(:disabled)');
            let indiceActivo = $visibles.index($opciones.find('.active'));

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                filtrarOpciones(this.value);
                $visibles = $opciones.find('[data-value]:visible:not(:disabled)');
                if (!$visibles.length) return;
                indiceActivo = event.key === 'ArrowDown'
                    ? (indiceActivo + 1) % $visibles.length
                    : (indiceActivo <= 0 ? $visibles.length - 1 : indiceActivo - 1);
                const $activo = $visibles.eq(indiceActivo);
                $opciones.find('.active').removeClass('active');
                $activo.addClass('active');
                const idOpcion = `${$opciones.attr('id')}_pedido_${$activo.data('value')}`;
                $activo.attr('id', idOpcion);
                $busqueda.attr('aria-activedescendant', idOpcion);
            } else if (event.key === 'Enter' && $opciones.hasClass('show')) {
                const $activo = $opciones.find('.active');
                if ($activo.length) {
                    event.preventDefault();
                    seleccionarOpcion($activo);
                }
            } else if (event.key === 'Escape') {
                ocultarOpciones();
            }
        });

        $opciones.on('click', '[data-value]', function () {
            seleccionarOpcion($(this));
        });

        $(document).on('click', function (event) {
            if (!$contenedor[0].contains(event.target)) {
                ocultarOpciones();
            }
        });

        $idPedido.on('change', function () {
            validarPedidoCombobox($idPedido, $busqueda);
        });

        return {
            reset: function () {
                $busqueda.val('').removeClass('is-valid is-invalid').removeAttr('style');
                $idPedido.val('').removeClass('is-valid is-invalid');
                $opciones.find('[data-value]').attr('aria-selected', 'false');
                filtrarOpciones('');
                ocultarOpciones();
            },
            seleccionar: function (idPedido) {
                seleccionarOpcion($opciones.find(`[data-value="${idPedido}"]`));
            }
        };
    }

    function validarPedidoCombobox($idPedido, $busqueda) {
        const esValido = validarCampoCompartido($idPedido, obtenerPatron('enteroPositivo'), {
            requerido: true,
            estilo: false,
            feedback: false
        });
        $busqueda.toggleClass('is-valid', esValido)
            .toggleClass('is-invalid', !esValido)
            .css({
                'border-color': esValido ? 'green' : 'red',
                'box-shadow': esValido ? '0 0 5px green' : '0 0 5px red'
            });
        return esValido;
    }

    const busquedaPedidoRegistro = configurarBusquedaPedido(
        '#buscar_detalle_pedido',
        '#id_detalle_pedido',
        '#opciones_detalle_pedido'
    );
    const busquedaPedidoEdicion = configurarBusquedaPedido(
        '#buscar_edit_detalle_pedido',
        '#edit_id_detalle_pedido',
        '#opciones_edit_detalle_pedido'
    );

    function agregarOpcionPedido($menu, detalle, permitirPedidosAsignados) {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'dropdown-item text-wrap';
        boton.setAttribute('role', 'option');
        boton.setAttribute('aria-selected', 'false');
        boton.dataset.value = detalle.id_detalle_pedido;
        const yaAsignado = Boolean(detalle.id_produccion);
        boton.disabled = yaAsignado && !permitirPedidosAsignados;
        boton.textContent = `${detalle.descripcion || ''}${yaAsignado && !permitirPedidosAsignados ? ' (Ya tiene una orden de producción)' : ''}`;
        $menu.find('[data-empty-message]').before(boton);
    }

    function agregarEmpleado($contenedor, empleado, prefijo) {
        const idEmpleado = String(empleado.id_empleado);
        const idCheckbox = `${prefijo}${idEmpleado}`;
        const fila = document.createElement('div');
        fila.className = 'form-check';

        const checkbox = document.createElement('input');
        checkbox.className = 'form-check-input';
        if (prefijo === 'edit_emp_') checkbox.classList.add('check-edit-empleado');
        checkbox.type = 'checkbox';
        checkbox.name = 'empleados[]';
        checkbox.value = idEmpleado;
        checkbox.id = idCheckbox;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = idCheckbox;
        label.append(document.createTextNode(`${empleado.nombres || ''} ${empleado.apellidos || ''} `));
        const cargo = document.createElement('span');
        cargo.className = 'text-muted';
        cargo.style.fontSize = '0.85em';
        cargo.textContent = `(${empleado.cargo || ''})`;
        label.append(cargo);

        fila.append(checkbox, label);
        $contenedor.append(fila);
    }

    function cargarDatosVista() {
        $.ajax({
            url: urlControlador,
            type: 'GET',
            data: { accion: 'datos_vista' },
            dataType: 'json'
        }).done(function (respuesta) {
            if (!respuesta.success || !respuesta.data) {
                mostrarAlertaInformativa('Error', respuesta.message || 'No se pudieron cargar las opciones de la vista.', 'error');
                return;
            }

            const datos = respuesta.data;
            const $estados = $('#reporteEstadosProduccion');
            $('#mensajeEstadosReporte').remove();
            datos.estados.forEach(function (estado, indice) {
                const columna = document.createElement('div');
                columna.className = 'col-sm-6';
                const contenedor = document.createElement('div');
                contenedor.className = 'form-check';
                const checkbox = document.createElement('input');
                checkbox.className = 'form-check-input check-estado-reporte';
                checkbox.type = 'checkbox';
                checkbox.name = 'estados[]';
                checkbox.value = estado;
                checkbox.id = `reporteEstado${indice}`;
                checkbox.checked = true;
                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = checkbox.id;
                label.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
                contenedor.append(checkbox, label);
                columna.append(contenedor);
                $estados.append(columna);
            });

            datos.detalles_pedido.forEach(function (detalle) {
                agregarOpcionPedido($('#opciones_detalle_pedido'), detalle, false);
                agregarOpcionPedido($('#opciones_edit_detalle_pedido'), detalle, true);
            });

            const $empleadosRegistrar = $('#empleadosRegistrar').empty();
            const $empleadosEditar = $('#empleadosEditar').empty();
            if (!datos.empleados.length) {
                $('<p class="text-muted mb-0 small">No hay trabajadores registrados o activos.</p>').appendTo($empleadosRegistrar);
                $('<p class="text-muted mb-0 small">No hay trabajadores registrados o activos.</p>').appendTo($empleadosEditar);
                return;
            }
            datos.empleados.forEach(function (empleado) {
                agregarEmpleado($empleadosRegistrar, empleado, 'emp_');
                agregarEmpleado($empleadosEditar, empleado, 'edit_emp_');
            });
        }).fail(function (xhr, estado, error) {
            console.error('Error al cargar las opciones de órdenes de producción:', error || estado);
            mostrarAlertaInformativa('Error', 'No se pudieron cargar los datos para los formularios.', 'error');
        });
    }

    cargarDatosVista();

    
    function validarCampoOrden(input, patron, obligatorio = true) {
        const regla = typeof patron === 'string' ? obtenerPatron(patron) : patron;
        return validarCampoCompartido(input, regla, {
            requerido: obligatorio,
            estilo: true,
            feedback: false
        });
    }

    function obtenerFechaMaximaAnioActual() {
        return `${new Date().getFullYear()}-12-31`;
    }

    $('#fecha_de_inicio, #fecha_terminado, #edit_fecha_de_inicio, #edit_fecha_terminado, #reporteFechaDesde, #reporteFechaHasta')
        .attr('max', obtenerFechaMaximaAnioActual());

    function validarFechaOrden(input, obligatoria, diasMinimosDesdeHoy, permitirFechasPasadas = false) {
        const fechaMaxima = obtenerFechaMaximaAnioActual();
        input.attr('max', fechaMaxima);

        return validarCampoCompartido(input, {
            patron: patrones.fechaISO,
            requerido: obligatoria,
            estilo: true,
            feedback: true,
            mensaje: diasMinimosDesdeHoy > 0
                ? 'La fecha tiene que ser mayor a hoy.'
                : 'La fecha no puede ser menor a hoy.',
            mensajeVacio: 'La fecha de inicio es obligatoria.',
            validar: function (valor) {
                if (valor > fechaMaxima) {
                    return 'La fecha no puede exceder el año actual.';
                }
                if (permitirFechasPasadas) {
                    return true;
                }
                return valor >= obtenerFechaLocalISO(diasMinimosDesdeHoy);
            }
        });
    }

    
    $('#fecha_de_inicio').on('input change', function() { validarFechaOrden($(this), true, 0); });
    $('#fecha_terminado').on('input change', function() { validarFechaOrden($(this), false, 1); });
    
    
    function validarFechasEdicion() {
        const permitirFechasPasadas = String($('#edit_estado_de_produccion').val()).trim().toLowerCase() === 'inactiva';
        validarFechaOrden($('#edit_fecha_de_inicio'), true, 0, permitirFechasPasadas);
        validarFechaOrden($('#edit_fecha_terminado'), false, 1, permitirFechasPasadas);
    }

    $('#edit_fecha_de_inicio, #edit_fecha_terminado')
        .on('input change', validarFechasEdicion);

    $('#edit_estado_de_produccion').on('change', validarFechasEdicion);

    $('#estado_de_produccion, #edit_estado_de_produccion').on('change', function () {
        mostrarAlertaInformativa(
            'Estado cambiado',
            `El estado de producción cambió a "${$(this).val()}".`,
            'info'
        );
    });

    $('#modalRegistrarOrden').on('show.bs.modal', function () {
        $('#fecha_de_inicio').val(obtenerFechaLocalISO());
        busquedaPedidoRegistro.reset();
    });


    async function confirmarAccion(titulo, texto) {
        return await confirmActionAlert(titulo, texto);
    }

    async function mostrarAlertaInformativa(titulo, texto, icono) {
        return await statusAlert(titulo, texto, icono);
    }


    $('#formRegistrarOrden').on('submit', async function (e) {
        e.preventDefault();

        
        let v1 = validarFechaOrden($('#fecha_de_inicio'), true, 0);
        let v2 = validarFechaOrden($('#fecha_terminado'), false, 1);
        let v3 = validarPedidoCombobox($('#id_detalle_pedido'), $('#buscar_detalle_pedido'));

        if (!v1 || !v2 || !v3) {
            await mostrarAlertaInformativa('Error', 'Por favor, corrija los campos en rojo.', 'error');
            return;
        }

        let confirmacion = await confirmarAccion('¿Registrar Orden?', 'Se creará una nueva orden de producción.');
        if (!confirmacion.isConfirmed) return;

        const $formulario = $(this);
        const $botonEnviar = $formulario.find('button[type="submit"]').first();
        $botonEnviar.prop('disabled', true);

        $.ajax({
            url: urlControlador,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        }).done(async function (data) {
            if (data.success) {
                await mostrarAlertaInformativa('¡Éxito!', data.message, 'success');
                $('#modalRegistrarOrden').modal('hide');
                $('#formRegistrarOrden')[0].reset();
                busquedaPedidoRegistro.reset();
                $('.is-valid, .is-invalid').removeClass('is-valid is-invalid').removeAttr('style');
                tablaOrdenes.ajax.reload();
            } else {
                await mostrarAlertaInformativa('Error', data.message, 'error');
            }
        }).fail(async function (xhr, estado, error) {
            console.error('Error al registrar la orden de producción:', error || estado);
            await mostrarAlertaInformativa('Error de red', 'No se pudo contactar con el servidor.', 'error');
        }).always(function () {
            $botonEnviar.prop('disabled', false);
        });
    });


    $('#tablaOrdenProduccion tbody').on('click', '.btn-editar', async function () {
        let orden = $(this).data('orden');
        
        
        $('#edit_id_orden').val(orden.id_produccion);
        $('#edit_fecha_de_inicio').val(orden.fecha_de_inicio);
        $('#edit_fecha_terminado').val(orden.fecha_terminado || '');
        busquedaPedidoEdicion.reset();
        busquedaPedidoEdicion.seleccionar(orden.id_detalle_pedido);
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

        
        const permitirFechasPasadas = String($('#edit_estado_de_produccion').val()).trim().toLowerCase() === 'inactiva';
        let v1 = validarFechaOrden($('#edit_fecha_de_inicio'), true, 0, permitirFechasPasadas);
        let v2 = validarFechaOrden($('#edit_fecha_terminado'), false, 1, permitirFechasPasadas);
        let v3 = validarPedidoCombobox($('#edit_id_detalle_pedido'), $('#buscar_edit_detalle_pedido'));

        if (!v1 || !v2 || !v3) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        let confirmacion = await confirmarAccion('¿Guardar Cambios?', 'Se actualizarán los datos de la orden de producción.');
        if (!confirmacion.isConfirmed) return;

        const $formulario = $(this);
        const $botonEnviar = $formulario.find('button[type="submit"]').first();
        $botonEnviar.prop('disabled', true);

        $.ajax({
            url: urlControlador,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        }).done(async function (data) {
            if (data.success) {
                await mostrarAlertaInformativa('Actualizado', data.message, 'success');
                $('#modalEditarOrden').modal('hide');
                tablaOrdenes.ajax.reload();
            } else {
                await mostrarAlertaInformativa('Error', data.message, 'error');
            }
        }).fail(async function (xhr, estado, error) {
            console.error('Error al actualizar la orden de producción:', error || estado);
            await mostrarAlertaInformativa('Error de red', 'No se pudo contactar con el servidor.', 'error');
        }).always(function () {
            $botonEnviar.prop('disabled', false);
        });
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
            
            tablaOrdenes.column(4).search('^Inactiva$', true, false).draw();
            $(this).attr('data-vista', 'inactivos');$('#txtBotonEstado').text('Ver activas');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
        } else {
            
            tablaOrdenes.column(4).search('^(?!Inactiva$).*$', true, false).draw();
            $(this).attr('data-vista', 'activos');$('#txtBotonEstado').text('Ver inactivas');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
        }
    });

    $('#reporteSeleccionarTodos').on('change', function () {
        $('.check-estado-reporte').prop('checked', this.checked);
    });

    $(document).on('change', '.check-estado-reporte', function () {
        $('#reporteSeleccionarTodos').prop(
            'checked',
            $('.check-estado-reporte').length === $('.check-estado-reporte:checked').length
        );
    });

    $('#formReporteOrdenes').on('submit', async function (e) {
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

        const fechaMaxima = obtenerFechaMaximaAnioActual();
        if (fechaDesde > fechaMaxima || fechaHasta > fechaMaxima) {
            mostrarAlertaInformativa('Fecha fuera de rango', 'Las fechas del reporte no pueden exceder el año actual.', 'warning');
            return;
        }

        const parametros = new URLSearchParams();
        parametros.set('accion', 'generar_reporte');
        estados.forEach(estado => parametros.append('estados[]', estado));
        if (fechaDesde) parametros.set('fecha_desde', fechaDesde);
        if (fechaHasta) parametros.set('fecha_hasta', fechaHasta);

        const pestanaReporte = abrirReporteEnPestana(`${urlControlador}&${parametros.toString()}`);
        if (!pestanaReporte) {
            await mostrarAlertaInformativa('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.', 'warning');
            return;
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalReporteOrdenes')).hide();
    });
});
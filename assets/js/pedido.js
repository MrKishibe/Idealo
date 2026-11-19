import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';
import { soloNumerosTeclado } from './helpers/validaciones.js';

$(document).ready(function () {
    const BASE_URL = 'index.php?controller=pedido';

    const $form = $('#formPedido');
    const $modalPedidoElement = $('#modalPedido');
    const $modalDetalleElement = $('#modalDetallePedido');
    const $modalReportePedidoElement = $('#modalReportePedido');

    const $idProducto = $('#idProducto');
    const $idCliente = $('#idCliente');
    const $filtroColor = $('#filtroColor');
    const $idProductoCaracteristica = $('#idProductoCaracteristica');
    const $estadoPedido = $('#estadoPedido');
    const $btnNuevoPedido = $('#btnNuevoPedido');
    const $btnAlternarEstado = $('#btnAlternarEstado');
    const $btnGenerarReporte = $('#btnGenerarReporte');
    const $formReportePedido = $('#formReportePedido');

    if (
        !$form.length ||
        !$modalPedidoElement.length ||
        !$modalDetalleElement.length ||
        !$idProducto.length ||
        !$filtroColor.length ||
        !$idProductoCaracteristica.length ||
        !$estadoPedido.length
    ) {
        console.error('Faltan elementos HTML requeridos.');
        return;
    }

    const modalPedido = bootstrap.Modal.getOrCreateInstance($modalPedidoElement[0]);
    const modalDetalle = bootstrap.Modal.getOrCreateInstance($modalDetalleElement[0]);
    const modalReportePedido = $modalReportePedidoElement.length
        ? bootstrap.Modal.getOrCreateInstance($modalReportePedidoElement[0])
        : null;

    let tablaPedidos = null;
    let verInhabilitados = false;
    let silenciarCambioProducto = false;

    function escaparHtml(valor) {
        return $('<div>').text(valor ?? '').html();
    }

    function obtenerFiltro() {
        return verInhabilitados ? 'inhabilitados' : 'activos';
    }

    // ==========================================
    // BUSCADOR NATIVO CON SCROLL Y MENSAJE DE "NO ENCONTRADO"
    // ==========================================
    function normalizarTexto(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function transformarSelectConBuscadorNativo($selectOriginal, placeholder = 'Escriba para filtrar...') {
        if (!$selectOriginal.length) return;

        const idSelect = $selectOriginal.attr('id') || 'sel_' + Math.random().toString(36).substr(2, 9);
        $(`#wrapper_${idSelect}`).remove();

        $selectOriginal.hide();

        const $wrapper = $(`<div id="wrapper_${idSelect}" class="position-relative mb-2"></div>`);
        const $inputBusqueda = $(`<input type="text" class="form-control form-control-sm mb-1" placeholder="${placeholder}" autocomplete="off">`);
        const $listaSugerencias = $(`<div class="list-group position-absolute w-100 shadow-sm bg-white border" style="max-height: 220px; overflow-y: auto; z-index: 1055; display: none;"></div>`);

        $wrapper.append($inputBusqueda).append($listaSugerencias);
        $selectOriginal.after($wrapper);

        function actualizarSugerencias(filtro = '') {
            $listaSugerencias.empty();
            const terminoNorm = normalizarTexto(filtro);
            const palabrasClave = terminoNorm.split(/\s+/).filter(Boolean);

            let opcionesVisibles = 0;

            $selectOriginal.find('option').each(function () {
                const $opt = $(this);
                const valorOpt = $opt.val();
                const textoOpt = $opt.text();

                if (!valorOpt) return; // Omitir opción vacía/placeholder inicial

                const textoNorm = normalizarTexto(textoOpt);
                const coincide = palabrasClave.length === 0 || palabrasClave.every(palabra => textoNorm.includes(palabra));

                if (coincide) {
                    opcionesVisibles++;
                    const $item = $(`<button type="button" class="list-group-item list-group-item-action py-2 px-2 small">${escaparHtml(textoOpt)}</button>`);
                    
                    if ($opt.is(':selected')) {
                        $item.addClass('active text-white');
                    }

                    $item.on('click', function (e) {
                        e.preventDefault();
                        $selectOriginal.val(valorOpt).trigger('change');
                        $inputBusqueda.val(textoOpt);
                        $listaSugerencias.hide();
                    });

                    $listaSugerencias.append($item);
                }
            });

            // Si no hay coincidencias, mostrar mensaje informativo de valor no existente
            if (opcionesVisibles === 0) {
                $listaSugerencias.append(`<div class="list-group-item py-2 px-2 small text-muted text-center fst-italic">No se encontró ningún registro que coincida.</div>`);
            }

            $listaSugerencias.show();
        }

        // Al hacer clic o enfocar, despliega inmediatamente todas las opciones con scroll
        $inputBusqueda.on('focus click', function () {
            actualizarSugerencias($(this).val());
        });

        // Filtrar en tiempo real mientras se escribe
        $inputBusqueda.on('input', function () {
            actualizarSugerencias($(this).val());
        });

        // Sincronizar cambios externos del select
        $selectOriginal.on('change', function () {
            const textoSeleccionado = $(this).find('option:selected').text();
            if (!$(this).val()) {
                $inputBusqueda.val('');
            } else {
                $inputBusqueda.val(textoSeleccionado);
            }
        });

        // Ocultar al hacer clic fuera del componente
        $(document).on('click.cierreBuscador_' + idSelect, function (e) {
            if (!$(e.target).closest($wrapper).length) {
                $listaSugerencias.hide();
            }
        });
    }

    function actualizarTotal() {
        const cantidad = Number($('#cantidad').val()) || 0;
        const manoObra = Number(($('#costoManoObra').val() || '').replace(',', '.')) || 0;
        const materiales = Number(($('#costoMateriales').val() || '').replace(',', '.')) || 0;
        const descuentoProductoUnitario = Number(($('#descuentoProducto').val() || '').replace(',', '.')) || 0;
        const descuentoGeneral = Number(($('#descuentoDivisa').val() || '').replace(',', '.')) || 0;

        const costoUnitarioNeto = (manoObra + materiales) - descuentoProductoUnitario;
        let total = (costoUnitarioNeto * cantidad) - descuentoGeneral;
        
        if (total < 0) total = 0;

        $('#totalVisual').text(total.toFixed(2));
    }

    function resetColores() {
        $filtroColor.html('<option value="">Seleccione primero un producto</option>').prop('disabled', true);
        $filtroColor.trigger('change');
    }

    function resetTallas() {
        $idProductoCaracteristica.html('<option value="">Seleccione primero un color</option>').prop('disabled', true);
        $idProductoCaracteristica.trigger('change');
    }

    // ==========================================
    // AJAX: CARGAR CATÁLOGOS INICIALES
    // ==========================================
    async function cargarCatalogos() {
        try {
            const respuesta = await $.ajax({
                url: `${BASE_URL}&accion=obtener_catalogos`,
                type: 'GET',
                dataType: 'json'
            });

            if (!respuesta.success) {
                throw new Error(respuesta.message || 'Error al cargar los catálogos.');
            }

            const { clientes, tiposPedido, productos, servicios } = respuesta.data;

            if ($idCliente.length) {
                $idCliente.html('<option value="">Seleccione un cliente</option>');
                clientes.forEach(c => {
                    $idCliente.append(new Option(`${c.nombre_razon_social || ''} ${c.apellido || ''} - ${c.numero_de_documento || 's/d'}`, c.id_cliente));
                });
                transformarSelectConBuscadorNativo($idCliente, 'Buscar cliente por nombre o documento...');
            }

            const $selTipoPedido = $('#idTipoPedido');
            if ($selTipoPedido.length) {
                $selTipoPedido.html('<option value="">Seleccione un tipo</option>');
                tiposPedido.forEach(tp => {
                    $selTipoPedido.append(new Option(tp.nombre_tipo_pedido, tp.id_tipo_pedido));
                });
            }

            if ($idProducto.length) {
                $idProducto.html('<option value="">Seleccione un producto</option>');
                productos.forEach(p => {
                    $idProducto.append(new Option(`${p.nombre_producto} - ${p.tipo_de_producto || 'Sin tipo'}`, p.id_producto));
                });
                transformarSelectConBuscadorNativo($idProducto, 'Buscar producto por nombre...');
            }

            const $selServicio = $('#idServicio');
            if ($selServicio.length) {
                $selServicio.html('<option value="">Seleccione un servicio</option>');
                servicios.forEach(s => {
                    $selServicio.append(new Option(s.nombre_servicio, s.id_servicio));
                });
            }

            const $containerTiposReporte = $('#reporteTiposPedidoContainer');
            if ($containerTiposReporte.length) {
                let htmlTipos = '';
                tiposPedido.forEach(tp => {
                    htmlTipos += `
                        <div class="form-check">
                            <input class="form-check-input chk-filtro" type="checkbox" name="id_tipo_pedido[]" value="${tp.id_tipo_pedido}" id="tp_${tp.id_tipo_pedido}">
                            <label class="form-check-label small" for="tp_${tp.id_tipo_pedido}">${escaparHtml(tp.nombre_tipo_pedido)}</label>
                        </div>`;
                });
                $containerTiposReporte.html(htmlTipos);
            }

            const $containerServiciosReporte = $('#reporteServiciosContainer');
            if ($containerServiciosReporte.length) {
                let htmlServicios = '';
                servicios.forEach(serv => {
                    htmlServicios += `
                        <div class="form-check">
                            <input class="form-check-input chk-filtro" type="checkbox" name="id_servicio[]" value="${serv.id_servicio}" id="serv_${serv.id_servicio}">
                            <label class="form-check-label small" for="serv_${serv.id_servicio}">${escaparHtml(serv.nombre_servicio)}</label>
                        </div>`;
                });
                $containerServiciosReporte.html(htmlServicios);
            }

        } catch (error) {
            console.error(error);
            await errorAlert('Error', 'No se pudieron cargar los datos iniciales del sistema.');
        }
    }

    // ==========================================
    // RESTRICCIONES DE ENTRADA
    // ==========================================
    function numerosDecimalesTeclado(e) {
        if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) return;
        if (!/^[0-9.,]$/.test(e.key)) e.preventDefault();
    }

    function alfanumericosTeclado(e) {
        if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) return;
        if (!/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s]$/.test(e.key)) e.preventDefault();
    }

    const $cantidad = $('#cantidad');
    if ($cantidad.length) {
        $cantidad.on('keydown', soloNumerosTeclado);
        $cantidad.on('input', actualizarTotal);
    }

    const $metodoServicio = $('#metodoServicio');
    if ($metodoServicio.length) {
        $metodoServicio.on('keydown', alfanumericosTeclado);
    }

    const idsDecimales = ['costoManoObra', 'costoMateriales', 'descuentoProducto', 'descuentoDivisa'];
    idsDecimales.forEach(id => {
        const $campo = $(`#${id}`);
        if ($campo.length) {
            $campo.on('keydown', numerosDecimalesTeclado);
            $campo.on('input', actualizarTotal);
        }
    });

    const $reporteCantidad = $('#reporteCantidad');
    const $reporteMonto = $('#reporteMonto');
    if ($reporteCantidad.length) $reporteCantidad.on('keydown', soloNumerosTeclado);
    if ($reporteMonto.length) $reporteMonto.on('keydown', numerosDecimalesTeclado);

    // ==========================================
    // AJAX: CARGAR COLORES Y TALLAS
    // ==========================================
    async function cargarColores(productoId, colorSeleccionado = '', tallaSeleccionada = '') {
        resetColores();
        resetTallas();

        if (!productoId) return;

        $filtroColor.html('<option value="">Cargando colores...</option>');

        try {
            const respuesta = await $.ajax({
                url: `${BASE_URL}&accion=colores_por_producto`,
                type: 'GET',
                data: { id_producto: productoId },
                dataType: 'json'
            });

            if (!respuesta.success) throw new Error(respuesta.message || 'No se pudieron cargar los colores.');

            $filtroColor.html('<option value="">Seleccione un color</option>');

            if (!Array.isArray(respuesta.data) || respuesta.data.length === 0) {
                $filtroColor.html('<option value="">No hay colores activos</option>').prop('disabled', true);
                return;
            }

            respuesta.data.forEach(item => {
                const $option = $('<option>').val(item.color).text(item.color);
                if (String(item.color) === String(colorSeleccionado)) $option.prop('selected', true);
                $filtroColor.append($option);
            });

            $filtroColor.prop('disabled', false);
            transformarSelectConBuscadorNativo($filtroColor, 'Buscar color...');

            if (colorSeleccionado) {
                await cargarTallas(productoId, colorSeleccionado, tallaSeleccionada);
            }
        } catch (error) {
            console.error(error);
            $filtroColor.html('<option value="">Error al cargar colores</option>').prop('disabled', true);
            await errorAlert('Error', error.message || 'Error de comunicación con el servidor.');
        }
    }

    async function cargarTallas(productoId, color, tallaSeleccionada = '') {
        resetTallas();

        if (!productoId || !color) return;

        $idProductoCaracteristica.html('<option value="">Cargando tallas...</option>');

        try {
            const respuesta = await $.ajax({
                url: `${BASE_URL}&accion=tallas_por_producto_y_color`,
                type: 'GET',
                data: { id_producto: productoId, color: color },
                dataType: 'json'
            });

            if (!respuesta.success) throw new Error(respuesta.message || 'No se pudieron cargar las tallas.');

            $idProductoCaracteristica.html('<option value="">Seleccione una talla</option>');

            if (!Array.isArray(respuesta.data) || respuesta.data.length === 0) {
                $idProductoCaracteristica.html('<option value="">No hay tallas activas para este color</option>').prop('disabled', true);
                return;
            }

            respuesta.data.forEach(item => {
                const textoOpt = `Talla: ${item.talla || 'N/A'} | Material: ${item.detalle_material || 'N/A'} | Prenda: ${item.tipo_de_prenda || 'N/A'}`;
                const $option = $('<option>').val(item.id_producto_caracteristica).text(textoOpt);
                if (String(item.id_producto_caracteristica) === String(tallaSeleccionada)) $option.prop('selected', true);
                $idProductoCaracteristica.append($option);
            });

            $idProductoCaracteristica.prop('disabled', false);
            transformarSelectConBuscadorNativo($idProductoCaracteristica, 'Buscar talla, material o prenda...');
        } catch (error) {
            console.error(error);
            $idProductoCaracteristica.html('<option value="">Error al cargar tallas</option>').prop('disabled', true);
            await errorAlert('Error', error.message || 'Error de comunicación con el servidor.');
        }
    }

    function badgeEstado(estado) {
        const valor = String(estado || '').toLowerCase();
        let clase = 'bg-secondary';
        if (valor === 'pendiente') clase = 'bg-warning text-dark';
        if (valor === 'en proceso') clase = 'bg-info text-dark';
        if (valor === 'realizado') clase = 'bg-primary';
        if (valor === 'entregado') clase = 'bg-success';
        if (valor === 'inhabilitado') clase = 'bg-danger';

        return `<span class="badge ${clase}">${escaparHtml(estado)}</span>`;
    }

    function columnasDataTable() {
        return [
            {
                data: 'id_pedido',
                orderable: false,
                searchable: false,
                render: data => `<strong>#${data}</strong>`
            },
            {
                data: 'fecha_creacion',
                type: 'string',
                render: function (data, type) {
                    if (!data) return '-';
                    if (type === 'sort' || type === 'type') return data;
                    const partes = data.split(' ')[0].split('-');
                    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : data;
                }
            },
            {
                data: null,
                type: 'string',
                render: data => escaparHtml(`${data.nombre_razon_social || ''} ${data.apellido || ''}`)
            },
            {
                data: null,
                type: 'string',
                render: data => `
                    <strong>${escaparHtml(data.nombre_producto || 'Sin producto')}</strong>
                    <small class="d-block text-muted">
                        ${escaparHtml(data.color || 'Sin color')} · Talla: ${escaparHtml(data.talla || 'N/A')}
                    </small>`
            },
            {
                data: 'nombre_servicio',
                type: 'string',
                render: data => escaparHtml(data || 'Sin servicio')
            },
            { data: 'cantidad', type: 'num' },
            {
                data: 'estado_pedido',
                type: 'string',
                render: data => badgeEstado(data)
            },
            {
                data: 'monto_total',
                type: 'num',
                render: data => Number(data || 0).toFixed(2)
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data) {
                    const estado = String(data.estado_pedido || '').toLowerCase();
                    let html = `
                        <button type="button" class="btn btn-sm btn-outline-info btnDetallePedido me-1" data-id="${data.id_pedido}" title="Ver detalle">
                            <i class="bi bi-eye-fill"></i>
                        </button>`;

                    if (estado === 'inhabilitado') {
                        html += `
                            <button type="button" class="btn btn-sm btn-outline-warning btnEditarPedido" data-id="${data.id_pedido}" title="Editar o reactivar">
                                <i class="bi bi-pencil-square"></i>
                            </button>`;
                    } else {
                        html += `
                            <button type="button" class="btn btn-sm btn-outline-primary btnEditarPedido me-1" data-id="${data.id_pedido}" title="Editar pedido">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btnInhabilitarPedido" data-id="${data.id_pedido}" title="Inhabilitar pedido">
                                <i class="bi bi-trash3-fill"></i>
                            </button>`;
                    }
                    return html;
                }
            }
        ];
    }

    function crearDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaPedidos')) {
            $('#tablaPedidos').DataTable().destroy();
        }

        tablaPedidos = $('#tablaPedidos').DataTable({
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            ajax: {
                url: `${BASE_URL}&accion=listar&filtro=${obtenerFiltro()}`,
                type: 'GET',
                dataType: 'json',
                dataSrc: function (json) {
                    if (!json.success) {
                        errorAlert('Error', json.message || 'No se pudieron cargar los registros.');
                        return [];
                    }
                    return json.data || [];
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    errorAlert('Error de carga', 'No se pudo conectar con el controlador de pedidos.');
                }
            },
            processing: true,
            responsive: true,
            pageLength: 10,
            order: [[1, 'desc']],
            language: {
                ...lenguajeDataTableEs,
                processing: crearMarkupSkeletonDataTable($('#tablaPedidos thead th').length)
            },
            columns: columnasDataTable()
        });
    }

    function actualizarVista() {
        const $titulo = $('#tituloVista');
        const $icono = $('#iconoEstado');
        const $texto = $('#txtBotonEstado');

        if (verInhabilitados) {
            if ($titulo.length) $titulo.text('Pedidos Inhabilitados');
            if ($texto.length) $texto.text('Ver activos');
            if ($icono.length) $icono.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
        } else {
            if ($titulo.length) $titulo.text('Gestión de Pedidos');
            if ($texto.length) $texto.text('Ver inhabilitados');
            if ($icono.length) $icono.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
        }
    }

    function limpiarFormulario() {
        silenciarCambioProducto = true;

        $form[0].reset();
        $form.removeClass('was-validated');

        $('#accionPedido').val('guardar');
        $('#idPedido').val('');
        $('#fechaCreacion').val(new Date().toISOString().split('T')[0]);

        if ($idCliente.length) {
            $idCliente.val('').trigger('change');
        }

        $idProducto.val('').trigger('change');

        resetColores();
        resetTallas();

        $estadoPedido.html(`
            <option value="pendiente">Pendiente</option>
            <option value="en proceso">En proceso</option>
            <option value="realizado">Realizado</option>
            <option value="entregado">Entregado</option>
        `).val('pendiente').prop('disabled', true);

        $('#tituloModalPedido').html('<i class="bi bi-cart-plus-fill me-2"></i>Registrar Pedido');
        $('#btnGuardarPedido').html('<i class="bi bi-check-circle-fill"></i> Guardar pedido');

        actualizarTotal();
        silenciarCambioProducto = false;
    }

    async function validarFormulario() {
        $form.addClass('was-validated');

        if (!$form[0].checkValidity()) {
            await errorAlert('Formulario incompleto', 'Complete los campos obligatorios.');
            return false;
        }

        if ($idCliente.length && !$idCliente.val()) {
            await errorAlert('Cliente requerido', 'Seleccione un cliente.');
            return false;
        }

        if (!$idProducto.val()) {
            await errorAlert('Producto requerido', 'Seleccione un producto.');
            return false;
        }

        const fechaCreacion = $('#fechaCreacion').val();
        const fechaEntrega = $('#fechaEntrega').val();

        if (fechaEntrega && fechaEntrega < fechaCreacion) {
            await errorAlert('Fecha inválida', 'La fecha de entrega no puede ser anterior a la fecha de creación.');
            return false;
        }

        if (!$filtroColor.val()) {
            await errorAlert('Color requerido', 'Seleccione un color.');
            return false;
        }

        if (!$idProductoCaracteristica.val()) {
            await errorAlert('Talla requerida', 'Seleccione una talla válida.');
            return false;
        }

        return true;
    }

    async function enviarFormulario() {
        if (!(await validarFormulario())) return;

        const $boton = $('#btnGuardarPedido');
        const textoOriginal = $boton.html();

        $boton.prop('disabled', true).text('Procesando...');

        const accion = $('#accionPedido').val();
        if (accion === 'guardar') {
            $estadoPedido.prop('disabled', false).val('pendiente');
        }

        try {
            const resultado = await $.ajax({
                url: `${BASE_URL}&accion=${accion}`,
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (!resultado.success) throw new Error(resultado.message || 'No se pudo guardar el pedido.');

            await successAlert('Operación exitosa', resultado.message);
            modalPedido.hide();
            limpiarFormulario();
            tablaPedidos.ajax.reload(null, false);
        } catch (error) {
            await errorAlert('Error', error.message || 'Error de comunicación con el servidor.');
        } finally {
            $boton.prop('disabled', false).html(textoOriginal);
            if ($('#accionPedido').val() === 'guardar') {
                $estadoPedido.prop('disabled', true);
            }
        }
    }

    async function editarPedido(idPedido) {
        try {
            const resultado = await $.ajax({
                url: `${BASE_URL}&accion=obtener`,
                type: 'GET',
                data: { id: idPedido },
                dataType: 'json'
            });

            if (!resultado.success) throw new Error(resultado.message || 'No se pudo obtener el pedido.');

            const pedido = resultado.data;
            const detalle = pedido.detalle || {};

            $('#accionPedido').val('editar');
            $('#idPedido').val(pedido.id_pedido);
            $('#idTipoPedido').val(pedido.id_tipo_pedido);
            $('#fechaCreacion').val(pedido.fecha_creacion);
            $('#fechaEntrega').val(pedido.fecha_entrega || '');
            $('#descripcion').val(pedido.descripcion || '');
            $('#descuentoDivisa').val(pedido.descuento_divisa || 0);

            if ($idCliente.length) {
                $idCliente.val(pedido.id_cliente || '').trigger('change');
            }

            silenciarCambioProducto = true;
            $idProducto.val(detalle.id_producto || '').trigger('change');
            silenciarCambioProducto = false;

            await cargarColores(detalle.id_producto || '', detalle.color || '', detalle.id_producto_caracteristica || '');

            $('#idServicio').val(detalle.id_servicio || '');
            $('#cantidad').val(detalle.cantidad || 1);
            $('#costoManoObra').val(detalle.costo_mano_de_obra || 0);
            $('#costoMateriales').val(detalle.costo_materiales || 0);
            $('#descuentoProducto').val(detalle.descuento_producto || 0);
            $('#metodoServicio').val(detalle.metodo_servicio || '');

            $estadoPedido.prop('disabled', false).html(`
                <option value="pendiente">Pendiente</option>
                <option value="en proceso">En proceso</option>
                <option value="realizado">Realizado</option>
                <option value="entregado">Entregado</option>
                <option value="inhabilitado">Inhabilitado</option>
            `).val(pedido.estado_pedido || 'pendiente');

            $('#tituloModalPedido').html('<i class="bi bi-pencil-square me-2"></i>Editar Pedido');
            $('#btnGuardarPedido').html('<i class="bi bi-save-fill"></i> Guardar cambios');

            actualizarTotal();
            modalPedido.show();
        } catch (error) {
            await errorAlert('Error', error.message || 'No se pudo conectar con el servidor.');
        }
    }

    async function detallePedido(idPedido) {
        try {
            const resultado = await $.ajax({
                url: `${BASE_URL}&accion=obtener`,
                type: 'GET',
                data: { id: idPedido },
                dataType: 'json'
            });

            if (!resultado.success) throw new Error(resultado.message || 'No se pudo obtener el detalle.');

            const pedido = resultado.data;
            const detalle = pedido.detalle || {};

            $('#detalleIdPedido').text('#' + pedido.id_pedido);
            $('#detalleFechaCreacion').text(pedido.fecha_creacion || '-');
            $('#detalleFechaEntrega').text(pedido.fecha_entrega || '-');
            $('#detalleTipoPedido').text(pedido.nombre_tipo_pedido || '-');
            $('#detalleEstadoPedido').text(pedido.estado_pedido || '-');
            $('#detalleDescripcion').text(pedido.descripcion || 'Sin descripción');
            $('#detalleDescuentoGeneral').text(Number(pedido.descuento_divisa || 0).toFixed(2));

            $('#detalleCliente').text(`${pedido.nombre_razon_social || ''} ${pedido.apellido || ''}`);
            $('#detalleDocumento').text(pedido.numero_de_documento || '-');
            $('#detalleCorreo').text(pedido.correo || '-');
            $('#detalleTelefono').text(pedido.telefono || '-');
            $('#detalleDireccion').text(pedido.direccion || '-');

            $('#detalleProducto').text(detalle.nombre_producto || '-');
            $('#detalleTipoProducto').text(detalle.tipo_de_producto || '-');
            $('#detalleTalla').text(detalle.talla || '-');
            $('#detalleMaterial').text(detalle.detalle_material || '-');
            $('#detalleColor').text(detalle.color || '-');
            $('#detalleTipoPrenda').text(detalle.tipo_de_prenda || '-');
            $('#detalleServicio').text(detalle.nombre_servicio || '-');
            $('#detalleCantidad').text(detalle.cantidad || '-');
            $('#detalleMetodoServicio').text(detalle.metodo_servicio || '-');
            $('#detalleManoObra').text(Number(detalle.costo_mano_de_obra || 0).toFixed(2));
            $('#detalleCostoMateriales').text(Number(detalle.costo_materiales || 0).toFixed(2));
            $('#detalleDescuentoProducto').text(Number(detalle.descuento_producto || 0).toFixed(2));
            $('#detalleTotal').text(Number(pedido.monto_total || 0).toFixed(2));

            modalDetalle.show();
        } catch (error) {
            await errorAlert('Error', error.message || 'No se pudo conectar con el servidor.');
        }
    }

    async function inhabilitarPedido(idPedido) {
        confirmDeleteAlert(async () => {
            try {
                const formData = new FormData();
                formData.append('accion', 'inhabilitar');
                formData.append('id_pedido', idPedido);

                const resultado = await $.ajax({
                    url: `${BASE_URL}&accion=inhabilitar`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                });

                if (!resultado.success) throw new Error(resultado.message || 'No se pudo inhabilitar el pedido.');

                await successAlert('Inhabilitado', resultado.message);
                tablaPedidos.ajax.reload(null, false);
            } catch (error) {
                await errorAlert('Error', error.message || 'Error al procesar la inhabilitación.');
            }
        });
    }

    // ==========================================
    // EVENTOS CON JQUERY
    // ==========================================
    $idProducto.on('change', function () {
        if (silenciarCambioProducto) return;
        cargarColores(this.value);
    });

    $filtroColor.on('change', function () {
        cargarTallas($idProducto.val(), this.value);
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        enviarFormulario();
    });

    $btnNuevoPedido.on('click', function () {
        modalPedido.show();
    });

    $btnAlternarEstado.on('click', function () {
        verInhabilitados = !verInhabilitados;
        actualizarVista();
        crearDataTable();
    });

    if ($btnGenerarReporte.length) {
        $btnGenerarReporte.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (modalReportePedido) modalReportePedido.show();
        });
    }

    const $chkTodo = $('#reporteTodo');
    if ($chkTodo.length) {
        $chkTodo.on('change', function () {
            const $camposFiltro = $('.campo-filtro');
            const isChecked = this.checked;

            $camposFiltro.each(function () {
                const $el = $(this);
                $el.prop('disabled', isChecked);
                if (isChecked) {
                    $el.val('');
                }
            });
        });
    }

    if ($formReportePedido.length) {
        $formReportePedido.on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const parametros = new URLSearchParams();
            const elementosForm = $formReportePedido[0].elements;

            for (let el of elementosForm) {
                if (el.name) {
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.checked) parametros.append(el.name, el.value);
                    } else if (el.tagName === 'SELECT' && el.multiple) {
                        $(el).find('option:selected').each(function () {
                            parametros.append(el.name, this.value);
                        });
                    } else {
                        parametros.append(el.name, el.value);
                    }
                }
            }

            parametros.set('accion', 'generarreportepdf');
            parametros.set('filtro', obtenerFiltro());

            if (tablaPedidos) {
                const datosFiltrados = tablaPedidos.rows({ search: 'applied' }).data().toArray();
                const ids = datosFiltrados.map(pedido => pedido.id_pedido);
                parametros.set('ids_filtrados', JSON.stringify(ids));
            }

            const urlReporte = `${BASE_URL}&${parametros.toString()}`;
            const pestanaReporte = abrirReporteEnPestana(urlReporte);

            if (!pestanaReporte) {
                errorAlert('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.');
                return;
            }

            if (modalReportePedido) modalReportePedido.hide();
        });
    }

    const $btnCancelarPedido = $modalPedidoElement.find('.btnCancelarPedido');
    if ($btnCancelarPedido.length) {
        $btnCancelarPedido.on('click', function () {
            limpiarFormulario();
        });
    }

    $(document).on('click', '.btnDetallePedido', function () {
        detallePedido($(this).data('id'));
    });

    $(document).on('click', '.btnEditarPedido', function () {
        editarPedido($(this).data('id'));
    });

    $(document).on('click', '.btnInhabilitarPedido', function () {
        inhabilitarPedido($(this).data('id'));
    });

    // ==========================================
    // INICIALIZACIÓN GENERAL ASÍNCRONA
    // ==========================================
    (async function init() {
        await cargarCatalogos();
        resetColores();
        resetTallas();
        limpiarFormulario();
        actualizarVista();
        crearDataTable();
    })();
});
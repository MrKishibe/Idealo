import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';
import { soloNumerosTeclado } from './helpers/validaciones.js';

document.addEventListener(
    'DOMContentLoaded',
    function () {
        const BASE_URL = 'index.php?controller=pedido';

        // true  -> el cliente se busca en el servidor (requiere accion=buscar_clientes)
        // false -> el cliente se filtra sobre las opciones ya renderizadas por PHP
        const CLIENTES_REMOTO = false;

        const form = document.getElementById('formPedido');
        const modalPedidoElement = document.getElementById('modalPedido');
        const modalDetalleElement = document.getElementById('modalDetallePedido');
        const modalReportePedidoElement = document.getElementById('modalReportePedido');

        const idProducto = document.getElementById('idProducto');
        const idCliente = document.getElementById('idCliente');
        const filtroColor = document.getElementById('filtroColor');
        const idProductoCaracteristica = document.getElementById('idProductoCaracteristica');
        const estadoPedido = document.getElementById('estadoPedido');
        const btnNuevoPedido = document.getElementById('btnNuevoPedido');
        const btnAlternarEstado = document.getElementById('btnAlternarEstado');
        const btnGenerarReporte = document.getElementById('btnGenerarReporte');
        const formReportePedido = document.getElementById('formReportePedido');

        if (
            !form ||
            !modalPedidoElement ||
            !modalDetalleElement ||
            !idProducto ||
            !filtroColor ||
            !idProductoCaracteristica ||
            !estadoPedido
        ) {
            console.error('Faltan elementos HTML requeridos.');
            return;
        }

        const modalPedido = bootstrap.Modal.getOrCreateInstance(modalPedidoElement);
        const modalDetalle = bootstrap.Modal.getOrCreateInstance(modalDetalleElement);
        const modalReportePedido = modalReportePedidoElement
            ? bootstrap.Modal.getOrCreateInstance(modalReportePedidoElement)
            : null;

        // jQuery donde vive Select2 (el global). Evita el problema de instancias duplicadas.
        const $s = window.jQuery || $;

        let tablaPedidos = null;
        let verInhabilitados = false;

        // Evita que cargar un producto por código dispare cargarColores() dos veces
        let silenciarCambioProducto = false;

        function elemento(id) {
            return document.getElementById(id);
        }

        function escaparHtml(valor) {
            const div = document.createElement('div');
            div.textContent = valor ?? '';
            return div.innerHTML;
        }

        function obtenerFiltro() {
            return verInhabilitados ? 'inhabilitados' : 'activos';
        }

        // ==========================================
        // SELECTS CON BÚSQUEDA (SELECT2)
        // ==========================================
        function normalizarTexto(texto) {
            return (texto || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function avisarSelect2Ausente() {
            console.error(
                'Select2 no está disponible. Verifica que select2.min.js se cargue después de jQuery y antes de este módulo.'
            );
        }

        function initSelectBusqueda(campo, opciones = {}) {
            const $campo = $s(campo);

            if (!$campo.length) {
                return $campo;
            }

            if (!$s.fn.select2) {
                avisarSelect2Ausente();
                return $campo;
            }

            if ($campo.hasClass('select2-hidden-accessible')) {
                $campo.select2('destroy');
            }

            $campo.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                language: 'es',
                placeholder: opciones.placeholder || 'Escriba para buscar...',
                dropdownParent: $s(opciones.dropdownParent || '#modalPedido'),
                matcher: function (params, data) {
                    const termino = normalizarTexto(params.term);

                    if (!termino) {
                        return data;
                    }

                    if (normalizarTexto(data.text).indexOf(termino) > -1) {
                        return data;
                    }

                    if (data.children) {
                        const hijos = data.children.filter(function (hijo) {
                            return normalizarTexto(hijo.text).indexOf(termino) > -1;
                        });

                        if (hijos.length) {
                            return $s.extend({}, data, { children: hijos });
                        }
                    }

                    return null;
                },
                ...(opciones.config || {})
            });

            return $campo;
        }

        function refrescarSelectBusqueda(campo, opciones = {}) {
            return initSelectBusqueda(campo, opciones);
        }

        function initSelectBusquedaRemota(campo, opciones = {}) {
            const $campo = $s(campo);

            if (!$campo.length) {
                return $campo;
            }

            if (!$s.fn.select2) {
                avisarSelect2Ausente();
                return $campo;
            }

            if ($campo.hasClass('select2-hidden-accessible')) {
                $campo.select2('destroy');
            }

            $campo.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumInputLength: 2,
                language: 'es',
                placeholder: 'Nombre, apellido o documento del cliente',
                dropdownParent: $s(opciones.dropdownParent || '#modalPedido'),
                ajax: {
                    url: `${BASE_URL}&accion=buscar_clientes`,
                    dataType: 'json',
                    delay: 300,
                    cache: true,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function (respuesta, params) {
                        const datos = (respuesta && respuesta.data) || [];

                        return {
                            results: datos.map(function (cliente) {
                                return {
                                    id: cliente.id_cliente,
                                    text: `${cliente.nombre_razon_social || ''} ${cliente.apellido || ''} — ${cliente.numero_de_documento || 's/d'}`
                                };
                            }),
                            pagination: {
                                more: Boolean(respuesta && respuesta.more)
                            }
                        };
                    }
                }
            });

            return $campo;
        }

        function initSelectCliente() {
            if (!idCliente) {
                return $s([]);
            }

            if (CLIENTES_REMOTO) {
                return initSelectBusquedaRemota(idCliente);
            }

            return initSelectBusqueda(idCliente, {
                placeholder: 'Buscar cliente por nombre o documento...'
            });
        }

        function actualizarTotal() {
            const cantidad = Number(elemento('cantidad')?.value) || 0;
            const manoObra = Number(elemento('costoManoObra')?.value) || 0;
            const materiales = Number(elemento('costoMateriales')?.value) || 0;
            const descuentoProducto = Number(elemento('descuentoProducto')?.value) || 0;
            const descuentoGeneral = Number(elemento('descuentoDivisa')?.value) || 0;

            let total =
                ((manoObra + materiales) * cantidad) -
                descuentoProducto -
                descuentoGeneral;

            if (total < 0) {
                total = 0;
            }

            const totalVisual = elemento('totalVisual');
            if (totalVisual) {
                totalVisual.textContent = total.toFixed(2);
            }
        }

        function resetColores() {
            filtroColor.innerHTML = `
                <option value="">
                    Seleccione primero un producto
                </option>
            `;
            filtroColor.disabled = true;

            refrescarSelectBusqueda(filtroColor, {
                placeholder: 'Seleccione primero un producto'
            });
        }

        function resetTallas() {
            idProductoCaracteristica.innerHTML = `
                <option value="">
                    Seleccione primero un color
                </option>
            `;
            idProductoCaracteristica.disabled = true;

            refrescarSelectBusqueda(idProductoCaracteristica, {
                placeholder: 'Seleccione primero un color'
            });
        }

        // ==========================================
        // RESTRICCIÓN DE SOLO NÚMEROS
        // ==========================================
        const idsNumericos = [
            'cantidad',
            'costoManoObra',
            'costoMateriales',
            'descuentoProducto',
            'descuentoDivisa'
        ];

        idsNumericos.forEach(function (id) {
            const campo = elemento(id);
            if (campo) {
                $s(campo).on('keydown', soloNumerosTeclado);
                campo.addEventListener('input', actualizarTotal);
            }
        });

        // ==========================================
        // AJAX: CARGAR COLORES POR PRODUCTO
        // ==========================================
        async function cargarColores(productoId, colorSeleccionado = '', tallaSeleccionada = '') {
            resetColores();
            resetTallas();

            if (!productoId) {
                return;
            }

            filtroColor.innerHTML = `
                <option value="">
                    Cargando colores...
                </option>
            `;

            refrescarSelectBusqueda(filtroColor, {
                placeholder: 'Cargando colores...'
            });

            try {
                const respuesta = await $s.ajax({
                    url: `${BASE_URL}&accion=colores_por_producto`,
                    type: 'GET',
                    data: { id_producto: productoId },
                    dataType: 'json'
                });

                if (!respuesta.success) {
                    throw new Error(respuesta.message || 'No se pudieron cargar los colores.');
                }

                filtroColor.innerHTML = `
                    <option value="">
                        Seleccione un color
                    </option>
                `;

                if (!Array.isArray(respuesta.data) || respuesta.data.length === 0) {
                    filtroColor.innerHTML = `
                        <option value="">
                            No hay colores activos
                        </option>
                    `;
                    filtroColor.disabled = true;

                    refrescarSelectBusqueda(filtroColor, {
                        placeholder: 'No hay colores activos'
                    });
                    return;
                }

                respuesta.data.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.color;
                    option.textContent = item.color;

                    if (String(option.value) === String(colorSeleccionado)) {
                        option.selected = true;
                    }

                    filtroColor.appendChild(option);
                });

                filtroColor.disabled = false;

                // Select2 toma el option.selected marcado arriba, no hace falta trigger
                refrescarSelectBusqueda(filtroColor, {
                    placeholder: 'Buscar color...'
                });

                if (colorSeleccionado) {
                    await cargarTallas(productoId, colorSeleccionado, tallaSeleccionada);
                }

            } catch (error) {
                console.error(error);
                filtroColor.innerHTML = `
                    <option value="">
                        Error al cargar colores
                    </option>
                `;
                filtroColor.disabled = true;

                refrescarSelectBusqueda(filtroColor, {
                    placeholder: 'Error al cargar colores'
                });

                await errorAlert('Error', error.message || 'Error de comunicación con el servidor.');
            }
        }

        // ==========================================
        // AJAX: CARGAR TALLAS POR PRODUCTO Y COLOR
        // ==========================================
        async function cargarTallas(productoId, color, tallaSeleccionada = '') {
            resetTallas();

            if (!productoId || !color) {
                return;
            }

            idProductoCaracteristica.innerHTML = `
                <option value="">
                    Cargando tallas...
                </option>
            `;

            refrescarSelectBusqueda(idProductoCaracteristica, {
                placeholder: 'Cargando tallas...'
            });

            try {
                const respuesta = await $s.ajax({
                    url: `${BASE_URL}&accion=tallas_por_producto_y_color`,
                    type: 'GET',
                    data: { id_producto: productoId, color: color },
                    dataType: 'json'
                });

                if (!respuesta.success) {
                    throw new Error(respuesta.message || 'No se pudieron cargar las tallas.');
                }

                idProductoCaracteristica.innerHTML = `
                    <option value="">
                        Seleccione una talla
                    </option>
                `;

                if (!Array.isArray(respuesta.data) || respuesta.data.length === 0) {
                    idProductoCaracteristica.innerHTML = `
                        <option value="">
                            No hay tallas activas para este color
                        </option>
                    `;
                    idProductoCaracteristica.disabled = true;

                    refrescarSelectBusqueda(idProductoCaracteristica, {
                        placeholder: 'No hay tallas activas para este color'
                    });
                    return;
                }

                respuesta.data.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.id_producto_caracteristica;
                    option.textContent = `Talla: ${item.talla || 'N/A'} | Material: ${item.detalle_material || 'N/A'} | Prenda: ${item.tipo_de_prenda || 'N/A'}`;

                    if (String(option.value) === String(tallaSeleccionada)) {
                        option.selected = true;
                    }

                    idProductoCaracteristica.appendChild(option);
                });

                idProductoCaracteristica.disabled = false;

                refrescarSelectBusqueda(idProductoCaracteristica, {
                    placeholder: 'Buscar talla, material o prenda...'
                });

            } catch (error) {
                console.error(error);
                idProductoCaracteristica.innerHTML = `
                    <option value="">
                        Error al cargar tallas
                    </option>
                `;
                idProductoCaracteristica.disabled = true;

                refrescarSelectBusqueda(idProductoCaracteristica, {
                    placeholder: 'Error al cargar tallas'
                });

                await errorAlert('Error', error.message || 'Error de comunicación con el servidor.');
            }
        }

        function badgeEstado(estado) {
            const valor = String(estado || '').toLowerCase();
            let clase = 'bg-secondary';

            if (valor === 'pendiente') {
                clase = 'bg-warning text-dark';
            }
            if (valor === 'en proceso') {
                clase = 'bg-info text-dark';
            }
            if (valor === 'realizado') {
                clase = 'bg-primary';
            }
            if (valor === 'entregado') {
                clase = 'bg-success';
            }
            if (valor === 'inhabilitado') {
                clase = 'bg-danger';
            }

            return `
                <span class="badge ${clase}">
                    ${escaparHtml(estado)}
                </span>
            `;
        }

        function columnasDataTable() {
            return [
                {
                    data: 'id_pedido',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `<strong>#${data}</strong>`;
                    }
                },
                {
                    data: 'fecha_creacion',
                    type: 'string',
                    render: function (data, type) {
                        if (!data) return '-';

                        if (type === 'sort' || type === 'type') {
                            return data;
                        }

                        const partes = data.split(' ')[0].split('-');

                        if (partes.length === 3) {
                            return `${partes[2]}/${partes[1]}/${partes[0]}`;
                        }

                        return data;
                    }
                },
                {
                    data: null,
                    type: 'string',
                    render: function (data) {
                        return escaparHtml(
                            `${data.nombre_razon_social || ''} ${data.apellido || ''}`
                        );
                    }
                },
                {
                    data: null,
                    type: 'string',
                    render: function (data) {
                        return `
                            <strong>
                                ${escaparHtml(data.nombre_producto || 'Sin producto')}
                            </strong>
                            <small class="d-block text-muted">
                                ${escaparHtml(data.color || 'Sin color')} · Talla: ${escaparHtml(data.talla || 'N/A')}
                            </small>
                        `;
                    }
                },
                {
                    data: 'nombre_servicio',
                    type: 'string',
                    render: function (data) {
                        return escaparHtml(data || 'Sin servicio');
                    }
                },
                {
                    data: 'cantidad',
                    type: 'num'
                },
                {
                    data: 'estado_pedido',
                    type: 'string',
                    render: function (data) {
                        return badgeEstado(data);
                    }
                },
                {
                    data: 'monto_total',
                    type: 'num',
                    render: function (data) {
                        return Number(data || 0).toFixed(2);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data) {
                        const estado = String(data.estado_pedido || '').toLowerCase();

                        let html = `
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-info btnDetallePedido me-1"
                                data-id="${data.id_pedido}"
                                title="Ver detalle">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        `;

                        if (estado === 'inhabilitado') {
                            html += `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-warning btnEditarPedido"
                                    data-id="${data.id_pedido}"
                                    title="Editar o reactivar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            `;
                        } else {
                            html += `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary btnEditarPedido me-1"
                                    data-id="${data.id_pedido}"
                                    title="Editar pedido">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger btnInhabilitarPedido"
                                    data-id="${data.id_pedido}"
                                    title="Inhabilitar pedido">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            `;
                        }

                        return html;
                    }
                }
            ];
        }

        function crearDataTable() {
            if ($s.fn.DataTable.isDataTable('#tablaPedidos')) {
                $s('#tablaPedidos').DataTable().destroy();
            }

            tablaPedidos = $s('#tablaPedidos').DataTable({
                dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                ajax: {
                    url: `${BASE_URL}&accion=listar&filtro=${obtenerFiltro()}`,
                    type: 'GET',
                    dataType: 'json',
                    dataSrc: function (json) {
                        if (!json.success) {
                            errorAlert(
                                'Error',
                                json.message || 'No se pudieron cargar los registros.'
                            );
                            return [];
                        }

                        return json.data || [];
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                        errorAlert(
                            'Error de carga',
                            'No se pudo conectar con el controlador de pedidos.'
                        );
                    }
                },
                processing: true,
                responsive: true,
                pageLength: 10,
                order: [[1, 'desc']],
                language: lenguajeDataTableEs,
                columns: columnasDataTable()
            });
        }

        function actualizarVista() {
            const titulo = elemento('tituloVista');
            const icono = elemento('iconoEstado');
            const texto = elemento('txtBotonEstado');

            if (verInhabilitados) {
                if (titulo) titulo.textContent = 'Pedidos Inhabilitados';
                if (texto) texto.textContent = 'Ver activos';

                if (icono) {
                    icono.classList.remove('bi-eye-slash-fill');
                    icono.classList.add('bi-eye-fill');
                }
            } else {
                if (titulo) titulo.textContent = 'Gestión de Pedidos';
                if (texto) texto.textContent = 'Ver inhabilitados';

                if (icono) {
                    icono.classList.remove('bi-eye-fill');
                    icono.classList.add('bi-eye-slash-fill');
                }
            }
        }

        function limpiarFormulario() {
            silenciarCambioProducto = true;

            form.reset();
            form.classList.remove('was-validated');

            elemento('accionPedido').value = 'guardar';
            elemento('idPedido').value = '';

            elemento('fechaCreacion').value =
                new Date().toISOString().split('T')[0];

            if (idCliente) {
                if ($s.fn.select2) {
                    $s(idCliente).val(null).trigger('change');
                } else {
                    idCliente.value = '';
                }
            }

            if ($s.fn.select2) {
                $s(idProducto).val(null).trigger('change');
            } else {
                idProducto.value = '';
            }

            resetColores();
            resetTallas();

            estadoPedido.innerHTML = `
                <option value="pendiente">Pendiente</option>
                <option value="en proceso">En proceso</option>
                <option value="realizado">Realizado</option>
                <option value="entregado">Entregado</option>
            `;
            estadoPedido.value = 'pendiente';
            estadoPedido.disabled = true;

            elemento('tituloModalPedido').innerHTML =
                '<i class="bi bi-cart-plus-fill me-2"></i>Registrar Pedido';

            elemento('btnGuardarPedido').innerHTML =
                '<i class="bi bi-check-circle-fill"></i> Guardar pedido';

            actualizarTotal();

            silenciarCambioProducto = false;
        }

        async function validarFormulario() {
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                await errorAlert('Formulario incompleto', 'Complete los campos obligatorios.');
                return false;
            }

            if (idCliente && !idCliente.value) {
                await errorAlert('Cliente requerido', 'Seleccione un cliente.');
                return false;
            }

            if (!idProducto.value) {
                await errorAlert('Producto requerido', 'Seleccione un producto.');
                return false;
            }

            const fechaCreacion = elemento('fechaCreacion').value;
            const fechaEntrega = elemento('fechaEntrega').value;

            if (fechaEntrega && fechaEntrega < fechaCreacion) {
                await errorAlert(
                    'Fecha inválida',
                    'La fecha de entrega no puede ser anterior a la fecha de creación.'
                );
                return false;
            }

            if (!filtroColor.value) {
                await errorAlert('Color requerido', 'Seleccione un color.');
                return false;
            }

            if (!idProductoCaracteristica.value) {
                await errorAlert(
                    'Talla requerida',
                    'Seleccione una talla válida.'
                );
                return false;
            }

            return true;
        }

        // ==========================================
        // AJAX: GUARDAR / EDITAR
        // ==========================================
        async function enviarFormulario() {
            if (!(await validarFormulario())) {
                return;
            }

            const boton = elemento('btnGuardarPedido');
            const textoOriginal = boton.innerHTML;

            boton.disabled = true;
            boton.textContent = 'Procesando...';

            const accion = elemento('accionPedido').value;

            if (accion === 'guardar') {
                estadoPedido.disabled = false;
                estadoPedido.value = 'pendiente';
            }

            try {
                const resultado = await $s.ajax({
                    url: `${BASE_URL}&accion=${accion}`,
                    type: 'POST',
                    data: new FormData(form),
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                });

                if (!resultado.success) {
                    throw new Error(resultado.message || 'No se pudo guardar el pedido.');
                }

                await successAlert('Operación exitosa', resultado.message);

                modalPedido.hide();

                // Se limpia únicamente después de guardar correctamente
                limpiarFormulario();

                tablaPedidos.ajax.reload(null, false);

            } catch (error) {
                await errorAlert('Error', error.message || 'Error de comunicación con el servidor.');

            } finally {
                boton.disabled = false;
                boton.innerHTML = textoOriginal;

                if (elemento('accionPedido').value === 'guardar') {
                    estadoPedido.disabled = true;
                }
            }
        }

        // ==========================================
        // AJAX: OBTENER DATOS DE EDICIÓN
        // ==========================================
        async function editarPedido(idPedido) {
            try {
                const resultado = await $s.ajax({
                    url: `${BASE_URL}&accion=obtener`,
                    type: 'GET',
                    data: { id: idPedido },
                    dataType: 'json'
                });

                if (!resultado.success) {
                    throw new Error(resultado.message || 'No se pudo obtener el pedido.');
                }

                const pedido = resultado.data;
                const detalle = pedido.detalle || {};

                elemento('accionPedido').value = 'editar';
                elemento('idPedido').value = pedido.id_pedido;
                elemento('idTipoPedido').value = pedido.id_tipo_pedido;
                elemento('fechaCreacion').value = pedido.fecha_creacion;
                elemento('fechaEntrega').value = pedido.fecha_entrega || '';
                elemento('descripcion').value = pedido.descripcion || '';
                elemento('descuentoDivisa').value = pedido.descuento_divisa || 0;

                // ----- CLIENTE -----
                if (idCliente) {
                    if ($s.fn.select2) {
                        const $cliente = $s(idCliente);
                        const textoCliente =
                            `${pedido.nombre_razon_social || ''} ${pedido.apellido || ''}`.trim();

                        if (
                            pedido.id_cliente &&
                            !$cliente.find(`option[value="${pedido.id_cliente}"]`).length
                        ) {
                            $cliente.append(
                                new Option(textoCliente || 'Cliente', pedido.id_cliente, true, true)
                            );
                        }

                        $cliente.val(pedido.id_cliente || null).trigger('change');
                    } else {
                        idCliente.value = pedido.id_cliente || '';
                    }
                }

                // ----- PRODUCTO -----
                silenciarCambioProducto = true;

                if ($s.fn.select2) {
                    const $producto = $s(idProducto);

                    if (
                        detalle.id_producto &&
                        !$producto.find(`option[value="${detalle.id_producto}"]`).length
                    ) {
                        $producto.append(
                            new Option(detalle.nombre_producto || 'Producto', detalle.id_producto, true, true)
                        );
                    }

                    $producto.val(detalle.id_producto || null).trigger('change');
                } else {
                    idProducto.value = detalle.id_producto || '';
                }

                silenciarCambioProducto = false;

                await cargarColores(
                    detalle.id_producto || '',
                    detalle.color || '',
                    detalle.id_producto_caracteristica || ''
                );

                elemento('idServicio').value = detalle.id_servicio || '';
                elemento('cantidad').value = detalle.cantidad || 1;
                elemento('costoManoObra').value = detalle.costo_mano_de_obra || 0;
                elemento('costoMateriales').value = detalle.costo_materiales || 0;
                elemento('descuentoProducto').value = detalle.descuento_producto || 0;
                elemento('metodoServicio').value = detalle.metodo_servicio || '';

                estadoPedido.disabled = false;
                estadoPedido.innerHTML = `
                    <option value="pendiente">Pendiente</option>
                    <option value="en proceso">En proceso</option>
                    <option value="realizado">Realizado</option>
                    <option value="entregado">Entregado</option>
                    <option value="inhabilitado">Inhabilitado</option>
                `;
                estadoPedido.value = pedido.estado_pedido || 'pendiente';

                elemento('tituloModalPedido').innerHTML =
                    '<i class="bi bi-pencil-square me-2"></i>Editar Pedido';

                elemento('btnGuardarPedido').innerHTML =
                    '<i class="bi bi-save-fill"></i> Guardar cambios';

                actualizarTotal();
                modalPedido.show();

            } catch (error) {
                await errorAlert('Error', error.message || 'No se pudo conectar con el servidor.');
            }
        }

        // ==========================================
        // AJAX: OBTENER DETALLE
        // ==========================================
        async function detallePedido(idPedido) {
            try {
                const resultado = await $s.ajax({
                    url: `${BASE_URL}&accion=obtener`,
                    type: 'GET',
                    data: { id: idPedido },
                    dataType: 'json'
                });

                if (!resultado.success) {
                    throw new Error(resultado.message || 'No se pudo obtener el detalle.');
                }

                const pedido = resultado.data;
                const detalle = pedido.detalle || {};

                elemento('detalleIdPedido').textContent = '#' + pedido.id_pedido;
                elemento('detalleFechaCreacion').textContent = pedido.fecha_creacion || '-';
                elemento('detalleFechaEntrega').textContent = pedido.fecha_entrega || '-';
                elemento('detalleTipoPedido').textContent = pedido.nombre_tipo_pedido || '-';
                elemento('detalleEstadoPedido').textContent = pedido.estado_pedido || '-';
                elemento('detalleDescripcion').textContent = pedido.descripcion || 'Sin descripción';
                elemento('detalleDescuentoGeneral').textContent =
                    Number(pedido.descuento_divisa || 0).toFixed(2);

                elemento('detalleCliente').textContent =
                    `${pedido.nombre_razon_social || ''} ${pedido.apellido || ''}`;
                elemento('detalleDocumento').textContent = pedido.numero_de_documento || '-';
                elemento('detalleCorreo').textContent = pedido.correo || '-';
                elemento('detalleTelefono').textContent = pedido.telefono || '-';
                elemento('detalleDireccion').textContent = pedido.direccion || '-';

                elemento('detalleProducto').textContent = detalle.nombre_producto || '-';
                elemento('detalleTipoProducto').textContent = detalle.tipo_de_producto || '-';
                elemento('detalleTalla').textContent = detalle.talla || '-';
                elemento('detalleMaterial').textContent = detalle.detalle_material || '-';
                elemento('detalleColor').textContent = detalle.color || '-';
                elemento('detalleTipoPrenda').textContent = detalle.tipo_de_prenda || '-';
                elemento('detalleServicio').textContent = detalle.nombre_servicio || '-';
                elemento('detalleCantidad').textContent = detalle.cantidad || '-';
                elemento('detalleMetodoServicio').textContent = detalle.metodo_servicio || '-';
                elemento('detalleManoObra').textContent =
                    Number(detalle.costo_mano_de_obra || 0).toFixed(2);
                elemento('detalleCostoMateriales').textContent =
                    Number(detalle.costo_materiales || 0).toFixed(2);
                elemento('detalleDescuentoProducto').textContent =
                    Number(detalle.descuento_producto || 0).toFixed(2);
                elemento('detalleTotal').textContent =
                    Number(pedido.monto_total || 0).toFixed(2);

                modalDetalle.show();

            } catch (error) {
                await errorAlert('Error', error.message || 'No se pudo conectar con el servidor.');
            }
        }

        // ==========================================
        // AJAX: INHABILITAR
        // ==========================================
        async function inhabilitarPedido(idPedido) {
            confirmDeleteAlert(async () => {
                try {
                    const formData = new FormData();
                    formData.append('accion', 'inhabilitar');
                    formData.append('id_pedido', idPedido);

                    const resultado = await $s.ajax({
                        url: `${BASE_URL}&accion=inhabilitar`,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json'
                    });

                    if (!resultado.success) {
                        throw new Error(
                            resultado.message || 'No se pudo inhabilitar el pedido.'
                        );
                    }

                    await successAlert('Inhabilitado', resultado.message);
                    tablaPedidos.ajax.reload(null, false);

                } catch (error) {
                    await errorAlert('Error', error.message || 'Error al procesar la inhabilitación.');
                }
            });
        }

        // ==========================================
        // EVENTOS DE SELECTS
        // ==========================================
        $s(idProducto).on('change', function () {
            if (silenciarCambioProducto) {
                return;
            }

            cargarColores(this.value);
        });

        $s(filtroColor).on('change', function () {
            cargarTallas(idProducto.value, this.value);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            enviarFormulario();
        });

        // ==========================================
        // BOTÓN REGISTRAR PEDIDO:
        // SOLO ABRE EL MODAL, NO LIMPIA LOS DATOS
        // ==========================================
        btnNuevoPedido.addEventListener('click', function () {
            modalPedido.show();
        });

        btnAlternarEstado.addEventListener('click', function () {
            verInhabilitados = !verInhabilitados;
            actualizarVista();
            crearDataTable();
        });

        // ==========================================
        // EVENTOS DE REPORTE
        // ==========================================
        if (btnGenerarReporte) {
            btnGenerarReporte.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (modalReportePedido) {
                    modalReportePedido.show();
                }
            });
        }

        const chkTodo = document.getElementById('reporteTodo');

        if (chkTodo) {
            chkTodo.addEventListener('change', function () {
                const camposFiltro = document.querySelectorAll('.campo-filtro');

                camposFiltro.forEach(campo => {
                    campo.disabled = this.checked;

                    if (this.checked) {
                        if (campo.tagName === 'SELECT') {
                            Array.from(campo.options).forEach(opt => opt.selected = false);
                        } else {
                            campo.value = '';
                        }
                    }
                });
            });
        }

        if (formReportePedido) {
            formReportePedido.addEventListener('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const parametros = new URLSearchParams();
                const elementosForm = formReportePedido.elements;

                for (let el of elementosForm) {
                    if (el.name) {
                        if (el.type === 'checkbox' || el.type === 'radio') {
                            if (el.checked) {
                                parametros.append(el.name, el.value);
                            }
                        } else if (el.tagName === 'SELECT' && el.multiple) {
                            Array.from(el.selectedOptions).forEach(opt => {
                                parametros.append(el.name, opt.value);
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

                if (modalReportePedido) {
                    modalReportePedido.hide();
                }
            });
        }

        // ==========================================
        // BOTÓN CANCELAR: ÚNICO BOTÓN QUE LIMPIA
        // ==========================================
        const btnCancelarPedido = modalPedidoElement.querySelector('.btnCancelarPedido');

        if (btnCancelarPedido) {
            btnCancelarPedido.addEventListener('click', function () {
                limpiarFormulario();
            });
        }

        // ==========================================
        // IMPORTANTE:
        // La "X" y el clic fuera del modal NO limpian el formulario.
        // No debe existir ningún evento hidden.bs.modal que llame a limpiarFormulario().
        // ==========================================

        // ==========================================
        // EVENTOS DE CLIC EN BOTONES DE LA TABLA
        // ==========================================
        document.addEventListener('click', function (event) {
            const botonDetalle = event.target.closest('.btnDetallePedido');

            if (botonDetalle) {
                detallePedido(botonDetalle.dataset.id);
                return;
            }

            const botonEditar = event.target.closest('.btnEditarPedido');

            if (botonEditar) {
                editarPedido(botonEditar.dataset.id);
                return;
            }

            const botonInhabilitar = event.target.closest('.btnInhabilitarPedido');

            if (botonInhabilitar) {
                inhabilitarPedido(botonInhabilitar.dataset.id);
            }
        });

        // ==========================================
        // INICIALIZACIÓN DE SELECTS CON BÚSQUEDA
        // ==========================================
        initSelectCliente();

        initSelectBusqueda(idProducto, {
            placeholder: 'Buscar producto por nombre...'
        });

        resetColores();
        resetTallas();

        // Inicialización: limpia una sola vez al cargar la página
        limpiarFormulario();
        actualizarVista();
        crearDataTable();
    }
);
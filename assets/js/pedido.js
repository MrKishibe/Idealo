import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';

document.addEventListener(
    'DOMContentLoaded',
    function () {
        const BASE_URL = 'index.php?controller=pedido';

        const form = document.getElementById('formPedido');
        const modalPedidoElement = document.getElementById('modalPedido');
        const modalDetalleElement = document.getElementById('modalDetallePedido');
        const modalReportePedidoElement = document.getElementById('modalReportePedido');
        
        const idProducto = document.getElementById('idProducto');
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
            !idProductoCaracteristica ||
            !estadoPedido
        ) {
            console.error('Faltan elementos HTML requeridos.');
            return;
        }

        const modalPedido = bootstrap.Modal.getOrCreateInstance(modalPedidoElement);
        const modalDetalle = bootstrap.Modal.getOrCreateInstance(modalDetalleElement);
        const modalReportePedido = modalReportePedidoElement ? bootstrap.Modal.getOrCreateInstance(modalReportePedidoElement) : null;

        let tablaPedidos = null;
        let verInhabilitados = false;

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

        function resetCaracteristicas() {
            idProductoCaracteristica.innerHTML = `
                <option value="">
                    Seleccione primero un producto
                </option>
            `;
            idProductoCaracteristica.disabled = true;
        }

        // ==========================================
        // 1. AJAX PARA CARGAR CARACTERÍSTICAS (GET)
        // ==========================================
        async function cargarCaracteristicas(
            productoId,
            caracteristicaSeleccionada = ''
        ) {
            resetCaracteristicas();

            if (!productoId) {
                return;
            }

            idProductoCaracteristica.innerHTML = `
                <option value="">
                    Cargando características...
                </option>
            `;

            try {
                const respuesta = await $.ajax({
                    url: `${BASE_URL}&accion=caracteristicas_por_producto`,
                    type: 'GET',
                    data: { id_producto: productoId },
                    dataType: 'json'
                });

                if (!respuesta.success) {
                    throw new Error(
                        respuesta.message ||
                        'No se pudieron cargar las características.'
                    );
                }

                idProductoCaracteristica.innerHTML = `
                    <option value="">
                        Seleccione una característica
                    </option>
                `;

                if (
                    !Array.isArray(respuesta.data) ||
                    respuesta.data.length === 0
                ) {
                    idProductoCaracteristica.innerHTML = `
                        <option value="">
                            No hay características activas
                        </option>
                    `;
                    idProductoCaracteristica.disabled = true;
                    return;
                }

                respuesta.data.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.id_producto_caracteristica;
                    option.textContent =
                        `Material: ${item.detalle_material || 'N/A'} | ` +
                        `Color: ${item.color || 'N/A'} | ` +
                        `Prenda: ${item.tipo_de_prenda || 'N/A'} | ` +
                        `Talla: ${item.talla || 'N/A'}`;

                    if (
                        String(option.value) ===
                        String(caracteristicaSeleccionada)
                    ) {
                        option.selected = true;
                    }

                    idProductoCaracteristica.appendChild(option);
                });

                idProductoCaracteristica.disabled = false;

            } catch (error) {
                console.error(error);

                idProductoCaracteristica.innerHTML = `
                    <option value="">
                        Error al cargar características
                    </option>
                `;
                idProductoCaracteristica.disabled = true;

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
                    render: function (data) {
                        return `<strong>#${data}</strong>`;
                    }
                },
                {
                    data: 'fecha_creacion'
                },
                {
                    data: null,
                    render: function (data) {
                        return escaparHtml(
                            `${data.nombre_razon_social || ''} ${data.apellido || ''}`
                        );
                    }
                },
                {
                    data: null,
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
                    render: function (data) {
                        return escaparHtml(data || 'Sin servicio');
                    }
                },
                {
                    data: 'cantidad'
                },
                {
                    data: 'estado_pedido',
                    render: function (data) {
                        return badgeEstado(data);
                    }
                },
                {
                    data: 'monto_total',
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
            if ($.fn.DataTable.isDataTable('#tablaPedidos')) {
                $('#tablaPedidos').DataTable().destroy();
            }

            tablaPedidos = $('#tablaPedidos').DataTable({
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
            form.reset();
            form.classList.remove('was-validated');

            elemento('accionPedido').value = 'guardar';
            elemento('idPedido').value = '';

            elemento('fechaCreacion').value =
                new Date().toISOString().split('T')[0];

            resetCaracteristicas();

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
        }

        async function validarFormulario() {
            form.classList.add('was-validated');

            if (!form.checkValidity()) {
                await errorAlert('Formulario incompleto', 'Complete los campos obligatorios.');
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

            if (!idProductoCaracteristica.value) {
                await errorAlert(
                    'Característica requerida',
                    'Seleccione una característica y talla.'
                );
                return false;
            }

            return true;
        }

        // ==========================================
        // 2. AJAX PARA GUARDAR / EDITAR (POST)
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
                const resultado = await $.ajax({
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
        // 3. AJAX PARA OBTENER DATOS DE EDICIÓN (GET)
        // ==========================================
        async function editarPedido(idPedido) {
            try {
                const resultado = await $.ajax({
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
                elemento('idCliente').value = pedido.id_cliente;
                elemento('idTipoPedido').value = pedido.id_tipo_pedido;
                elemento('fechaCreacion').value = pedido.fecha_creacion;
                elemento('fechaEntrega').value = pedido.fecha_entrega || '';
                elemento('descripcion').value = pedido.descripcion || '';
                elemento('descuentoDivisa').value = pedido.descuento_divisa || 0;

                idProducto.value = detalle.id_producto || '';
                await cargarCaracteristicas(
                    detalle.id_producto || '',
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
        // 4. AJAX PARA OBTENER DETALLE (GET)
        // ==========================================
        async function detallePedido(idPedido) {
            try {
                const resultado = await $.ajax({
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
        // 5. AJAX PARA INHABILITAR (POST)
        // ==========================================
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

        // ===== EVENTOS =====
        idProducto.addEventListener('change', function () {
            cargarCaracteristicas(idProducto.value);
        });

        [
            'cantidad',
            'costoManoObra',
            'costoMateriales',
            'descuentoProducto',
            'descuentoDivisa'
        ].forEach(function (id) {
            const campo = elemento(id);
            if (campo) {
                campo.addEventListener('input', actualizarTotal);
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            enviarFormulario();
        });

        btnNuevoPedido.addEventListener('click', function () {
            limpiarFormulario();
        });

        btnAlternarEstado.addEventListener('click', function () {
            verInhabilitados = !verInhabilitados;
            actualizarVista();
            crearDataTable();
        });

        // ==========================================
        // 6. EVENTOS DE REPORTE CON MODAL Y AJAX BLOB
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

        // Lógica para bloquear/desbloquear campos si se marca "Todo" en reportes
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

                const formData = new FormData(formReportePedido);
                formData.append('filtro', obtenerFiltro());

                // Capturar IDs visibles en el DataTable actual si se desea sincronizar
                if (tablaPedidos) {
                    const datosFiltrados = tablaPedidos.rows({ search: 'applied' }).data().toArray();
                    const ids = datosFiltrados.map(pedido => pedido.id_pedido);
                    formData.append('ids_filtrados', JSON.stringify(ids));
                }

                $.ajax({
                    url: `${BASE_URL}&accion=generarreportepdf`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function (blob) {
                        if (modalReportePedido) {
                            modalReportePedido.hide();
                        }
                        const link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.target = '_blank';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    },
                    error: async function (xhr) {
                        try {
                            const reader = new FileReader();
                            reader.onload = async function() {
                                try {
                                    const errorRes = JSON.parse(reader.result);
                                    await errorAlert('Error al generar reporte', errorRes.message || 'No se pudo procesar el PDF.');
                                } catch (err) {
                                    await errorAlert('Error', 'No se pudo generar el reporte PDF.');
                                }
                            };
                            reader.readAsText(xhr.response);
                        } catch (err) {
                            await errorAlert('Error', 'No se pudo conectar con el servidor para generar el reporte.');
                        }
                    }
                });
            });
        }

        const btnCancelarPedido = modalPedidoElement.querySelector(
            '.btn-light[data-bs-dismiss="modal"]'
        );

        if (btnCancelarPedido) {
            btnCancelarPedido.addEventListener('click', function () {
                limpiarFormulario();
            });
        }

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

        // Inicialización
        limpiarFormulario();
        actualizarVista();
        crearDataTable();
    }
);
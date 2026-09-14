document.addEventListener(
    'DOMContentLoaded',
    function () {
        const BASE_URL =
            `${window.location.origin}/idealo/index.php?url=pedido/listarpedido`;

        const form =
            document.getElementById('formPedido');

        const modalPedidoElement =
            document.getElementById('modalPedido');

        const modalDetalleElement =
            document.getElementById('modalDetallePedido');

        const idProducto =
            document.getElementById('idProducto');

        const idProductoCaracteristica =
            document.getElementById('idProductoCaracteristica');

        const estadoPedido =
            document.getElementById('estadoPedido');

        const btnNuevoPedido =
            document.getElementById('btnNuevoPedido');

        const btnAlternarEstado =
            document.getElementById('btnAlternarEstado');

        const btnGenerarReporte =
            document.getElementById('btnGenerarReporte');

        const btnGuardarPedido =
            document.getElementById('btnGuardarPedido');

        const btnCancelarPedido =
            modalPedidoElement
                ? modalPedidoElement.querySelector(
                    '.modal-footer .btn-light[data-bs-dismiss="modal"]'
                )
                : null;

        if (
            !form ||
            !modalPedidoElement ||
            !modalDetalleElement ||
            !idProducto ||
            !idProductoCaracteristica ||
            !estadoPedido ||
            !btnNuevoPedido ||
            !btnAlternarEstado ||
            !btnGenerarReporte ||
            !btnGuardarPedido ||
            !btnCancelarPedido
        ) {
            console.error(
                'Faltan elementos HTML requeridos para el módulo de pedidos.'
            );

            return;
        }

        const modalPedido =
            bootstrap.Modal.getOrCreateInstance(
                modalPedidoElement
            );

        const modalDetalle =
            bootstrap.Modal.getOrCreateInstance(
                modalDetalleElement
            );

        let tablaPedidos = null;
        let verInhabilitados = false;
        let hayBorradorPedido = false;

        function elemento(id) {
            return document.getElementById(id);
        }

        /*
        |--------------------------------------------------------------------------
        | SweetAlert2 sin temporizador
        |--------------------------------------------------------------------------
        |
        | La alerta solo se cierra cuando el usuario pulsa el botón OK.
        */
        function alerta(icono, titulo, texto) {
            return Swal.fire({
                icon: icono,
                title: titulo,
                text: texto,
                confirmButtonText: 'OK',
                confirmButtonColor: '#198754',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
        }

        function escaparHtml(valor) {
            const div = document.createElement('div');

            div.textContent = valor ?? '';

            return div.innerHTML;
        }

        function obtenerFiltro() {
            return verInhabilitados
                ? 'inhabilitados'
                : 'activos';
        }

        function obtenerNumero(id) {
            const campo =
                elemento(id);

            if (!campo) {
                return 0;
            }

            return Number(campo.value) || 0;
        }

        function formatearNumero(valor) {
            return Number(valor || 0).toFixed(2);
        }

        function actualizarTotal() {
            const cantidad =
                obtenerNumero('cantidad');

            const manoObra =
                obtenerNumero('costoManoObra');

            const materiales =
                obtenerNumero('costoMateriales');

            const descuentoProducto =
                obtenerNumero('descuentoProducto');

            const descuentoGeneral =
                obtenerNumero('descuentoDivisa');

            let total =
                ((manoObra + materiales) * cantidad) -
                descuentoProducto -
                descuentoGeneral;

            if (total < 0) {
                total = 0;
            }

            const totalVisual =
                elemento('totalVisual');

            if (totalVisual) {
                totalVisual.textContent =
                    total.toFixed(2);
            }
        }

        function resetCaracteristicas() {
            idProductoCaracteristica.innerHTML = `
                <option value="">
                    Seleccione primero un producto
                </option>
            `;

            idProductoCaracteristica.value =
                '';

            idProductoCaracteristica.disabled =
                true;
        }

        function opcionesEstado(
            incluirInhabilitado = false
        ) {
            let opciones = `
                <option value="pendiente">
                    Pendiente
                </option>

                <option value="en proceso">
                    En proceso
                </option>

                <option value="realizado">
                    Realizado
                </option>

                <option value="entregado">
                    Entregado
                </option>
            `;

            if (incluirInhabilitado) {
                opciones += `
                    <option value="inhabilitado">
                        Inhabilitado
                    </option>
                `;
            }

            return opciones;
        }

        function prepararNuevoPedido() {
            form.reset();

            form.classList.remove(
                'was-validated'
            );

            elemento('accionPedido').value =
                'guardar';

            elemento('idPedido').value =
                '';

            elemento('fechaCreacion').value =
                new Date()
                    .toISOString()
                    .split('T')[0];

            elemento('fechaEntrega').value =
                '';

            elemento('descripcion').value =
                '';

            elemento('descuentoDivisa').value =
                '0';

            idProducto.value =
                '';

            elemento('idServicio').value =
                '';

            elemento('cantidad').value =
                '1';

            elemento('costoManoObra').value =
                '0';

            elemento('costoMateriales').value =
                '0';

            elemento('descuentoProducto').value =
                '0';

            elemento('metodoServicio').value =
                '';

            resetCaracteristicas();

            estadoPedido.innerHTML =
                opcionesEstado(false);

            estadoPedido.value =
                'pendiente';

            estadoPedido.disabled =
                true;

            btnGuardarPedido.innerHTML =
                '<i class="bi bi-check-circle-fill"></i>' +
                ' Guardar pedido';

            elemento('tituloModalPedido').innerHTML =
                '<i class="bi bi-cart-plus-fill me-2"></i>' +
                'Registrar Pedido';

            hayBorradorPedido = false;

            actualizarTotal();
        }

        function validarFormulario() {
            form.classList.add(
                'was-validated'
            );

            if (!form.checkValidity()) {
                alerta(
                    'warning',
                    'Formulario incompleto',
                    'Complete los campos obligatorios.'
                );

                return false;
            }

            const fechaCreacion =
                elemento('fechaCreacion').value;

            const fechaEntrega =
                elemento('fechaEntrega').value;

            if (
                fechaEntrega &&
                fechaEntrega < fechaCreacion
            ) {
                alerta(
                    'warning',
                    'Fecha inválida',
                    'La fecha de entrega no puede ser anterior a la fecha de creación.'
                );

                return false;
            }

            if (!idProductoCaracteristica.value) {
                alerta(
                    'warning',
                    'Característica requerida',
                    'Seleccione una característica y talla.'
                );

                return false;
            }

            return true;
        }

        async function obtenerJson(url) {
            const response = await fetch(
                url,
                {
                    headers: {
                        Accept: 'application/json'
                    }
                }
            );

            const textoRespuesta =
                await response.text();

            let resultado;

            try {
                resultado = JSON.parse(
                    textoRespuesta
                );
            } catch (error) {
                console.error(
                    'Respuesta no válida del servidor:',
                    textoRespuesta
                );

                throw new Error(
                    'El servidor no devolvió JSON válido.'
                );
            }

            if (!response.ok || !resultado.success) {
                throw new Error(
                    resultado.message ||
                    'No se pudo procesar la solicitud.'
                );
            }

            return resultado;
        }

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

            idProductoCaracteristica.disabled =
                true;

            try {
                const respuesta = await obtenerJson(
                    `${BASE_URL}&accion=caracteristicas_por_producto&id_producto=${encodeURIComponent(productoId)}`
                );

                const caracteristicas = Array.isArray(
                    respuesta.data
                )
                    ? respuesta.data
                    : [];

                idProductoCaracteristica.innerHTML = `
                    <option value="">
                        Seleccione una característica
                    </option>
                `;

                if (caracteristicas.length === 0) {
                    idProductoCaracteristica.innerHTML = `
                        <option value="">
                            No hay características activas
                        </option>
                    `;

                    idProductoCaracteristica.disabled =
                        true;

                    return;
                }

                caracteristicas.forEach(function (item) {
                    const option =
                        document.createElement('option');

                    option.value =
                        item.id_producto_caracteristica;

                    option.textContent =
                        `Material: ${item.detalle_material || 'N/A'} | ` +
                        `Color: ${item.color || 'N/A'} | ` +
                        `Prenda: ${item.tipo_de_prenda || 'N/A'} | ` +
                        `Talla: ${item.talla || 'N/A'}`;

                    idProductoCaracteristica.appendChild(
                        option
                    );
                });

                idProductoCaracteristica.disabled =
                    false;

                if (
                    caracteristicaSeleccionada !== ''
                ) {
                    idProductoCaracteristica.value =
                        String(caracteristicaSeleccionada);

                    if (
                        idProductoCaracteristica.value === ''
                    ) {
                        console.warn(
                            'La característica del pedido no se encuentra disponible.',
                            caracteristicaSeleccionada
                        );
                    }
                }

            } catch (error) {
                console.error(
                    'Error al cargar características:',
                    error
                );

                idProductoCaracteristica.innerHTML = `
                    <option value="">
                        Error al cargar características
                    </option>
                `;

                idProductoCaracteristica.disabled =
                    true;

                await alerta(
                    'error',
                    'Error',
                    error.message
                );
            }
        }

        function badgeEstado(estado) {
            const valor =
                String(estado || '').toLowerCase();

            let clase = 'bg-secondary';

            if (valor === 'pendiente') {
                clase = 'bg-warning text-dark';
            } else if (valor === 'en proceso') {
                clase = 'bg-info text-dark';
            } else if (valor === 'realizado') {
                clase = 'bg-primary';
            } else if (valor === 'entregado') {
                clase = 'bg-success';
            } else if (valor === 'inhabilitado') {
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
                        return `
                            <strong>
                                #${escaparHtml(data)}
                            </strong>
                        `;
                    }
                },
                {
                    data: 'fecha_creacion',
                    render: function (data) {
                        return escaparHtml(data || '-');
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        const cliente = [
                            data.nombre_razon_social || '',
                            data.apellido || ''
                        ]
                            .join(' ')
                            .trim();

                        return escaparHtml(
                            cliente || 'Sin cliente'
                        );
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return `
                            <strong>
                                ${escaparHtml(
                                    data.nombre_producto ||
                                    'Sin producto'
                                )}
                            </strong>

                            <small class="d-block text-muted">
                                ${escaparHtml(
                                    data.color ||
                                    'Sin color'
                                )}
                                · Talla:
                                ${escaparHtml(
                                    data.talla ||
                                    'N/A'
                                )}
                            </small>
                        `;
                    }
                },
                {
                    data: 'nombre_servicio',
                    render: function (data) {
                        return escaparHtml(
                            data || 'Sin servicio'
                        );
                    }
                },
                {
                    data: 'cantidad',
                    render: function (data) {
                        return escaparHtml(data || 0);
                    }
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
                        return formatearNumero(data);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data) {
                        const estado =
                            String(
                                data.estado_pedido || ''
                            ).toLowerCase();

                        let html = `
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-info btnDetallePedido me-1"
                                data-id="${escaparHtml(data.id_pedido)}"
                                title="Ver detalle">

                                <i class="bi bi-eye-fill"></i>
                            </button>
                        `;

                        if (estado === 'inhabilitado') {
                            html += `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-warning btnEditarPedido"
                                    data-id="${escaparHtml(data.id_pedido)}"
                                    title="Editar o reactivar">

                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            `;
                        } else {
                            html += `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary btnEditarPedido me-1"
                                    data-id="${escaparHtml(data.id_pedido)}"
                                    title="Editar pedido">

                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger btnInhabilitarPedido"
                                    data-id="${escaparHtml(data.id_pedido)}"
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
            if (
                $.fn.DataTable.isDataTable(
                    '#tablaPedidos'
                )
            ) {
                $('#tablaPedidos')
                    .DataTable()
                    .destroy();
            }

            tablaPedidos =
                $('#tablaPedidos').DataTable({
                    ajax: {
                        url:
                            `${BASE_URL}&accion=listar&filtro=${obtenerFiltro()}`,

                        dataSrc: function (json) {
                            if (!json.success) {
                                alerta(
                                    'error',
                                    'Error',
                                    json.message ||
                                    'No se pudieron cargar los registros.'
                                );

                                return [];
                            }

                            return json.data || [];
                        },

                        error: function (xhr) {
                            console.error(
                                'Error al cargar DataTable:',
                                xhr.responseText
                            );

                            alerta(
                                'error',
                                'Error de carga',
                                'El servidor no devolvió los registros correctamente.'
                            );
                        }
                    },

                    processing: true,
                    responsive: true,
                    pageLength: 10,

                    language: {
                        processing: 'Procesando...',
                        search: 'Buscar:',
                        lengthMenu:
                            'Mostrar _MENU_ registros',
                        info:
                            'Mostrando _START_ a _END_ de _TOTAL_ registros',
                        infoEmpty:
                            'Mostrando 0 a 0 de 0 registros',
                        infoFiltered:
                            '(filtrado de _MAX_ registros)',
                        zeroRecords:
                            'No se encontraron pedidos',
                        emptyTable:
                            'No hay pedidos registrados',
                        paginate: {
                            first: 'Primero',
                            last: 'Último',
                            next: 'Siguiente',
                            previous: 'Anterior'
                        }
                    },

                    columns: columnasDataTable()
                });
        }

        function actualizarVista() {
            const titulo =
                elemento('tituloVista');

            const icono =
                elemento('iconoEstado');

            const texto =
                elemento('txtBotonEstado');

            if (verInhabilitados) {
                if (titulo) {
                    titulo.textContent =
                        'Pedidos Inhabilitados';
                }

                if (texto) {
                    texto.textContent =
                        'Ver activos';
                }

                if (icono) {
                    icono.classList.remove(
                        'bi-eye-slash-fill'
                    );

                    icono.classList.add(
                        'bi-eye-fill'
                    );
                }

            } else {
                if (titulo) {
                    titulo.textContent =
                        'Gestión de Pedidos';
                }

                if (texto) {
                    texto.textContent =
                        'Ver inhabilitados';
                }

                if (icono) {
                    icono.classList.remove(
                        'bi-eye-fill'
                    );

                    icono.classList.add(
                        'bi-eye-slash-fill'
                    );
                }
            }
        }

        async function enviarFormulario() {
            if (!validarFormulario()) {
                return;
            }

            const accion =
                elemento('accionPedido').value;

            const estadoEstabaDeshabilitado =
                estadoPedido.disabled;

            btnGuardarPedido.disabled =
                true;

            btnGuardarPedido.textContent =
                'Procesando...';

            estadoPedido.disabled =
                false;

            if (accion === 'guardar') {
                estadoPedido.value =
                    'pendiente';
            }

            try {
                const datosFormulario =
                    new FormData(form);

                const response = await fetch(
                    BASE_URL,
                    {
                        method: 'POST',
                        body: datosFormulario,
                        headers: {
                            Accept: 'application/json'
                        }
                    }
                );

                const textoRespuesta =
                    await response.text();

                let resultado;

                try {
                    resultado = JSON.parse(
                        textoRespuesta
                    );
                } catch (error) {
                    console.error(
                        'Respuesta del servidor:',
                        textoRespuesta
                    );

                    throw new Error(
                        'El servidor no devolvió JSON válido.'
                    );
                }

                if (
                    !response.ok ||
                    !resultado.success
                ) {
                    throw new Error(
                        resultado.message ||
                        'No se pudo guardar el pedido.'
                    );
                }

                await alerta(
                    'success',
                    'Operación exitosa',
                    resultado.message
                );

                prepararNuevoPedido();

                modalPedido.hide();

                if (tablaPedidos) {
                    tablaPedidos.ajax.reload(
                        null,
                        false
                    );
                }

            } catch (error) {
                console.error(
                    'Error al guardar pedido:',
                    error
                );

                await alerta(
                    'error',
                    'Error',
                    error.message ||
                    'No se pudo completar la operación.'
                );

            } finally {
                btnGuardarPedido.disabled =
                    false;

                if (
                    elemento('accionPedido').value ===
                    'editar'
                ) {
                    btnGuardarPedido.innerHTML =
                        '<i class="bi bi-save-fill"></i>' +
                        ' Guardar cambios';
                } else {
                    btnGuardarPedido.innerHTML =
                        '<i class="bi bi-check-circle-fill"></i>' +
                        ' Guardar pedido';
                }

                if (
                    elemento('accionPedido').value ===
                    'guardar'
                ) {
                    estadoPedido.disabled =
                        true;
                } else {
                    estadoPedido.disabled =
                        estadoEstabaDeshabilitado;
                }
            }
        }

        async function editarPedido(idPedido) {
            if (!idPedido) {
                await alerta(
                    'error',
                    'Error',
                    'No se recibió el identificador del pedido.'
                );

                return;
            }

            try {
                const resultado = await obtenerJson(
                    `${BASE_URL}&accion=obtener&id=${encodeURIComponent(idPedido)}`
                );

                const pedido =
                    resultado.data || {};

                const detalle =
                    pedido.detalle || {};

                if (!pedido.id_pedido) {
                    throw new Error(
                        'El pedido recibido no contiene un identificador válido.'
                    );
                }

                elemento('accionPedido').value =
                    'editar';

                elemento('idPedido').value =
                    pedido.id_pedido ?? '';

                elemento('idCliente').value =
                    pedido.id_cliente ?? '';

                elemento('idTipoPedido').value =
                    pedido.id_tipo_pedido ?? '';

                elemento('fechaCreacion').value =
                    pedido.fecha_creacion ?? '';

                elemento('fechaEntrega').value =
                    pedido.fecha_entrega ?? '';

                elemento('descripcion').value =
                    pedido.descripcion ?? '';

                elemento('descuentoDivisa').value =
                    pedido.descuento_divisa ?? 0;

                const productoId =
                    detalle.id_producto ??
                    pedido.id_producto ??
                    '';

                const caracteristicaId =
                    detalle.id_producto_caracteristica ??
                    pedido.id_producto_caracteristica ??
                    '';

                idProducto.value =
                    productoId;

                await cargarCaracteristicas(
                    productoId,
                    caracteristicaId
                );

                elemento('idServicio').value =
                    detalle.id_servicio ??
                    pedido.id_servicio ??
                    '';

                elemento('cantidad').value =
                    detalle.cantidad ??
                    pedido.cantidad ??
                    1;

                elemento('costoManoObra').value =
                    detalle.costo_mano_de_obra ??
                    pedido.costo_mano_de_obra ??
                    0;

                elemento('costoMateriales').value =
                    detalle.costo_materiales ??
                    pedido.costo_materiales ??
                    0;

                elemento('descuentoProducto').value =
                    detalle.descuento_producto ??
                    pedido.descuento_producto ??
                    0;

                elemento('metodoServicio').value =
                    detalle.metodo_servicio ??
                    pedido.metodo_servicio ??
                    '';

                estadoPedido.innerHTML =
                    opcionesEstado(true);

                estadoPedido.disabled =
                    false;

                estadoPedido.value =
                    pedido.estado_pedido ??
                    'pendiente';

                if (estadoPedido.value === '') {
                    estadoPedido.value =
                        'pendiente';
                }

                elemento('tituloModalPedido').innerHTML =
                    '<i class="bi bi-pencil-square me-2"></i>' +
                    'Editar Pedido';

                btnGuardarPedido.innerHTML =
                    '<i class="bi bi-save-fill"></i>' +
                    ' Guardar cambios';

                form.classList.remove(
                    'was-validated'
                );

                hayBorradorPedido = true;

                actualizarTotal();

                modalPedido.show();

            } catch (error) {
                console.error(
                    'Error al cargar pedido para editar:',
                    error
                );

                await alerta(
                    'error',
                    'Error al editar',
                    error.message ||
                    'No se pudo cargar el pedido seleccionado.'
                );
            }
        }

        async function detallePedido(idPedido) {
            if (!idPedido) {
                await alerta(
                    'error',
                    'Error',
                    'No se recibió el identificador del pedido.'
                );

                return;
            }

            try {
                const resultado = await obtenerJson(
                    `${BASE_URL}&accion=obtener&id=${encodeURIComponent(idPedido)}`
                );

                const pedido =
                    resultado.data || {};

                const detalle =
                    pedido.detalle || {};

                elemento('detalleIdPedido').textContent =
                    '#' + (
                        pedido.id_pedido || '-'
                    );

                elemento('detalleFechaCreacion').textContent =
                    pedido.fecha_creacion || '-';

                elemento('detalleFechaEntrega').textContent =
                    pedido.fecha_entrega || '-';

                elemento('detalleTipoPedido').textContent =
                    pedido.nombre_tipo_pedido || '-';

                elemento('detalleEstadoPedido').textContent =
                    pedido.estado_pedido || '-';

                elemento('detalleDescripcion').textContent =
                    pedido.descripcion ||
                    'Sin descripción';

                elemento(
                    'detalleDescuentoGeneral'
                ).textContent =
                    formatearNumero(
                        pedido.descuento_divisa
                    );

                elemento('detalleCliente').textContent =
                    [
                        pedido.nombre_razon_social || '',
                        pedido.apellido || ''
                    ]
                        .join(' ')
                        .trim() || '-';

                elemento('detalleDocumento').textContent =
                    pedido.numero_de_documento || '-';

                elemento('detalleCorreo').textContent =
                    pedido.correo || '-';

                elemento('detalleTelefono').textContent =
                    pedido.telefono || '-';

                elemento('detalleDireccion').textContent =
                    pedido.direccion || '-';

                elemento('detalleProducto').textContent =
                    detalle.nombre_producto || '-';

                elemento('detalleTipoProducto').textContent =
                    detalle.tipo_de_producto || '-';

                elemento('detalleTalla').textContent =
                    detalle.talla || '-';

                elemento('detalleMaterial').textContent =
                    detalle.detalle_material || '-';

                elemento('detalleColor').textContent =
                    detalle.color || '-';

                elemento('detalleTipoPrenda').textContent =
                    detalle.tipo_de_prenda || '-';

                elemento('detalleServicio').textContent =
                    detalle.nombre_servicio || '-';

                elemento('detalleCantidad').textContent =
                    detalle.cantidad || '-';

                elemento('detalleMetodoServicio').textContent =
                    detalle.metodo_servicio || '-';

                elemento('detalleManoObra').textContent =
                    formatearNumero(
                        detalle.costo_mano_de_obra
                    );

                elemento(
                    'detalleCostoMateriales'
                ).textContent =
                    formatearNumero(
                        detalle.costo_materiales
                    );

                elemento(
                    'detalleDescuentoProducto'
                ).textContent =
                    formatearNumero(
                        detalle.descuento_producto
                    );

                elemento('detalleTotal').textContent =
                    formatearNumero(
                        pedido.monto_total
                    );

                modalDetalle.show();

            } catch (error) {
                console.error(
                    'Error al obtener detalle del pedido:',
                    error
                );

                await alerta(
                    'error',
                    'Error',
                    error.message ||
                    'No se pudo obtener el detalle del pedido.'
                );
            }
        }

        async function inhabilitarPedido(idPedido) {
            if (!idPedido) {
                await alerta(
                    'error',
                    'Error',
                    'No se recibió el identificador del pedido.'
                );

                return;
            }

            const confirmacion = await Swal.fire({
                icon: 'warning',
                title: '¿Inhabilitar pedido?',
                text:
                    'El pedido quedará archivado y podrá reactivarse desde edición.',
                showCancelButton: true,
                confirmButtonText: 'Sí, inhabilitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            if (!confirmacion.isConfirmed) {
                return;
            }

            try {
                const datos =
                    new FormData();

                datos.append(
                    'accion',
                    'inhabilitar'
                );

                datos.append(
                    'id_pedido',
                    idPedido
                );

                const response = await fetch(
                    BASE_URL,
                    {
                        method: 'POST',
                        body: datos,
                        headers: {
                            Accept: 'application/json'
                        }
                    }
                );

                const textoRespuesta =
                    await response.text();

                let resultado;

                try {
                    resultado = JSON.parse(
                        textoRespuesta
                    );
                } catch (error) {
                    console.error(
                        'Respuesta del servidor:',
                        textoRespuesta
                    );

                    throw new Error(
                        'El servidor no devolvió JSON válido.'
                    );
                }

                if (
                    !response.ok ||
                    !resultado.success
                ) {
                    throw new Error(
                        resultado.message ||
                        'No se pudo inhabilitar el pedido.'
                    );
                }

                await alerta(
                    'success',
                    'Pedido inhabilitado',
                    resultado.message
                );

                if (tablaPedidos) {
                    tablaPedidos.ajax.reload(
                        null,
                        false
                    );
                }

            } catch (error) {
                console.error(
                    'Error al inhabilitar pedido:',
                    error
                );

                await alerta(
                    'error',
                    'Error',
                    error.message ||
                    'No se pudo inhabilitar el pedido.'
                );
            }
        }

        idProducto.addEventListener(
            'change',
            function () {
                hayBorradorPedido = true;

                cargarCaracteristicas(
                    idProducto.value
                );
            }
        );

        form.addEventListener(
            'input',
            function () {
                hayBorradorPedido = true;
            }
        );

        form.addEventListener(
            'change',
            function () {
                hayBorradorPedido = true;
            }
        );

        [
            'cantidad',
            'costoManoObra',
            'costoMateriales',
            'descuentoProducto',
            'descuentoDivisa'
        ].forEach(function (id) {
            const campo =
                elemento(id);

            if (campo) {
                campo.addEventListener(
                    'input',
                    actualizarTotal
                );
            }
        });

        form.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                enviarFormulario();
            }
        );

        btnNuevoPedido.addEventListener(
            'click',
            function () {
                /*
                | Si fue cerrado con X, Escape o clic fuera, se conserva
                | el contenido existente y solo se muestra otra vez.
                */
                if (!hayBorradorPedido) {
                    prepararNuevoPedido();
                }

                modalPedido.show();
            }
        );

        btnAlternarEstado.addEventListener(
            'click',
            function () {
                verInhabilitados =
                    !verInhabilitados;

                actualizarVista();

                crearDataTable();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Botón Generar Reporte
        |--------------------------------------------------------------------------
        |
        | Por ahora no tiene funcionalidad.
        */
        btnGenerarReporte.addEventListener(
            'click',
            function () {
                // Generación de reporte pendiente.
            }
        );

        /*
        |--------------------------------------------------------------------------
        | X, Escape o clic fuera
        |--------------------------------------------------------------------------
        |
        | No se limpia el formulario para conservar el borrador.
        */
        modalPedidoElement.addEventListener(
            'hidden.bs.modal',
            function () {
                // El borrador se conserva de forma intencional.
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Botón Cancelar
        |--------------------------------------------------------------------------
        |
        | Este sí borra el formulario antes de que Bootstrap cierre el modal.
        */
        btnCancelarPedido.addEventListener(
            'click',
            function () {
                prepararNuevoPedido();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Eventos delegados para botones de DataTables
        |--------------------------------------------------------------------------
        */
        document.addEventListener(
            'click',
            function (event) {
                const botonDetalle =
                    event.target.closest(
                        '.btnDetallePedido'
                    );

                if (botonDetalle) {
                    detallePedido(
                        botonDetalle.dataset.id
                    );

                    return;
                }

                const botonEditar =
                    event.target.closest(
                        '.btnEditarPedido'
                    );

                if (botonEditar) {
                    editarPedido(
                        botonEditar.dataset.id
                    );

                    return;
                }

                const botonInhabilitar =
                    event.target.closest(
                        '.btnInhabilitarPedido'
                    );

                if (botonInhabilitar) {
                    inhabilitarPedido(
                        botonInhabilitar.dataset.id
                    );
                }
            }
        );

        prepararNuevoPedido();

        actualizarVista();

        crearDataTable();
    }
);
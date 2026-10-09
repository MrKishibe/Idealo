document.addEventListener('DOMContentLoaded', function () {
    // Definición de URL Base
    const path = window.location.pathname;
    const basePath = path.substring(0, path.indexOf('/', 1));
    const BASE_URL = `${window.location.origin}${basePath}/index.php?url=pedido/listarpedido`;

    // Mapeo de elementos HTML del DOM
    const ids = [
        'formPedido', 'modalPedido', 'modalDetallePedido', 'idProducto',
        'idProductoCaracteristica', 'estadoPedido', 'btnNuevoPedido',
        'btnAlternarEstado', 'btnGenerarReporte', 'btnGuardarPedido',
        'accionPedido', 'idPedido', 'fechaCreacion', 'fechaEntrega',
        'descripcion', 'descuentoDivisa', 'idServicio', 'cantidad',
        'costoManoObra', 'costoMateriales', 'descuentoProducto',
        'metodoServicio', 'totalVisual', 'tituloModalPedido', 'tituloVista',
        'iconoEstado', 'txtBotonEstado', 'idCliente', 'idTipoPedido'
    ];

    const el = ids.reduce((acc, id) => {
        acc[id] = document.getElementById(id);
        return acc;
    }, {});

    const btnCancelarPedido = el.modalPedido 
        ? el.modalPedido.querySelector('.modal-footer .btn-light[data-bs-dismiss="modal"]')
        : null;

    // Verificación de existencia de elementos necesarios
    if (!el.formPedido || !el.modalPedido || !el.modalDetallePedido || !el.idProducto || 
        !el.idProductoCaracteristica || !el.estadoPedido || !el.btnNuevoPedido || 
        !el.btnAlternarEstado || !el.btnGenerarReporte || !el.btnGuardarPedido || !btnCancelarPedido) {
        console.error('Faltan elementos HTML requeridos para el módulo de pedidos.');
        return;
    }

    // Instancias de Modales Bootstrap
    const modalPedido = bootstrap.Modal.getOrCreateInstance(el.modalPedido);
    const modalDetalle = bootstrap.Modal.getOrCreateInstance(el.modalDetallePedido);

    let tablaPedidos = null;
    let verInhabilitados = false;
    let hayBorradorPedido = false;

    // Notificaciones mediante SweetAlert2
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
        return verInhabilitados ? 'inhabilitados' : 'activos';
    }

    function obtenerNumero(id) {
        return Number(el[id]?.value) || 0;
    }

    function formatearNumero(valor) {
        return Number(valor || 0).toFixed(2);
    }

    /**
     * Helper unificado utilizando $.ajax envuelto en Promesas (async/await)
     */
    function peticionAjax(url, opciones = {}) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: opciones.type || 'GET',
                data: opciones.data || null,
                processData: opciones.processData !== undefined ? opciones.processData : true,
                contentType: opciones.contentType !== undefined ? opciones.contentType : 'application/x-www-form-urlencoded; charset=UTF-8',
                dataType: 'json'
            })
            .done(function (resultado) {
                if (!resultado || typeof resultado !== 'object') {
                    return reject(new Error('El servidor no devolvió una respuesta válida.'));
                }
                if (!resultado.success) {
                    return reject(new Error(resultado.message || 'No se pudo procesar la solicitud.'));
                }
                resolve(resultado);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error('Error en petición AJAX:', jqXHR.responseText || textStatus);
                let mensaje = 'Error al comunicarse con el servidor.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    mensaje = jqXHR.responseJSON.message;
                }
                reject(new Error(mensaje));
            });
        });
    }

    function actualizarTotal() {
        const cantidad = obtenerNumero('cantidad');
        const manoObra = obtenerNumero('costoManoObra');
        const materiales = obtenerNumero('costoMateriales');
        const descuentoProducto = obtenerNumero('descuentoProducto');
        const descuentoGeneral = obtenerNumero('descuentoDivisa');

        let total = ((manoObra + materiales) * cantidad) - descuentoProducto - descuentoGeneral;
        if (total < 0) total = 0;

        if (el.totalVisual) {
            el.totalVisual.textContent = total.toFixed(2);
        }
    }

    function resetCaracteristicas() {
        el.idProductoCaracteristica.innerHTML = '<option value="">Seleccione primero un producto</option>';
        el.idProductoCaracteristica.value = '';
        el.idProductoCaracteristica.disabled = true;
    }

    function opcionesEstado(incluirInhabilitado = false) {
        let opciones = `
            <option value="pendiente">Pendiente</option>
            <option value="en proceso">En proceso</option>
            <option value="realizado">Realizado</option>
            <option value="entregado">Entregado</option>
        `;
        if (incluirInhabilitado) {
            opciones += `<option value="inhabilitado">Inhabilitado</option>`;
        }
        return opciones;
    }

    function prepararNuevoPedido() {
        el.formPedido.reset();
        el.formPedido.classList.remove('was-validated');

        el.accionPedido.value = 'guardar';
        el.idPedido.value = '';
        el.fechaCreacion.value = new Date().toISOString().split('T')[0];
        el.fechaEntrega.value = '';
        el.descripcion.value = '';
        el.descuentoDivisa.value = '0';
        el.idProducto.value = '';
        el.idServicio.value = '';
        el.cantidad.value = '1';
        el.costoManoObra.value = '0';
        el.costoMateriales.value = '0';
        el.descuentoProducto.value = '0';
        el.metodoServicio.value = '';

        resetCaracteristicas();

        el.estadoPedido.innerHTML = opcionesEstado(false);
        el.estadoPedido.value = 'pendiente';
        el.estadoPedido.disabled = true;

        el.btnGuardarPedido.innerHTML = '<i class="bi bi-check-circle-fill"></i> Guardar pedido';
        el.tituloModalPedido.innerHTML = '<i class="bi bi-cart-plus-fill me-2"></i>Registrar Pedido';

        hayBorradorPedido = false;
        actualizarTotal();
    }

    /**
     * Validaciones del Formulario reutilizando $.expresionesRegulares
     */
    function validarFormulario() {
        el.formPedido.classList.add('was-validated');

        if (!el.formPedido.checkValidity()) {
            alerta('warning', 'Formulario incompleto', 'Complete los campos obligatorios.');
            return false;
        }

        const exp = $.expresionesRegulares;

        // Validaciones numéricas y de formato utilizando helpers/expresiones.js
        if (el.cantidad.value && !exp.validar('enteroPositivo', el.cantidad.value)) {
            alerta('warning', 'Cantidad inválida', 'La cantidad debe ser un número entero positivo.');
            return false;
        }

        if (el.costoManoObra.value && !exp.validar('numeroNoNegativo', el.costoManoObra.value)) {
            alerta('warning', 'Monto inválido', 'El costo de mano de obra debe ser un valor no negativo.');
            return false;
        }

        if (el.costoMateriales.value && !exp.validar('numeroNoNegativo', el.costoMateriales.value)) {
            alerta('warning', 'Monto inválido', 'El costo de materiales debe ser un valor no negativo.');
            return false;
        }

        if (el.descuentoProducto.value && !exp.validar('numeroNoNegativo', el.descuentoProducto.value)) {
            alerta('warning', 'Descuento inválido', 'El descuento de producto debe ser un valor no negativo.');
            return false;
        }

        if (el.descuentoDivisa.value && !exp.validar('numeroNoNegativo', el.descuentoDivisa.value)) {
            alerta('warning', 'Descuento inválido', 'El descuento general debe ser un valor no negativo.');
            return false;
        }

        if (el.descripcion.value.trim() !== '' && !exp.validar('descripcion', el.descripcion.value)) {
            alerta('warning', 'Descripción inválida', 'La descripción contiene caracteres no permitidos o formato incorrecto.');
            return false;
        }

        const fechaCreacion = el.fechaCreacion.value;
        const fechaEntrega = el.fechaEntrega.value;

        if (fechaEntrega) {
            if (!exp.validar('fechaISO', fechaEntrega)) {
                alerta('warning', 'Fecha inválida', 'La fecha de entrega debe tener formato AAAA-MM-DD.');
                return false;
            }
            if (fechaEntrega < fechaCreacion) {
                alerta('warning', 'Fecha inválida', 'La fecha de entrega no puede ser anterior a la fecha de creación.');
                return false;
            }
        }

        if (!el.idProductoCaracteristica.value) {
            alerta('warning', 'Característica requerida', 'Seleccione una característica y talla.');
            return false;
        }

        return true;
    }

    async function cargarCaracteristicas(productoId, caracteristicaSeleccionada = '') {
        resetCaracteristicas();
        if (!productoId) return;

        el.idProductoCaracteristica.innerHTML = '<option value="">Cargando características...</option>';
        el.idProductoCaracteristica.disabled = true;

        try {
            const respuesta = await peticionAjax(`${BASE_URL}&accion=caracteristicas_por_producto&id_producto=${encodeURIComponent(productoId)}`);
            const caracteristicas = Array.isArray(respuesta.data) ? respuesta.data : [];

            if (caracteristicas.length === 0) {
                el.idProductoCaracteristica.innerHTML = '<option value="">No hay características activas</option>';
                el.idProductoCaracteristica.disabled = true;
                return;
            }

            el.idProductoCaracteristica.innerHTML = '<option value="">Seleccione una característica</option>';
            caracteristicas.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id_producto_caracteristica;
                option.textContent = `Material: ${item.detalle_material || 'N/A'} | Color: ${item.color || 'N/A'} | Prenda: ${item.tipo_de_prenda || 'N/A'} | Talla: ${item.talla || 'N/A'}`;
                el.idProductoCaracteristica.appendChild(option);
            });

            el.idProductoCaracteristica.disabled = false;

            if (caracteristicaSeleccionada !== '') {
                el.idProductoCaracteristica.value = String(caracteristicaSeleccionada);
                if (el.idProductoCaracteristica.value === '') {
                    console.warn('La característica del pedido no se encuentra disponible.', caracteristicaSeleccionada);
                }
            }
        } catch (error) {
            console.error('Error al cargar características:', error);
            el.idProductoCaracteristica.innerHTML = '<option value="">Error al cargar características</option>';
            el.idProductoCaracteristica.disabled = true;
            await alerta('error', 'Error', error.message);
        }
    }

    function badgeEstado(estado) {
        const valor = String(estado || '').toLowerCase();
        let clase = 'bg-secondary';

        if (valor === 'pendiente') clase = 'bg-warning text-dark';
        else if (valor === 'en proceso') clase = 'bg-info text-dark';
        else if (valor === 'realizado') clase = 'bg-primary';
        else if (valor === 'entregado') clase = 'bg-success';
        else if (valor === 'inhabilitado') clase = 'bg-danger';

        return `<span class="badge ${clase}">${escaparHtml(estado)}</span>`;
    }

    function columnasDataTable() {
        return [
            {
                data: 'id_pedido',
                render: data => `<strong>#${escaparHtml(data)}</strong>`
            },
            {
                data: 'fecha_creacion',
                render: data => escaparHtml(data || '-')
            },
            {
                data: null,
                render: data => {
                    const cliente = [data.nombre_razon_social || '', data.apellido || ''].join(' ').trim();
                    return escaparHtml(cliente || 'Sin cliente');
                }
            },
            {
                data: null,
                render: data => `
                    <strong>${escaparHtml(data.nombre_producto || 'Sin producto')}</strong>
                    <small class="d-block text-muted">
                        ${escaparHtml(data.color || 'Sin color')} · Talla: ${escaparHtml(data.talla || 'N/A')}
                    </small>
                `
            },
            {
                data: 'nombre_servicio',
                render: data => escaparHtml(data || 'Sin servicio')
            },
            {
                data: 'cantidad',
                render: data => escaparHtml(data || 0)
            },
            {
                data: 'estado_pedido',
                render: data => badgeEstado(data)
            },
            {
                data: 'monto_total',
                render: data => formatearNumero(data)
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: data => {
                    const estado = String(data.estado_pedido || '').toLowerCase();
                    let html = `
                        <button type="button" class="btn btn-sm btn-outline-info btnDetallePedido me-1" data-id="${escaparHtml(data.id_pedido)}" title="Ver detalle">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    `;

                    if (estado === 'inhabilitado') {
                        html += `
                            <button type="button" class="btn btn-sm btn-outline-warning btnEditarPedido" data-id="${escaparHtml(data.id_pedido)}" title="Editar o reactivar">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        `;
                    } else {
                        html += `
                            <button type="button" class="btn btn-sm btn-outline-primary btnEditarPedido me-1" data-id="${escaparHtml(data.id_pedido)}" title="Editar pedido">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btnInhabilitarPedido" data-id="${escaparHtml(data.id_pedido)}" title="Inhabilitar pedido">
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
                dataSrc: function (json) {
                    if (!json.success) {
                        alerta('error', 'Error', json.message || 'No se pudieron cargar los registros.');
                        return [];
                    }
                    return json.data || [];
                },
                error: function (xhr) {
                    console.error('Error al cargar DataTable:', xhr.responseText);
                    alerta('error', 'Error de carga', 'El servidor no devolvió los registros correctamente.');
                }
            },
            processing: true,
            responsive: true,
            pageLength: 10,
            language: {
                processing: 'Procesando...',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros en total)',
                zeroRecords: 'No se encontraron pedidos',
                emptyTable: 'No hay pedidos registrados',
                loadingRecords: 'Cargando...',
                paginate: {
                    first: 'Primero',
                    last: 'Último',
                    next: 'Siguiente',
                    previous: 'Anterior'
                },
                aria: {
                    sortAscending: ': Activar para ordenar la columna de manera ascendente',
                    sortDescending: ': Activar para ordenar la columna de manera descendente'
                }
            },
            columns: columnasDataTable()
        });
    }

    function actualizarVista() {
        if (verInhabilitados) {
            if (el.tituloVista) el.tituloVista.textContent = 'Pedidos Inhabilitados';
            if (el.txtBotonEstado) el.txtBotonEstado.textContent = 'Ver activos';
            if (el.iconoEstado) {
                el.iconoEstado.classList.remove('bi-eye-slash-fill');
                el.iconoEstado.classList.add('bi-eye-fill');
            }
        } else {
            if (el.tituloVista) el.tituloVista.textContent = 'Gestión de Pedidos';
            if (el.txtBotonEstado) el.txtBotonEstado.textContent = 'Ver inhabilitados';
            if (el.iconoEstado) {
                el.iconoEstado.classList.remove('bi-eye-fill');
                el.iconoEstado.classList.add('bi-eye-slash-fill');
            }
        }
    }

    async function enviarFormulario() {
        if (!validarFormulario()) return;

        const accion = el.accionPedido.value;
        const estadoEstabaDeshabilitado = el.estadoPedido.disabled;

        el.btnGuardarPedido.disabled = true;
        el.btnGuardarPedido.textContent = 'Procesando...';
        el.estadoPedido.disabled = false;

        if (accion === 'guardar') {
            el.estadoPedido.value = 'pendiente';
        }

        try {
            const datosFormulario = new FormData(el.formPedido);

            const resultado = await peticionAjax(BASE_URL, {
                type: 'POST',
                data: datosFormulario,
                processData: false,
                contentType: false
            });

            await alerta('success', 'Operación exitosa', resultado.message);
            prepararNuevoPedido();
            modalPedido.hide();

            if (tablaPedidos) {
                tablaPedidos.ajax.reload(null, false);
            }
        } catch (error) {
            console.error('Error al guardar pedido:', error);
            await alerta('error', 'Error', error.message || 'No se pudo completar la operación.');
        } finally {
            el.btnGuardarPedido.disabled = false;

            if (el.accionPedido.value === 'editar') {
                el.btnGuardarPedido.innerHTML = '<i class="bi bi-save-fill"></i> Guardar cambios';
            } else {
                el.btnGuardarPedido.innerHTML = '<i class="bi bi-check-circle-fill"></i> Guardar pedido';
            }

            if (el.accionPedido.value === 'guardar') {
                el.estadoPedido.disabled = true;
            } else {
                el.estadoPedido.disabled = estadoEstabaDeshabilitado;
            }
        }
    }

    async function editarPedido(idPedido) {
        if (!idPedido) {
            await alerta('error', 'Error', 'No se recibió el identificador del pedido.');
            return;
        }

        try {
            const resultado = await peticionAjax(`${BASE_URL}&accion=obtener&id=${encodeURIComponent(idPedido)}`);
            const pedido = resultado.data || {};
            const detalle = pedido.detalle || {};

            if (!pedido.id_pedido) {
                throw new Error('El pedido recibido no contiene un identificador válido.');
            }

            el.accionPedido.value = 'editar';
            el.idPedido.value = pedido.id_pedido ?? '';
            el.idCliente.value = pedido.id_cliente ?? '';
            el.idTipoPedido.value = pedido.id_tipo_pedido ?? '';
            el.fechaCreacion.value = pedido.fecha_creacion ?? '';
            el.fechaEntrega.value = pedido.fecha_entrega ?? '';
            el.descripcion.value = pedido.descripcion ?? '';
            el.descuentoDivisa.value = pedido.descuento_divisa ?? 0;

            const productoId = detalle.id_producto ?? pedido.id_producto ?? '';
            const caracteristicaId = detalle.id_producto_caracteristica ?? pedido.id_producto_caracteristica ?? '';

            el.idProducto.value = productoId;
            await cargarCaracteristicas(productoId, caracteristicaId);

            el.idServicio.value = detalle.id_servicio ?? pedido.id_servicio ?? '';
            el.cantidad.value = detalle.cantidad ?? pedido.cantidad ?? 1;
            el.costoManoObra.value = detalle.costo_mano_de_obra ?? pedido.costo_mano_de_obra ?? 0;
            el.costoMateriales.value = detalle.costo_materiales ?? pedido.costo_materiales ?? 0;
            el.descuentoProducto.value = detalle.descuento_producto ?? pedido.descuento_producto ?? 0;
            el.metodoServicio.value = detalle.metodo_servicio ?? pedido.metodo_servicio ?? '';

            el.estadoPedido.innerHTML = opcionesEstado(true);
            el.estadoPedido.disabled = false;
            el.estadoPedido.value = pedido.estado_pedido ?? 'pendiente';

            if (el.estadoPedido.value === '') {
                el.estadoPedido.value = 'pendiente';
            }

            el.tituloModalPedido.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Editar Pedido';
            el.btnGuardarPedido.innerHTML = '<i class="bi bi-save-fill"></i> Guardar cambios';

            el.formPedido.classList.remove('was-validated');
            hayBorradorPedido = true;
            actualizarTotal();
            modalPedido.show();

        } catch (error) {
            console.error('Error al cargar pedido para editar:', error);
            await alerta('error', 'Error al editar', error.message || 'No se pudo cargar el pedido seleccionado.');
        }
    }

    async function detallePedido(idPedido) {
        if (!idPedido) {
            await alerta('error', 'Error', 'No se recibió el identificador del pedido.');
            return;
        }

        try {
            const resultado = await peticionAjax(`${BASE_URL}&accion=obtener&id=${encodeURIComponent(idPedido)}`);
            const pedido = resultado.data || {};
            const detalle = pedido.detalle || {};

            const mapDetalle = {
                detalleIdPedido: '#' + (pedido.id_pedido || '-'),
                detalleFechaCreacion: pedido.fecha_creacion || '-',
                detalleFechaEntrega: pedido.fecha_entrega || '-',
                detalleTipoPedido: pedido.nombre_tipo_pedido || '-',
                detalleEstadoPedido: pedido.estado_pedido || '-',
                detalleDescripcion: pedido.descripcion || 'Sin descripción',
                detalleDescuentoGeneral: formatearNumero(pedido.descuento_divisa),
                detalleCliente: [pedido.nombre_razon_social || '', pedido.apellido || ''].join(' ').trim() || '-',
                detalleDocumento: pedido.numero_de_documento || '-',
                detalleCorreo: pedido.correo || '-',
                detalleTelefono: pedido.telefono || '-',
                detalleDireccion: pedido.direccion || '-',
                detalleProducto: detalle.nombre_producto || '-',
                detalleTipoProducto: detalle.tipo_de_producto || '-',
                detalleTalla: detalle.talla || '-',
                detalleMaterial: detalle.detalle_material || '-',
                detalleColor: detalle.color || '-',
                detalleTipoPrenda: detalle.tipo_de_prenda || '-',
                detalleServicio: detalle.nombre_servicio || '-',
                detalleCantidad: detalle.cantidad || '-',
                detalleMetodoServicio: detalle.metodo_servicio || '-',
                detalleManoObra: formatearNumero(detalle.costo_mano_de_obra),
                detalleCostoMateriales: formatearNumero(detalle.costo_materiales),
                detalleDescuentoProducto: formatearNumero(detalle.descuento_producto),
                detalleTotal: formatearNumero(pedido.monto_total)
            };

            Object.entries(mapDetalle).forEach(([id, text]) => {
                const elemento = document.getElementById(id);
                if (elemento) elemento.textContent = text;
            });

            modalDetalle.show();

        } catch (error) {
            console.error('Error al obtener detalle del pedido:', error);
            await alerta('error', 'Error', error.message || 'No se pudo obtener el detalle del pedido.');
        }
    }

    async function inhabilitarPedido(idPedido) {
        if (!idPedido) {
            await alerta('error', 'Error', 'No se recibió el identificador del pedido.');
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'warning',
            title: '¿Inhabilitar pedido?',
            text: 'El pedido quedará archivado y podrá reactivarse desde edición.',
            showCancelButton: true,
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false,
            allowEscapeKey: false
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const datos = new FormData();
            datos.append('accion', 'inhabilitar');
            datos.append('id_pedido', idPedido);

            const resultado = await peticionAjax(BASE_URL, {
                type: 'POST',
                data: datos,
                processData: false,
                contentType: false
            });

            await alerta('success', 'Pedido inhabilitado', resultado.message);

            if (tablaPedidos) {
                tablaPedidos.ajax.reload(null, false);
            }
        } catch (error) {
            console.error('Error al inhabilitar pedido:', error);
            await alerta('error', 'Error', error.message || 'No se pudo inhabilitar el pedido.');
        }
    }

    // Event Listeners
    el.idProducto.addEventListener('change', function () {
        hayBorradorPedido = true;
        cargarCaracteristicas(el.idProducto.value);
    });

    el.formPedido.addEventListener('input', () => { hayBorradorPedido = true; });
    el.formPedido.addEventListener('change', () => { hayBorradorPedido = true; });

    ['cantidad', 'costoManoObra', 'costoMateriales', 'descuentoProducto', 'descuentoDivisa'].forEach(id => {
        if (el[id]) {
            el[id].addEventListener('input', actualizarTotal);
        }
    });

    el.formPedido.addEventListener('submit', function (event) {
        event.preventDefault();
        enviarFormulario();
    });

    el.btnNuevoPedido.addEventListener('click', function () {
        if (!hayBorradorPedido) {
            prepararNuevoPedido();
        }
        modalPedido.show();
    });

    el.btnAlternarEstado.addEventListener('click', function () {
        verInhabilitados = !verInhabilitados;
        actualizarVista();
        crearDataTable();
    });

    el.btnGenerarReporte.addEventListener('click', function () {
        // Generación de reporte pendiente.
    });

    btnCancelarPedido.addEventListener('click', function () {
        prepararNuevoPedido();
    });

    // Delegación de eventos en el documento
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
    prepararNuevoPedido();
    actualizarVista();
    crearDataTable();
});
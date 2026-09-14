<!DOCTYPE html>
<html lang="es">


<head>


    <meta charset="UTF-8">


    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">


    <title>
        Idéalo - Gestión de Pedidos
    </title>


    <!-- Hojas de estilo locales según tu estructura exacta de carpetas -->
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/img/iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">


    <style>
        .pedido-seccion {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0 15px;
            font-weight: 800;
            color: #334155;
        }


        .pedido-seccion i {
            color: #4567a9;
        }


        .pedido-seccion::after {
            content: '';
            height: 1px;
            flex: 1;
            background: #e8edf3;
        }


        .pedido-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 22px;
            padding: 14px 16px;
            border: 1px solid #bcd4ff;
            border-radius: 10px;
            background: #eff6ff;
            color: #1e3a8a;
        }


        .pedido-total strong {
            font-size: 1.2rem;
        }


        .detalle-bloque {
            height: 100%;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fafcff;
        }


        .detalle-bloque h6 {
            font-weight: 800;
        }


        .detalle-bloque p {
            margin-bottom: 9px;
        }


        .detalle-bloque p:last-child {
            margin-bottom: 0;
        }


        /* Tabla más compacta (solo un poco) */
        #tablaPedidos {
            font-size: 0.92rem;
        }


        #tablaPedidos thead th {
            padding: 0.55rem 0.6rem;
            white-space: nowrap;
        }


        #tablaPedidos tbody td {
            padding: 0.45rem 0.6rem;
            vertical-align: middle;
        }


        /* Celda de acciones: botones más ordenados y separados */
        #tablaPedidos tbody td.text-center {
            white-space: nowrap;
        }


        #tablaPedidos .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }


        #tablaPedidos .btnDetallePedido,
        #tablaPedidos .btnEditarPedido,
        #tablaPedidos .btnInhabilitarPedido {
            margin: 0 3px;
        }


        /* Header: separar un poco más los botones en móviles */
        @media (max-width: 576px) {
            .page-header .d-flex {
                flex-wrap: wrap;
                gap: 0.5rem !important;
            }


            .page-header .btn {
                flex: 1 1 auto;
                text-align: center;
            }
        }
    </style>


</head>


<body>


<?php include 'src/view/sidebar.php'; ?>


<main class="main-content">


    <div class="view-container">


        <header class="page-header">


            <div>


                <h1 id="tituloVista">
                    Gestión de Pedidos
                </h1>


                <p>
                    Administra los pedidos registrados en el sistema.
                </p>


            </div>


            <div class="d-flex gap-2 align-items-center">


                <button type="button"
                        id="btnGenerarReporte"
                        class="btn btn-outline-danger px-3 py-2"
                        style="
                            border-radius: var(--radius-md);
                            font-weight: 600;
                        ">


                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>
                    Generar Reporte


                </button>


                <button type="button"
                        id="btnAlternarEstado"
                        class="btn btn-outline-secondary px-3 py-2"
                        style="
                            border-radius: var(--radius-md);
                            font-weight: 600;
                        "
                        data-vista="activos">


                    <i class="bi bi-eye-slash-fill"
                       id="iconoEstado"></i>


                    <span id="txtBotonEstado">
                        Ver inhabilitados
                    </span>


                </button>


                <button type="button"
                        id="btnNuevoPedido"
                        class="btn-idealo-success"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPedido">


                    <i class="bi bi-cart-plus-fill"></i>
                    Registrar Pedido


                </button>


            </div>


        </header>


        <div class="table-container p-3">


            <div class="table-responsive">


                <table class="custom-table"
                       id="tablaPedidos"
                       style="width:100%;">


                    <thead>


                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Servicio</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th class="text-center">
                                Acciones
                            </th>
                        </tr>


                    </thead>


                    <tbody></tbody>


                </table>


            </div>


        </div>


    </div>


</main>


<!-- MODAL REGISTRAR / EDITAR -->


<div class="modal fade modal-idealo"
     id="modalPedido"
     tabindex="-1"
     aria-labelledby="tituloModalPedido"
     aria-hidden="true">


    <div class="modal-dialog modal-dialog-centered modal-xl">


        <div class="modal-content">


            <div class="modal-header">


                <h5 class="modal-title"
                    id="tituloModalPedido">


                    <i class="bi bi-cart-plus-fill me-2"></i>
                    Registrar Pedido


                </h5>


                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                </button>


            </div>


            <form id="formPedido"
                  action="/idealo/index.php?url=pedido/listarpedido"
                  method="POST"
                  class="needs-validation"
                  novalidate>


                <input type="hidden"
                       name="tipo_accion"
                       value="pedido">


                <input type="hidden"
                       name="accion"
                       id="accionPedido"
                       value="guardar">


                <input type="hidden"
                       name="id_pedido"
                       id="idPedido"
                       value="">


                <div class="modal-body">


                    <div class="pedido-seccion">


                        <i class="bi bi-receipt"></i>
                        Datos del pedido


                    </div>


                    <div class="row g-3">


                        <div class="col-md-6">


                            <label for="idCliente"
                                   class="form-label">


                                Cliente


                            </label>


                            <select name="id_cliente"
                                    id="idCliente"
                                    class="form-select"
                                    required>


                                <option value="">
                                    Seleccione un cliente
                                </option>


                                <?php foreach (
                                    $clientes as $cliente
                                ): ?>


                                    <option value="<?=
                                        (int) $cliente[
                                            'id_cliente'
                                        ]
                                    ?>">


                                        <?= htmlspecialchars(
                                            $cliente[
                                                'nombre_razon_social'
                                            ] .
                                            ' ' .
                                            (
                                                $cliente[
                                                    'apellido'
                                                ] ?? ''
                                            ) .
                                            ' - ' .
                                            $cliente[
                                                'numero_de_documento'
                                            ]
                                        ) ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                            <div class="invalid-feedback">
                                Seleccione un cliente.
                            </div>


                        </div>


                        <div class="col-md-6">


                            <label for="idTipoPedido"
                                   class="form-label">


                                Tipo de pedido


                            </label>


                            <select name="id_tipo_pedido"
                                    id="idTipoPedido"
                                    class="form-select"
                                    required>


                                <option value="">
                                    Seleccione un tipo
                                </option>


                                <?php foreach (
                                    $tiposPedido as $tipo
                                ): ?>


                                    <option value="<?=
                                        (int) $tipo[
                                            'id_tipo_pedido'
                                        ]
                                    ?>">


                                        <?= htmlspecialchars(
                                            $tipo[
                                                'nombre_tipo_pedido'
                                            ]
                                        ) ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                            <div class="invalid-feedback">
                                Seleccione un tipo de pedido.
                            </div>


                        </div>


                        <div class="col-md-6">


                            <label for="fechaCreacion"
                                   class="form-label">


                                Fecha de creación


                            </label>


                            <input type="date"
                                   name="fecha_creacion"
                                   id="fechaCreacion"
                                   class="form-control"
                                   value="<?= date('Y-m-d') ?>"
                                   required>


                        </div>


                        <div class="col-md-6">


                            <label for="fechaEntrega"
                                   class="form-label">


                                Fecha de entrega


                            </label>


                            <input type="date"
                                   name="fecha_entrega"
                                   id="fechaEntrega"
                                   class="form-control">


                        </div>


                        <div class="col-md-8">


                            <label for="descripcion"
                                   class="form-label">


                                Descripción


                            </label>


                            <textarea name="descripcion"
                                      id="descripcion"
                                      class="form-control"
                                      rows="2"
                                      maxlength="500"
                                      placeholder="Descripción opcional..."></textarea>


                        </div>


                        <div class="col-md-4">


                            <label for="descuentoDivisa"
                                   class="form-label">


                                Descuento general


                            </label>


                            <input type="number"
                                   name="descuento_divisa"
                                   id="descuentoDivisa"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">


                        </div>


                    </div>


                    <div class="pedido-seccion">


                        <i class="bi bi-box-seam-fill"></i>
                        Producto y servicio


                    </div>


                    <div class="row g-3">


                        <div class="col-md-6">


                            <label for="idProducto"
                                   class="form-label">


                                Producto


                            </label>


                            <select name="id_producto"
                                    id="idProducto"
                                    class="form-select"
                                    required>


                                <option value="">
                                    Seleccione un producto
                                </option>


                                <?php foreach (
                                    $productos as $producto
                                ): ?>


                                    <option value="<?=
                                        (int) $producto[
                                            'id_producto'
                                        ]
                                    ?>">


                                        <?= htmlspecialchars(
                                            $producto[
                                                'nombre_producto'
                                            ] .
                                            ' - ' .
                                            (
                                                $producto[
                                                    'tipo_de_producto'
                                                ] ?? 'Sin tipo'
                                            )
                                        ) ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                            <div class="invalid-feedback">
                                Seleccione un producto.
                            </div>


                        </div>


                        <div class="col-md-6">


                            <label for="idProductoCaracteristica"
                                   class="form-label">


                                Característica y talla


                            </label>


                            <select name="id_producto_caracteristica"
                                    id="idProductoCaracteristica"
                                    class="form-select"
                                    disabled
                                    required>


                                <option value="">
                                    Seleccione primero un producto
                                </option>


                            </select>


                            <div class="invalid-feedback">
                                Seleccione una característica.
                            </div>


                            <small class="text-muted">
                                Solo se cargan características activas
                                del producto seleccionado.
                            </small>


                        </div>


                        <div class="col-md-6">


                            <label for="idServicio"
                                   class="form-label">


                                Servicio


                            </label>


                            <select name="id_servicio"
                                    id="idServicio"
                                    class="form-select"
                                    required>


                                <option value="">
                                    Seleccione un servicio
                                </option>


                                <?php foreach (
                                    $servicios as $servicio
                                ): ?>


                                    <option value="<?=
                                        (int) $servicio[
                                            'id_servicio'
                                        ]
                                    ?>">


                                        <?= htmlspecialchars(
                                            $servicio[
                                                'nombre_servicio'
                                            ]
                                        ) ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                            <div class="invalid-feedback">
                                Seleccione un servicio.
                            </div>


                        </div>


                        <div class="col-md-3">


                            <label for="cantidad"
                                   class="form-label">


                                Cantidad


                            </label>


                            <input type="number"
                                   name="cantidad"
                                   id="cantidad"
                                   class="form-control"
                                   min="1"
                                   value="1"
                                   required>


                        </div>


                        <div class="col-md-3">


                            <label for="costoManoObra"
                                   class="form-label">


                                Mano de obra


                            </label>


                            <input type="number"
                                   name="costo_mano_de_obra"
                                   id="costoManoObra"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">


                        </div>


                        <div class="col-md-3">


                            <label for="costoMateriales"
                                   class="form-label">


                                Materiales


                            </label>


                            <input type="number"
                                   name="costo_materiales"
                                   id="costoMateriales"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">


                        </div>


                        <div class="col-md-3">


                            <label for="descuentoProducto"
                                   class="form-label">


                                Descuento producto


                            </label>


                            <input type="number"
                                   name="descuento_producto"
                                   id="descuentoProducto"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">


                        </div>


                        <div class="col-md-6">


                            <label for="metodoServicio"
                                   class="form-label">


                                Método del servicio


                            </label>


                            <input type="text"
                                   name="metodo_servicio"
                                   id="metodoServicio"
                                   class="form-control"
                                   maxlength="100">


                        </div>


                        <div class="col-md-6">


                            <label for="estadoPedido"
                                   class="form-label">


                                Estado


                            </label>


                            <select name="estado_pedido"
                                    id="estadoPedido"
                                    class="form-select"
                                    disabled>


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


                            </select>


                            <small class="text-muted">
                                Los pedidos nuevos se guardan como pendientes.
                            </small>


                        </div>


                    </div>


                    <div class="pedido-total">


                        <span>
                            <i class="bi bi-calculator-fill me-2"></i>
                            Total estimado
                        </span>


                        <strong id="totalVisual">
                            0.00
                        </strong>


                    </div>


                </div>


                <div class="modal-footer border-0
                            pt-0 px-4 pb-4">


                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">


                        Cancelar


                    </button>


                    <button type="submit"
                            class="btn-idealo-success"
                            id="btnGuardarPedido">


                        <i class="bi bi-check-circle-fill"></i>
                        Guardar pedido


                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- MODAL DETALLE -->


<div class="modal fade modal-idealo"
     id="modalDetallePedido"
     tabindex="-1"
     aria-labelledby="tituloDetallePedido"
     aria-hidden="true">


    <div class="modal-dialog modal-dialog-centered
                modal-xl">


        <div class="modal-content">


            <div class="modal-header">


                <h5 class="modal-title"
                    id="tituloDetallePedido">


                    <i class="bi bi-eye-fill me-2"></i>
                    Detalle del pedido


                </h5>


                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                </button>


            </div>


            <div class="modal-body">


                <div class="row g-3">


                    <div class="col-md-6">


                        <div class="detalle-bloque">


                            <h6 class="text-primary">
                                <i class="bi bi-receipt me-2"></i>
                                Pedido
                            </h6>


                            <hr>


                            <p>
                                <strong>ID:</strong>
                                <span id="detalleIdPedido">-</span>
                            </p>


                            <p>
                                <strong>Fecha creación:</strong>
                                <span id="detalleFechaCreacion">-</span>
                            </p>


                            <p>
                                <strong>Fecha entrega:</strong>
                                <span id="detalleFechaEntrega">-</span>
                            </p>


                            <p>
                                <strong>Tipo:</strong>
                                <span id="detalleTipoPedido">-</span>
                            </p>


                            <p>
                                <strong>Estado:</strong>
                                <span id="detalleEstadoPedido">-</span>
                            </p>


                            <p>
                                <strong>Descripción:</strong>
                                <span id="detalleDescripcion">-</span>
                            </p>


                            <p class="mb-0">
                                <strong>Descuento general:</strong>
                                <span id="detalleDescuentoGeneral">-</span>
                            </p>


                        </div>


                    </div>


                    <div class="col-md-6">


                        <div class="detalle-bloque">


                            <h6 class="text-success">
                                <i class="bi bi-person-fill me-2"></i>
                                Cliente
                            </h6>


                            <hr>


                            <p>
                                <strong>Cliente:</strong>
                                <span id="detalleCliente">-</span>
                            </p>


                            <p>
                                <strong>Documento:</strong>
                                <span id="detalleDocumento">-</span>
                            </p>


                            <p>
                                <strong>Correo:</strong>
                                <span id="detalleCorreo">-</span>
                            </p>


                            <p>
                                <strong>Teléfono:</strong>
                                <span id="detalleTelefono">-</span>
                            </p>


                            <p class="mb-0">
                                <strong>Dirección:</strong>
                                <span id="detalleDireccion">-</span>
                            </p>


                        </div>


                    </div>


                    <div class="col-12">


                        <div class="detalle-bloque">


                            <h6 class="text-warning">
                                <i class="bi bi-box-seam-fill me-2"></i>
                                Producto y servicio
                            </h6>


                            <hr>


                            <div class="row g-3">


                                <div class="col-md-4">
                                    <strong>Producto</strong>
                                    <p id="detalleProducto">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Tipo</strong>
                                    <p id="detalleTipoProducto">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Talla</strong>
                                    <p id="detalleTalla">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Material</strong>
                                    <p id="detalleMaterial">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Color</strong>
                                    <p id="detalleColor">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Tipo de prenda</strong>
                                    <p id="detalleTipoPrenda">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Servicio</strong>
                                    <p id="detalleServicio">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Cantidad</strong>
                                    <p id="detalleCantidad">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Método</strong>
                                    <p id="detalleMetodoServicio">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Mano de obra</strong>
                                    <p id="detalleManoObra">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Materiales</strong>
                                    <p id="detalleCostoMateriales">-</p>
                                </div>


                                <div class="col-md-4">
                                    <strong>Descuento</strong>
                                    <p id="detalleDescuentoProducto">-</p>
                                </div>


                            </div>


                        </div>


                    </div>


                    <div class="col-12">


                        <div class="alert alert-success
                                    d-flex
                                    justify-content-between
                                    align-items-center
                                    mb-0">


                            <strong>
                                Total del pedido
                            </strong>


                            <strong id="detalleTotal">
                                0.00
                            </strong>


                        </div>


                    </div>


                </div>


            </div>


            <div class="modal-footer border-0
                        pt-0 px-4 pb-4">


                <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">


                    Cerrar


                </button>


            </div>


        </div>


    </div>


</div>


<!-- 1. jQuery (Nombre exacto de tu carpeta: jquery-3.7.0.min.js) -->
    <script src="assets/js/jquery-3.7.0.min.js"></script>


    <!-- 2. Bootstrap JS -->
    <script src="assets/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>


    <!-- 3. DataTables -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>


    <!-- 4. SweetAlert2 (Nombre exacto de tu carpeta: sweetalert2.all.min.js) -->
    <script src="assets/js/sweetalert2.all.min.js"></script>


    <!-- 5. Tu script de pedidos -->
    <script src="assets/js/pedido.js"></script>


</body>


</html>
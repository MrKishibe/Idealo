<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Pedidos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>

<body>

    <?php include 'src/view/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1>Gestión de Pedidos</h1>
                    <p>Administra las solicitudes, entregas, montos y estados comerciales de los trabajos.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnGenerarReporte" class="btn btn-outline-danger px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generar Reporte
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPedido">
                        <i class="bi bi-cart-plus-fill"></i> Registrar Pedido
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaPedidos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Cliente / Fechas</th>
                                <th>Tipo / Descripción</th>
                                <th>Descuento ($)</th>
                                <th>Monto Total ($)</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($pedidos) && is_array($pedidos)): ?>
                                <?php foreach ($pedidos as $p):
                                    $estado = strtolower($p['estado_pedido'] ?? 'pendiente');
                                    $claseBadge = ($estado === 'completado') ? 'bg-success' : (($estado === 'pendiente') ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                    <tr>
                                        <td><strong>#<?= $p['id_pedido'] ?></strong></td>
                                        <td>
                                            <span class="d-block"><strong><?= htmlspecialchars($p['id_cliente']) ?></strong></span>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar-plus"></i> Alta: <?= $p['fecha_creacion'] ?>
                                            </small>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                <i class="bi bi-calendar-check"></i> Entrega: <?= $p['fecha_entrega'] ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary mb-1"><?= htmlspecialchars($p['id_tipo_pedido']) ?></span>
                                            <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($p['descripcion']) ?></small>
                                        </td>
                                        <td>$<?= number_format($p['descuento_divisa'], 2) ?></td>
                                        <td><strong>$<?= number_format($p['monto_total'], 2) ?></strong></td>
                                        <td><span class="badge <?= $claseBadge ?>"><?= ucfirst($estado) ?></span></td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button class="btn btn-warning btn-sm btnEditarPedido"
                                                    data-id="<?= $p['id_pedido'] ?>"
                                                    data-fechacreacion="<?= $p['fecha_creacion'] ?>"
                                                    data-fechaentrega="<?= $p['fecha_entrega'] ?>"
                                                    data-tipo="<?= htmlspecialchars($p['id_tipo_pedido']) ?>"
                                                    data-descripcion="<?= htmlspecialchars($p['descripcion']) ?>"
                                                    data-descuento="<?= $p['descuento_divisa'] ?>"
                                                    data-monto="<?= $p['monto_total'] ?>"
                                                    data-cliente="<?= htmlspecialchars($p['id_cliente']) ?>"
                                                    data-estado="<?= $estado ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm btnEliminarPedido" data-id="<?= $p['id_pedido'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalRegistrarPedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cart-plus-fill me-2"></i>Registrar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?controller=pedido&action=guardar" method="POST" id="formRegistrarPedido">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente (ID o Razón Social)</label>
                                <input type="text" class="form-control" name="id_cliente" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Trabajo</label>
                                <select class="form-select" name="id_tipo_pedido" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                    <option value="Sublimación">Sublimación</option>
                                    <option value="Estampado">Estampado</option>
                                    <option value="Bordado">Bordado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Entrada</label>
                                <input type="date" class="form-control" name="fecha_creacion" value="2026-06-18" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Promesa / Entrega</label>
                                <input type="date" class="form-control" name="fecha_entrega" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Detalles de las Prendas o Productos</label>
                                <textarea class="form-control" name="descripcion" rows="2" placeholder="Especificar cantidad, tallas, tipo de tela, etc." required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descuento ($)</label>
                                <input type="number" step="0.01" class="form-control" name="descuento_divisa" value="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Presupuesto / Monto Total ($)</label>
                                <input type="number" step="0.01" class="form-control" name="monto_total" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Registrar Pedido</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarPedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modificar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?controller=pedido&action=editar" method="POST" id="formEditarPedido">
                    <input type="hidden" name="id_pedido" id="edit_id_pedido">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" name="id_cliente" id="edit_id_cliente" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Trabajo</label>
                                <select class="form-select" name="id_tipo_pedido" id="edit_id_tipo_pedido" required>
                                    <option value="Sublimación">Sublimación</option>
                                    <option value="Estampado">Estampado</option>
                                    <option value="Bordado">Bordado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Creación</label>
                                <input type="date" class="form-control" name="fecha_creacion" id="edit_fecha_creacion" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Entrega</label>
                                <input type="date" class="form-control" name="fecha_entrega" id="edit_fecha_entrega" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Descuento ($)</label>
                                <input type="number" step="0.01" class="form-control" name="descuento_divisa" id="edit_descuento_divisa">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Monto Total ($)</label>
                                <input type="number" step="0.01" class="form-control" name="monto_total" id="edit_monto_total" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado de Producción</label>
                                <select class="form-select" name="estado_pedido" id="edit_estado_pedido" required>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--azul-opaco); border: none; border-radius: var(--radius-md); padding: 10px 20px; font-weight: 600;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="assets/js/pedido.js"></script>
</body>

</html>
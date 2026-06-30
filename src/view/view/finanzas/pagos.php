<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Control de Pagos</title>
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

    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Control de Pagos</h1>
                    <p>Administra los flujos de caja, abonos de pedidos y transacciones.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago" onclick="limpiarFormularioCrear()">
                        <i class="bi bi-cash-stack"></i> Registrar Pago
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaPagos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Referencia</th>
                                <th>Pedido</th>
                                <th>Monto Abonado</th>
                                <th>Método de Pago</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPagos">
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($pago['referencia'] ?: 'N/A') ?></strong></td>
                                    <td>Pedido #<?= $pago['id_pedido'] ?></td>
                                    <td class="text-success" style="font-weight: 600;">$<?= number_format($pago['monto_pago'], 2) ?></td>
                                    <td><?= htmlspecialchars($pago['nombre_metodo']) ?></td>
                                    <td><?= date('d/m/Y h:i A', strtotime($pago['fecha_pago'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $pago['estado'] === 'procesado' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($pago['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary" style="border-radius: var(--radius-sm);" data-bs-toggle="modal" data-bs-target="#modalEditarPago" onclick='cargarDatosEdicion(<?= json_encode($pago) ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-<?= $pago['estado'] === 'procesado' ? 'danger' : 'success' ?>"
                                                style="border-radius: var(--radius-sm);"
                                                onclick="cambiarEstadoPago(<?= $pago['id_pago'] ?>, <?= $pago['estado'] === 'procesado' ? 0 : 1 ?>)">
                                                <i class="bi bi-<?= $pago['estado'] === 'procesado' ? 'eye-slash-fill' : 'eye-fill' ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalRegistrarPago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Registrar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarPago">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pedido Relacionado</label>
                                <select class="form-select" name="id_pedido" id="id_pedido" required>
                                    <option value="" disabled selected>Seleccione un pedido...</option>
                                    <?php foreach ($pedidos as $ped): ?>
                                        <option value="<?= $ped['id_pedido'] ?>">Pedido #<?= $ped['id_pedido'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto a Abonar ($)</label>
                                <input type="number" step="0.01" class="form-control" name="monto_pago" id="monto_pago" required placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pago</label>
                                <select class="form-select" name="id_metodo_de_pago" id="id_metodo_de_pago" required>
                                    <option value="" disabled selected>Seleccione método...</option>
                                    <?php foreach ($metodos as $met): ?>
                                        <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Número de Referencia</label>
                                <input type="text" class="form-control" name="referencia" id="referencia" placeholder="Ej: 481023">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Fecha y Hora de Transacción</label>
                                <input type="datetime-local" class="form-control" name="fecha_pago" id="fecha_pago" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarPago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Registro de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarPago">
                    <input type="hidden" name="id_pago" id="edit_id_pago">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pedido Relacionado</label>
                                <select class="form-select" name="id_pedido" id="edit_id_pedido" required>
                                    <?php foreach ($pedidos as $ped): ?>
                                        <option value="<?= $ped['id_pedido'] ?>">Pedido #<?= $ped['id_pedido'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto Abonado ($)</label>
                                <input type="number" step="0.01" class="form-control" name="monto_pago" id="edit_monto_pago" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pago</label>
                                <select class="form-select" name="id_metodo_de_pago" id="edit_id_metodo_de_pago" required>
                                    <?php foreach ($metodos as $met): ?>
                                        <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Número de Referencia</label>
                                <input type="text" class="form-control" name="referencia" id="edit_referencia">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Fecha y Hora de Transacción</label>
                                <input type="datetime-local" class="form-control" name="fecha_pago" id="edit_fecha_pago" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-dismiss="modal">Cancelar</button>
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
    <script src="assets/js/pagos.js"></script>
</body>

</html>
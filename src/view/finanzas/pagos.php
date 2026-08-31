<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Control de Pagos</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" data-vista="activos" onclick="alternarVistaInhabilitados('tablaPagos')">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                
                <button type="button" id="btnGenerarReportePagos" class="btn btn-outline-danger px-2 py-1" style="border-radius: var(--radius-md); font-weight: 600;">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generar Reporte
                </button>
                
                <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago" onclick="document.getElementById('formRegistrarPago').reset();">
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
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                        <tr class="fila-pago" data-estado="<?= htmlspecialchars($pago['estado']) ?>">
                            <td><strong><?= htmlspecialchars($pago['referencia']) ?></strong></td>
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
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarPago" 
                                        onclick="cargarDatosEdicionPago('<?= $pago['id_pago'] ?>', '<?= $pago['id_pedido'] ?>', '<?= $pago['monto_pago'] ?>', '<?= $pago['id_metodo_de_pago'] ?>', '<?= htmlspecialchars($pago['referencia']) ?>', '<?= date('Y-m-d\TH:i', strtotime($pago['fecha_pago'])) ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm <?= $pago['estado'] === 'inhabilitado' ? 'btn-outline-success' : 'btn-outline-danger' ?>" 
                                        onclick="cambiarEstado(<?= $pago['id_pago'] ?>, 'pago', '<?= $pago['estado'] === 'inhabilitado' ? 'procesado' : 'inhabilitado' ?>')">
                                        <i class="bi <?= $pago['estado'] === 'inhabilitado' ? 'bi-check-circle-fill' : 'bi-trash-fill' ?>"></i>
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

<!-- Modal Registrar Pago -->
<div class="modal fade modal-idealo" id="modalRegistrarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=pagos" method="POST" class="finanzas-form" id="formRegistrarPago">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Registrar Nuevo Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="entidad" value="pago">
                    
                    <div class="mb-3">
                        <label class="form-label">Número de Pedido</label>
                        <select class="form-select" name="id_pedido" required>
                            <option value="">Seleccione un pedido...</option>
                            <?php foreach($pedidos as $ped): ?>
                                <option value="<?= $ped['id_pedido'] ?>">Pedido #<?= $ped['id_pedido'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto Abonado ($)</label>
                            <input type="number" step="0.01" class="form-control" name="monto_pago" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select class="form-select" name="id_metodo_de_pago" required>
                                <option value="">Seleccione...</option>
                                <?php foreach($metodos as $met): ?>
                                    <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <!-- Etiqueta y atributos actualizados -->
                        <label class="form-label">Referencia (6 Dígitos)</label>
                        <input type="text" class="form-control" name="referencia" maxlength="6" required placeholder="Ej: 123456">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha del Pago</label>
                        <input type="datetime-local" class="form-control" name="fecha_pago" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Pago</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Pago -->
<div class="modal fade modal-idealo" id="modalEditarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=pagos" method="POST" class="finanzas-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="entidad" value="pago">
                    <input type="hidden" name="id_pago" id="edit_id_pago">
                    
                    <div class="mb-3">
                        <label class="form-label">Número de Pedido</label>
                        <select class="form-select" name="id_pedido" id="edit_id_pedido" required>
                            <?php foreach($pedidos as $ped): ?>
                                <option value="<?= $ped['id_pedido'] ?>">Pedido #<?= $ped['id_pedido'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto Abonado ($)</label>
                            <input type="number" step="0.01" class="form-control" name="monto_pago" id="edit_monto_pago" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select class="form-select" name="id_metodo_de_pago" id="edit_id_metodo" required>
                                <?php foreach($metodos as $met): ?>
                                    <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <!-- Etiqueta y atributos actualizados -->
                        <label class="form-label">Referencia (6 Dígitos)</label>
                        <input type="text" class="form-control" name="referencia" id="edit_referencia" maxlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha del Pago</label>
                        <input type="datetime-local" class="form-control" name="fecha_pago" id="edit_fecha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="assets/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/finanzas.js"></script>
<script>
function cargarDatosEdicionPago(id, pedido, monto, metodo, ref, fecha) {
    document.getElementById('edit_id_pago').value = id;
    document.getElementById('edit_id_pedido').value = pedido;
    document.getElementById('edit_monto_pago').value = monto;
    document.getElementById('edit_id_metodo').value = metodo;
    document.getElementById('edit_referencia').value = ref;
    document.getElementById('edit_fecha').value = fecha;
}

document.getElementById('btnGenerarReportePagos').addEventListener('click', function() {
    const btnAlternar = document.getElementById('btnAlternarEstado');
    const vistaActual = btnAlternar ? btnAlternar.getAttribute('data-vista') : 'activos';
    const estadoFiltro = (vistaActual === 'inhabilitados') ? 'inhabilitados' : 'activos';
    
    const urlReporte = `index.php?controller=Finanzas&action=pagos&accion=generar_reporte&tipo=pagos&estado=${estadoFiltro}`;
    window.open(urlReporte, '_blank');
});
</script>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Control de Pagos</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
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
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" data-vista="activos">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                
                <button type="button" id="btnGenerarReportePagos" class="btn btn-outline-danger px-2 py-1" style="border-radius: var(--radius-md); font-weight: 600;">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generar Reporte
                </button>
                
                <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago" onclick="establecerFechaActualRegistro()">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade modal-idealo" id="modalRegistrarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=pagos" method="POST" class="finanzas-form" id="formRegistrarPago" enctype="multipart/form-data">
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
                        <select class="form-select w-100" name="id_pedido" id="select_registrar_pedido" required>
                            <option value="">Seleccione un pedido...</option>
                            <?php foreach($pedidos as $ped): ?>
                                <option value="<?= $ped['id_pedido'] ?>">
                                    Pedido #<?= $ped['id_pedido'] ?> - <?= htmlspecialchars($ped['nombre_razon_social'] . ' ' . $ped['apellido']) ?> (Total: $<?= number_format($ped['monto_total'], 2) ?>)
                                </option>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Referencia (6 Dígitos)</label>
                            <input type="text" class="form-control" name="referencia" maxlength="6" required placeholder="Ej: 123456">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha del Pago</label>
                            <input type="datetime-local" class="form-control" name="fecha_pago" id="input_fecha_pago" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comprobante_pago" class="form-label">Comprobante de Pago</label>
                        <input type="file" class="form-control" id="comprobante_pago" name="comprobante_pago" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <div class="form-text">Formatos permitidos: JPG, PNG, WEBP, PDF. Máximo: 2MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancelar-modal" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Pago</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade modal-idealo" id="modalEditarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=pagos" method="POST" class="finanzas-form" id="formEditarPago" enctype="multipart/form-data">
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
                        <select class="form-select w-100" name="id_pedido" id="edit_id_pedido" required>
                            <?php foreach($pedidos as $ped): ?>
                                <option value="<?= $ped['id_pedido'] ?>">
                                    Pedido #<?= $ped['id_pedido'] ?> - <?= htmlspecialchars($ped['nombre_razon_social'] . ' ' . $ped['apellido']) ?> (Total: $<?= number_format($ped['monto_total'], 2) ?>)
                                </option>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Referencia (6 Dígitos)</label>
                            <input type="text" class="form-control" name="referencia" id="edit_referencia" maxlength="6" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha del Pago</label>
                            <input type="datetime-local" class="form-control" name="fecha_pago" id="edit_fecha" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_comprobante_pago" class="form-label">Actualizar Comprobante (Opcional)</label>
                            <input type="file" class="form-control" id="edit_comprobante_pago" name="comprobante_pago" accept="image/jpeg,image/png,image/webp,application/pdf">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="edit_status_pago">Estatus del Pago</label>
                            <select class="form-select" name="status_pago" id="edit_status_pago" required>
                                <option value="procesado">Procesado</option>
                                <option value="inhabilitado">Inhabilitado</option>
                            </select>
                        </div>
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

<script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/jquery-4.0.0.min.js"></script>
<script src="assets/libs/jquery.dataTables.min.js"></script>
<script src="assets/libs/dataTables.bootstrap5.min.js"></script>
<script src="assets/libs/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function obtenerFechaHoraLocal() {
    const ahora = new Date();
    ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
    return ahora.toISOString().slice(0, 16);
}

function establecerFechaActualRegistro() {
    const inputFecha = document.getElementById('input_fecha_pago');
    if (inputFecha && !inputFecha.value) {
        inputFecha.value = obtenerFechaHoraLocal();
    }
}
</script>

<script type="importmap">
    {"imports":{"jquery":"./assets/js/helpers/jquery-bridge.js"}}
</script>
<script type="module" src="assets/js/finanzas.js"></script>
<script type="module">
import { abrirReporteEnPestana } from './assets/js/helpers/report-viewer.js';

document.getElementById('btnGenerarReportePagos').addEventListener('click', function() {
    const btnAlternar = document.getElementById('btnAlternarEstado');
    const vistaActual = btnAlternar ? btnAlternar.getAttribute('data-vista') : 'activos';
    const estadoFiltro = (vistaActual === 'inhabilitados') ? 'inhabilitados' : 'activos';
    const urlReporte = `index.php?controller=Finanzas&action=pagos&accion=generar_reporte&tipo=pagos&estado=${estadoFiltro}`;

    if (!abrirReporteEnPestana(urlReporte)) {
        window.Swal.fire('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.', 'warning');
    }
});
</script>
</body>
</html>
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
    <link rel="stylesheet" href="assets/libs/skeleton-elements/skeleton-elements.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/datatable-skeleton.css">
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
                
                <button type="button" id="btnGenerarReportePagos" class="btn btn-outline-danger px-3 py-2 btn-generar-reportes" style="border-radius: var(--radius-md); font-weight: 600;">
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

<!-- Modal Registrar Pago -->
<div class="modal fade modal-idealo" id="modalRegistrarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=Finanzas&action=pagos" method="POST" class="finanzas-form" id="formRegistrarPago" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Registrar Nuevo Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="entidad" value="pago">
                    
                    <div class="mb-3">
                        <label class="form-label">Pedido</label>
                        <select class="form-select w-100" name="id_pedido" id="select_registrar_pedido" required>
                            <option value="">Seleccione un pedido...</option>
                            <?php foreach($pedidos as $ped): ?>
                                <option value="<?= $ped['id_pedido'] ?>">
                                    <?= htmlspecialchars($ped['descripcion_pedido'] ?? 'Pedido #' . $ped['id_pedido']) ?> - <small class="text-muted"><?= htmlspecialchars($ped['nombre_razon_social'] . ' ' . $ped['apellido']) ?></small> (Total: $<?= number_format($ped['monto_total'], 2) ?>)
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
                    <button type="button" class="btn btn-outline-secondary btn-cancelar-modal px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" style="border-radius: 8px; font-weight: 600;">Guardar Pago</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Pago -->
<div class="modal fade modal-idealo" id="modalEditarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=Finanzas&action=pagos" method="POST" class="finanzas-form" id="formEditarPago" enctype="multipart/form-data">
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
                        <label class="form-label">Pedido</label>
                        <select class="form-select w-100" name="id_pedido" id="edit_id_pedido" required>
                            <?php foreach($pedidos as $ped): ?>
                                <option value="<?= $ped['id_pedido'] ?>">
                                    <?= htmlspecialchars($ped['descripcion_pedido'] ?? 'Pedido #' . $ped['id_pedido']) ?> - <small class="text-muted"><?= htmlspecialchars($ped['nombre_razon_social'] . ' ' . $ped['apellido']) ?></small> (Total: $<?= number_format($ped['monto_total'], 2) ?>)
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
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 600;">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver Comprobante -->
<div class="modal fade modal-idealo" id="modalVerComprobante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text-fill me-2"></i>Visualizar Comprobante de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="contenedorComprobante" style="min-height: 300px; display: flex; align-items: center; justify-content: center;"></div>
            <div class="modal-footer">
                <a href="#" id="btnDescargarComprobante" class="btn btn-outline-primary px-3" target="_blank" download style="border-radius: 8px; font-weight: 600;">
                    <i class="bi bi-download me-1"></i> Descargar Archivo
                </a>
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Filtrar Reporte -->
<div class="modal fade modal-idealo" id="modalFiltroReporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formFiltroReporte" action="index.php?controller=Finanzas&action=generarReporte" method="GET" target="_blank">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Generar Reporte de Pagos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="controller" value="Finanzas">
                    <input type="hidden" name="action" value="generarReporte">

                    <div class="mb-3">
                        <label for="filtro_id_metodo" class="form-label">Filtrar por Método de Pago:</label>
                        <select class="form-select" name="id_metodo" id="filtro_id_metodo">
                            <option value="">Todos los métodos</option>
                            <?php foreach($metodos as $met): ?>
                                <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo'] ?? $met['nombre_metodo_de_pago']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="fecha_desde" class="form-label">Fecha Desde:</label>
                            <input type="date" class="form-control" name="fecha_desde" id="fecha_desde">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="fecha_hasta" class="form-label">Fecha Hasta:</label>
                            <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="estado_reporte" class="form-label">Estado de los Pagos:</label>
                        <select class="form-select" name="estado_reporte" id="estado_reporte">
                            <option value="activos" selected>Solo Activos / Procesados</option>
                            <option value="inhabilitados">Solo Inhabilitados</option>
                            <option value="todos">Todos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-printer-fill me-1"></i> Generar Reporte PDF
                    </button>
                </div>
            </form>
        </div>
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
    {
        "imports": {
            "jquery": "./assets/js/helpers/jquery-bridge.js",
            "sweetalert2": "./assets/js/helpers/sweetalert2-bridge.js"
        }
    }
</script>
<script type="module" src="assets/js/pagos.js"></script>
</body>
</html>
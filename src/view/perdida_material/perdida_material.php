<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Pérdidas de Material</title> 
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
</head>

<body>
    <?php include 'src/view/sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container" style="padding: 2rem max(2vw, 20px);">
            <header class="page-header d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h1 class="fw-bold text-dark mb-1">Pérdidas de Material</h1>
                    <p class="text-muted mb-0">Registra y gestiona las pérdidas o desmarques del material en producción.</p>
                </div>
               <button type="button" id="btnGenerarReporte" class="btn btn-outline-danger px-2 py-1" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-toggle="modal" data-bs-target="#modalFiltrosReportePerdida">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generar Reporte
                </button>
                
                <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#modalRegistrarPerdida" style="border-radius: 12px; font-weight: 600;">
                    <i class="bi bi-trash3-fill me-1"></i> Registrar Pérdida
                </button>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaPerdidasMaterial" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cantidad</th>
                                <th>Fecha</th>
                                <th>Motivo</th>
                                <th>Costo Unitario</th>
                                <th>Materia Prima</th>
                                <th>Producción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPerdidaMaterial"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalFiltrosReportePerdida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formFiltrosReportePerdida">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Filtrar reporte de pérdidas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <fieldset class="mb-3">
                            <legend class="form-label">Rango por fecha de registro</legend>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="reporteFechaDesde">Desde</label>
                                    <input type="date" class="form-control" id="reporteFechaDesde">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="reporteFechaHasta">Hasta</label>
                                    <input type="date" class="form-control" id="reporteFechaHasta">
                                </div>
                            </div>
                            <small class="text-muted">Las fechas elegidas se incluyen en el reporte. Déjalas vacías para no limitar por fecha.</small>
                        </fieldset>
                        <div class="mb-3">
                            <label class="form-label" for="reporteEstadoProduccion">Estado de producción</label>
                            <select class="form-select" id="reporteEstadoProduccion">
                                <option value="">Todos los estados</option>
                                <option value="activas">Activas (cualquier estado excepto Inactiva)</option>
                                <?php $estadosProduccion = $estadosProduccion ?? []; ?>
                                <?php foreach ($estadosProduccion as $estado): ?>
                                    <option value="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(strtolower($estado) === 'en espera' ? 'Pendiente (En espera)' : $estado, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-file-earmark-pdf-fill me-1"></i>Generar PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalRegistrarPerdida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trash3-fill me-2"></i>Registrar Pérdida de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarPerdida" method="post" action="index.php?controller=perdidaMaterial&action=guardar" data-consumos="<?php echo $consumosJson; ?>">
                    <input type="hidden" name="accion" value="guardar">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cantidad perdida</label>
                                <input type="number" class="form-control" name="cantidad_perdida" step="1" min="1" required placeholder="Ej: 2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de registro</label>
                                <input type="date" class="form-control" name="fecha_de_registro" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Costo unitario (del consumo)</label>
                                <input type="number" class="form-control" name="costo_unitario" step="0.01" min="0" readonly required placeholder="Se calcula al seleccionar el consumo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Orden de producción</label>
                                <select class="form-select" name="id_produccion" required>
                                    <option value="">Seleccione una orden</option>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <?php
                                        $labelOrden = 'Orden #' . $orden['id_produccion'];
                                        if (!empty($orden['descripcion_pedido'])) {
                                            $labelOrden .= ' - ' . $orden['descripcion_pedido'];
                                        }
                                        if (!empty($orden['estado_de_produccion'])) {
                                            $labelOrden .= ' - ' . $orden['estado_de_produccion'];
                                        }
                                        ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion']); ?>">
                                            <?php echo htmlspecialchars($labelOrden); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Materia prima</label>
                                <select class="form-select" name="id_materia_prima" required>
                                    <option value="">Seleccione una materia prima</option>
                                    <?php foreach ($materiasPrimas as $materiaPrima): ?>
                                        <?php
                                        $labelMateriaPrima = $materiaPrima['nombre_materia_prima'];
                                        if (!empty($materiaPrima['unidad_de_medida'])) {
                                            $labelMateriaPrima .= ' (' . $materiaPrima['unidad_de_medida'] . ')';
                                        }
                                        ?>
                                        <option value="<?php echo htmlspecialchars($materiaPrima['id_materia_prima']); ?>">
                                            <?php echo htmlspecialchars($labelMateriaPrima); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motivo</label>
                                <textarea class="form-control" name="motivo" rows="3" required placeholder="Describa el motivo de la pérdida o desmarque..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarPerdida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Pérdida de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarPerdida" method="post" action="index.php?controller=perdidaMaterial&action=editar" data-consumos="<?php echo $consumosJson; ?>">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_perdida_material" id="edit_id_perdida_material">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cantidad perdida</label>
                                <input type="number" class="form-control" name="cantidad_perdida" id="edit_cantidad_perdida" step="1" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de registro</label>
                                <input type="date" class="form-control" name="fecha_de_registro" id="edit_fecha_de_registro" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Costo unitario (del consumo)</label>
                                <input type="number" class="form-control" name="costo_unitario" id="edit_costo_unitario" step="0.01" min="0" readonly required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Orden de producción</label>
                                <select class="form-select" name="id_produccion" id="edit_id_produccion" required>
                                    <option value="">Seleccione una orden</option>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <?php
                                        $labelOrden = 'Orden #' . $orden['id_produccion'];
                                        if (!empty($orden['descripcion_pedido'])) {
                                            $labelOrden .= ' - ' . $orden['descripcion_pedido'];
                                        }
                                        if (!empty($orden['estado_de_produccion'])) {
                                            $labelOrden .= ' - ' . $orden['estado_de_produccion'];
                                        }
                                        ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion']); ?>">
                                            <?php echo htmlspecialchars($labelOrden); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Materia prima</label>
                                <select class="form-select" name="id_materia_prima" id="edit_id_materia_prima" required>
                                    <option value="">Seleccione una materia prima</option>
                                    <?php foreach ($materiasPrimas as $materiaPrima): ?>
                                        <?php
                                        $labelMateriaPrima = $materiaPrima['nombre_materia_prima'];
                                        if (!empty($materiaPrima['unidad_de_medida'])) {
                                            $labelMateriaPrima .= ' (' . $materiaPrima['unidad_de_medida'] . ')';
                                        }
                                        ?>
                                        <option value="<?php echo htmlspecialchars($materiaPrima['id_materia_prima']); ?>">
                                            <?php echo htmlspecialchars($labelMateriaPrima); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motivo</label>
                                <textarea class="form-control" name="motivo" id="edit_motivo" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="assets/libs/jquery-4.0.0.min.js"></script>
<script src="assets/js/helpers/expresiones.js"></script>
    <script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/jquery.dataTables.min.js"></script>
    <script src="assets/libs/dataTables.bootstrap5.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>

    <script src="assets/js/perdida_material.js"></script>
</body>
</html>
</body>

</html>

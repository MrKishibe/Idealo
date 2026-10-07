<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Consumo de Material</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/icons.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
</head>

<body>
    <?php include 'src/view/sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Consumo de Materiales</h1>
                    <p>Registra y administra el consumo de materia prima en las órdenes de producción.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnGenerarReporte" class="btn btn-outline-danger px-2 py-1" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-toggle="modal" data-bs-target="#modalFiltrosReporteConsumo">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generar Reporte
                    </button>

                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarConsumo">
                        <i class="bi bi-clipboard-plus me-1"></i> Registrar Consumo
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaConsumos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Materia Prima / Descripción</th>
                                <th>Producto</th>
                                <th>Costo Unitario</th>
                                <th>Cantidad Usada</th>
                                <th>Costo Total</th>
                                <th>Producción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyConsumos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalFiltrosReporteConsumo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formFiltrosReporteConsumo">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Filtrar reporte de consumo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="reporteMateriaPrima">Materia prima</label>
                            <select class="form-select" id="reporteMateriaPrima">
                                <option value="">Todas las materias primas</option>
                                <?php $materiasReporte = $materiasReporte ?? []; ?>
                                <?php foreach ($materiasReporte as $idMateria => $materia): ?>
                                    <option value="<?= htmlspecialchars($idMateria, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($materia['nombre'] . ($materia['unidad'] !== '' ? ' (' . $materia['unidad'] . ')' : ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="reporteEstadoProduccion">Estado de producción</label>
                            <select class="form-select" id="reporteEstadoProduccion">
                                <option value="">Todos los estados</option>
                                <option value="activas">Activas (cualquier estado excepto Inactiva)</option>
                                <?php $estadosProduccionReporte = $estadosProduccionReporte ?? []; ?>
                                <?php foreach ($estadosProduccionReporte as $estado): ?>
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

    <div class="modal fade modal-idealo" id="modalRegistrarConsumo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clipboard-plus me-2"></i>Registrar Consumo de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formRegistrarConsumo" method="post" action="index.php?controller=consumoMaterial&action=listar">
                    <input type="hidden" name="accion" value="guardar">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Materia prima</label>
                                <select class="form-select" name="id_materia_prima" id="registrar_id_materia_prima" required>
                                    <option value="">Seleccione una materia prima</option>
                                    <?php $materias = $materias ?? []; ?>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?php echo htmlspecialchars($materia['id_materia_prima']); ?>"
                                            <?php echo (float) ($materia['stock_actual'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                            <?php echo htmlspecialchars($materia['nombre_materia_prima']); ?>
                                            (<?php echo htmlspecialchars($materia['unidad_de_medida']); ?>)
                                            <?php if ((float) ($materia['stock_actual'] ?? 0) <= 0): ?>
                                                - Sin stock
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Orden de producción</label>
                                <select class="form-select" name="id_produccion" id="registrar_id_produccion" required>
                                    <option value="">Seleccione una orden</option>
                                    <?php $ordenes = $ordenes ?? []; ?>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion'] ?? ''); ?>"
                                            data-producto="<?php echo htmlspecialchars($orden['producto_asociado'] ?? ''); ?>">
                                            OP-<?php echo str_pad((string)($orden['id_produccion'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                                            - <?php echo htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin pedido'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Producto asociado a la orden</label>
                                <input type="text" class="form-control" id="registrar_producto_asociado" readonly placeholder="Se completa al seleccionar la orden">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Costo unitario</label>
                                <input type="number" class="form-control" name="costo_unitario" id="registrar_costo_unitario" step="0.01" min="0" required readonly placeholder="Se completa al seleccionar la materia prima">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cantidad usada</label>
                                <input type="number" class="form-control" name="cantidad_usada" step="1" min="1" required placeholder="Ej. 5">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción del consumo</label>
                                <textarea class="form-control" name="descripcion_de_consumo" rows="3" placeholder="Ej. Consumo de tela para lote de camisas."></textarea>
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

    <div class="modal fade modal-idealo" id="modalEditarConsumo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Consumo de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditarConsumo" method="post" action="index.php?controller=consumoMaterial&action=listar">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_consumo_material" id="edit_id_consumo_material">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Materia prima</label>
                                <select class="form-select" name="id_materia_prima" id="edit_id_materia_prima" required>
                                    <option value="">Seleccione una materia prima</option>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?php echo htmlspecialchars($materia['id_materia_prima']); ?>">
                                            <?php echo htmlspecialchars($materia['nombre_materia_prima']); ?>
                                            (<?php echo htmlspecialchars($materia['unidad_de_medida']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Orden de producción</label>
                                <select class="form-select" name="id_produccion" id="edit_id_produccion" required>
                                    <option value="">Seleccione una orden</option>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion'] ?? ''); ?>"
                                            data-producto="<?php echo htmlspecialchars($orden['producto_asociado'] ?? ''); ?>">
                                            OP-<?php echo str_pad((string)($orden['id_produccion'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                                            - <?php echo htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin pedido'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Producto asociado a la orden</label>
                                <input type="text" class="form-control" id="edit_producto_asociado" readonly placeholder="Se completa al seleccionar la orden">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Costo unitario</label>
                                <input type="number" class="form-control" name="costo_unitario" id="edit_costo_unitario" step="0.01" min="0" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cantidad usada</label>
                                <input type="number" class="form-control" name="cantidad_usada" id="edit_cantidad_usada" step="1" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción del consumo</label>
                                <textarea class="form-control" name="descripcion_de_consumo" id="edit_descripcion_de_consumo" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/js/helpers/expresiones.js"></script>
    <script src="assets/libs/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/jquery.dataTables.min.js"></script>
    <script src="assets/libs/dataTables.bootstrap5.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script src="assets/js/consumo_material.js"></script>
</body>

</html>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Consumo de Material</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
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
                    <h1 id="tituloVista">Consumo de Materiales</h1>
                    <p>Registra y administra el consumo de materia prima en las órdenes de producción.</p>
                </div>
                <div>
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
                                <th>Costo Unitario</th>
                                <th>Cantidad Usada</th>
                                <th>Costo Total</th>
                                <th>Producción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyConsumos">
                            <?php $consumos = $consumos ?? []; ?>
                            <?php if (!empty($consumos)): ?>
                                <?php foreach ($consumos as $c): ?>
                                    <?php $costoTotal = (float)($c['costo_unitario'] ?? 0) * (float)($c['cantidad_usada'] ?? 0); ?>
                                    <tr id="fila-<?php echo htmlspecialchars($c['id_consumo_material'] ?? ''); ?>">
                                        <td class="fw-bold">#<?php echo htmlspecialchars($c['id_consumo_material'] ?? ''); ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['nombre_materia_prima'] ?? 'Sin material'); ?></div>
                                            <small class="text-muted"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($c['descripcion_de_consumo'] ?? 'Sin descripción'); ?></small>
                                        </td>
                                        <td class="fw-bold text-muted">$<?php echo number_format((float)($c['costo_unitario'] ?? 0), 2, '.', ','); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($c['cantidad_usada'] ?? 0) . ' ' . htmlspecialchars($c['unidad_de_medida'] ?? ''); ?></td>
                                        <td class="text-success fw-bold">$<?php echo number_format((float)$costoTotal, 2, '.', ','); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">OP-<?php echo str_pad((string)($c['id_produccion'] ?? 0), 4, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-primary btnEditarConsumo"
                                                    type="button"
                                                    data-id_consumo_material="<?php echo htmlspecialchars($c['id_consumo_material'] ?? ''); ?>"
                                                    data-id_materia_prima="<?php echo htmlspecialchars($c['id_materia_prima'] ?? ''); ?>"
                                                    data-costo_unitario="<?php echo htmlspecialchars($c['costo_unitario'] ?? ''); ?>"
                                                    data-cantidad_usada="<?php echo htmlspecialchars($c['cantidad_usada'] ?? ''); ?>"
                                                    data-descripcion_de_consumo="<?php echo htmlspecialchars($c['descripcion_de_consumo'] ?? ''); ?>"
                                                    data-id_produccion="<?php echo htmlspecialchars($c['id_produccion'] ?? ''); ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox-fill fs-3 d-block mb-2"></i>
                                        No hay consumos registrados aún.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalRegistrarConsumo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clipboard-plus text-success me-2"></i>Registrar Consumo de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formRegistrarConsumo" method="post" action="index.php?controller=consumoMaterial&action=listar">
                    <input type="hidden" name="accion" value="guardar">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Materia prima</label>
                                <select class="form-select" name="id_materia_prima" required>
                                    <option value="">Seleccione una materia prima</option>
                                    <?php $materias = $materias ?? []; ?>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?php echo htmlspecialchars($materia['id_materia_prima']); ?>">
                                            <?php echo htmlspecialchars($materia['nombre_materia_prima']); ?>
                                            (<?php echo htmlspecialchars($materia['unidad_de_medida']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Orden de producción</label>
                                <select class="form-select" name="id_produccion" required>
                                    <option value="">Seleccione una orden</option>
                                    <?php $ordenes = $ordenes ?? []; ?>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion'] ?? ''); ?>">
                                            OP-<?php echo str_pad((string)($orden['id_produccion'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                                            - <?php echo htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin pedido'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Costo unitario</label>
                                <input type="number" class="form-control" name="costo_unitario" step="0.01" min="0" required placeholder="Ej. 12.50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cantidad usada</label>
                                <input type="number" class="form-control" name="cantidad_usada" step="1" min="1" required placeholder="Ej. 5">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción del consumo</label>
                                <textarea class="form-control" name="descripcion_de_consumo" rows="3" placeholder="Ej. Consumo de tela para lote de camisas."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarConsumo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Consumo de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditarConsumo" method="post" action="index.php?controller=consumoMaterial&action=listar">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_consumo_material" id="edit_id_consumo_material">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Materia prima</label>
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
                                <label class="form-label fw-semibold">Orden de producción</label>
                                <select class="form-select" name="id_produccion" id="edit_id_produccion" required>
                                    <option value="">Seleccione una orden</option>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion'] ?? ''); ?>">
                                            OP-<?php echo str_pad((string)($orden['id_produccion'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                                            - <?php echo htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin pedido'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Costo unitario</label>
                                <input type="number" class="form-control" name="costo_unitario" id="edit_costo_unitario" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cantidad usada</label>
                                <input type="number" class="form-control" name="cantidad_usada" id="edit_cantidad_usada" step="1" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción del consumo</label>
                                <textarea class="form-control" name="descripcion_de_consumo" id="edit_descripcion_de_consumo" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
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
    <script src="assets/js/consumo_material.js"></script>
</body>

</html>
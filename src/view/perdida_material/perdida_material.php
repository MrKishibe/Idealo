<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Pérdidas de Material</title> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
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
                <button type="button" class="btn btn-danger px-4" data-bs-toggle="modal" data-bs-target="#modalRegistrarPerdida" style="border-radius: 12px; font-weight: 600;">
                    <i class="bi bi-trash3-fill me-1"></i> Registrar Pérdida
                </button>
            </header>

            <div class="table-responsive shadow-sm" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden;">
                <table class="table table-hover mb-0 align-middle" id="tablaPerdidasMaterial" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Cantidad</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3">Costo Unitario</th>
                            <th class="px-4 py-3">Producción</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPerdidaMaterial">
                        <?php $perdidas = $perdidas ?? []; ?>
                        <?php if (!empty($perdidas)): ?>
                            <?php foreach ($perdidas as $perdida): ?>
                                <?php
                                    $produccionLabel = 'Orden #' . ($perdida['id_produccion'] ?? '');
                                    if (!empty($perdida['descripcion_pedido'])) {
                                        $desc = trim($perdida['descripcion_pedido']);
                                        if (!empty($perdida['cantidad_detalle'])) {
                                            $produccionLabel = 'Pedido de ' . $perdida['cantidad_detalle'] . ' ' . $desc;
                                        } else {
                                            $produccionLabel = $desc;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="px-4 py-3"><code>#<?php echo htmlspecialchars($perdida['id_perdida_material'] ?? ''); ?></code></td>
                                    <td class="px-4 py-3"><strong><?php echo htmlspecialchars($perdida['cantidad_perdida'] ?? ''); ?></strong></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($perdida['fecha_de_registro'] ?? ''); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($perdida['motivo'] ?? ''); ?></td>
                                    <td class="px-4 py-3">$<?php echo number_format((float)($perdida['costo_unitario'] ?? 0), 2, '.', ','); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($produccionLabel); ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-sm btn-light border btnEditarPerdida" data-bs-toggle="modal" data-bs-target="#modalEditarPerdida"
                                                data-id_perdida="<?php echo htmlspecialchars($perdida['id_perdida_material'] ?? ''); ?>"
                                                data-cantidad="<?php echo htmlspecialchars($perdida['cantidad_perdida'] ?? ''); ?>"
                                                data-fecha="<?php echo htmlspecialchars($perdida['fecha_de_registro'] ?? ''); ?>"
                                                data-costo="<?php echo htmlspecialchars($perdida['costo_unitario'] ?? ''); ?>"
                                                data-id_produccion="<?php echo htmlspecialchars($perdida['id_produccion'] ?? ''); ?>"
                                                data-motivo="<?php echo htmlspecialchars($perdida['motivo'] ?? ''); ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox-fill fs-3 d-block mb-2"></i>
                                    No hay pérdidas registradas aún.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalRegistrarPerdida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-trash3-fill text-danger me-2"></i>Registrar Pérdida de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarPerdida" method="post" action="index.php?controller=perdidaMaterial&action=guardar">
                    <input type="hidden" name="accion" value="guardar">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cantidad perdida</label>
                                <input type="number" class="form-control" name="cantidad_perdida" step="1" min="0" required placeholder="Ej: 2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de registro</label>
                                <input type="date" class="form-control" name="fecha_de_registro" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Costo unitario</label>
                                <input type="number" class="form-control" name="costo_unitario" step="0.01" min="0" required placeholder="Ej: 15.50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Orden de producción</label>
                                <select class="form-select" name="id_produccion" required>
                                    <option value="">Seleccione...</option>
                                    <?php $ordenes = $ordenes ?? []; ?>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <?php
                                        // Mostrar descripción del pedido si está disponible, incluyendo cantidad si existe
                                        if (!empty($orden['descripcion_pedido'])) {
                                            $desc = trim($orden['descripcion_pedido']);
                                            if (!empty($orden['cantidad_detalle'])) {
                                                $labelOrden = 'Pedido de ' . $orden['cantidad_detalle'] . ' ' . $desc;
                                            } else {
                                                $labelOrden = $desc;
                                            }
                                        } else {
                                            $labelOrden = 'Orden #' . ($orden['id_produccion'] ?? '');
                                        }

                                        // Añadir estado o fecha si se desea contexto adicional
                                        if (!empty($orden['estado_de_produccion'])) {
                                            $labelOrden .= ' - ' . $orden['estado_de_produccion'];
                                        }
                                        ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($labelOrden); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Motivo</label>
                                <textarea class="form-control" name="motivo" rows="3" required placeholder="Describa el motivo de la pérdida o desmarque..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarPerdida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Pérdida de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarPerdida" method="post" action="index.php?controller=perdidaMaterial&action=editar">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_perdida_material" id="edit_id_perdida_material">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cantidad perdida</label>
                                <input type="number" class="form-control" name="cantidad_perdida" id="edit_cantidad_perdida" step="1" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de registro</label>
                                <input type="date" class="form-control" name="fecha_de_registro" id="edit_fecha_de_registro" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Costo unitario</label>
                                <input type="number" class="form-control" name="costo_unitario" id="edit_costo_unitario" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Orden de producción</label>
                                <select class="form-select" name="id_produccion" id="edit_id_produccion" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <?php
                                        if (!empty($orden['descripcion_pedido'])) {
                                            $desc = trim($orden['descripcion_pedido']);
                                            if (!empty($orden['cantidad_detalle'])) {
                                                $labelOrden = 'Pedido de ' . $orden['cantidad_detalle'] . ' ' . $desc;
                                            } else {
                                                $labelOrden = $desc;
                                            }
                                        } else {
                                            $labelOrden = 'Orden #' . ($orden['id_produccion'] ?? '');
                                        }

                                        if (!empty($orden['estado_de_produccion'])) {
                                            $labelOrden .= ' - ' . $orden['estado_de_produccion'];
                                        }
                                        if (!empty($orden['fecha_de_inicio'])) {
                                            $labelOrden .= ' - ' . $orden['fecha_de_inicio'];
                                        }
                                        ?>
                                        <option value="<?php echo htmlspecialchars($orden['id_produccion'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($labelOrden); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Motivo</label>
                                <textarea class="form-control" name="motivo" id="edit_motivo" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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

    <script src="assets/js/perdida_material.js"></script>
</body>
</html>
</body>

</html>

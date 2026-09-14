<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Materia Prima</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <?php include 'src/view/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Materia Prima</h1>
                    <p>Administra los insumos y materiales de tu negocio de sublimación.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarMateriaPrima">
                        <i class="bi bi-box-seam-fill"></i> Registrar Materia Prima
                    </button>
                </div>
            </header>
            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaMateriaPrima" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Costo Unit.</th>
                                <th>cantidad Actual</th>
                                <th>cantidad Mínimo</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiasPrimas as $mp): ?>
                            <tr id="fila-<?php echo htmlspecialchars($mp['id_materia_prima']); ?>">
                                <td class="fw-bold"><?php echo htmlspecialchars($mp['nombre_materia_prima']); ?></td>
                                <td><?php echo htmlspecialchars($mp['nombre_de_material'] ?? 'Sin tipo'); ?></td>
                                <td><?php echo number_format($mp['costo_unitario'], 2, '.', ','); ?></td>
                                <td><?php echo number_format($mp['stock_actual'], 2, '.', ',') . ' ' . htmlspecialchars($mp['unidad_de_medida']); ?></td>
                                <td><?php echo number_format($mp['stock_minimo'], 2, '.', ',') . ' ' . htmlspecialchars($mp['unidad_de_medida']); ?></td>
                                <td><span class="badge <?php echo $mp['status_materia_prima'] === 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($mp['status_materia_prima']); ?></span></td>
                                <td>
                                    <div class="text-center">
                                        <?php if ($mp['status_materia_prima'] === 'Activo'): ?>
                                        <button class="btn btn-sm btn-outline-primary btnEditarActivo me-1" data-id="<?php echo $mp['id_materia_prima']; ?>" data-nombre="<?php echo htmlspecialchars($mp['nombre_materia_prima']); ?>" data-id-tipo="<?php echo $mp['id_tipo_materia_prima']; ?>" data-costo="<?php echo $mp['costo_unitario']; ?>" data-stock-actual="<?php echo $mp['stock_actual']; ?>" data-stock-minimo="<?php echo $mp['stock_minimo']; ?>" data-unidad="<?php echo htmlspecialchars($mp['unidad_de_medida']); ?>" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btnCambiarEstado" data-id="<?php echo $mp['id_materia_prima']; ?>" data-nombre="<?php echo htmlspecialchars($mp['nombre_materia_prima']); ?>" title="Inhabilitar"><i class="bi bi-trash3-fill"></i></button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-warning btnEditarInactivo" data-id="<?php echo $mp['id_materia_prima']; ?>" data-nombre="<?php echo htmlspecialchars($mp['nombre_materia_prima']); ?>" data-id-tipo="<?php echo $mp['id_tipo_materia_prima']; ?>" data-costo="<?php echo $mp['costo_unitario']; ?>" data-stock-actual="<?php echo $mp['stock_actual']; ?>" data-stock-minimo="<?php echo $mp['stock_minimo']; ?>" data-unidad="<?php echo htmlspecialchars($mp['unidad_de_medida']); ?>" title="Editar / Reactivar"><i class="bi bi-pencil-square"></i></button>
                                        <?php endif; ?>
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

    <!-- Modal Registrar -->
    <div class="modal fade modal-idealo" id="modalRegistrarMateriaPrima" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam-fill me-2"></i>Registrar Materia Prima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formMateriaPrima">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre_materia_prima" maxlength="100" required>
                                <div class="invalid-feedback">El nombre debe tener entre 3 y 100 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" id="id_tipo_materia_prima" required><option value="">Cargando...</option></select>
                                <div class="invalid-feedback">Debe seleccionar un tipo.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Costo Unit.</label>
                                <input type="number" class="form-control" id="costo_unitario" step="0.01" min="0" required>
                                <div class="invalid-feedback">El costo debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" class="form-control" id="stock_actual" step="0.01" min="0" required>
                                <div class="invalid-feedback">El stock debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="stock_minimo" step="0.01" min="0" required>
                                <div class="invalid-feedback">El stock debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unidad de Medida</label>
                                <select class="form-select" id="unidad_de_medida" required>
                                    <option value="">Seleccione...</option>
                                    <optgroup label="Conteo / Piezas">
                                        <option value="unidad">unidad (ud)</option>
                                        <option value="pieza">pieza (pza)</option>
                                        <option value="paquete">paquete (paq)</option>
                                        <option value="caja">caja (cj)</option>
                                        <option value="docena">docena (doc)</option>
                                        <option value="set">set</option>
                                    </optgroup>
                                    <optgroup label="Longitud">
                                        <option value="milímetro">milímetro (mm)</option>
                                        <option value="centímetro">centímetro (cm)</option>
                                        <option value="metro">metro (m)</option>
                                    </optgroup>
                                    <optgroup label="Peso/Masa">
                                        <option value="miligramo">miligramo (mg)</option>
                                        <option value="gramo">gramo (g)</option>
                                        <option value="kilogramo">kilogramo (kg)</option>
                                    </optgroup>
                                    <optgroup label="Líquidos/Volumen">
                                        <option value="mililitro">mililitro (ml)</option>
                                        <option value="litro">litro (L)</option>
                                        <option value="galon">galón (gal)</option>
                                    </optgroup>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar una unidad.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-idealo-success" id="btnEnvio">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Activo -->
    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Materia Prima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarActivo">
                    <input type="hidden" id="edit_activo_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="edit_activo_nombre" maxlength="100" required>
                                <div class="invalid-feedback">El nombre debe tener entre 3 y 100 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" id="edit_activo_id_tipo" required><option value="">Cargando...</option></select>
                                <div class="invalid-feedback">Debe seleccionar un tipo.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Costo</label>
                                <input type="number" class="form-control" id="edit_activo_costo" step="0.01" min="0" required>
                                <div class="invalid-feedback">El costo debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" class="form-control" id="edit_activo_stock_actual" step="0.01" min="0" required>
                                <div class="invalid-feedback">El stock debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="edit_activo_stock_minimo" step="0.01" min="0" required>
                                <div class="invalid-feedback">El stock debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unidad de Medida</label>
                                <select class="form-select" id="edit_activo_unidad" required>
                                    <option value="">Seleccione...</option>
                                    <optgroup label="Conteo / Piezas">
                                        <option value="unidad">unidad (ud)</option>
                                        <option value="pieza">pieza (pza)</option>
                                        <option value="paquete">paquete (paq)</option>
                                        <option value="caja">caja (cj)</option>
                                        <option value="docena">docena (doc)</option>
                                        <option value="set">set</option>
                                    </optgroup>
                                    <optgroup label="Longitud">
                                        <option value="milímetro">milímetro (mm)</option>
                                        <option value="centímetro">centímetro (cm)</option>
                                        <option value="metro">metro (m)</option>
                                    </optgroup>
                                    <optgroup label="Peso/Masa">
                                        <option value="miligramo">miligramo (mg)</option>
                                        <option value="gramo">gramo (g)</option>
                                        <option value="kilogramo">kilogramo (kg)</option>
                                    </optgroup>
                                    <optgroup label="Líquidos/Volumen">
                                        <option value="mililitro">mililitro (ml)</option>
                                        <option value="litro">litro (L)</option>
                                        <option value="galon">galón (gal)</option>
                                    </optgroup>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar una unidad.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnGuardarEdicionActivo">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Inactivo (Nombre + Todos los campos + Estado) -->
    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Materia Prima Inhabilitada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarInactivo">
                    <input type="hidden" id="edit_inactivo_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="edit_inactivo_nombre" maxlength="100" required>
                                <div class="invalid-feedback">El nombre debe tener entre 3 y 100 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" id="edit_inactivo_id_tipo" required><option value="">Cargando...</option></select>
                                <div class="invalid-feedback">Debe seleccionar un tipo.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Costo</label>
                                <input type="number" class="form-control" id="edit_inactivo_costo" step="0.01" min="0" required>
                                <div class="invalid-feedback">El costo debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" class="form-control" id="edit_inactivo_stock_actual" step="0.01" min="0" required>
                                <div class="invalid-feedback">El stock debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="edit_inactivo_stock_minimo" step="0.01" min="0" required>
                                <div class="invalid-feedback">El stock debe ser mayor o igual a 0.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unidad de Medida</label>
                                <select class="form-select" id="edit_inactivo_unidad" required>
                                    <option value="">Seleccione...</option>
                                    <optgroup label="Conteo / Piezas">
                                        <option value="unidad">unidad (ud)</option>
                                        <option value="pieza">pieza (pza)</option>
                                        <option value="paquete">paquete (paq)</option>
                                        <option value="caja">caja (cj)</option>
                                        <option value="docena">docena (doc)</option>
                                        <option value="set">set</option>
                                    </optgroup>
                                    <optgroup label="Longitud">
                                        <option value="milímetro">milímetro (mm)</option>
                                        <option value="centímetro">centímetro (cm)</option>
                                        <option value="metro">metro (m)</option>
                                    </optgroup>
                                    <optgroup label="Peso/Masa">
                                        <option value="miligramo">miligramo (mg)</option>
                                        <option value="gramo">gramo (g)</option>
                                        <option value="kilogramo">kilogramo (kg)</option>
                                    </optgroup>
                                    <optgroup label="Líquidos/Volumen">
                                        <option value="mililitro">mililitro (ml)</option>
                                        <option value="litro">litro (L)</option>
                                        <option value="galon">galón (gal)</option>
                                    </optgroup>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar una unidad.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="edit_inactivo_estado" required>
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar un estado.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btnGuardarEdicionInactivo">Guardar Cambios</button>
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
    <script src="assets/js/materiaprima.js"></script>
</body>
</html>
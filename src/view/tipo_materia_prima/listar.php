<?php
if (!isset($materiales)) {
    $materiales = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Tipo de Material</title>
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
                    <h1 id="tituloVista">Tipo de Material</h1>
                    <p>Administra las categorías de insumos de tu negocio de sublimación.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarMaterial">
                        <i class="bi bi-tags-fill"></i> Registrar Insumo
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaTipoMaterial" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nombre del Material</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($materiales)): ?>
                                <?php foreach ($materiales as $mat): ?>
                                    <tr id="fila-<?php echo $mat['id_tipo_materia_prima']; ?>">
                                        <td class="fw-bold"><?php echo htmlspecialchars($mat["nombre_de_material"]); ?></td>
                                        <td><?php echo htmlspecialchars($mat["descripcion"] ?: 'Sin especificaciones'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $mat["status_tipo_material"] === 'Activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $mat["status_tipo_material"]; ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <?php if ($mat["status_tipo_material"] === 'Activo'): ?>
                                                    <button class="btn btn-sm btn-outline-primary btnEditarActivo me-1"
                                                        data-id="<?php echo $mat['id_tipo_materia_prima']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($mat["nombre_de_material"]); ?>"
                                                        data-descripcion="<?php echo htmlspecialchars($mat["descripcion"]); ?>"
                                                        title="Editar Material">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger btnCambiarEstado"
                                                        data-id="<?php echo $mat['id_tipo_materia_prima']; ?>"
                                                        data-estado="Inactivo"
                                                        title="Inhabilitar Material">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-warning btnEditarInactivo"
                                                        data-id="<?php echo $mat['id_tipo_materia_prima']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($mat["nombre_de_material"]); ?>"
                                                        title="Reactivar / Modificar">
                                                        <i class="bi bi-pencil-square"></i> Editar / Reactivar
                                                    </button>
                                                <?php endif; ?>
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

    <div class="modal fade modal-idealo" id="modalRegistrarMaterial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-tags-fill me-2"></i>Registrar Tipo de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formTipoMaterial" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del Tipo de Material</label>
                                <input type="text" class="form-control" id="nombre_tipo_material" placeholder="Ej. Tazas de Cerámica" required>
                                <div class="invalid-feedback">El nombre debe tener entre 3 y 50 caracteres (letras, números o /-.()).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción o Notas</label>
                                <textarea class="form-control" id="descripcion_tipo_material" rows="3" placeholder="Detalles del tipo de insumo..."></textarea>
                                <div class="invalid-feedback">Máximo 250 caracteres permitidos.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-idealo-success" id="btnEnvio">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Tipo de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarActivo" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_activo_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del Tipo de Material</label>
                                <input type="text" class="form-control" id="edit_activo_nombre" required>
                                <div class="invalid-feedback">El nombre debe tener entre 3 y 50 caracteres.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción o Notas</label>
                                <textarea class="form-control" id="edit_activo_descripcion" rows="3"></textarea>
                                <div class="invalid-feedback">Máximo 250 caracteres permitidos.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnGuardarEdicionActivo">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Registro Inhabilitado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarMaterial">
                    <input type="hidden" id="edit_id_material">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre</label>
                                <input type="text" class="form-control" id="edit_nombre_material" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado de Registro</label>
                                <select class="form-select border-danger" id="edit_status_material">
                                    <option value="Inactivo" selected>Inactivo (Archivado)</option>
                                    <option value="Activo">Activo (Reactivar Material)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnGuardarEdicionInactivo">Guardar Cambios</button>
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
    <script src="assets/js/tipomaterial.js"></script>

</body>

</html>
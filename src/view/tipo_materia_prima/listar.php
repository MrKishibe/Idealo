<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Tipos de Materia Prima</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/skeleton-elements.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/datatable-skeleton.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
</head>
<body>
    <?php include __DIR__ . '/../sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Tipo de Material</h1>
                    <p>Administra los tipos de materiales e insumos de tu negocio.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i>
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarMaterial">
                        <i class="bi bi-tags-fill"></i> Registrar Tipo
                    </button>
                </div>
            </header>
            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaTipoMaterial" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Contenido cargado dinámicamente vía AJAX -->
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
                    <h5 class="modal-title"><i class="bi bi-tags-fill me-2"></i>Registrar Tipo de Materia Prima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formTipoMaterial">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre_tipo_material" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre_tipo_material" maxlength="50" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion_tipo_material" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion_tipo_material" maxlength="250" rows="3"></textarea>
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

    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Tipo de Materia Prima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formEditarActivo">
                    <input type="hidden" id="edit_activo_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_activo_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit_activo_nombre" maxlength="50" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_activo_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_activo_descripcion" maxlength="250" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Tipo de Materia Prima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formEditarMaterial">
                    <input type="hidden" id="edit_id_material">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre_material" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit_nombre_material" maxlength="50" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion_material" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion_material" maxlength="250" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status_material" class="form-label">Estado</label>
                            <select class="form-select" id="edit_status_material" required>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/jquery.dataTables.min.js"></script>
    <script src="assets/libs/dataTables.bootstrap5.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script type="importmap">
    {
        "imports": {
            "jquery": "./assets/js/helpers/jquery-bridge.js",
            "sweetalert2": "./assets/js/helpers/sweetalert2-bridge.js"
        }
    }
    </script>
    <script type="module" src="assets/js/tipomaterial.js"></script>
</body>
</html>
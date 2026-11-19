<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Roles</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/skeleton-elements.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/datatable-skeleton.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
    <link rel="stylesheet" href="assets/libs/css/iconos.css">
</head>

<body>

    <?php include 'src/view/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Gestión de Roles</h1>
                    <p>Administra los roles de acceso disponibles para los usuarios del sistema.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i>
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarRol">
                        <i class="bi bi-shield-plus"></i>
                        <span>Registrar Rol</span>
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaRoles" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ROL</th>
                                <th>ESTADO</th>
                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL REGISTRAR ROL -->
    <div class="modal fade modal-idealo" id="modalRegistrarRol" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-plus me-2"></i>Registrar Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarRol" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del Rol</label>
                                <input type="text" class="form-control" id="reg_tipo_de_usuario" name="tipo_de_usuario" maxlength="50" placeholder="Ej. superadministrador" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnEnvioRegistro">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR ROL -->
    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formEditarActivo" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_activo_id_rol" name="id_rol">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del Rol</label>
                                <input type="text" class="form-control" id="edit_activo_tipo_de_usuario" name="tipo_de_usuario" maxlength="50" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnGuardarEdicionActivo">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR INACTIVO / REACTIVAR -->
    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Reactivar Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarInactivo">
                    <input type="hidden" id="edit_inactivo_id_rol">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre del Rol</label>
                                <input type="text" class="form-control bg-light" id="edit_inactivo_tipo_de_usuario" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado de Registro</label>
                                <select class="form-select border-danger" id="edit_inactivo_status">
                                    <option value="inactivo" selected>Inactivo (Inhabilitado)</option>
                                    <option value="activo">Activo (Reactivar Rol)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-idealo-success" id="btnGuardarEdicionInactivo">Guardar Cambios</button>
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
        {"imports":{"jquery":"./assets/js/helpers/jquery-bridge.js","sweetalert2":"./assets/js/helpers/sweetalert2-bridge.js"}}
    </script>
    <script type="module" src="assets/js/rol.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/rol.js') ?>"></script>

</body>

</html>

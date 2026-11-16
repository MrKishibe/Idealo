<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Permisos por Rol</title>
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
                    <h1 id="tituloVista">Permisos por Rol</h1>
                    <p>Consulta los permisos disponibles y asígnalos a los roles del sistema.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-2 py-1" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i>
                        <span id="txtBotonEstado">Ver inactivos</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalAsignarPermisos">
                        <i class="bi bi-shield-plus me-1"></i> Asignar permisos
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaPermisos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Permiso</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalAsignarPermisos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formAsignarPermisos">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i>Asignar permisos a un rol</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="selectRolPermisos">Rol</label>
                            <select class="form-select" id="selectRolPermisos" name="id_rol" required>
                                <option value="">Seleccione un rol</option>
                            </select>
                        </div>
                        <fieldset>
                            <legend class="form-label">Permisos</legend>
                            <p class="form-text">Los permisos ya asignados se conservan. Puedes agregar permisos activos.</p>
                            <div id="listaPermisosRol" class="border rounded p-3" style="max-height: 18rem; overflow-y: auto;">
                                <p class="text-muted mb-0">Seleccione un rol para cargar los permisos.</p>
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnGuardarPermisos" disabled>Guardar permisos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/libs/bootstrap.bundle.min.js"></script>
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
    <script type="module" src="assets/js/permisos.js"></script>
</body>

</html>
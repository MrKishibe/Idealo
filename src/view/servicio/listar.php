<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Servicios</title>

    <!-- Fuentes y CDN -->
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/iconos.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/skeleton-elements.css">
    <link rel="stylesheet" href="assets/libs/skeleton-elements/datatable-skeleton.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
</head>

<body>

    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container">

            <!-- Encabezado de la Vista -->
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Gestión de Servicios</h1>
                    <p>Administra los servicios extra del sistema.</p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="button" 
                            id="btnAlternarEstado" 
                            class="btn btn-outline-secondary px-3 py-2" 
                            style="border-radius: var(--radius-md); font-weight: 600;" 
                            data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i>
                        <span id="txtBotonEstado">Ver Inhabilitados</span>
                    </button>

                    <button type="button" 
                            class="btn-idealo-success" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalRegistrarServicio">
                        <i class="bi bi-plus-circle me-1"></i> Registrar Servicio
                    </button>
                </div>
            </header>

            <!-- Tabla Principal (Los datos se cargan dinámicamente vía AJAX con DataTable) -->
            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaServicios" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nombre del Servicio</th>
                                <th>Estado</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyServicios">
                            <!-- Contenido poblado dinámicamente por JavaScript / DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- ================================================================
         MODAL PARA REGISTRAR SERVICIO
         ================================================================= -->
    <div class="modal fade modal-idealo" id="modalRegistrarServicio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i> Registrar Servicio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formRegistrarServicio" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="nombre_servicio">Nombre del Servicio</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre_servicio" 
                                       id="nombre_servicio" 
                                       placeholder="Ej. Sublimación Especial"
                                       maxlength="50"
                                       required 
                                       autocomplete="off">
                                <div class="invalid-feedback">
                                    El nombre del servicio es obligatorio.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================================================================
         MODAL PARA EDITAR SERVICIO
         ================================================================= -->
    <div class="modal fade modal-idealo" id="modalEditarServicio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i> Editar Servicio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditarServicio" class="needs-validation" novalidate>
                    <input type="hidden" name="id_servicio" id="edit_id_servicio">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="edit_nombre_servicio">Nombre del Servicio</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nombre_servicio" 
                                       id="edit_nombre_servicio" 
                                       maxlength="50"
                                       required 
                                       autocomplete="off">
                                <div class="invalid-feedback">
                                    El nombre del servicio es obligatorio.
                                </div>
                            </div>

                            <div class="col-12" id="contenedor_edit_estado" style="display: none;">
                                <label class="form-label" for="edit_status_servicio">Estado del Registro</label>
                                <select class="form-select" name="status_servicio" id="edit_status_servicio">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <div class="invalid-feedback">
                                    Debe seleccionar un estado válido.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
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
    <script type="module" src="assets/js/servicio.js"></script>

</body>

</html>
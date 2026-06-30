<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Servicios</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>

<body>

    <?php include 'src/view/sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container" style="padding: 2rem max(2vw, 20px);">
            <header class="page-header d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h1 class="fw-bold text-dark mb-1" id="tituloVista" style="font-size: 1.75rem;">Gestión de Servicios</h1>
                    <p class="text-muted mb-0">Administra los servicios extra del sistema.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2" id="btnAlternarEstado" data-vista="activos" style="border-radius: 12px; font-weight: 600;">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i> <span id="txtBotonEstado">Ver Inhabilitados</span>
                    </button>
                    <button type="button" class="btn btn-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarServicio" style="border-radius: 12px; font-weight: 600;">
                        <i class="bi bi-plus-circle me-1"></i> Registrar Servicio
                    </button>
                </div>
            </header>

            <div class="card p-4 shadow-sm" style="border-radius: 16px; border: 1px solid #e2e8f0; background: #fff;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaServicios" style="width:100%;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th class="px-4 py-3">Nombre del Servicio</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyServicios">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalRegistrarServicio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-plus-circle text-success me-2"></i>Registrar Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarServicio">
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre del Servicio</label>
                            <input type="text" class="form-control" name="nombre_servicio" id="nombre_servicio" required autocomplete="off">
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

    <div class="modal fade" id="modalEditarServicio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarServicio">
                    <input type="hidden" name="id_servicio" id="edit_id_servicio">
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre del Servicio</label>
                            <input type="text" class="form-control" name="nombre_servicio" id="edit_nombre_servicio" required autocomplete="off">
                        </div>
                        <div class="mb-3" id="contenedor_edit_estado" style="display: none;">
                            <label class="form-label fw-semibold">Estado del Registro</label>
                            <select class="form-select" name="status_servicio" id="edit_status_servicio">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
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
    <script src="assets/js/servicio.js"></script>

</body>

</html>
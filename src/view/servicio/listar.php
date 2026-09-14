<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Servicios</title>

    <!-- Fuentes y CDN -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- CSS Locales -->
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
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

            <!-- Tabla Principal -->
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
                            <?php if (isset($servicios) && is_array($servicios)): ?>
                                <?php foreach ($servicios as $servicio): ?>
                                    <?php
                                        $idServicio = $servicio['id_servicio'] ?? '';
                                        $nombreServicio = $servicio['nombre_servicio'] ?? '';
                                        $estadoServicio = $servicio['status_servicio'] ?? 'inactivo';
                                        $esActivo = strtolower($estadoServicio) === 'activo';
                                    ?>
                                    <tr id="fila-<?php echo htmlspecialchars($idServicio, ENT_QUOTES, 'UTF-8'); ?>">
                                        <td class="fw-bold">
                                            <?php echo htmlspecialchars($nombreServicio, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $esActivo ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($estadoServicio), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary me-1 btnEditarServicio" 
                                                    data-id="<?php echo htmlspecialchars($idServicio, ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-nombre="<?php echo htmlspecialchars($nombreServicio, ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-estado="<?php echo htmlspecialchars($estadoServicio, ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Editar Servicio">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm <?php echo $esActivo ? 'btn-outline-danger' : 'btn-outline-success'; ?> btnCambiarEstado" 
                                                    data-id="<?php echo htmlspecialchars($idServicio, ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-nombre="<?php echo htmlspecialchars($nombreServicio, ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-estado="<?php echo $esActivo ? 'inactivo' : 'activo'; ?>" 
                                                    title="<?php echo $esActivo ? 'Inhabilitar' : 'Reactivar'; ?> Servicio">
                                                <i class="bi <?php echo $esActivo ? 'bi-trash3-fill' : 'bi-check-circle-fill'; ?>"></i>
                                            </button>
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
    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="assets/js/servicio.js"></script>

</body>

</html>
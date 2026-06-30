<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Productos</title>
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
                    <h1 class="fw-bold text-dark mb-1" id="tituloVista">Catálogo de Productos</h1>
                    <p class="text-muted mb-0">Administra el catálogo de productos del sistema.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3" style="border-radius: 12px; font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#modalRegistrarProducto" style="border-radius: 12px; font-weight: 600;" onclick="limpiarFormularioCrear()">
                        <i class="bi bi-box-seam-plus me-1"></i> Registrar Producto
                    </button>
                </div>
            </header>

            <div class="table-responsive shadow-sm" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden;">
                <table class="table table-hover mb-0 align-middle" id="tablaProductos" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td class="px-4 py-3"><code>#<?php echo htmlspecialchars($prod['id_producto']); ?></code></td>
                                    <td class="px-4 py-3"><strong><?php echo htmlspecialchars($prod['nombre_producto']); ?></strong></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($prod['tipo_de_producto']); ?></td>
                                    <td class="px-4 py-3"><?php $esActivo = ($prod['status_producto'] === 'activo'); ?><span class="badge bg-<?php echo $esActivo ? 'success' : 'danger'; ?>-subtle text-<?php echo $esActivo ? 'success' : 'danger'; ?> border border-<?php echo $esActivo ? 'success' : 'danger'; ?> px-2 py-1" style="border-radius:6px; font-size:11px; text-transform:capitalize;"><?php echo htmlspecialchars($prod['status_producto']); ?></span></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#modalEditarProducto" onclick='cargarDatosEditar(<?php echo json_encode($prod); ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light border text-<?php echo $esActivo ? 'danger' : 'success'; ?>" onclick="alternarEstadoProducto(<?php echo $prod['id_producto']; ?>, '<?php echo $prod['status_producto']; ?>')">
                                                <i class="bi bi-<?php echo $esActivo ? 'eye-slash-fill' : 'eye-fill'; ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalRegistrarProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam-plus text-success me-2"></i>Registrar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarProducto">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre del Producto</label>
                                <input type="text" class="form-control" name="nombre_producto" required placeholder="Ej: Camiseta Algodón">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de Producto</label>
                                <select class="form-select" name="tipo_de_producto" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Ropa">Ropa</option>
                                    <option value="Calzado">Calzado</option>
                                    <option value="Accesorios">Accesorios</option>
                                    <option value="Tela">Tela</option>
                                    <option value="Insumo">Insumo</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select" name="status_producto">
                                    <option value="activo" selected>Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
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

    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarProducto">
                    <input type="hidden" name="id_producto" id="edit_id_producto">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre del Producto</label>
                                <input type="text" class="form-control" name="nombre_producto" id="edit_nombre_producto" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de Producto</label>
                                <select class="form-select" name="tipo_de_producto" id="edit_tipo_de_producto" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Ropa">Ropa</option>
                                    <option value="Calzado">Calzado</option>
                                    <option value="Accesorios">Accesorios</option>
                                    <option value="Tela">Tela</option>
                                    <option value="Insumo">Insumo</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select" name="status_producto" id="edit_status_producto">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
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
    <script src="assets/js/productos.js"></script>
</body>

</html>
<?php
if (!isset($productos)) {
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Catálogo de Productos</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
    <link rel="stylesheet" href="assets/css/iconos.css">
</head>

<body>

    <?php include 'src/view/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Catálogo de Productos</h1>
                    <p>Administra los productos, características y variantes de tallas de la empresa.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <img src="assets/Img/Iconos/eye-slash.svg" id="iconoEstado" class="icono-svg icono-sm icono-gris me-1" alt="Icono">
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarProducto" onclick="limpiarFormularioCrear()">
                        <img src="assets/Img/Iconos/box-seam.svg" class="icono-svg icono-sm icono-blanco me-1" alt="Registrar">
                        <span>Registrar Producto</span>
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaProductos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE</th>
                                <th>TIPO</th>
                                <th>SUBTIPO / PRENDA</th>
                                <th>MATERIAL</th>
                                <th>COLOR</th>
                                <th>TALLAS / MEDIDAS</th>
                                <th>ESTADO</th>
                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($productos)): ?>
                                <?php foreach ($productos as $prod):
                                    $estadoReal = strtolower($prod['status_producto'] ?? 'activo');
                                    $tallasStr = !empty($prod['tallas_array']) ? implode(', ', $prod['tallas_array']) : '';
                                ?>
                                    <tr id="fila-<?php echo $prod['id_producto']; ?>" data-estado="<?php echo $estadoReal; ?>">
                                        <td class="fw-bold"><code>#<?php echo htmlspecialchars($prod['id_producto']); ?></code></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($prod['nombre_producto']); ?></td>
                                        <td><?php echo htmlspecialchars($prod['tipo_de_producto']); ?></td>
                                        <td><?php echo htmlspecialchars($prod['tipo_de_prenda'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($prod['detalle_material'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($prod['color'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (!empty($prod['tallas_array'])): ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($prod['tallas_array'] as $t): ?>
                                                        <span class="badge bg-light text-primary border px-2 py-1" style="text-transform: uppercase;">
                                                            <?php echo htmlspecialchars($t); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Sin tallas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $estadoReal === 'activo' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($estadoReal); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center d-flex justify-content-center gap-1">
                                                <?php if ($estadoReal === 'activo'): ?>
                                                    <button type="button" class="btn-accion-edit btnEditarActivo"
                                                        data-id="<?php echo $prod['id_producto']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($prod['nombre_producto']); ?>"
                                                        data-tipo="<?php echo htmlspecialchars($prod['tipo_de_producto']); ?>"
                                                        data-prenda="<?php echo htmlspecialchars($prod['tipo_de_prenda'] ?? ''); ?>"
                                                        data-material="<?php echo htmlspecialchars($prod['detalle_material'] ?? ''); ?>"
                                                        data-color="<?php echo htmlspecialchars($prod['color'] ?? ''); ?>"
                                                        data-tallas="<?php echo htmlspecialchars($tallasStr); ?>"
                                                        title="Editar Producto">
                                                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg" alt="Editar">
                                                    </button>
                                                    <button type="button" class="btn-accion-delete btnCambiarEstado"
                                                        data-id="<?php echo $prod['id_producto']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($prod['nombre_producto']); ?>"
                                                        title="Inactivar Producto">
                                                        <img src="assets/Img/Iconos/trash.svg" class="icono-svg" alt="Inactivar">
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn-accion-reactivar btnEditarInactivo"
                                                        data-id="<?php echo $prod['id_producto']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($prod['nombre_producto']); ?>">
                                                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-amarillo" alt="Editar">
                                                        <span>Editar / Reactivar</span>
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

    <!-- MODAL REGISTRAR PRODUCTO -->
    <div class="modal fade modal-idealo" id="modalRegistrarProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/box-seam.svg" class="icono-svg icono-md" alt="Registrar">
                        <span>Registrar Producto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarProducto" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="reg_nombre_producto" name="nombre_producto" placeholder="Ej. Camiseta Básica" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Producto</label>
                                <select class="form-select" id="reg_tipo_de_producto" name="tipo_de_producto" required>
                                    <option value="Ropa" selected>Ropa</option>
                                    <option value="Accesorio">Accesorio</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" id="reg_lbl_subtipo">Tipo de Prenda</label>
                                <input type="text" class="form-control" id="reg_tipo_de_prenda" name="tipo_de_prenda" placeholder="Ej. Camiseta, Pantalón" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Detalle del Material</label>
                                <input type="text" class="form-control" id="reg_detalle_material" name="detalle_material" placeholder="Ej. Algodón, Cuero" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" id="reg_color" name="color" placeholder="Ej. Negro, Azul, Blanco" required>
                            </div>
                            <!-- TALLAS MÚLTIPLES -->
                            <div class="col-md-6 mt-md-0 mt-3">
                                <div class="p-2 bg-light border rounded mt-2">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0 text-dark" id="reg_lbl_talla">Tallas disponibles (Ropa)</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-link p-0 me-2 text-decoration-none" onclick="seleccionarTodasTallas('reg')">Seleccionar todas</button>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-muted text-decoration-none" onclick="deseleccionarTodasTallas('reg')">Limpiar</button>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2" id="reg_contenedor_tallas">
                                        <!-- Opciones generadas según el tipo -->
                                    </div>
                                </div>
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

    <!-- MODAL EDITAR ACTIVO -->
    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-md icono-azul" alt="Editar">
                        <span>Editar Ficha de Producto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarActivo" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_activo_id_producto" name="id_producto">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="edit_activo_nombre_producto" name="nombre_producto" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Producto</label>
                                <select class="form-select" id="edit_activo_tipo_de_producto" name="tipo_de_producto" required>
                                    <option value="Ropa">Ropa</option>
                                    <option value="Accesorio">Accesorio</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" id="edit_lbl_subtipo">Tipo de Prenda</label>
                                <input type="text" class="form-control" id="edit_activo_tipo_de_prenda" name="tipo_de_prenda" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Detalle del Material</label>
                                <input type="text" class="form-control" id="edit_activo_detalle_material" name="detalle_material" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" id="edit_activo_color" name="color" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="edit_activo_status_producto" name="status_producto">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>

                            <!-- TALLAS MÚLTIPLES EDICIÓN -->
                            <div class="col-12 mt-2">
                                <div class="p-3 bg-light border rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0 text-dark" id="edit_lbl_talla">Tallas disponibles</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-link p-0 me-2 text-decoration-none" onclick="seleccionarTodasTallas('edit')">Seleccionar todas</button>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-muted text-decoration-none" onclick="deseleccionarTodasTallas('edit')">Limpiar</button>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2" id="edit_contenedor_tallas">
                                        <!-- Opciones generadas según el tipo -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarEdicionActivo">Guardar Cambios</button>
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
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-md" alt="Editar">
                        <span>Editar Registro Inhabilitado</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarInactivo">
                    <input type="hidden" id="edit_inactivo_id_producto">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre del Producto</label>
                                <input type="text" class="form-control bg-light" id="edit_inactivo_nombre" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado de Registro</label>
                                <select class="form-select border-danger" id="edit_inactivo_status">
                                    <option value="inactivo" selected>Inactivo (Inhabilitado)</option>
                                    <option value="activo">Activo (Reactivar Producto)</option>
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
    <script src="assets/js/productos.js"></script>

</body>

</html>
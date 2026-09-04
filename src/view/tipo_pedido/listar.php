<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Tipos de Pedido</title>

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
                    <h1 id="tituloVista">Tipo de Pedido</h1>
                    <p>Administra los tipos de pedido disponibles para tu negocio.</p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="button" 
                            id="btnAlternarEstado" 
                            class="btn btn-outline-secondary px-3 py-2" 
                            style="border-radius: var(--radius-md); font-weight: 600;" 
                            data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i>
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>

                    <button type="button" 
                            class="btn-idealo-success" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalRegistrarPedido">
                        <i class="bi bi-cart-plus-fill"></i> Registrar Tipo de Pedido
                    </button>
                </div>
            </header>

            <!-- Tabla Principial -->
            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaTipoPedido" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nombre del Tipo de Pedido</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($pedidos)): ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <?php
                                        $idPedido = $pedido['id_tipo_pedido'] ?? '';
                                        $nombrePedido = $pedido['nombre_tipo_pedido'] ?? '';
                                        $estadoPedido = $pedido['status_tipo_servicio'] ?? 'Inactivo';
                                    ?>
                                    <tr id="fila-<?php echo htmlspecialchars($idPedido, ENT_QUOTES, 'UTF-8'); ?>">
                                        <td class="fw-bold">
                                            <?php echo htmlspecialchars($nombrePedido, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $estadoPedido === 'Activo' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars($estadoPedido, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <?php if ($estadoPedido === 'Activo'): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary btnEditarActivo me-1" 
                                                            data-id="<?php echo htmlspecialchars($idPedido, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-nombre="<?php echo htmlspecialchars($nombrePedido, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            title="Editar Tipo de Pedido">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>

                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                                                            data-id="<?php echo htmlspecialchars($idPedido, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-nombre="<?php echo htmlspecialchars($nombrePedido, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-estado="Inactivo" 
                                                            title="Inhabilitar Tipo de Pedido">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                                                            data-id="<?php echo htmlspecialchars($idPedido, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-nombre="<?php echo htmlspecialchars($nombrePedido, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            title="Reactivar Tipo de Pedido">
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

    <!-- ================================================================
         MODAL PARA REGISTRAR
         ================================================================ -->
    <div class="modal fade modal-idealo" id="modalRegistrarPedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cart-plus-fill me-2"></i> Registrar Tipo de Pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formTipoPedido" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del Tipo de Pedido</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre_tipo_pedido" 
                                       name="nombre_tipo_pedido" 
                                       placeholder="Ej. Pedido personalizado" 
                                       maxlength="50" 
                                       required>
                                <div class="invalid-feedback">
                                    El nombre debe tener entre 3 y 50 caracteres.
                                </div>
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

    <!-- ================================================================
         MODAL PARA EDITAR ACTIVO
         ================================================================ -->
    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i> Editar Tipo de Pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditarActivo" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_activo_id" name="edit_activo_id">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del Tipo de Pedido</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="edit_activo_nombre" 
                                       name="edit_activo_nombre" 
                                       maxlength="50" 
                                       required>
                                <div class="invalid-feedback">
                                    El nombre debe tener entre 3 y 50 caracteres.
                                </div>
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

    <!-- ================================================================
         MODAL PARA REACTIVAR INACTIVO
         ================================================================ -->
    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Editar Registro Inhabilitado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditarPedido">
                    <input type="hidden" id="edit_id_pedido" name="edit_id_pedido">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre del Tipo de Pedido</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="edit_nombre_pedido" 
                                       name="edit_nombre_pedido" 
                                       readonly 
                                       disabled>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado del Registro</label>
                                <select class="form-select border-danger" id="edit_status_pedido" name="edit_status_pedido">
                                    <option value="Inactivo" selected>Inactivo (Archivado)</option>
                                    <option value="Activo">Activo (Reactivar Pedido)</option>
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

    <!-- Scripts -->
    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="assets/js/tipopedido.js"></script>

</body>

</html>
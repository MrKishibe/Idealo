<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Tipo Pedido (Sublimación)</title>
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
        <div class="view-container" style="padding: 2rem max(2vw, 20px);">
            <header class="page-header d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem;">Tipos de Pedido</h1>
                    <p class="text-muted mb-0">Gestión de tipos de servicios y pedidos de sublimación y estampado.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarTipoPedido" style="border-radius: 12px; font-weight: 600;">
                        <i class="bi bi-plus-circle me-1"></i> Registrar Tipo Pedido
                    </button>
                </div>
            </header>

            <div class="table-responsive shadow-sm" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; padding: 1.5rem;">
                <table class="table table-hover mb-0 align-middle" id="tablaTiposMapeados" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">ID Tipo Pedido</th>
                            <th class="px-4 py-3">Nombre Tipo Pedido</th>
                            <th class="px-4 py-3">Estado Tipo Servicio</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tiposPedido) && is_array($tiposPedido)): ?>
                            <?php foreach ($tiposPedido as $tipo): ?>
                                <tr>
                                    <td class="px-4 py-3 fw-bold text-secondary">
                                        #<?php echo htmlspecialchars($tipo['id_tipo_pedido']); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($tipo['nombre_tipo_pedido']); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (strtolower($tipo['status_tipo_servicio']) === 'activo'): ?>
                                            <span class="badge bg-success-light text-success px-3 py-2" style="border-radius: 8px;">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-light text-danger px-3 py-2" style="border-radius: 8px;">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-sm btn-light border btnEditarTipo" data-id="<?php echo (int)$tipo['id_tipo_pedido']; ?>">
                                                <i class="bi bi-pencil-square text-primary"></i>
                                            </button>
                                            <a href="index.php?controller=tipoPedido&action=eliminar&id=<?php echo $tipo['id_tipo_pedido']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('¿Deseas inactivar este tipo de pedido?')">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
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

    <div class="modal fade" id="modalRegistrarTipoPedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-plus-circle text-success me-2"></i>Registrar Tipo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?controller=tipoPedido&action=guardar" method="POST">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Nombre Tipo Pedido</label>
                                <input type="text" class="form-control" name="nombre_tipo_pedido" placeholder="Ej. Sublimación" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado Tipo Servicio</label>
                                <select class="form-select" name="status_tipo_servicio" style="border-radius: 10px;">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
                        <button type="submit" class="btn btn-success" style="border-radius: 10px; font-weight:600;">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarTipoPedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Tipo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?controller=tipoPedido&action=editar" method="POST">
                    <input type="hidden" name="id_tipo_pedido" id="edit_id_tipo_pedido">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Nombre Tipo Pedido</label>
                                <input type="text" class="form-control" name="nombre_tipo_pedido" id="edit_nombre_tipo_pedido" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado Tipo Servicio</label>
                                <select class="form-select" name="status_tipo_servicio" id="edit_status_tipo_servicio" style="border-radius: 10px;">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="border-radius: 10px; font-weight:600;">Guardar Cambios</button>
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

    <script>
        // Inyección limpia de los datos simulados de PHP para evitar bloqueos en Mozilla
        const datosTiposPedido = <?php echo json_encode(array_column($tiposPedido ?? [], null, 'id_tipo_pedido'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        // Evento para levantar el modal de edición cargando los objetos mapeados
        $(document).on('click', '.btnEditarTipo', function() {
            const id = $(this).data('id');
            const tipo = datosTiposPedido[id];

            if (tipo) {
                document.getElementById('edit_id_tipo_pedido').value = tipo.id_tipo_pedido;
                document.getElementById('edit_nombre_tipo_pedido').value = tipo.nombre_tipo_pedido || '';

                let status = tipo.status_tipo_servicio || 'Activo';
                status = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
                document.getElementById('edit_status_tipo_servicio').value = status;

                const modal = new bootstrap.Modal(document.getElementById('modalEditarTipoPedido'));
                modal.show();
            }
        });

        // Inicialización de DataTables
        $(document).ready(function() {
            localStorage.clear();
            sessionStorage.clear();

            $('#tablaTiposMapeados').DataTable({
                "destroy": true,
                "bStateSave": false,
                "language": {
                    "emptyTable": "No hay tipos de pedido registrados.",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ tipos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 tipos",
                    "infoFiltered": "(filtrado de un total de _MAX_)",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron coincidencias",
                    "paginate": {
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "pageLength": 10,
                "order": [
                    [0, 'asc']
                ]
            });
        });
    </script>
</body>

</html>
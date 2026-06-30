<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Usuarios</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
    <style>
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #475569 !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            margin-left: 10px;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            margin: 0 10px;
        }

        .table thead {
            background: #f8fafc;
        }

        .dt-pagination .page-item.active .page-link {
            background-color: #10b981;
            border-color: #10b981;
        }
    </style>
</head>

<body>

    <?php
    // Datos hardcodeados asignados directamente para renderizar la interfaz de forma independiente
    $usuarios = [
        [
            'id_usuario'     => 1,
            'cedula_usuario' => '24555123',
            'id_rol'         => 1, // Administrador
            'status_usuario' => 'activo'
        ],
        [
            'id_usuario'     => 2,
            'cedula_usuario' => '26111890',
            'id_rol'         => 2, // Supervisor
            'status_usuario' => 'activo'
        ],
        [
            'id_usuario'     => 3,
            'cedula_usuario' => '30444555',
            'id_rol'         => 3, // Operador
            'status_usuario' => 'activo'
        ],
        [
            'id_usuario'     => 4,
            'cedula_usuario' => '19222333',
            'id_rol'         => 3, // Operador
            'status_usuario' => 'inactivo'
        ],
        [
            'id_usuario'     => 5,
            'cedula_usuario' => '32646181',
            'id_rol'         => 2, // Supervisor
            'status_usuario' => 'activo'
        ]
    ];
    ?>

    <?php include 'src/view/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container" style="padding: 2rem max(2vw, 20px);">
            <header class="page-header d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem;">Gestión de Usuarios</h1>
                    <p class="text-muted mb-0">Administra las credenciales de acceso y roles del sistema.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarUsuario" style="border-radius: 12px; font-weight: 600;">
                        <i class="bi bi-person-plus-fill me-1"></i> Registrar Usuario
                    </button>
                </div>
            </header>

            <div class="table-responsive shadow-sm" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden;">
                <table class="table table-hover mb-0 align-middle" id="tablaUsuarios">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Cédula</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold">#<?php echo htmlspecialchars($user['id_usuario']); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($user['cedula_usuario']); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>
                                            <?php
                                            if ($user['id_rol'] == 1) echo "Administrador";
                                            else if ($user['id_rol'] == 2) echo "Supervisor";
                                            else echo "Operador";
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($user['status_usuario'] === 'activo'): ?>
                                            <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill font-semibold small">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 rounded-pill font-semibold small">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario" onclick='cargarDatosEditar(<?php echo json_encode($user); ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="index.php?controller=usuario&action=eliminar&id=<?php echo $user['id_usuario']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('¿Deseas inactivar este usuario?')">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (empty($usuarios)): ?>
                    <div class="text-center py-5 text-muted" style="padding: 3rem;">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="mb-0 mt-3">No hay usuarios registrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalRegistrarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-plus-fill text-success me-2"></i>Registrar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?controller=usuario&action=guardar" method="POST">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cédula del Usuario</label>
                                <input type="text" class="form-control" name="cedula_usuario" required placeholder="Ej: 32646181">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Asignar Rol</label>
                                <select class="form-select" name="id_rol" required>
                                    <option value="">Seleccione...</option>
                                    <option value="1">Administrador</option>
                                    <option value="2">Supervisor</option>
                                    <option value="3">Operador</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select" name="status_usuario">
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

    <div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?controller=usuario&action=editar" method="POST">
                    <input type="hidden" name="id_usuario" id="edit_id_usuario">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cédula del Usuario</label>
                                <input type="text" class="form-control" name="cedula_usuario" id="edit_cedula_usuario" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cambiar Rol</label>
                                <select class="form-select" name="id_rol" id="edit_id_rol" required>
                                    <option value="">Seleccione...</option>
                                    <option value="1">Administrador</option>
                                    <option value="2">Supervisor</option>
                                    <option value="3">Operador</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select" name="status_usuario" id="edit_status_usuario">
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
    <script>
        function cargarDatosEditar(user) {
            document.getElementById('edit_id_usuario').value = user.id_usuario || '';
            document.getElementById('edit_cedula_usuario').value = user.cedula_usuario || '';
            document.getElementById('edit_id_rol').value = user.id_rol || '';
            document.getElementById('edit_status_usuario').value = user.status_usuario || 'activo';
        }

        $(document).ready(function() {
            $('#tablaUsuarios').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "Todos"]
                ],
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                order: [
                    [0, 'asc']
                ],
                destroy: true
            });
        });
    </script>

</body>

</html>
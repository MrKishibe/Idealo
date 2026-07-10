<?php
if (!isset($usuarios)) {
    $usuarios = [
        [
            'id_usuario'     => 1,
            'cedula_usuario' => '31.973.792',
            'rol'            => 'Administrador',
            'id_rol'         => 1,
            'status_usuario' => 'activo'
        ],
        [
            'id_usuario'     => 2,
            'cedula_usuario' => '30.233.554',
            'rol'            => 'Administrador',
            'id_rol'         => 1,
            'status_usuario' => 'activo'
        ],
        [
            'id_usuario'     => 3,
            'cedula_usuario' => '30.601.666',
            'rol'            => 'Supervisor',
            'id_rol'         => 2,
            'status_usuario' => 'activo'
        ],
        [
            'id_usuario'     => 4,
            'cedula_usuario' => '30.591.032',
            'rol'            => 'Operador',
            'id_rol'         => 3,
            'status_usuario' => 'inactivo'
        ],
        [
            'id_usuario'     => 5,
            'cedula_usuario' => '30.529.022',
            'rol'            => 'Administrador',
            'id_rol'         => 1,
            'status_usuario' => 'activo'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Usuarios</title>
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
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Gestión de Usuarios</h1>
                    <p>Administra las credenciales de acceso y roles del sistema.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarUsuario">
                        <i class="bi bi-person-plus-fill"></i> Registrar Usuario
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaUsuarios" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Rol / Nivel de Acceso</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($usuarios)): ?>
                                <?php foreach ($usuarios as $user):
                                    $estadoReal = strtolower($user['status_usuario'] ?? 'activo');
                                ?>
                                    <tr id="fila-<?php echo $user['id_usuario']; ?>">
                                        <td class="fw-bold"><?php echo htmlspecialchars($user['cedula_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($user['rol']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $estadoReal === 'activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo ucfirst($estadoReal); ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center d-flex justify-content-center gap-1">
                                                <?php if ($estadoReal === 'activo'): ?>
                                                    <button class="btn btn-sm btn-outline-primary btnEditarActivo"
                                                        data-id="<?php echo $user['id_usuario']; ?>"
                                                        data-cedula="<?php echo htmlspecialchars($user['cedula_usuario']); ?>"
                                                        data-rol="<?php echo htmlspecialchars($user['id_rol']); ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger btnCambiarEstado"
                                                        data-id="<?php echo $user['id_usuario']; ?>"
                                                        data-cedula="<?php echo htmlspecialchars($user['cedula_usuario']); ?>">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-warning btnEditarInactivo"
                                                        data-id="<?php echo $user['id_usuario']; ?>"
                                                        data-cedula="<?php echo htmlspecialchars($user['cedula_usuario']); ?>">
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

</body>

</html>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Empleados</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>

<body>

<?php include 'src/view/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-container" style="padding: 2rem max(2vw, 20px);">

            <header class="page-header d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem; letter-spacing: -0.5px;">Gestión de Empleados</h1>
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">Administra las cuentas, roles operativos y acceso de tu personal de taller.</p>
                </div>
                <div>
                    <button type="button" class="btn-idealo-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarEmpleado" style="border-radius: 12px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-person-plus-fill" style="font-size: 16px;"></i> Registrar Nuevo Empleado
                    </button>
                </div>
            </header>

            <div class="table-space shadow-sm" style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="custom-table table mb-0" style="vertical-align: middle;">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <tr>
                                <th class="px-4 py-3 text-secondary" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Cédula</th>
                                <th class="px-4 py-3 text-secondary" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Nombre Completo</th>
                                <th class="px-4 py-3 text-secondary" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Contacto</th>
                                <th class="px-4 py-3 text-secondary" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Cargo</th>
                                <th class="px-4 py-3 text-secondary" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Salario Base</th>
                                <th class="px-4 py-3 text-secondary" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Nivel de Acceso</th>
                                <th class="px-4 py-3 text-secondary text-center" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($empleados)): ?>
                                <?php foreach ($empleados as $emp): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                                        <td class="px-4 py-3">
                                            <span class="text-dark fw-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($emp['cedula_usuario']); ?></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="fw-bold text-dark" style="font-size: 14px;"><?php echo htmlspecialchars(($emp['nombres'] ?? 'Sin vincular') . ' ' . ($emp['apellidos'] ?? '')); ?></div>
                                            <small class="text-muted d-block" style="font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($emp['direccion'] ?? 'Sin dirección'); ?>">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($emp['direccion'] ?? 'Sin dirección'); ?>
                                            </small>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-dark" style="font-size: 14px; font-weight: 500;">
                                                <i class="bi bi-telephone text-secondary me-1"></i><?php echo htmlspecialchars($emp['telefono'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-secondary" style="font-size: 14px; font-weight: 500;">
                                            <?php echo htmlspecialchars($emp['cargo'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 13px; font-weight: 700; border-radius: 6px;">
                                                $<?php echo number_format($emp['salario'] ?? 0.00, 2); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="badge bg-light text-dark border px-2 py-1" style="font-weight: 600; font-size: 12px; border-radius: 6px;">
                                                <?php echo ($emp['id_rol'] == 1) ? 'Administrador' : 'Diseñador'; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button class="btn btn-sm btn-light border" style="border-radius: 8px; padding: 5px 8px; color: #475569;" data-bs-toggle="modal" data-bs-target="#modalEditarEmpleado" onclick='cargarDatosEditar(<?php echo json_encode($emp); ?>)'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="index.php?controller=usuario&action=eliminar&id=<?php echo $emp['id_usuario']; ?>" class="btn btn-sm btn-light border text-danger" style="border-radius: 8px; padding: 5px 8px;" onclick="return confirm('¿Estás seguro de dar de baja a este colaborador?')">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted" style="font-size: 14px; font-weight: 500;">
                                        <i class="bi bi-people mb-2 d-block" style="font-size: 24px;"></i> No hay colaboradores activos registrados.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalRegistrarEmpleado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: var(--shadow-lg);">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-plus text-success me-2"></i>Registrar Nuevo Empleado</h5>
                    <button type="button" class="btn-close" data-bs-redirect="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?controller=usuario&action=guardar" method="POST">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Nombres</label>
                                <input type="text" class="form-control" name="nombres" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Apellidos</label>
                                <input type="text" class="form-control" name="apellidos" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Cédula de Identidad</label>
                                <input type="text" class="form-control" name="cedula_usuario" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" style="border-radius: 10px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Contraseña para el Sistema</label>
                                <input type="password" class="form-control" name="contrasena" required style="border-radius: 10px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Dirección de Habitación</label>
                                <textarea class="form-control" name="direccion" rows="2" style="border-radius: 10px;"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Cargo Operativo</label>
                                <input type="text" class="form-control" name="cargo" placeholder="Ej: Sublimador" style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Salario Mensual ($)</label>
                                <input type="number" step="0.01" class="form-control" name="salario" value="0.00" style="border-radius: 10px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Rol de Permisos</label>
                                <select class="form-select" name="id_rol" style="border-radius: 10px;">
                                    <option value="2">Diseñador / Sublimador (Estándar)</option>
                                    <option value="1">Administrador (Acceso Completo)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light px-4 py-2" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancelar</button>
                        <button type="submit" class="btn btn-success px-4 py-2" style="border-radius: 10px; font-weight: 600;">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: var(--shadow-lg);">
                <div class="modal-header px-4 pt-4 pb-2 border-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Modificar Información del Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?controller=usuario&action=editar" method="POST">
                    <input type="hidden" name="id_usuario" id="edit_id_usuario">
                    <div class="modal-body px-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Nombres</label>
                                <input type="text" class="form-control" name="nombres" id="edit_nombres" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Apellidos</label>
                                <input type="text" class="form-control" name="apellidos" id="edit_apellidos" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Cédula de Identidad</label>
                                <input type="text" class="form-control" name="cedula_usuario" id="edit_cedula" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="edit_telefono" style="border-radius: 10px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Dirección de Habitación</label>
                                <textarea class="form-control" name="direccion" id="edit_direccion" rows="2" style="border-radius: 10px;"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Cargo Operativo</label>
                                <input type="text" class="form-control" name="cargo" id="edit_cargo" style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Salario Mensual ($)</label>
                                <input type="number" step="0.01" class="form-control" name="salario" id="edit_salario" style="border-radius: 10px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary fw-semibold mb-1" style="font-size: 13px;">Rol de Permisos</label>
                                <select class="form-select" name="id_rol" id="edit_id_rol" style="border-radius: 10px;">
                                    <option value="2">Diseñador / Sublimador (Estándar)</option>
                                    <option value="1">Administrador (Acceso Completo)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 pt-3 border-0">
                        <button type="button" class="btn btn-light px-4 py-2" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 600;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
 

        function cargarDatosEditar(emp) {
            document.getElementById('edit_id_usuario').value = emp.id_usuario;
            document.getElementById('edit_nombres').value = emp.nombres || '';
            document.getElementById('edit_apellidos').value = emp.apellidos || '';
            document.getElementById('edit_cedula').value = emp.cedula_usuario;
            document.getElementById('edit_telefono').value = emp.telefono || '';
            document.getElementById('edit_direccion').value = emp.direccion || '';
            document.getElementById('edit_cargo').value = emp.cargo || '';
            document.getElementById('edit_salario').value = emp.salario || '0.00';
            document.getElementById('edit_id_rol').value = emp.id_rol;
        }
    </script>
</body>

</html>
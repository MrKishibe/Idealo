<?php
if (!isset($empleados)) {
    $empleados = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Empleados</title>
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
                    <h1 id="tituloVista">Gestión de Empleados</h1>
                    <p>Administra los cargos, salarios e información del personal de la empresa.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarEmpleado">
                        <i class="bi bi-person-plus-fill"></i> Registrar Empleado
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaEmpleados" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre Completo / Dirección</th>
                                <th>Teléfono</th>
                                <th>Cargo</th>
                                <th>Salario</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($empleados)): ?>
                                <?php foreach ($empleados as $emp):
                                    $nombreCompleto = $emp['nombres'] . ' ' . $emp['apellidos'];
                                    $estadoReal = strtolower($emp['status_empleado'] ?? 'activo');
                                ?>
                                    <tr id="fila-<?php echo $emp['id_empleado']; ?>">
                                        <td class="fw-bold"><?php echo htmlspecialchars($emp['cedula']); ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($emp['direccion'] ?: 'Sin dirección'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($emp['telefono'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                                        <td class="text-success fw-bold">$<?php echo number_format((float)$emp['salario'], 2, '.', ''); ?></td>
                                        <td>
                                            <span class="badge <?php echo $estadoReal === 'activo' ? 'bg-success' : 'bg-danger'; ?>"><?php echo ucfirst($estadoReal); ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center d-flex justify-content-center gap-1">
                                                <?php if ($estadoReal === 'activo'): ?>
                                                    <button class="btn btn-sm btn-outline-primary btnEditarActivo"
                                                        data-id="<?php echo $emp['id_empleado']; ?>"
                                                        data-cedula="<?php echo htmlspecialchars($emp['cedula']); ?>"
                                                        data-nombres="<?php echo htmlspecialchars($emp['nombres']); ?>"
                                                        data-apellidos="<?php echo htmlspecialchars($emp['apellidos']); ?>"
                                                        data-telefono="<?php echo htmlspecialchars($emp['telefono'] ?? ''); ?>"
                                                        data-direccion="<?php echo htmlspecialchars($emp['direccion'] ?? ''); ?>"
                                                        data-cargo="<?php echo htmlspecialchars($emp['cargo']); ?>"
                                                        data-salario="<?php echo htmlspecialchars($emp['salario']); ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger btnCambiarEstado"
                                                        data-id="<?php echo $emp['id_empleado']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($nombreCompleto); ?>">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-warning btnEditarInactivo"
                                                        data-id="<?php echo $emp['id_empleado']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($nombreCompleto); ?>">
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

    <!-- Modal Registrar Empleado -->
    <div class="modal fade modal-idealo" id="modalRegistrarEmpleado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Registrar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEmpleado" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cédula</label>
                                <input type="text" class="form-control" id="reg_cedula" placeholder="Ej. V-12345678" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <select class="form-select" id="reg_cargo" required>
                                    <option value="" disabled selected>Seleccione un cargo...</option>
                                    <option value="Costurero">Costurero</option>
                                    <option value="Contador">Contador</option>
                                    <option value="Cortador">Cortador</option>
                                    <option value="Panchero">Panchero</option>
                                    <option value="Recepcionista">Recepcionista</option>
                                    <option value="Editor">Editor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="reg_nombres" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="reg_apellidos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="reg_telefono" placeholder="Ej. 04125555555">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salario Base ($)</label>
                                <input type="number" step="0.01" class="form-control" id="reg_salario" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" id="reg_direccion" rows="2"></textarea>
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

    <!-- Modal Editar Activo -->
    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Ficha de Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarActivo" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_activo_id_empleado">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cédula (No editable)</label>
                                <input type="text" class="form-control bg-light" id="edit_activo_cedula" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <select class="form-select" id="edit_activo_cargo" required>
                                    <option value="Costurero">Costurero</option>
                                    <option value="Contador">Contador</option>
                                    <option value="Cortador">Cortador</option>
                                    <option value="Panchero">Panchero</option>
                                    <option value="Recepcionista">Recepcionista</option>
                                    <option value="Editor">Editor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="edit_activo_nombres" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="edit_activo_apellidos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="edit_activo_telefono">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salario Base ($)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_activo_salario" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" id="edit_activo_direccion" rows="2"></textarea>
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

    <!-- Modal Editar Inactivo -->
    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Registro Inhabilitado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarInactivo">
                    <input type="hidden" id="edit_inactivo_id_empleado">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre del Empleado</label>
                                <input type="text" class="form-control bg-light" id="edit_inactivo_nombre_completo" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado de Registro</label>
                                <select class="form-select border-danger" id="edit_inactivo_status">
                                    <option value="inactivo" selected>Inactivo (Inhabilitado)</option>
                                    <option value="activo">Activo (Reactivar Empleado)</option>
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
    <script src="assets/js/empleado.js"></script>

</body>

</html>
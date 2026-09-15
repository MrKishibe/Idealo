<?php
if (!isset($usuarios)) {
    $usuarios = [];
}
if (!isset($roles)) {
    $roles = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Usuarios</title>
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
                    <h1 id="tituloVista">Gestión de Usuarios</h1>
                    <p>Administra las credenciales de acceso, contraseñas y roles del sistema.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <img src="assets/Img/Iconos/eye-slash.svg" id="iconoEstado" class="icono-svg icono-sm icono-gris me-1" alt="Icono">
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarUsuario" onclick="limpiarFormularioCrear()">
                        <img src="assets/Img/Iconos/person-plus-fill.svg" class="icono-svg icono-sm icono-blanco me-1" alt="Registrar">
                        <span>Registrar Usuario</span>
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaUsuarios" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE DE USUARIO</th>
                                <th>ROL</th>
                                <th>ESTADO</th>
                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($usuarios)): ?>
                                <?php foreach ($usuarios as $user):
                                    $estadoReal = strtolower($user['status_usuario'] ?? 'activo');
                                ?>
                                    <tr id="fila-<?php echo $user['id_usuario']; ?>" data-estado="<?php echo $estadoReal; ?>">
                                        <td class="fw-bold"><code>#<?php echo htmlspecialchars($user['id_usuario']); ?></code></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($user['nombre_usuario']); ?></td>
                                        <td>
                                            <span class="badge bg-light text-primary border px-2 py-1" style="font-weight: 600; font-size: 12px; border-radius: 6px;">
                                                <img src="assets/Img/Iconos/shield-lock.svg" class="icono-svg icono-gris me-1"><?php echo htmlspecialchars(ucfirst($user['tipo_de_usuario'] ?? 'Sin rol')); ?>
                                            </span>
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
                                                        data-id="<?php echo $user['id_usuario']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($user['nombre_usuario']); ?>"
                                                        data-rol="<?php echo $user['id_rol']; ?>"
                                                        title="Editar Usuario">
                                                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg" alt="Editar">
                                                    </button>
                                                    <button type="button" class="btn-accion-delete btnCambiarEstado"
                                                        data-id="<?php echo $user['id_usuario']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($user['nombre_usuario']); ?>"
                                                        title="Inactivar Usuario">
                                                        <img src="assets/Img/Iconos/trash.svg" class="icono-svg" alt="Inactivar">
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn-accion-reactivar btnEditarInactivo"
                                                        data-id="<?php echo $user['id_usuario']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($user['nombre_usuario']); ?>">
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

    <!-- MODAL REGISTRAR USUARIO -->
    <div class="modal fade modal-idealo" id="modalRegistrarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/person-plus-fill.svg" class="icono-svg icono-md" alt="Registrar">
                        <span>Registrar Usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarUsuario" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="reg_nombre_usuario" name="nombre_usuario" maxlength="20" placeholder="Ej. jperez" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="reg_contrasena" name="contrasena" minlength="6" placeholder="Mínimo 6 caracteres" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="reg_confirmar_contrasena" minlength="6" placeholder="Repite la contraseña" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Rol de Permisos</label>
                                <select class="form-select" id="reg_id_rol" name="id_rol" required>
                                    <option value="">Seleccione un rol</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars(ucfirst($rol['tipo_de_usuario'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-md icono-azul" alt="Editar">
                        <span>Editar Usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarActivo" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_activo_id_usuario" name="id_usuario">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="edit_activo_nombre_usuario" name="nombre_usuario" maxlength="20" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nueva Contraseña <small class="text-muted">(opcional)</small></label>
                                <input type="password" class="form-control" id="edit_activo_contrasena" name="contrasena" minlength="6" placeholder="Dejar vacío para mantener la actual">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="edit_activo_confirmar_contrasena" minlength="6" placeholder="Repite la nueva contraseña">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Rol de Permisos</label>
                                <select class="form-select" id="edit_activo_id_rol" name="id_rol" required>
                                    <option value="">Seleccione un rol</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars(ucfirst($rol['tipo_de_usuario'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                    <input type="hidden" id="edit_inactivo_id_usuario">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre de Usuario</label>
                                <input type="text" class="form-control bg-light" id="edit_inactivo_nombre_usuario" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado de Registro</label>
                                <select class="form-select border-danger" id="edit_inactivo_status">
                                    <option value="inactivo" selected>Inactivo (Inhabilitado)</option>
                                    <option value="activo">Activo (Reactivar Usuario)</option>
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
    <script src="assets/js/usuario.js"></script>

</body>

</html>
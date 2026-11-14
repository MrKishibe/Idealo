<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Usuarios</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
    <link rel="stylesheet" href="assets/libs/css/iconos.css">
    <link rel="stylesheet" href="assets/libs/css/recorte-foto.css?v=<?= filemtime(__DIR__ . '/../../../assets/libs/css/recorte-foto.css') ?>">
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
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary" data-vista="activos">
                        <i class="bi bi-eye-slash-fill" id="iconoEstado"></i>
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarUsuario">
                        <i class="bi bi-person-plus-fill"></i>
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
                                <th>CORREO</th>
                                <th>ROL</th>
                                <th>ESTADO</th>
                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL REGISTRAR USUARIO -->
    <div class="modal fade modal-idealo" id="modalRegistrarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Registrar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarUsuario" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="reg_nombre_usuario" name="nombre_usuario" maxlength="20" placeholder="Ej. jperez" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="reg_correo" name="correo" placeholder="ejemplo@correo.com" required>
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
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="foto-empleado-bloque">
                                    <button type="button" class="foto-empleado-preview foto-empleado-preview-lg" id="reg_foto_ver"
                                        data-preview="reg_foto_preview" title="Ver foto del usuario"
                                        aria-label="Ver foto del usuario" disabled>
                                        <img id="reg_foto_preview" src="assets/Img/Iconos/person-fill.svg" class="es-icono" alt="Vista previa de la foto">
                                    </button>
                                    <div class="flex-grow-1">
                                        <div class="form-label fw-semibold mb-1">Foto de perfil <span class="text-muted fw-normal">(opcional)</span></div>
                                        <p class="text-muted small mb-2">JPG, PNG o WEBP · Máx. 2 MB. Podrás ajustar el encuadre antes de guardarla.</p>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-subir-foto-usuario" data-prefijo="reg">
                                                <i class="bi bi-camera-fill me-1"></i> Subir foto
                                            </button>
                                            <input type="file" id="reg_foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:#f8f9fa; border:1px solid #dee2e6;">
                                    <i class="bi bi-link-45deg flex-shrink-0" style="font-size:20px;"></i>
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold mb-0" for="reg_es_empleado">Este usuario es un empleado</label>
                                        <small class="text-muted d-block">Márcalo si la persona pertenece a la planilla de empleados.</small>
                                    </div>
                                    <div class="form-check form-switch ps-0 mb-0">
                                        <input class="form-check-input" type="checkbox" id="reg_es_empleado" name="es_empleado" value="1" role="switch">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="reg_empleado_wrap">
                                <label class="form-label">Empleado Asociado</label>
                                <select class="form-select" id="reg_id_empleado" name="id_empleado">
                                    <option value="">Seleccione el empleado...</option>
                                </select>
                                <small class="text-muted">Solo se muestran empleados sin usuario asociado.</small>
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

    <!-- MODAL EDITAR USUARIO -->
    <div class="modal fade modal-idealo" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formEditarActivo" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="edit_activo_id_usuario" name="id_usuario">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="edit_activo_nombre_usuario" name="nombre_usuario" maxlength="20" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="edit_activo_correo" name="correo" required>
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
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="foto-empleado-bloque">
                                    <button type="button" class="foto-empleado-preview foto-empleado-preview-lg" id="edit_activo_foto_ver"
                                        data-preview="edit_activo_foto_preview" title="Ver foto del usuario"
                                        aria-label="Ver foto del usuario" disabled>
                                        <img id="edit_activo_foto_preview" src="assets/Img/Iconos/person-fill.svg" class="es-icono" alt="Vista previa de la foto">
                                    </button>
                                    <div class="flex-grow-1">
                                        <div class="form-label fw-semibold mb-1">Foto de perfil <span class="text-muted fw-normal">(opcional)</span></div>
                                        <p class="text-muted small mb-2">JPG, PNG o WEBP · Máx. 2 MB. Podrás ajustar el encuadre antes de guardarla.</p>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-subir-foto-usuario" data-prefijo="edit_activo">
                                                <i class="bi bi-camera-fill me-1"></i> Subir foto
                                            </button>
                                            <input type="file" id="edit_activo_foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:#f8f9fa; border:1px solid #dee2e6;">
                                    <i class="bi bi-link-45deg flex-shrink-0" style="font-size:20px;"></i>
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold mb-0" for="edit_es_empleado">Este usuario es un empleado</label>
                                        <small class="text-muted d-block">Márcalo si la persona pertenece a la planilla de empleados.</small>
                                    </div>
                                    <div class="form-check form-switch ps-0 mb-0">
                                        <input class="form-check-input" type="checkbox" id="edit_es_empleado" name="es_empleado" value="1" role="switch">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="edit_empleado_wrap">
                                <label class="form-label">Empleado Asociado</label>
                                <select class="form-select" id="edit_id_empleado" name="id_empleado">
                                    <option value="">Seleccione el empleado...</option>
                                </select>
                                <small class="text-muted">Solo se muestran empleados sin usuario asociado.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnGuardarEdicionActivo">Guardar Cambios</button>
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
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Reactivar Usuario</h5>
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
                        <button type="button" class="btn-idealo-success" id="btnGuardarEdicionInactivo">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalRecortarFotoUsuario" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-camera-fill"></i>
                        <span>Ajustar Foto del Usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="recorte-layout">
                        <div class="recorte-viewport" id="recorteUsuarioViewport" tabindex="0"
                            role="application" aria-label="Área de recorte. Arrastra o usa las flechas para centrar la imagen.">
                            <img id="recorteUsuarioImagen" alt="Vista de recorte" draggable="false">
                        </div>
                        <div class="recorte-preview-wrap">
                            <span>Vista previa</span>
                            <canvas id="recorteUsuarioPreview" width="128" height="128"
                                role="img" aria-label="Vista previa de la foto recortada"></canvas>
                        </div>
                    </div>
                    <div class="recorte-controles">
                        <label for="recorteUsuarioZoom" class="form-label">Zoom</label>
                        <input type="range" class="form-range" id="recorteUsuarioZoom" min="1" max="3" step="0.01" value="1" disabled>
                        <div class="form-text">Arrastra la imagen para centrarla y ajusta el zoom a tu gusto.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-idealo-success" id="btnConfirmarRecorteUsuario" disabled>Usar Foto</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalVerFotoUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-camera-fill"></i>
                        <span id="verFotoUsuarioTitulo">Foto del Usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center ver-foto-empleado-cuerpo">
                    <img id="verFotoUsuarioImagen" src="" class="ver-foto-empleado" alt="Foto del usuario">
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/jquery.dataTables.min.js"></script>
    <script src="assets/libs/dataTables.bootstrap5.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script type="importmap">
        {"imports":{"jquery":"./assets/js/helpers/jquery-bridge.js","sweetalert2":"./assets/js/helpers/sweetalert2-bridge.js"}}
    </script>
    <script type="module" src="assets/js/usuario.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/usuario.js') ?>"></script>

</body>

</html>
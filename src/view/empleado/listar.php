<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Empleados</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css?v=<?= filemtime(__DIR__ . '/../../../assets/libs/css/estilo.css') ?>">
    <link rel="stylesheet" href="assets/libs/css/iconos.css">
    <link rel="stylesheet" href="assets/libs/css/recorte-foto.css?v=<?= filemtime(__DIR__ . '/../../../assets/libs/css/recorte-foto.css') ?>">
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
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary" data-vista="activos">
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
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

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
                                <input type="text" class="form-control" id="reg_cedula" name="cedula" placeholder="Ej. V-12345678" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <select class="form-select" id="reg_cargo" name="cargo" required>
                                    <option value="" disabled selected>Seleccione un cargo...</option>
                                    <option value="Costurero">Costurero</option>
                                    <option value="Contador">Contador</option>
                                    <option value="Cortador">Cortador</option>
                                    <option value="Plachero">Plachero</option>
                                    <option value="Recepcionista">Recepcionista</option>
                                    <option value="Editor">Editor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="reg_nombres" name="nombres" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="reg_apellidos" name="apellidos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="reg_telefono" name="telefono" placeholder="Ej. 04125555555">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salario Base ($)</label>
                                <input type="number" step="0.01" class="form-control" id="reg_salario" name="salario" required>
                            </div>
                            <div class="col-12">
                                <div class="foto-empleado-bloque">
                                    <button type="button" class="foto-empleado-preview foto-empleado-preview-lg" id="reg_foto_ver"
                                        data-preview="reg_foto_preview" title="Ver foto del empleado"
                                        aria-label="Ver foto del empleado" disabled>
                                        <img id="reg_foto_preview" src="assets/Img/Iconos/person-fill.svg" class="es-icono" alt="Vista previa de la foto">
                                    </button>
                                    <div class="flex-grow-1">
                                        <div class="form-label fw-semibold mb-1">Foto de perfil <span class="text-muted fw-normal">(opcional)</span></div>
                                        <p class="text-muted small mb-2">JPG, PNG o WEBP · Máx. 2 MB. Podrás ajustar el encuadre antes de guardarla.</p>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-subir-foto-empleado" data-prefijo="reg">
                                                <i class="bi bi-camera-fill me-1"></i> Subir foto
                                            </button>
                                            <input type="file" id="reg_foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" id="reg_direccion" name="direccion" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:#f8f9fa; border:1px solid #dee2e6;">
                                    <i class="bi bi-link-45deg flex-shrink-0" style="font-size:20px;"></i>
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold mb-0" for="reg_es_usuario">Este empleado tiene un usuario</label>
                                        <small class="text-muted d-block">Márcalo si la persona entra al sistema con sus credenciales.</small>
                                    </div>
                                    <div class="form-check form-switch ps-0 mb-0">
                                        <input class="form-check-input" type="checkbox" id="reg_es_usuario" name="es_usuario" value="1" role="switch">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="reg_usuario_wrap">
                                <label class="form-label">Usuario Asociado</label>
                                <select class="form-select" id="reg_id_usuario" name="id_usuario">
                                    <option value="">Seleccione el usuario...</option>
                                </select>
                                <small class="text-muted">Solo se muestran usuarios sin empleado asociado.</small>
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
                                    <option value="Plachero">Plachero</option>
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
                                <div class="foto-empleado-bloque">
                                    <button type="button" class="foto-empleado-preview foto-empleado-preview-lg" id="edit_activo_foto_ver"
                                        data-preview="edit_activo_foto_preview" title="Ver foto del empleado"
                                        aria-label="Ver foto del empleado" disabled>
                                        <img id="edit_activo_foto_preview" src="assets/Img/Iconos/person-fill.svg" class="es-icono" alt="Vista previa de la foto">
                                    </button>
                                    <div class="flex-grow-1">
                                        <div class="form-label fw-semibold mb-1">Foto de perfil <span class="text-muted fw-normal">(opcional)</span></div>
                                        <p class="text-muted small mb-2">JPG, PNG o WEBP · Máx. 2 MB. Podrás ajustar el encuadre antes de guardarla.</p>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-subir-foto-empleado" data-prefijo="edit_activo">
                                                <i class="bi bi-camera-fill me-1"></i> Subir foto
                                            </button>
                                            <input type="file" id="edit_activo_foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" id="edit_activo_direccion" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:#f8f9fa; border:1px solid #dee2e6;">
                                    <i class="bi bi-link-45deg flex-shrink-0" style="font-size:20px;"></i>
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold mb-0" for="edit_es_usuario">Este empleado tiene un usuario</label>
                                        <small class="text-muted d-block">Márcalo si la persona entra al sistema con sus credenciales.</small>
                                    </div>
                                    <div class="form-check form-switch ps-0 mb-0">
                                        <input class="form-check-input" type="checkbox" id="edit_es_usuario" name="es_usuario" value="1" role="switch">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="edit_usuario_wrap">
                                <label class="form-label">Usuario Asociado</label>
                                <select class="form-select" id="edit_id_usuario" name="id_usuario">
                                    <option value="">Seleccione el usuario...</option>
                                </select>
                                <small class="text-muted">Solo se muestran usuarios sin empleado asociado.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-idealo-success" id="btnGuardarEdicionActivo">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarInactivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Registro Inhabilitado</h5>
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
                                    <option value="Inactivo" selected>Inactivo (Inhabilitado)</option>
                                    <option value="Activo">Activo (Reactivar Empleado)</option>
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

    <div class="modal fade modal-idealo" id="modalVerFotoEmpleado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-camera-fill"></i>
                        <span id="verFotoEmpleadoTitulo">Foto del Empleado</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center ver-foto-empleado-cuerpo">
                    <img id="verFotoEmpleadoImagen" src="" class="ver-foto-empleado" alt="Foto del empleado">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalRecortarFotoEmpleado" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-camera-fill"></i>
                        <span>Ajustar Foto del Empleado</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="recorte-layout">
                        <div class="recorte-viewport" id="recorteEmpleadoViewport" tabindex="0"
                            role="application" aria-label="Área de recorte. Arrastra o usa las flechas para centrar la imagen.">
                            <img id="recorteEmpleadoImagen" alt="Vista de recorte" draggable="false">
                        </div>
                        <div class="recorte-preview-wrap">
                            <span>Vista previa</span>
                            <canvas id="recorteEmpleadoPreview" width="128" height="128"
                                role="img" aria-label="Vista previa de la foto recortada"></canvas>
                        </div>
                    </div>
                    <div class="recorte-controles">
                        <label for="recorteEmpleadoZoom" class="form-label">Zoom</label>
                        <input type="range" class="form-range" id="recorteEmpleadoZoom" min="1" max="3" step="0.01" value="1" disabled>
                        <div class="form-text">Arrastra la imagen para centrarla y ajusta el zoom a tu gusto.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarRecorteEmpleado" disabled>Usar Foto</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/libs/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/jquery.dataTables.min.js"></script>
    <script src="assets/libs/dataTables.bootstrap5.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script type="importmap">
        {"imports":{"jquery":"./assets/js/helpers/jquery-bridge.js","sweetalert2":"./assets/js/helpers/sweetalert2-bridge.js"}}
    </script>
    <script type="module" src="assets/js/empleado.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/empleado.js') ?>"></script>

</body>

</html>
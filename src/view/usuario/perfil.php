<?php
if (!isset($perfil)) {
    $perfil = null;
}

$nombreMostrar   = $perfil['nombre_usuario'] ?? 'Usuario';
$subRol          = ucfirst($perfil['tipo_de_usuario'] ?? 'Sin rol');
$estadoReal      = strtolower($perfil['status_usuario'] ?? 'activo');
$estadoBadge     = $estadoReal === 'activo' ? 'bg-success' : 'bg-danger';
$tieneEmpleado   = !empty($perfil['nombres']) || !empty($perfil['cedula']);

$imagenPerfil = trim($perfil['imagen'] ?? '');
$tieneFoto    = $imagenPerfil !== '' && is_file(__DIR__ . '/../../../' . ltrim($imagenPerfil, '/'));
$rutaFoto     = $tieneFoto ? $imagenPerfil : 'assets/Img/Iconos/person-fill.svg';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - Idéalo</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
    <link rel="stylesheet" href="assets/libs/css/iconos.css">
    <link rel="stylesheet" href="assets/libs/css/perfil.css">
</head>

<body>

    <div class="dashboard-layout">

        <?php include 'src/view/sidebar.php'; ?>

        <main class="main-content">
            <div class="view-container">

                <div class="page-header">
                    <div>
                        <h1>Mi Perfil</h1>
                        <p>Gestiona tu información personal y credenciales de acceso</p>
                    </div>
                </div>

                <div class="profile-container">
                    <div class="profile-card">

                        <div class="profile-cover"></div>

                        <div class="profile-body">

                            <div class="profile-avatar-wrapper">
                                <div class="profile-avatar" id="perfilAvatar">
                                    <img id="perfilAvatarImg"
                                        src="<?php echo htmlspecialchars($rutaFoto); ?>"
                                        class="<?php echo $tieneFoto ? 'perfil-foto' : 'icono-svg icono-gris'; ?>"
                                        alt="Foto de perfil">
                                    <button type="button" class="btn-foto-perfil" id="btnCambiarFoto"
                                        title="Cambiar foto de perfil" aria-label="Cambiar foto de perfil">
                                        <img src="assets/Img/Iconos/camera-fill.svg" alt="">
                                    </button>
                                    <input type="file" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" hidden>
                                </div>
                            </div>

                            <div class="profile-header-text">
                                <h2 id="perfilNombreH2"><?php echo htmlspecialchars($nombreMostrar); ?></h2>
                                <p id="perfilSubtitulo">
                                    <?php echo htmlspecialchars($subRol); ?>
                                    <span class="badge <?php echo $estadoBadge; ?> ms-2"><?php echo ucfirst($estadoReal); ?></span>
                                </p>
                            </div>

                            <hr class="profile-separator">

                            <div class="profile-info-grid">

                                <div class="info-group">
                                    <label>Nombre de Usuario</label>
                                    <div class="info-value">
                                        <img src="assets/Img/Iconos/person-badge.svg" class="icono-svg icono-gris" alt="Nombre de usuario">
                                        <span id="perfilNombreUsuario"><?php echo htmlspecialchars($perfil['nombre_usuario'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>

                                <div class="info-group">
                                    <label>Correo Electrónico</label>
                                    <div class="info-value">
                                        <img src="assets/Img/Iconos/envelope.svg" class="icono-svg icono-gris" alt="Correo electrónico">
                                        <span id="perfilCorreo"><?php echo htmlspecialchars($perfil['correo'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>

                                <?php if ($tieneEmpleado): ?>
                                    <div class="info-group">
                                        <label>Nombre Completo</label>
                                        <div class="info-value">
                                            <img src="assets/Img/Iconos/person-lines-fill.svg" class="icono-svg icono-gris" alt="Nombre completo">
                                            <span><?php echo htmlspecialchars(trim(($perfil['nombres'] ?? '') . ' ' . ($perfil['apellidos'] ?? ''))); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-group">
                                        <label>Cédula</label>
                                        <div class="info-value">
                                            <img src="assets/Img/Iconos/credit-card.svg" class="icono-svg icono-gris" alt="Cédula">
                                            <span><?php echo htmlspecialchars($perfil['cedula'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-group">
                                        <label>Teléfono</label>
                                        <div class="info-value">
                                            <img src="assets/Img/Iconos/telephone.svg" class="icono-svg icono-gris" alt="Teléfono">
                                            <span><?php echo htmlspecialchars($perfil['telefono'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-group">
                                        <label>Cargo</label>
                                        <div class="info-value">
                                            <img src="assets/Img/Iconos/briefcase.svg" class="icono-svg icono-gris" alt="Cargo">
                                            <span><?php echo htmlspecialchars($perfil['cargo'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="info-group">
                                    <label>Rol de Usuario</label>
                                    <div class="info-value">
                                        <img src="assets/Img/Iconos/shield-lock.svg" class="icono-svg icono-gris" alt="Rol">
                                        <span class="badge-role"><?php echo htmlspecialchars($subRol); ?></span>
                                    </div>
                                </div>

                                <div class="info-group">
                                    <label>ID de Usuario</label>
                                    <div class="info-value">
                                        <img src="assets/Img/Iconos/hash.svg" class="icono-svg icono-gris" alt="ID">
                                        <span>#<?php echo htmlspecialchars($perfil['id_usuario'] ?? ''); ?></span>
                                    </div>
                                </div>

                            </div>

                            <hr class="profile-separator">

                            <div class="profile-actions">
                                <button type="button" class="btn-idealo-secondary" id="btnCambiarFotoAccion">
                                    <img src="assets/Img/Iconos/camera-fill.svg" class="icono-svg icono-gris" alt="Cambiar foto"> Cambiar Foto
                                </button>
                                <button class="btn-idealo-secondary" data-bs-toggle="modal" data-bs-target="#modalCambiarContrasena">
                                    <img src="assets/Img/Iconos/key.svg" class="icono-svg icono-gris" alt="Cambiar contraseña"> Cambiar Contraseña
                                </button>
                                <button class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                                    <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-blanco" alt="Editar perfil"> Editar Perfil
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </main>

    </div>

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div class="modal fade modal-idealo" id="modalCambiarContrasena" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/key.svg" class="icono-svg icono-sm icono-gris" alt="Cambiar contraseña">
                        <span>Cambiar Contraseña</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCambiarContrasena" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Contraseña Actual</label>
                                <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva" minlength="6" placeholder="Mínimo 6 caracteres" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="confirmar_contrasena_nueva" minlength="6" placeholder="Repite la nueva contraseña" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR PERFIL -->
    <div class="modal fade modal-idealo" id="modalEditarPerfil" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-sm icono-gris" alt="Editar perfil">
                        <span>Editar Perfil</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarPerfil" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="perfil_nombre_usuario" name="nombre_usuario" maxlength="20" required>
                                <small class="text-muted">Entre 3 y 20 caracteres (letras, números o guiones bajos).</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="perfil_correo" name="correo" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL RECORTAR FOTO -->
    <div class="modal fade modal-idealo" id="modalRecortarFoto" tabindex="-1" aria-labelledby="tituloRecortarFoto" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="tituloRecortarFoto">
                        <img src="assets/Img/Iconos/camera-fill.svg" class="icono-svg icono-sm icono-gris" alt="">
                        <span>Ajustar Foto de Perfil</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="recorte-layout">
                        <div class="recorte-viewport" id="recorteViewport" tabindex="0"
                            role="application" aria-label="Área de recorte. Arrastra o usa las flechas para centrar la imagen.">
                            <img id="recorteImagen" alt="Vista de recorte" draggable="false">
                        </div>
                        <div class="recorte-preview-wrap">
                            <span>Vista previa</span>
                            <canvas id="recortePreview" width="128" height="128"
                                role="img" aria-label="Vista previa de la foto recortada"></canvas>
                        </div>
                    </div>
                    <div class="recorte-controles">
                        <label for="recorteZoom" class="form-label">Zoom</label>
                        <input type="range" class="form-range" id="recorteZoom" min="1" max="3" step="0.01" value="1" disabled>
                        <div class="form-text">Arrastra la imagen para centrarla y ajusta el zoom a tu gusto.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarRecorte" disabled>Confirmar Foto</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script src="assets/js/perfil.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/perfil.js') ?>"></script>

</body>

</html>
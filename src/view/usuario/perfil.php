<?php
if (!isset($perfil)) {
    $perfil = null;
}

$nombreCompleto = trim(($perfil['nombres'] ?? '') . ' ' . ($perfil['apellidos'] ?? ''));
$nombreUsuario  = $perfil['nombre_usuario'] ?? 'Usuario';
$nombreMostrar  = $nombreCompleto !== '' ? $nombreCompleto : $nombreUsuario;
$usaNombreUsuario = $nombreCompleto === '';

$subRol          = ucfirst($perfil['tipo_de_usuario'] ?? 'Sin rol');
$estadoReal      = strtolower($perfil['status_usuario'] ?? 'activo');
$esActivo        = $estadoReal === 'activo';

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
    <link rel="stylesheet" href="assets/libs/css/perfil.css?v=<?= filemtime(__DIR__ . '/../../../assets/libs/css/perfil.css') ?>">
    <link rel="stylesheet" href="assets/libs/css/recorte-foto.css">
</head>

<body>

    <?php include 'src/view/sidebar.php'; ?>

    <main class="main-content">
            <div class="view-container">

                <header class="page-header">
                    <div>
                        <h1>Mi Perfil</h1>
                        <p>Gestiona tu información personal y tus credenciales de acceso</p>
                    </div>
                </header>

                <div class="perfil-layout">

                    <!-- Columna izquierda: resumen del perfil -->
                    <aside class="perfil-columna perfil-columna-lateral">
                        <section class="perfil-card perfil-resumen" aria-labelledby="perfilNombreH2">

                            <div class="perfil-avatar-wrapper">
                                <div class="perfil-avatar" id="perfilAvatar">
                                    <img id="perfilAvatarImg"
                                        src="<?php echo htmlspecialchars($rutaFoto); ?>"
                                        class="<?php echo $tieneFoto ? 'perfil-foto' : 'icono-svg icono-gris'; ?>"
                                        alt="Foto de perfil">
                                    <button type="button" class="btn-foto-perfil" id="btnCambiarFotoAccion"
                                        title="Cambiar foto de perfil" aria-label="Cambiar foto de perfil">
                                        <img src="assets/Img/Iconos/camera-fill.svg" alt="">
                                    </button>
                                    <input type="file" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" hidden>
                                </div>
                            </div>

                            <h2 class="perfil-nombre" id="perfilNombreH2"<?php echo $usaNombreUsuario ? ' data-usa-usuario="1"' : ''; ?>>
                                <?php echo htmlspecialchars($nombreMostrar); ?>
                            </h2>
                            <p class="perfil-usuario" id="perfilUsuario">@<?php echo htmlspecialchars($nombreUsuario); ?></p>

                            <div class="perfil-rol">
                                <span class="perfil-rol-texto">
                                    <img src="assets/Img/Iconos/shield-lock.svg" class="icono-svg icono-verde" alt="Rol">
                                    <?php echo htmlspecialchars($subRol); ?>
                                </span>
                                <span class="perfil-badge <?php echo $esActivo ? 'perfil-badge-activo' : 'perfil-badge-inactivo'; ?>" id="perfilEstado">
                                    <span class="perfil-badge-punto" aria-hidden="true"></span>
                                    <?php echo $esActivo ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </div>

                            <div class="perfil-acciones">
                                <button type="button" class="perfil-btn perfil-btn-secundario perfil-btn-bloque" id="btnCambiarFoto">
                                    <img src="assets/Img/Iconos/camera-fill.svg" class="icono-svg icono-verde" alt="Cambiar foto">
                                    Cambiar Foto
                                </button>

                                <button type="button" class="perfil-btn perfil-btn-secundario perfil-btn-bloque"
                                    id="btnCambiarContrasena" data-bs-toggle="modal" data-bs-target="#modalCambiarContrasena">
                                    <img src="assets/Img/Iconos/key.svg" class="icono-svg icono-verde" alt="Cambiar contraseña">
                                    Cambiar Contraseña
                                </button>

                                <button type="button" class="perfil-btn perfil-btn-primario perfil-btn-bloque" id="btnEditarPerfil" data-modo="editar">
                                    <img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-blanco" alt="">
                                    <span class="perfil-btn-texto">Editar Perfil</span>
                                </button>
                            </div>

                        </section>
                    </aside>

                    <!-- Columna derecha: datos y seguridad -->
                    <div class="perfil-columna perfil-columna-principal">

                        <!-- Tarjeta: datos personales -->
                        <section class="perfil-card" id="tarjetaDatos" aria-labelledby="tituloDatosPersonales">

                            <header class="perfil-card-header">
                                <img src="assets/Img/Iconos/person-lines-fill.svg" class="icono-svg icono-verde" alt="">
                                <h2 id="tituloDatosPersonales">Datos Personales</h2>
                            </header>

                            <p class="perfil-aviso">
                                <img src="assets/Img/Iconos/lock.svg" class="icono-svg icono-gris" alt="">
                                Los campos bloqueados se administran desde el módulo de Empleados.
                            </p>

                            <div class="perfil-grid">

                                <div class="perfil-campo" data-campo="nombre_usuario">
                                    <label class="perfil-campo-label" for="perfil_nombre_usuario">Nombre de usuario</label>
                                    <span class="perfil-campo-valor" id="perfilNombreUsuario"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_nombre_usuario"
                                        name="nombre_usuario" maxlength="20" autocomplete="username" required>
                                    <small class="perfil-error" id="error_nombre_usuario"></small>
                                </div>

                                <div class="perfil-campo" data-campo="correo">
                                    <label class="perfil-campo-label" for="perfil_correo">Correo electrónico</label>
                                    <span class="perfil-campo-valor" id="perfilCorreo"><?php echo htmlspecialchars($perfil['correo'] ?? 'N/A'); ?></span>
                                    <input type="email" class="perfil-input perfil-input-inline" id="perfil_correo"
                                        name="correo" autocomplete="email" required>
                                    <small class="perfil-error" id="error_correo"></small>
                                </div>

                                <div class="perfil-campo" data-campo="nombre_completo">
                                    <span class="perfil-campo-label">Nombre completo</span>
                                    <span class="perfil-campo-valor"><?php echo htmlspecialchars($nombreCompleto !== '' ? $nombreCompleto : 'No registrado'); ?></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_nombre_completo"
                                        value="<?php echo htmlspecialchars($nombreCompleto); ?>" readonly tabindex="-1" aria-label="Nombre completo (solo lectura)">
                                </div>

                                <div class="perfil-campo" data-campo="cedula">
                                    <span class="perfil-campo-label">Cédula</span>
                                    <span class="perfil-campo-valor"><?php echo htmlspecialchars($perfil['cedula'] ?? 'N/A'); ?></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_cedula"
                                        value="<?php echo htmlspecialchars($perfil['cedula'] ?? ''); ?>" readonly tabindex="-1" aria-label="Cédula (solo lectura)">
                                </div>

                                <div class="perfil-campo" data-campo="telefono">
                                    <span class="perfil-campo-label">Teléfono</span>
                                    <span class="perfil-campo-valor"><?php echo htmlspecialchars($perfil['telefono'] ?? 'N/A'); ?></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_telefono"
                                        value="<?php echo htmlspecialchars($perfil['telefono'] ?? ''); ?>" readonly tabindex="-1" aria-label="Teléfono (solo lectura)">
                                </div>

                                <div class="perfil-campo" data-campo="cargo">
                                    <span class="perfil-campo-label">Cargo</span>
                                    <span class="perfil-campo-valor"><?php echo htmlspecialchars($perfil['cargo'] ?? 'N/A'); ?></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_cargo"
                                        value="<?php echo htmlspecialchars($perfil['cargo'] ?? ''); ?>" readonly tabindex="-1" aria-label="Cargo (solo lectura)">
                                </div>

                                <div class="perfil-campo" data-campo="rol">
                                    <span class="perfil-campo-label">Rol de usuario</span>
                                    <span class="perfil-campo-valor"><span class="perfil-rol-badge"><?php echo htmlspecialchars($subRol); ?></span></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_rol"
                                        value="<?php echo htmlspecialchars($subRol); ?>" readonly tabindex="-1" aria-label="Rol de usuario (solo lectura)">
                                </div>

                                <div class="perfil-campo" data-campo="id_usuario">
                                    <span class="perfil-campo-label">ID de usuario</span>
                                    <span class="perfil-campo-valor">#<?php echo htmlspecialchars($perfil['id_usuario'] ?? ''); ?></span>
                                    <input type="text" class="perfil-input perfil-input-inline" id="perfil_id_usuario"
                                        value="#<?php echo htmlspecialchars($perfil['id_usuario'] ?? ''); ?>" readonly tabindex="-1" aria-label="ID de usuario (solo lectura)">
                                </div>

                            </div>

                        </section>

                    </div>
                </div>

            </div>
        </main>

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

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div class="modal fade modal-idealo" id="modalCambiarContrasena" tabindex="-1" aria-labelledby="tituloCambiarContrasena" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="tituloCambiarContrasena">
                        <img src="assets/Img/Iconos/key.svg" class="icono-svg icono-md icono-verde" alt="">
                        <span>Cambiar Contraseña</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formCambiarContrasena" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="contrasena_actual">Contraseña actual</label>
                            <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual"
                                autocomplete="current-password" placeholder="Ingresa tu contraseña actual" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="contrasena_nueva">Nueva contraseña</label>
                            <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva"
                                minlength="6" autocomplete="new-password" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="confirmar_contrasena_nueva">Confirmar nueva contraseña</label>
                            <input type="password" class="form-control" id="confirmar_contrasena_nueva"
                                name="confirmar_contrasena_nueva" minlength="6" autocomplete="new-password"
                                placeholder="Repite la nueva contraseña" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnGuardarContrasena">Guardar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script type="importmap">
        {"imports":{"jquery":"./assets/js/helpers/jquery-bridge.js","sweetalert2":"./assets/js/helpers/sweetalert2-bridge.js"}}
    </script>
    <script type="module" src="assets/js/perfil.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/perfil.js') ?>"></script>

</body>

</html>

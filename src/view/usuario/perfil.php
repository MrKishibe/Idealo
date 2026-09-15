<?php
if (!isset($perfil)) {
    $perfil = null;
}

$nombreMostrar   = $perfil['nombre_usuario'] ?? 'Usuario';
$subRol          = ucfirst($perfil['tipo_de_usuario'] ?? 'Sin rol');
$estadoReal      = strtolower($perfil['status_usuario'] ?? 'activo');
$estadoBadge     = $estadoReal === 'activo' ? 'bg-success' : 'bg-danger';
$tieneEmpleado   = !empty($perfil['nombres']) || !empty($perfil['cedula']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - Idéalo</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
    <link rel="stylesheet" href="assets/css/iconos.css">
    <style>
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            background-color: var(--gris-fondo);
            color: var(--gris-texto);
            font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
            letter-spacing: -0.01em;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
            background-color: var(--gris-fondo);
        }

        .view-container {
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .profile-container {
            width: 100%;
            animation: fadeIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .profile-card {
            background-color: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            transition: var(--transition-smooth);
        }

        .profile-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .profile-cover {
            height: 160px;
            background: linear-gradient(135deg, var(--azul-opaco), #34495e);
            position: relative;
        }

        .profile-cover::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: radial-gradient(circle at 90% 10%, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        }

        .profile-body {
            padding: 32px;
            position: relative;
        }

        .profile-avatar-wrapper {
            position: absolute;
            top: -80px;
            left: 32px;
            z-index: 5;
        }

        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background-color: var(--gris-fondo);
            border: 5px solid var(--blanco);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: var(--shadow-md);
            transition: var(--transition-smooth);
        }

        .profile-card:hover .profile-avatar {
            transform: scale(1.03);
        }

        .profile-avatar img {
            width: 72px;
            height: 72px;
        }

        .profile-header-text {
            margin-left: 160px;
            margin-bottom: 16px;
            min-height: 50px;
        }

        .profile-header-text h2 {
            font-size: 26px;
            font-weight: 700;
            color: var(--azul-opaco);
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .profile-header-text p {
            color: var(--gris-mutado);
            font-size: 14.5px;
            margin-top: 4px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .profile-separator {
            border: 0;
            border-top: 1px solid #edf2f7;
            margin: 24px 0;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gris-mutado);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .info-value {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gris-texto);
            font-size: 15px;
            font-weight: 500;
        }

        .info-value img {
            width: 16px;
            height: 16px;
        }

        .badge-role {
            background-color: var(--azul-glow);
            color: var(--azul-opaco);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .profile-actions {
            display: flex;
            justify-content: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-idealo-secondary {
            background-color: var(--blanco);
            color: var(--gris-texto);
            border: 1.5px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition-fast);
        }

        .btn-idealo-secondary:hover {
            background-color: #f8fafc;
            border-color: var(--gris-mutado);
            transform: translateY(-2px);
        }

        @media (max-width: 576px) {
            .profile-body {
                padding: 20px;
            }

            .profile-avatar-wrapper {
                position: relative;
                top: -60px;
                left: 0;
                display: flex;
                justify-content: center;
                width: 100%;
            }

            .profile-header-text {
                margin-left: 0;
                text-align: center;
                margin-top: -40px;
            }

            .profile-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-idealo-success,
            .btn-idealo-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
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
                                <div class="profile-avatar">
                                    <img src="assets/Img/Iconos/person-fill.svg" class="icono-svg icono-gris" alt="Avatar">
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
                                <button class="btn-idealo-secondary" data-bs-toggle="modal" data-bs-target="#modalCambiarContrasena">
                                    <img src="assets/Img/Iconos/key.svg" class="icono-svg icono-gris" alt="Cambiar contraseña"> Cambiar Contraseña
                                </button>
                                <button class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil" onclick="cargarPerfilEdicion()">
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

    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="assets/js/perfil.js"></script>

</body>

</html>
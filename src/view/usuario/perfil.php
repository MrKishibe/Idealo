<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - Idéalo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
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
        background: radial-gradient(circle at 90% 10%, rgba(255,255,255,0.15) 0%, transparent 70%);
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

    .profile-avatar i {
        font-size: 4.5rem;
        color: var(--gris-mutado);
    }

    .profile-header-text {
        margin-left: 160px;
        margin-bottom: 32px;
        min-height: 50px;
    }

    .profile-header-text h2 {
        font-size: 26px;
        font-weight: 700;
        color: var(--azul-opaco);
        letter-spacing: -0.02em;
    }

    .profile-header-text p {
        color: var(--gris-mutado);
        font-size: 14.5px;
        margin-top: 4px;
        font-weight: 500;
    }

    .profile-separator {
        border: 0;
        border-top: 1px solid #edf2f7;
        margin: 28px 0;
    }

    .profile-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
    }

    .info-value {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--gris-texto);
        font-size: 15px;
        font-weight: 500;
    }

    .info-value i {
        color: var(--gris-mutado);
        font-size: 16px;
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
        .btn-idealo-success, .btn-idealo-secondary {
            width: 100%;
            justify-content: center;
        }
    }
</style>

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
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>

                        <div class="profile-header-text">
                            <h2>Admin</h2>
                            <p>Administrador de Sistema</p>
                        </div>

                        <hr class="profile-separator">

                        <div class="profile-info-grid">
                            
                            <div class="info-group">
                                <label>Correo Electrónico</label>
                                <div class="info-value">
                                    <i class="bi bi-envelope"></i>
                                    <span>admin123@idealo.com</span>
                                </div>
                            </div>

                            <div class="info-group">
                                <label>Teléfono</label>
                                <div class="info-value">
                                    <i class="bi bi-telephone"></i>
                                    <span>0424 12345667</span>
                                </div>
                            </div>

                            <div class="info-group">
                                <label>Rol de Usuario</label>
                                <div class="info-value">
                                    <i class="bi bi-shield-lock"></i>
                                    <span class="badge-role">Admin</span>
                                </div>
                            </div>

                            <div class="info-group">
                                <label>Fecha de Registro</label>
                                <div class="info-value">
                                    <i class="bi bi-calendar3"></i>
                                    <span>4 de Junio, 2026</span>
                                </div>
                            </div>

                        </div>

                        <hr class="profile-separator">

                        <div class="profile-actions">
                            <button class="btn-idealo-secondary">
                                <i class="bi bi-key"></i> Cambiar Contraseña
                            </button>
                            <button class="btn-idealo-success">
                                <i class="bi bi-pencil-square"></i> Editar Perfil
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </main>

</div>


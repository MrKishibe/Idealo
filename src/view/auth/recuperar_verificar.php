<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Verificar Código</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --azul-opaco: #1e293b;
            --azul-hover: #0f172a;
            --success-color: #10b981;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-main: #334155;
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.login-body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        body.login-body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            filter: blur(80px);
            z-index: 0;
        }

        body.login-body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
            filter: blur(100px);
            z-index: 0;
        }

        .login-container {
            background: var(--card-bg);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1;
            animation: slideUpIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.25rem;
        }

        .login-header h2 {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--azul-opaco);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header h2::before {
            content: '\f4db';
            font-family: 'bootstrap-icons';
            color: var(--success-color);
        }

        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            color: #475569;
        }

        .input-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid #e2e8f0;
            transition: var(--transition-smooth);
            background: #fff;
        }

        .input-group:focus-within {
            border-color: var(--azul-opaco);
            box-shadow: 0 0 0 4px rgba(30, 41, 59, 0.1);
            transform: translateY(-1px);
        }

        .input-group-text {
            background: #f8fafc;
            border: none;
            color: #94a3b8;
            padding-left: 1.25rem;
            padding-right: 0.75rem;
            transition: var(--transition-smooth);
        }

        .input-group:focus-within .input-group-text {
            color: var(--azul-opaco);
        }

        .form-control {
            border: none;
            padding: 0.75rem 1rem 0.75rem 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            background: transparent;
            text-align: center;
            letter-spacing: 0.5rem;
        }

        .form-control:focus {
            box-shadow: none;
            background: transparent;
        }

        .btn-idealo-submit {
            width: 100%;
            background: var(--azul-opaco);
            color: #fff;
            border: none;
            padding: 0.85rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: var(--transition-smooth);
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.2);
        }

        .btn-idealo-submit:hover {
            background: var(--azul-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 41, 59, 0.3);
        }

        .alert {
            border: none;
            border-radius: 12px;
            font-weight: 600;
            animation: fadeIn 0.4s ease;
        }

        .link-back {
            text-align: center;
            margin-top: 1.5rem;
        }

        .link-back a {
            color: var(--azul-opaco);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .link-back a:hover {
            text-decoration: underline;
        }

        .email-info {
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        @keyframes slideUpIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h2>Idéalo</h2>
            <p>Verificar Código</p>
        </div>

        <div class="email-info">
            Hemos enviado un código de 6 dígitos a<br>
            <strong><?php echo htmlspecialchars($correo); ?></strong>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($tipo); ?> p-2 mb-3 text-center" style="font-size: 14px;">
                <i class="bi bi-<?php echo ($tipo === 'danger') ? 'exclamation-circle-fill' : 'check-circle-fill'; ?>"></i> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form action="index.php?controller=recuperacionPassword&action=verificar" method="POST">
            <div class="mb-4">
                <label for="codigo" class="form-label fw-semibold text-center d-block">Código de Verificación</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo" placeholder="000000" maxlength="6" minlength="6" required autocomplete="off" pattern="\d{6}">
                </div>
            </div>

            <button type="submit" class="btn-idealo-submit">Verificar Código</button>
        </form>

        <div class="link-back">
            <a href="index.php?controller=recuperacionPassword&action=solicitar"><i class="bi bi-arrow-left"></i> Volver atrás</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('codigo').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
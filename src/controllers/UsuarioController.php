<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

use Idealo\Models\UsuarioModel;


$action = $_GET['action'] ?? 'listar';

if ($action === 'listar') {
    $usuarioModel = new UsuarioModel();

    $usuarios = $usuarioModel->listarTodos();
    $roles = $usuarioModel->listarRoles();

    require_once __DIR__ . '/../view/usuario/listar.php';
    exit();
}

if ($action === 'perfil') {
    $usuarioModel = new UsuarioModel();

    $idSesion = intval($_SESSION['usuario'] ?? 0);
    $perfil = $idSesion > 0 ? $usuarioModel->obtenerPerfil($idSesion) : null;

    require_once __DIR__ . '/../view/usuario/perfil.php';
    exit();
}

if ($action === 'listarUsuariosAjax') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $usuarioModel = new UsuarioModel();
        $usuarios = $usuarioModel->listarTodos();

        echo json_encode([
            'status' => 'success',
            'usuarios' => $usuarios
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'listarEmpleadosAjax') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $usuarioModel = new UsuarioModel();
        $empleados = $usuarioModel->listarEmpleadosParaVincular();

        echo json_encode([
            'status' => 'success',
            'empleados' => $empleados
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'guardar') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $esEmpleado = !empty($_POST['es_empleado']);
        $idEmpleado = intval($_POST['id_empleado'] ?? 0);

        if ($esEmpleado && $idEmpleado <= 0) {
            throw new Exception('[Validación] Si el usuario es empleado, debes seleccionar el empleado asociado.');
        }

        $usuarioModel = new UsuarioModel();

        $resultado = $usuarioModel->guardar([
            'nombre_usuario' => trim($_POST['nombre_usuario'] ?? ''),
            'correo'         => trim($_POST['correo'] ?? ''),
            'contrasena'     => $_POST['contrasena'] ?? '',
            'id_rol'         => intval($_POST['id_rol'] ?? 0),
        ]);

        $mensaje = $resultado['message'] ?? 'No se pudo registrar el usuario.';

        if (!empty($resultado['success'])) {
            $idUsuario = intval($resultado['id_usuario'] ?? 0);
            if ($esEmpleado && $idEmpleado > 0 && $idUsuario > 0) {
                $usuarioModel->vincularEmpleado($idUsuario, $idEmpleado);
                $mensaje .= ' Empleado vinculado con éxito.';
            }
        }

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $mensaje,
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'editar') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $esEmpleado = !empty($_POST['es_empleado']);
        $idEmpleado = intval($_POST['id_empleado'] ?? 0);
        $idUsuario  = intval($_POST['id_usuario'] ?? 0);

        if ($esEmpleado && $idEmpleado <= 0) {
            throw new Exception('[Validación] Si el usuario es empleado, debes seleccionar el empleado asociado.');
        }

        $usuarioModel = new UsuarioModel();

        $resultado = $usuarioModel->editar([
            'id_usuario'     => $idUsuario,
            'nombre_usuario' => trim($_POST['nombre_usuario'] ?? ''),
            'correo'         => trim($_POST['correo'] ?? ''),
            'contrasena'     => $_POST['contrasena'] ?? '',
            'id_rol'         => intval($_POST['id_rol'] ?? 0),
        ]);

        $mensaje = $resultado['message'] ?? 'No se pudo actualizar el usuario.';

        if (!empty($resultado['success']) && $idUsuario > 0) {
            if ($esEmpleado && $idEmpleado > 0) {
                $usuarioModel->vincularEmpleado($idUsuario, $idEmpleado);
                $mensaje .= ' Empleado vinculado con éxito.';
            } else {
                $usuarioModel->desvincularEmpleado($idUsuario);
            }
        }

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $mensaje,
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'cambiarEstado') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idUsuario = intval($_POST['id_usuario'] ?? 0);
        $estado = trim($_POST['status_usuario'] ?? '');

        $usuarioModel = new UsuarioModel();
        $resultado = $usuarioModel->cambiarEstado($idUsuario, $estado);

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? 'No se pudo cambiar el estado del usuario.',
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'cambiarContrasena') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idUsuario = intval($_SESSION['usuario'] ?? 0);
        if ($idUsuario <= 0) {
            throw new Exception('Debes iniciar sesión para cambiar tu contraseña.');
        }

        $usuarioModel = new UsuarioModel();
        $resultado = $usuarioModel->cambiarContrasena(
            $idUsuario,
            $_POST['contrasena_actual'] ?? '',
            $_POST['contrasena_nueva'] ?? ''
        );

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? 'No se pudo actualizar la contraseña.',
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'actualizarPerfil') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idUsuario = intval($_SESSION['usuario'] ?? 0);
        if ($idUsuario <= 0) {
            throw new Exception('Debes iniciar sesión para editar tu perfil.');
        }

        $nuevoNombre = trim($_POST['nombre_usuario'] ?? '');
        $nuevoCorreo = trim($_POST['correo'] ?? '');

        $usuarioModel = new UsuarioModel();
        $resultado = $usuarioModel->cambiarDatosPerfil($idUsuario, $nuevoNombre, $nuevoCorreo);

        if (!empty($resultado['success'])) {
            $_SESSION['nombre_usuario'] = $nuevoNombre;
        }

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? 'No se pudo actualizar el perfil.',
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'verificarNombreUsuario') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
        $idUsuario = isset($_POST['id_usuario']) && $_POST['id_usuario'] !== '' ? intval($_POST['id_usuario']) : null;

        $usuarioModel = new UsuarioModel();
        $existe = $usuarioModel->existeNombreUsuario($nombreUsuario, $idUsuario);

        echo json_encode([
            'status' => 'success',
            'existe' => $existe
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'verificarCorreo') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $correo = trim($_POST['correo'] ?? '');
        $idUsuario = isset($_POST['id_usuario']) && $_POST['id_usuario'] !== '' ? intval($_POST['id_usuario']) : null;

        $usuarioModel = new UsuarioModel();
        $existe = $usuarioModel->existeCorreo($correo, $idUsuario);

        echo json_encode([
            'status' => 'success',
            'existe' => $existe
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Acción no encontrada.'
]);
exit();

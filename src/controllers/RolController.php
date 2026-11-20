<?php

require_once __DIR__ . '/../helpers/AuthorizationHelper.php';
\Idealo\Helpers\AuthorizationHelper::enforce('rol');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/RolModel.php';

use Idealo\Models\RolModel;

$action = $_GET['action'] ?? 'listar';

if ($action === 'listar') {
    require_once __DIR__ . '/../view/rol/listar.php';
    exit();
}

if ($action === 'listarRolesAjax') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $rolModel = new RolModel();
        $roles = $rolModel->listarTodos();

        echo json_encode([
            'status' => 'success',
            'roles'  => $roles
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
        $rolModel = new RolModel();
        $resultado = $rolModel->guardar([
            'tipo_de_usuario' => trim($_POST['tipo_de_usuario'] ?? '')
        ]);

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? 'No se pudo registrar el rol.',
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
        $rolModel = new RolModel();
        $resultado = $rolModel->editar([
            'id_rol'          => intval($_POST['id_rol'] ?? 0),
            'tipo_de_usuario' => trim($_POST['tipo_de_usuario'] ?? '')
        ]);

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? 'No se pudo actualizar el rol.',
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
        $idRol  = intval($_POST['id_rol'] ?? 0);
        $estado = trim($_POST['status_roles'] ?? '');

        $rolModel = new RolModel();
        $resultado = $rolModel->cambiarEstado($idRol, $estado);

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? 'No se pudo cambiar el estado del rol.',
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

if ($action === 'verificarNombreRol') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $nombreRol = trim($_POST['tipo_de_usuario'] ?? '');
        $idRol = isset($_POST['id_rol']) && $_POST['id_rol'] !== '' ? intval($_POST['id_rol']) : null;

        $rolModel = new RolModel();
        $existe = $rolModel->existeNombre($nombreRol, $idRol);

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

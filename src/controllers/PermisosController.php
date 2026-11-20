<?php

require_once __DIR__ . '/../helpers/AuthorizationHelper.php';
\Idealo\Helpers\AuthorizationHelper::enforce('permisos');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/PermisosModel.php';

use Idealo\Models\PermisosModel;

$accion = $_GET['action'] ?? 'listar';
$modelo = new PermisosModel();

$responder = static function (array $respuesta, int $codigo = 200): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
};

$rolActual = strtolower(trim((string) ($_SESSION['nombre_rol'] ?? '')));
if (!in_array($rolActual, ['administrador', 'superadministrador'], true)) {
    if ($accion === 'listar') {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>Acceso denegado</h1><p>Solo un administrador puede gestionar los permisos.</p>';
        exit;
    }

    $responder(['status' => 'error', 'message' => 'No tienes permiso para gestionar los permisos.'], 403);
}

if ($accion === 'listar') {
    require_once __DIR__ . '/../view/permisos/permisos.php';
    exit;
}

if ($accion === 'listarPermisosAjax') {
    try {
        $responder([
            'status' => 'success',
            'permisos' => $modelo->listarPermisos()
        ]);
    } catch (Throwable $error) {
        error_log('Error al listar permisos: ' . $error->getMessage());
        $responder(['status' => 'error', 'message' => 'No se pudieron cargar los permisos.'], 500);
    }
}

if ($accion === 'registrarPermiso' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$modelo->registrarPermiso($_POST)) {
            $responder(['status' => 'error', 'message' => 'No se pudo registrar el permiso.'], 500);
        }

        $responder(['status' => 'success', 'message' => 'Permiso registrado correctamente.']);
    } catch (InvalidArgumentException $error) {
        $responder(['status' => 'error', 'message' => $error->getMessage()], 400);
    } catch (Throwable $error) {
        error_log('Error al registrar permiso: ' . $error->getMessage());
        $responder(['status' => 'error', 'message' => 'No se pudo registrar el permiso.'], 500);
    }
}

if ($accion === 'listarPermisosPorRolAjax') {
    try {
        $idRol = filter_var($_GET['id_rol'] ?? null, FILTER_VALIDATE_INT);
        if ($idRol === false || $idRol === null || $idRol <= 0) {
            $responder(['status' => 'error', 'message' => 'Debe seleccionar un rol válido.'], 400);
        }

        $responder([
            'status' => 'success',
            'permisos' => $modelo->listarPermisosPorRol($idRol)
        ]);
    } catch (Throwable $error) {
        error_log('Error al consultar permisos por rol: ' . $error->getMessage());
        $responder(['status' => 'error', 'message' => 'No se pudieron consultar los permisos del rol.'], 500);
    }
}

if ($accion === 'asignarPermisosRol' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $modelo->asignarPermisosRol($_POST);
        $responder(['status' => 'success', 'message' => 'Permisos del rol actualizados correctamente.']);
    } catch (InvalidArgumentException $error) {
        $responder(['status' => 'error', 'message' => $error->getMessage()], 400);
    } catch (Throwable $error) {
        error_log('Error al actualizar permisos del rol: ' . $error->getMessage());
        $responder(['status' => 'error', 'message' => 'No se pudieron actualizar los permisos del rol.'], 500);
    }
}

if ($accion === 'eliminarPermiso' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $idPermiso = filter_var($_POST['id_permiso'] ?? null, FILTER_VALIDATE_INT);
        if ($idPermiso === false || $idPermiso === null || $idPermiso <= 0) {
            $responder(['status' => 'error', 'message' => 'Debe indicar un permiso válido.'], 400);
        }

        if (!$modelo->eliminarPermiso($idPermiso)) {
            $responder(['status' => 'error', 'message' => 'No se pudo inactivar el permiso.'], 500);
        }

        $responder(['status' => 'success', 'message' => 'Permiso inactivado correctamente.']);
    } catch (Throwable $error) {
        error_log('Error al inactivar permiso: ' . $error->getMessage());
        $responder(['status' => 'error', 'message' => 'No se pudo inactivar el permiso.'], 500);
    }
}

$responder(['status' => 'error', 'message' => 'Acción no encontrada.'], 404);

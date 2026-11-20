<?php

namespace Idealo\Helpers;

use Idealo\Config\Database;
use PDO;

require_once __DIR__ . '/../../config/database.php';

class AuthorizationHelper
{
    private const MODULES = [
        'usuarios' => 'Usuarios',
        'clientes' => 'Clientes',
        'empleados' => 'Empleados',
        'pedidos' => 'Pedidos',
        'tipos_pedido' => 'Tipos de pedido',
        'inventario' => 'Inventario',
        'servicios' => 'Servicios',
        'produccion' => 'Producción',
        'finanzas' => 'Finanzas',
    ];

    private const OPERATIONS = [
        'consultar' => 'Consultar',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'eliminar' => 'Eliminar',
        'gestionar' => 'Gestionar todo',
    ];

    private const CONTROLLER_MODULES = [
        'usuario' => 'usuarios',
        'cliente' => 'clientes',
        'empleado' => 'empleados',
        'pedido' => 'pedidos',
        'tipopedido' => 'tipos_pedido',
        'producto' => 'inventario',
        'materiaprima' => 'inventario',
        'tipomateriaprima' => 'inventario',
        'consumomaterial' => 'inventario',
        'perdidamaterial' => 'inventario',
        'servicio' => 'servicios',
        'ordenproduccion' => 'produccion',
        'finanzas' => 'finanzas',
    ];

    private const SELF_SERVICE_ACTIONS = [
        'perfil',
        'cambiarcontrasena',
        'actualizarperfil',
        'subirfotoperfil',
    ];

    private const ROUTE_ACTION_CONTROLLERS = [
        'auth',
        'usuario',
        'producto',
        'rol',
        'permisos',
        'servicio',
    ];

    public static function getPermissionOptions(): array
    {
        $options = [];
        foreach (self::MODULES as $module => $moduleLabel) {
            foreach (self::OPERATIONS as $operation => $operationLabel) {
                $options[] = [
                    'module' => $module,
                    'operation' => $operation,
                    'module_label' => $moduleLabel,
                    'operation_label' => $operationLabel,
                ];
            }
        }

        return $options;
    }

    public static function isValidPermissionScope(string $module, string $operation): bool
    {
        return isset(self::MODULES[$module]) && isset(self::OPERATIONS[$operation]);
    }

    public static function getPermissionScopeLabel(?string $module, ?string $operation): string
    {
        if ($module === null || $operation === null
            || !self::isValidPermissionScope($module, $operation)) {
            return 'Sin módulo u operación';
        }

        return self::MODULES[$module] . ' — ' . self::OPERATIONS[$operation];
    }

    public static function enforce(string $controller, ?string $routeAction = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $controller = self::normalizeController($controller);
        $action = self::resolveAction($controller, $routeAction);

        if ($controller === 'auth') {
            if (in_array($action, ['login', 'logout', 'recuperar', 'verificarrecuperacion', 'reestablecerrecuperacion'], true)) {
                return;
            }

            self::requireActiveIdentity();
            return;
        }

        $identity = self::requireActiveIdentity();
        $roleName = mb_strtolower(trim((string) $identity['tipo_de_usuario']), 'UTF-8');

        if ($controller === 'permisos' || $controller === 'rol') {
            if (!in_array($roleName, ['administrador', 'superadministrador'], true)) {
                self::deny('Solo un administrador puede gestionar roles y permisos.', 403);
            }
            return;
        }

        if ($controller === 'usuario' && in_array($action, self::SELF_SERVICE_ACTIONS, true)) {
            return;
        }

        $module = self::CONTROLLER_MODULES[$controller] ?? null;
        if ($module === null) {
            self::deny('No existe una regla de acceso para esta sección.', 403);
        }

        foreach (self::resolveOperations($controller, $routeAction) as $operation) {
            if (!self::hasPermission((int) $identity['id_rol'], $module, $operation)) {
                self::deny('Tu rol no tiene asignado el permiso necesario para esta acción.', 403);
            }
        }
    }

    private static function normalizeController(string $controller): string
    {
        $controller = basename(str_replace('\\', '/', $controller));
        $controller = preg_replace('/Controller$/i', '', $controller);
        return strtolower((string) $controller);
    }

    private static function resolveAction(string $controller, ?string $routeAction): string
    {
        if (in_array($controller, self::ROUTE_ACTION_CONTROLLERS, true)) {
            $action = $_GET['action'] ?? $routeAction ?? '';
        } else {
            $action = $_POST['accion'] ?? $_POST['action'] ?? $_GET['accion'] ?? null;
            if ($action === null || $action === '') {
                $action = $_POST['ajax'] ?? $_GET['ajax'] ?? $_GET['action'] ?? $routeAction ?? '';
            }
        }

        return strtolower(trim((string) $action));
    }

    private static function requireActiveIdentity(): array
    {
        $idUsuario = filter_var($_SESSION['usuario'] ?? null, FILTER_VALIDATE_INT);
        if ($idUsuario === false || $idUsuario === null || $idUsuario <= 0) {
            self::deny('Debes iniciar sesión para continuar.', 401);
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "SELECT u.id_usuario, u.id_rol, r.tipo_de_usuario
             FROM usuario u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = :id_usuario
               AND u.status_usuario = 'activo'
               AND r.status_roles = 'activo'
             LIMIT 1"
        );
        $stmt->execute([':id_usuario' => $idUsuario]);
        $identity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$identity) {
            unset($_SESSION['usuario'], $_SESSION['rol'], $_SESSION['nombre_rol'], $_SESSION['nombre_usuario']);
            self::deny('La cuenta o el rol están inactivos. Inicia sesión nuevamente.', 401);
        }

        $_SESSION['rol'] = (int) $identity['id_rol'];
        $_SESSION['nombre_rol'] = (string) $identity['tipo_de_usuario'];

        return $identity;
    }

    private static function resolveOperations(string $controller, ?string $routeAction): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (in_array($controller, self::ROUTE_ACTION_CONTROLLERS, true)) {
            return [
                self::operationFromAction($controller, self::resolveAction($controller, $routeAction))
                    ?? ($method === 'GET' ? 'consultar' : 'gestionar')
            ];
        }

        if ($method === 'POST'
            && in_array($controller, ['empleado', 'materiaprima', 'tipomateriaprima', 'tipopedido'], true)) {
            $bodyOperation = self::resolveBodyOperation($controller);
            if ($bodyOperation !== null) {
                return [$bodyOperation];
            }
        }

        $candidates = [];
        $actions = [
            $_POST['accion'] ?? null,
            $_POST['action'] ?? null,
            $_GET['accion'] ?? null,
            $_GET['ajax'] ?? null,
            $_GET['action'] ?? null,
            $routeAction,
        ];
        foreach ($actions as $action) {
            if ($action === null || $action === '') {
                continue;
            }
            $operation = self::operationFromAction($controller, (string) $action);
            if ($operation !== null) {
                $candidates[] = $operation;
            }
        }

        $bodyOperation = self::resolveBodyOperation($controller);
        if ($bodyOperation !== null && !in_array($bodyOperation, $candidates, true)) {
            $candidates[] = $bodyOperation;
        }

        if ($candidates !== []) {
            $mutations = array_values(array_unique(array_filter(
                $candidates,
                static function (string $operation): bool {
                    return $operation !== 'consultar';
                }
            )));
            return $mutations !== [] ? $mutations : ['consultar'];
        }

        return [$method === 'GET' ? 'consultar' : 'gestionar'];
    }

    private static function operationFromAction(string $controller, string $action): ?string
    {
        $action = preg_replace('/[^a-z0-9]/', '', strtolower($action));

        if (preg_match('/(eliminar|inhabilitar|borrar|delete|quitar)/', $action)) {
            return 'eliminar';
        }
        if (preg_match('/(editar|actualizar|cambiar|asignar|activar|inactivar|reestablecer)/', $action)) {
            return 'editar';
        }
        if (preg_match('/(guardar|registrar|crear|insertar|save)/', $action)) {
            return $controller === 'producto' && self::hasEntityId($controller) ? 'editar' : 'crear';
        }
        if (preg_match('/(listar|obtener|buscar|datos|reporte|verificar|consultar|catalogo|ajax|vista|exist)/', $action)) {
            return 'consultar';
        }

        return null;
    }

    private static function resolveBodyOperation(string $controller): ?string
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return null;
        }

        if (in_array($controller, ['empleado', 'materiaprima', 'tipomateriaprima', 'tipopedido'], true)) {
            if (self::hasEntityId($controller)) {
                return 'editar';
            }

            if (($controller === 'empleado' && isset($_POST['cedula']))
                || ($controller !== 'empleado' && isset($_POST['nombre']))) {
                return 'crear';
            }
        }

        return null;
    }

    private static function hasEntityId(string $controller): bool
    {
        $idFields = [
            'usuario' => ['id_usuario'],
            'cliente' => ['id_cliente'],
            'empleado' => ['id_empleado', 'id_accion'],
            'pedido' => ['id_pedido'],
            'tipopedido' => ['id_tipo_pedido', 'id_accion'],
            'producto' => ['id_producto'],
            'materiaprima' => ['id_materia_prima', 'id_accion'],
            'tipomateriaprima' => ['id_tipo_materia_prima', 'id_accion'],
            'consumomaterial' => ['id_consumo'],
            'perdidamaterial' => ['id_perdida'],
            'servicio' => ['id_servicio'],
            'ordenproduccion' => ['id_produccion'],
            'finanzas' => ['id', 'id_pago', 'id_cuenta', 'id_metodo'],
        ];

        foreach ($idFields[$controller] ?? [] as $field) {
            $value = $_POST[$field] ?? $_GET[$field] ?? null;
            if ($value !== null && $value !== '' && (int) $value > 0) {
                return true;
            }
        }

        return false;
    }

    private static function hasPermission(int $idRol, string $module, string $operation): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM permisos_rol pr
             INNER JOIN permiso p ON p.id_permiso = pr.id_permiso
             WHERE pr.id_rol = :id_rol
               AND p.status_permiso = 'activo'
               AND p.modulo_permiso = :modulo
               AND (p.accion_permiso = :accion OR p.accion_permiso = 'gestionar')
             LIMIT 1"
        );
        $stmt->execute([
            ':id_rol' => $idRol,
            ':modulo' => $module,
            ':accion' => $operation,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private static function deny(string $message, int $status): void
    {
        http_response_code($status);
        $isAjax = isset($_GET['ajax'])
            || stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
            || stripos((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'xmlhttprequest') !== false;

        if ($status === 401 && !$isAjax) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        if ($isAjax || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        $messageJson = json_encode(
            $message,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Acceso denegado</title><script src="assets/libs/sweetalert2.all.min.js"></script></head><body>'
            . '<script>Swal.fire({icon:"error",title:"Acceso denegado",text:' . $messageJson
            . ',confirmButtonText:"Volver al panel",confirmButtonColor:"#198754",allowOutsideClick:false,allowEscapeKey:false})'
            . '.then(function(){window.location.replace("index.php?controller=auth&action=dashboard");});</script>'
            . '<noscript><h1>Acceso denegado</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</p><a href="index.php?controller=auth&action=dashboard">Volver al panel</a></noscript></body></html>';
        exit;
    }
}

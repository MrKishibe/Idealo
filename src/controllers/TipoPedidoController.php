<?php

use Idealo\Models\TipoPedidoModel;

$rutaVista = __DIR__ . '/../view/tipo_pedido/listar.php';

/*
|--------------------------------------------------------------------------
| Función exclusiva para el módulo Tipo Pedido (evita redeclaración)
|--------------------------------------------------------------------------
*/
if (!function_exists('responderJSONTipoPedido')) {
    function responderJSONTipoPedido(array $respuesta): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $respuesta,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CONTROL DE PETICIONES POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {

    /*
    |--------------------------------------------------------------------------
    | Editar registro o cambiar estado
    |--------------------------------------------------------------------------
    */
    if (
        isset($_POST['id_accion']) &&
        isset($_POST['nuevo_estado'])
    ) {
        $id = filter_var(
            $_POST['id_accion'],
            FILTER_VALIDATE_INT
        );

        $nuevoEstado = trim($_POST['nuevo_estado']);

        if ($id === false || $id <= 0) {
            responderJSONTipoPedido([
                'success' => false,
                'message' => 'El identificador del tipo de pedido no es valido.',
                'evento' => 'validacion',
                'estado' => 'error_validacion'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Edición completa
        |--------------------------------------------------------------------------
        */
        if (isset($_POST['nombre'])) {
            $nombre = trim($_POST['nombre']);

            $validacion = TipoPedidoModel::validarDatos(
                $nombre,
                $nuevoEstado,
                $id
            );

            if ($validacion !== true) {
                responderJSONTipoPedido([
                    'success' => false,
                    'message' => $validacion['error'],
                    'evento' => 'editar',
                    'estado' => 'error_validacion'
                ]);
            }

            $resultado = TipoPedidoModel::getActualizarDatos($id);

            if (isset($resultado['exitoso'])) {
                responderJSONTipoPedido([
                    'success' => true,
                    'message' => $resultado['exitoso'],
                    'evento' => 'editar',
                    'estado' => 'completado'
                ]);
            }

            responderJSONTipoPedido([
                'success' => false,
                'message' => $resultado['error'] ?? 'Error interno al actualizar el tipo de pedido.',
                'evento' => 'editar',
                'estado' => 'error'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Cambio rápido de estado
        |--------------------------------------------------------------------------
        */
        $respuesta = TipoPedidoModel::getCambiarEstado(
            $id,
            $nuevoEstado
        );

        if (isset($respuesta['exitoso'])) {
            responderJSONTipoPedido([
                'success' => true,
                'message' => 'Estado actualizado con exito.',
                'evento' => 'cambiar_estado',
                'estado' => 'completado'
            ]);
        }

        responderJSONTipoPedido([
            'success' => false,
            'message' => $respuesta['error'] ?? 'Error al cambiar el estado.',
            'evento' => 'cambiar_estado',
            'estado' => 'error'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar nuevo tipo de pedido
    |--------------------------------------------------------------------------
    */
    if (
        isset($_POST['nombre']) &&
        !isset($_POST['id_accion'])
    ) {
        $nombre = trim($_POST['nombre']);
        $status = 'Activo';

        $validacion = TipoPedidoModel::validarDatos(
            $nombre,
            $status
        );

        if ($validacion !== true) {
            responderJSONTipoPedido([
                'success' => false,
                'message' => $validacion['error'],
                'evento' => 'guardar',
                'estado' => 'error_validacion'
            ]);
        }

        $resultado = TipoPedidoModel::getRegistrarDatos();

        if (isset($resultado['exitoso'])) {
            responderJSONTipoPedido([
                'success' => true,
                'message' => 'Tipo de pedido registrado con exito.',
                'id' => $resultado['id'],
                'nombre' => $nombre,
                'status' => $status,
                'evento' => 'guardar',
                'estado' => 'completado'
            ]);
        }

        responderJSONTipoPedido([
            'success' => false,
            'message' => $resultado['error'] ?? 'Error interno al guardar el tipo de pedido.',
            'evento' => 'guardar',
            'estado' => 'error'
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| Carga AJAX
|--------------------------------------------------------------------------
*/
if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'listar'
) {
    $pedidos = TipoPedidoModel::consultarPedidos();

    if (
        is_array($pedidos) &&
        isset($pedidos['error'])
    ) {
        responderJSONTipoPedido([
            'success' => false,
            'message' => $pedidos['error'],
            'evento' => 'listar',
            'estado' => 'error'
        ]);
    }

    if (!is_array($pedidos)) {
        $pedidos = [];
    }

    $activos = array_filter(
        $pedidos,
        function ($pedido) {
            return (
                ($pedido['status_tipo_servicio'] ?? '') === 'Activo'
            );
        }
    );

    $inactivos = array_filter(
        $pedidos,
        function ($pedido) {
            return (
                ($pedido['status_tipo_servicio'] ?? '') === 'Inactivo'
            );
        }
    );

    responderJSONTipoPedido([
        'success' => true,
        'pedidos' => array_values($pedidos),
        'total' => count($pedidos),
        'activos' => count($activos),
        'inactivos' => count($inactivos),
        'evento' => 'listar',
        'estado' => 'completado'
    ]);
}

/*
|--------------------------------------------------------------------------
| Carga normal de la vista
|--------------------------------------------------------------------------
*/
$pedidos = TipoPedidoModel::consultarPedidos();

if (
    is_array($pedidos) &&
    isset($pedidos['error'])
) {
    die(
        'Error critico de datos: ' .
        htmlspecialchars(
            $pedidos['error'],
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

if (!is_array($pedidos)) {
    $pedidos = [];
}

if (!file_exists($rutaVista)) {
    header('HTTP/1.1 404 Not Found');

    die(
        'Error 404: No existe la vista requerida en: <strong>' .
        htmlspecialchars(
            $rutaVista,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</strong>'
    );
}

require_once $rutaVista;
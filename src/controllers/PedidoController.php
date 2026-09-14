<?php

use Idealo\Models\PedidoModel;



$model = new PedidoModel();

$rutaVista = __DIR__ . '/../view/pedido/listarpedido.php';


/*
|--------------------------------------------------------------------------
| Respuesta JSON
|--------------------------------------------------------------------------
*/
$responderJson = static function (
    array $respuesta,
    int $codigoHttp = 200
): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($codigoHttp);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
};


/*
|--------------------------------------------------------------------------
| Respuesta de error JSON
|--------------------------------------------------------------------------
*/
$responderError = static function (
    Throwable $e,
    string $evento
) use (
    $responderJson
): void {
    $responderJson(
        [
            'success' => false,
            'message' => $e->getMessage(),
            'evento' => $evento
        ],
        400
    );
};


/*
|--------------------------------------------------------------------------
| Obtener texto desde POST
|--------------------------------------------------------------------------
*/
$textoPost = static function (
    string $campo
): string {
    return trim(
        (string) (
            $_POST[$campo] ?? ''
        )
    );
};


/*
|--------------------------------------------------------------------------
| Obtener entero desde POST
|--------------------------------------------------------------------------
*/
$enteroPost = static function (
    string $campo
): int {
    return (int) (
        $_POST[$campo] ?? 0
    );
};


/*
|--------------------------------------------------------------------------
| Obtener decimal desde POST
|--------------------------------------------------------------------------
*/
$decimalPost = static function (
    string $campo
): float {
    return (float) (
        $_POST[$campo] ?? 0
    );
};


/*
|--------------------------------------------------------------------------
| Crear estructura de datos del pedido
|--------------------------------------------------------------------------
*/
$crearDatosPedido = static function () use (
    $textoPost,
    $enteroPost,
    $decimalPost
): array {
    return [
        'pedido' => [
            'fecha_creacion' =>
                $textoPost(
                    'fecha_creacion'
                ),

            'fecha_entrega' =>
                $textoPost(
                    'fecha_entrega'
                ),

            'id_cliente' =>
                $enteroPost(
                    'id_cliente'
                ),

            'id_tipo_pedido' =>
                $enteroPost(
                    'id_tipo_pedido'
                ),

            'descripcion' =>
                $textoPost(
                    'descripcion'
                ),

            'descuento_divisa' =>
                $decimalPost(
                    'descuento_divisa'
                ),

            'estado_pedido' =>
                $textoPost(
                    'estado_pedido'
                )
        ],

        'detalle' => [
            'id_producto_caracteristica' =>
                $enteroPost(
                    'id_producto_caracteristica'
                ),

            'id_servicio' =>
                $enteroPost(
                    'id_servicio'
                ),

            'cantidad' =>
                $enteroPost(
                    'cantidad'
                ),

            'costo_mano_de_obra' =>
                $decimalPost(
                    'costo_mano_de_obra'
                ),

            'costo_materiales' =>
                $decimalPost(
                    'costo_materiales'
                ),

            'descuento_producto' =>
                $decimalPost(
                    'descuento_producto'
                ),

            'metodo_servicio' =>
                $textoPost(
                    'metodo_servicio'
                )
        ]
    ];
};


/*
|--------------------------------------------------------------------------
| AJAX: características según producto
|--------------------------------------------------------------------------
*/
if (
    ($_GET['accion'] ?? '') ===
    'caracteristicas_por_producto'
) {
    try {
        $idProducto = (int) (
            $_GET['id_producto'] ?? 0
        );

        if ($idProducto <= 0) {
            throw new Exception(
                'El producto seleccionado no es válido.'
            );
        }

        $caracteristicas =
            $model->obtenerCaracteristicasPorProducto(
                $idProducto
            );

        $responderJson([
            'success' => true,
            'data' => $caracteristicas
        ]);

    } catch (Throwable $e) {
        $responderError(
            $e,
            'caracteristicas_por_producto'
        );
    }
}


/*
|--------------------------------------------------------------------------
| AJAX: listar pedidos
|--------------------------------------------------------------------------
*/
if (
    ($_GET['accion'] ?? '') ===
    'listar'
) {
    try {
        $filtro = strtolower(
            trim(
                (string) (
                    $_GET['filtro'] ??
                    'activos'
                )
            )
        );

        if (
            !in_array(
                $filtro,
                [
                    'activos',
                    'inhabilitados'
                ],
                true
            )
        ) {
            $filtro = 'activos';
        }

        $pedidos = $model->listarPedidos(
            $filtro
        );

        $responderJson([
            'success' => true,
            'data' => $pedidos
        ]);

    } catch (Throwable $e) {
        $responderError(
            $e,
            'listar'
        );
    }
}


/*
|--------------------------------------------------------------------------
| AJAX: obtener pedido por ID
|--------------------------------------------------------------------------
*/
if (
    ($_GET['accion'] ?? '') ===
    'obtener'
) {
    try {
        $idPedido = (int) (
            $_GET['id'] ?? 0
        );

        if ($idPedido <= 0) {
            throw new Exception(
                'El identificador del pedido no es válido.'
            );
        }

        $pedido = $model->obtenerPedido(
            $idPedido
        );

        if (!$pedido) {
            throw new Exception(
                'El pedido solicitado no existe.'
            );
        }

        $responderJson([
            'success' => true,
            'data' => $pedido
        ]);

    } catch (Throwable $e) {
        $responderError(
            $e,
            'obtener'
        );
    }
}


/*
|--------------------------------------------------------------------------
| POST: registrar, editar e inhabilitar
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim(
        (string) (
            $_POST['accion'] ?? ''
        )
    );

    try {
        if ($accion === 'guardar') {
            $datos = $crearDatosPedido();

            /*
            |--------------------------------------------------------------------------
            | Todo pedido nuevo inicia pendiente
            |--------------------------------------------------------------------------
            */
            $datos['pedido']['estado_pedido'] =
                'pendiente';

            $idPedido = $model->guardarPedido(
                $datos
            );

            $responderJson([
                'success' => true,
                'message' =>
                    'Pedido registrado correctamente.',
                'id_pedido' => $idPedido
            ]);

        } elseif ($accion === 'editar') {
            $datos = $crearDatosPedido();

            $idPedido = $enteroPost(
                'id_pedido'
            );

            if ($idPedido <= 0) {
                throw new Exception(
                    'El identificador del pedido no es válido.'
                );
            }

            $datos['id_pedido'] = $idPedido;

            $model->editarPedido($datos);

            $responderJson([
                'success' => true,
                'message' =>
                    'Pedido actualizado correctamente.'
            ]);

        } elseif ($accion === 'inhabilitar') {
            $idPedido = $enteroPost(
                'id_pedido'
            );

            if ($idPedido <= 0) {
                throw new Exception(
                    'El identificador del pedido no es válido.'
                );
            }

            $model->inhabilitarPedido(
                $idPedido
            );

            $responderJson([
                'success' => true,
                'message' =>
                    'Pedido inhabilitado correctamente.'
            ]);

        } else {
            throw new Exception(
                'La acción solicitada no es válida.'
            );
        }

    } catch (Throwable $e) {
        $responderError(
            $e,
            $accion !== '' ? $accion : 'post'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Cargar la vista principal
|--------------------------------------------------------------------------
*/
if (!file_exists($rutaVista)) {
    http_response_code(404);

    die(
        'No existe la vista de gestión de pedidos.'
    );
}

try {
    $clientes = $model->obtenerClientes();

    $tiposPedido = $model->obtenerTiposPedido();

    $productos = $model->obtenerProductosActivos();

    $servicios = $model->obtenerServicios();

    require_once $rutaVista;

} catch (Throwable $e) {
    http_response_code(500);

    die(
        'Error al cargar el módulo de pedidos: ' .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}
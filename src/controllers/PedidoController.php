<?php

use Idealo\Models\PedidoModel;

$model = new PedidoModel();
$rutaVista = __DIR__ . '/../view/pedido/listarpedido.php';

/*
|--------------------------------------------------------------------------
| Sesión (necesaria para el usuario del reporte)
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Respuesta JSON estándar
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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
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
| Utilidades de entrada
|--------------------------------------------------------------------------
*/
$textoPost = static function (string $campo): string {
    return trim((string) ($_POST[$campo] ?? ''));
};

$enteroPost = static function (string $campo): int {
    return (int) ($_POST[$campo] ?? 0);
};

$decimalPost = static function (string $campo): float {
    return (float) ($_POST[$campo] ?? 0);
};

/*
|--------------------------------------------------------------------------
| Normalización de texto para búsquedas (sin acentos, minúsculas)
|--------------------------------------------------------------------------
*/
$normalizar = static function (string $texto): string {
    $texto = mb_strtolower(trim($texto), 'UTF-8');

    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u'
    ]);
};

$crearDatosPedido = static function () use (
    $textoPost,
    $enteroPost,
    $decimalPost
): array {
    return [
        'pedido' => [
            'fecha_creacion' => $textoPost('fecha_creacion'),
            'fecha_entrega' => $textoPost('fecha_entrega'),
            'id_cliente' => $enteroPost('id_cliente'),
            'id_tipo_pedido' => $enteroPost('id_tipo_pedido'),
            'descripcion' => $textoPost('descripcion'),
            'descuento_divisa' => $decimalPost('descuento_divisa'),
            'estado_pedido' => $textoPost('estado_pedido')
        ],
        'detalle' => [
            'id_producto_caracteristica' => $enteroPost('id_producto_caracteristica'),
            'id_servicio' => $enteroPost('id_servicio'),
            'cantidad' => $enteroPost('cantidad'),
            'costo_mano_de_obra' => $decimalPost('costo_mano_de_obra'),
            'costo_materiales' => $decimalPost('costo_materiales'),
            'descuento_producto' => $decimalPost('descuento_producto'),
            'metodo_servicio' => $textoPost('metodo_servicio')
        ]
    ];
};

/*
|--------------------------------------------------------------------------
| AJAX: Buscar clientes (autocompletado Select2)
|--------------------------------------------------------------------------
| Se apoya en obtenerClientes() y filtra en PHP, así no hay que tocar el
| modelo. Si algún día la tabla crece demasiado, muévelo a SQL.
*/
if (($_GET['accion'] ?? '') === 'buscar_clientes') {
    try {
        $termino = $normalizar((string) ($_GET['q'] ?? ''));
        $pagina = max(1, (int) ($_GET['page'] ?? 1));
        $porPagina = 20;

        $clientes = $model->obtenerClientes();
        $coincidencias = [];

        foreach ($clientes as $cliente) {
            $nombreCompleto = trim(
                ($cliente['nombre_razon_social'] ?? '') . ' ' . ($cliente['apellido'] ?? '')
            );
            $documento = (string) ($cliente['numero_de_documento'] ?? '');

            $texto = $normalizar($nombreCompleto . ' ' . $documento);

            if ($termino === '' || mb_strpos($texto, $termino) !== false) {
                $coincidencias[] = [
                    'id_cliente' => (int) $cliente['id_cliente'],
                    'nombre_razon_social' => $cliente['nombre_razon_social'] ?? '',
                    'apellido' => $cliente['apellido'] ?? '',
                    'numero_de_documento' => $documento
                ];
            }
        }

        $total = count($coincidencias);
        $desde = ($pagina - 1) * $porPagina;
        $datos = array_slice($coincidencias, $desde, $porPagina);

        $responderJson([
            'success' => true,
            'data' => $datos,
            'total' => $total,
            'more' => ($desde + $porPagina) < $total
        ]);
    } catch (Throwable $e) {
        $responderError($e, 'buscar_clientes');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener características según el producto seleccionado
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'caracteristicas_por_producto') {
    try {
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        if ($idProducto <= 0) throw new Exception('El producto seleccionado no es válido.');
        $caracteristicas = $model->obtenerCaracteristicasPorProducto($idProducto);
        $responderJson(['success' => true, 'data' => $caracteristicas]);
    } catch (Throwable $e) {
        $responderError($e, 'caracteristicas_por_producto');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener colores según el producto seleccionado
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'colores_por_producto') {
    try {
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        if ($idProducto <= 0) throw new Exception('El producto seleccionado no es válido.');

        $colores = $model->obtenerColoresPorProducto($idProducto);

        $responderJson(['success' => true, 'data' => $colores]);
    } catch (Throwable $e) {
        $responderError($e, 'colores_por_producto');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener tallas según el producto y color seleccionados
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'tallas_por_producto_y_color') {
    try {
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        $color = trim((string) ($_GET['color'] ?? ''));
        if ($idProducto <= 0 || $color === '') throw new Exception('Parámetros no válidos para cargar las tallas.');

        $tallas = $model->obtenerTallasPorProductoYColor($idProducto, $color);

        $responderJson(['success' => true, 'data' => $tallas]);
    } catch (Throwable $e) {
        $responderError($e, 'tallas_por_producto_y_color');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Listar pedidos (activos o inhabilitados)
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'listar') {
    try {
        $filtro = strtolower(trim((string) ($_GET['filtro'] ?? 'activos')));
        if (!in_array($filtro, ['activos', 'inhabilitados'], true)) {
            $filtro = 'activos';
        }
        $pedidos = $model->listarPedidos($filtro);
        $responderJson(['success' => true, 'data' => $pedidos]);
    } catch (Throwable $e) {
        $responderError($e, 'listar');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener un pedido específico por ID
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'obtener') {
    try {
        $idPedido = (int) ($_GET['id'] ?? 0);
        if ($idPedido <= 0) throw new Exception('El identificador del pedido no es válido.');
        $pedido = $model->obtenerPedido($idPedido);
        if (!$pedido) throw new Exception('El pedido solicitado no existe.');
        $responderJson(['success' => true, 'data' => $pedido]);
    } catch (Throwable $e) {
        $responderError($e, 'obtener');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Generar Reporte PDF Vertical, Numerado y con Usuario Dinámico
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'generarreportepdf') {
    try {
        $filtros = [
            'reporte_todo'   => trim($_GET['reporte_todo'] ?? ''),
            'fecha_inicio'   => trim($_GET['fecha_inicio'] ?? ''),
            'fecha_fin'      => trim($_GET['fecha_fin'] ?? ''),
            'id_tipo_pedido' => $_GET['id_tipo_pedido'] ?? [],
            'id_servicio'    => $_GET['id_servicio'] ?? [],
            'estado'         => $_GET['estado'] ?? [],
            'cantidad'       => trim($_GET['cantidad'] ?? ''),
            'operador_monto' => trim($_GET['operador_monto'] ?? ''),
            'monto'          => trim($_GET['monto'] ?? ''),
            'ids_filtrados'  => json_decode($_GET['ids_filtrados'] ?? '[]', true),
            'filtro'         => trim($_GET['filtro'] ?? 'activos')
        ];

        $pedidos = $model->listarPedidosParaReporte($filtros);

        // Métricas para las tarjetas de resumen y contadores desglosados
        $totalPedidos = count($pedidos);
        $totalPendientes = 0;
        $totalEnProceso = 0;
        $totalPlanificados = 0;
        $totalEntregadosRealizados = 0;
        $montoTotalAcumulado = 0;

        foreach ($pedidos as $p) {
            $montoTotalAcumulado += (float)$p['monto_total'];
            $est = strtolower(trim($p['estado_pedido'] ?? ''));
            if ($est === 'pendiente') {
                $totalPendientes++;
            } elseif ($est === 'en proceso') {
                $totalEnProceso++;
            } elseif ($est === 'planificado') {
                $totalPlanificados++;
            } elseif ($est === 'entregado' || $est === 'realizado') {
                $totalEntregadosRealizados++;
            }
        }

        // Subtítulo del período seleccionado
        $periodoTexto = (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin']))
            ? "Detalle de transacciones: {$filtros['fecha_inicio']} al {$filtros['fecha_fin']}"
            : "Detalle general de transacciones registradas";

        require_once __DIR__ . '/../helpers/ReportePdf.php';
        $pdf = new \Idealo\Helpers\ReportePdf('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setCompactReportDetails('Reporte de Pedidos', $periodoTexto);
        $pdf->AddPage();
        $pdf->addCompactReportHeader();
        $pdf->addMetricCards([
            ['label' => 'Total:', 'value' => (string) $totalPedidos, 'width' => 34],
            ['label' => 'Pendientes:', 'value' => (string) $totalPendientes, 'width' => 34],
            ['label' => 'En proceso:', 'value' => (string) $totalEnProceso, 'width' => 34],
            ['label' => 'Planificados:', 'value' => (string) $totalPlanificados, 'width' => 34],
            ['label' => 'Completados:', 'value' => (string) $totalEntregadosRealizados, 'width' => 36],
        ]);
        $pdf->addTableHeader([
            ['width' => 25, 'label' => 'Fecha', 'align' => 'C'],
            ['width' => 51, 'label' => 'Cliente'],
            ['width' => 51, 'label' => 'Producto'],
            ['width' => 15, 'label' => 'Cant.', 'align' => 'C'],
            ['width' => 20, 'label' => 'Estado', 'align' => 'C'],
            ['width' => 18, 'label' => 'Total', 'align' => 'R'],
        ]);

        if (empty($pedidos)) {
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 10, 'No se encontraron registros con los parámetros seleccionados.', 1, 1, 'C');
        } else {
            foreach (array_values($pedidos) as $index => $p) {
                $cliente = trim(($p['nombre_razon_social'] ?? '') . ' ' . ($p['apellido'] ?? ''));
                $fechaFormateada = !empty($p['fecha_creacion']) ? date('d/m/Y', strtotime($p['fecha_creacion'])) : '-';
                $estadoLower = strtolower((string) ($p['estado_pedido'] ?? ''));
                if ($estadoLower === 'pendiente') {
                    $colorEstado = [180, 83, 9];
                } elseif ($estadoLower === 'entregado' || $estadoLower === 'realizado') {
                    $colorEstado = [16, 185, 129];
                } else {
                    $colorEstado = [14, 165, 233];
                }

                $pdf->addTableRow([
                    ['width' => 25, 'text' => $fechaFormateada, 'align' => 'C'],
                    ['width' => 51, 'text' => substr($cliente, 0, 28)],
                    ['width' => 51, 'text' => substr((string) ($p['nombre_producto'] ?? 'N/A'), 0, 28)],
                    ['width' => 15, 'text' => (string) ($p['cantidad'] ?? ''), 'align' => 'C'],
                    [
                        'width' => 20,
                        'text' => ucfirst((string) ($p['estado_pedido'] ?? '')),
                        'align' => 'C',
                        'fontStyle' => 'B',
                        'fontSize' => 8.5,
                        'textColor' => $colorEstado,
                    ],
                    [
                        'width' => 18,
                        'text' => number_format((float) ($p['monto_total'] ?? 0), 2),
                        'align' => 'R',
                        'textColor' => [15, 23, 42],
                    ],
                ], $index % 2 !== 0);
            }

        }

        $pdf->Ln(2);
        $pdf->addTotalBanner(
            'Monto Total Acumulado',
            '$ ' . number_format($montoTotalAcumulado, 2, '.', ',')
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        $pdf->Output('reporte de pedidos.pdf', 'I');
        exit;

    } catch (Throwable $e) {
        $responderError($e, 'generarreportepdf');
    }
}

/*
|--------------------------------------------------------------------------
| Control de peticiones POST (Registrar, Editar, Inhabilitar)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? $_POST['action'] ?? ''));
    try {
        if ($accion === 'guardar') {
            $datos = $crearDatosPedido();
            $datos['pedido']['estado_pedido'] = 'pendiente';
            $idPedido = $model->guardarPedido($datos);
            $responderJson(['success' => true, 'message' => 'Pedido registrado correctamente.', 'id_pedido' => $idPedido]);
        } elseif ($accion === 'editar') {
            $datos = $crearDatosPedido();
            $idPedido = $enteroPost('id_pedido');
            if ($idPedido <= 0) throw new Exception('El identificador del pedido no es válido.');
            $datos['id_pedido'] = $idPedido;
            $model->editarPedido($datos);
            $responderJson(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
        } elseif ($accion === 'inhabilitar') {
            $idPedido = $enteroPost('id_pedido');
            if ($idPedido <= 0) throw new Exception('El identificador del pedido no es válido.');
            $model->inhabilitarPedido($idPedido);
            $responderJson(['success' => true, 'message' => 'Pedido inhabilitado correctamente.']);
        } else {
            throw new Exception('La acción solicitada no es válida.');
        }
    } catch (Throwable $e) {
        $responderError($e, $accion !== '' ? $accion : 'post');
    }
}

/*
|--------------------------------------------------------------------------
| Cargar la vista principal del módulo
|--------------------------------------------------------------------------
*/
if (!file_exists($rutaVista)) {
    http_response_code(404);
    die('No existe la vista de gestión de pedidos.');
}

try {
    $clientes = $model->obtenerClientes();
    $tiposPedido = $model->obtenerTiposPedido();
    $productos = $model->obtenerProductosActivos();
    $servicios = $model->obtenerServicios();
    require_once $rutaVista;
} catch (Throwable $e) {
    http_response_code(500);
    die('Error al cargar el módulo de pedidos: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
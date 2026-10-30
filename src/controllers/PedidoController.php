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

$textoPost = static function (string $campo): string {
    return trim((string) ($_POST[$campo] ?? ''));
};

$enteroPost = static function (string $campo): int {
    return (int) ($_POST[$campo] ?? 0);
};

$decimalPost = static function (string $campo): float {
    return (float) ($_POST[$campo] ?? 0);
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
| AJAX: características según producto
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
| AJAX: listar pedidos
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
| AJAX: obtener pedido por ID
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
| AJAX: Generar Reporte PDF con TCPDF
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'generarreportepdf') {
    try {
        $filtros = [
            'reporte_todo'   => trim($_POST['reporte_todo'] ?? ''),
            'fecha_inicio'   => trim($_POST['fecha_inicio'] ?? ''),
            'fecha_fin'      => trim($_POST['fecha_fin'] ?? ''),
            'id_tipo_pedido' => $_POST['id_tipo_pedido'] ?? [], // Recibido como array de checkboxes
            'id_servicio'    => $_POST['id_servicio'] ?? [],    // Recibido como array de checkboxes
            'estado'         => $_POST['estado'] ?? [],         // Recibido como array de checkboxes
            'cantidad'       => trim($_POST['cantidad'] ?? ''),
            'operador_monto' => trim($_POST['operador_monto'] ?? ''),
            'monto'          => trim($_POST['monto'] ?? ''),
            'ids_filtrados'  => json_decode($_POST['ids_filtrados'] ?? '[]', true)
        ];

        $pedidos = $model->listarPedidosParaReporte($filtros);

        // Instanciar TCPDF cargado por Composer (Paisaje 'L', milímetros, A4)
        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Idéalo');
        $pdf->SetTitle('Reporte de Pedidos');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        // Título del reporte
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Idéalo - Reporte de Pedidos', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        // Cabecera de la tabla
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(30, 41, 59);

        $pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Cliente', 1, 0, 'L', true);
        $pdf->Cell(55, 8, 'Producto', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Cant.', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Estado', 1, 0, 'C', true);
        $pdf->Cell(65, 8, 'Total ($)', 1, 1, 'R', true);

        // Filas de datos
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(51, 51, 51);

        $totalGeneral = 0;
        foreach ($pedidos as $p) {
            $totalGeneral += (float)$p['monto_total'];
            $cliente = trim(($p['nombre_razon_social'] ?? '') . ' ' . ($p['apellido'] ?? ''));

            $pdf->Cell(15, 7, '#' . $p['id_pedido'], 1, 0, 'C');
            $pdf->Cell(25, 7, $p['fecha_creacion'], 1, 0, 'C');
            $pdf->Cell(60, 7, substr($cliente, 0, 35), 1, 0, 'L');
            $pdf->Cell(55, 7, substr($p['nombre_producto'] ?? 'N/A', 0, 30), 1, 0, 'L');
            $pdf->Cell(20, 7, $p['cantidad'], 1, 0, 'C');
            $pdf->Cell(35, 7, ucfirst($p['estado_pedido']), 1, 0, 'C');
            $pdf->Cell(65, 7, number_format($p['monto_total'], 2), 1, 1, 'R');
        }

        // Fila de Total Acumulado
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(210, 8, 'Monto Total Acumulado:', 1, 0, 'R');
        $pdf->Cell(65, 8, '$ ' . number_format($totalGeneral, 2), 1, 1, 'R');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        $pdf->Output('reporte_pedidos.pdf', 'I');
        exit;

    } catch (Throwable $e) {
        $responderError($e, 'generarreportepdf');
    }
}

/*
|--------------------------------------------------------------------------
| POST: registrar, editar e inhabilitar
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? ''));
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
| Cargar la vista principal
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
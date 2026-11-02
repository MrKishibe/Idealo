<?php

use Idealo\Models\PedidoModel;

$model = new PedidoModel();
$rutaVista = __DIR__ . '/../view/pedido/listarpedido.php';

/*
| Respuesta JSON estándar
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
| Respuesta de error JSON
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
| AJAX: Obtener características según el producto seleccionado
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
| AJAX: Listar pedidos (activos o inhabilitados)
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
| AJAX: Obtener un pedido específico por ID
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
| AJAX: Generar Reporte PDF con TCPDF
*/
if (($_GET['accion'] ?? '') === 'generarreportepdf') {
    try {
        $filtros = [
            'reporte_todo'   => trim($_GET['reporte_todo'] ?? ''),
            'fecha_inicio'   => trim($_GET['fecha_inicio'] ?? ''),
            'fecha_fin'      => trim($_GET['fecha_fin'] ?? ''),
            'id_tipo_pedido' => $_GET['id_tipo_pedido'] ?? [], // Array de checkboxes o selects
            'id_servicio'    => $_GET['id_servicio'] ?? [],    // Array de checkboxes o selects
            'estado'         => $_GET['estado'] ?? [],         // Array de checkboxes o selects
            'cantidad'       => trim($_GET['cantidad'] ?? ''),
            'operador_monto' => trim($_GET['operador_monto'] ?? ''),
            'monto'          => trim($_GET['monto'] ?? ''),
            'ids_filtrados'  => json_decode($_GET['ids_filtrados'] ?? '[]', true),
            'filtro'         => trim($_GET['filtro'] ?? 'activos')
        ];

        $pedidos = $model->listarPedidosParaReporte($filtros);

        // Instanciar TCPDF en formato Horizontal (Landscape 'L')
        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Idéalo');
        $pdf->SetTitle('reporte de pedidos');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // ENCABEZADO: Logotipo pequeño junto al título principal
        $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
        if (file_exists($logoPath)) {
            $pdf->ImageSVG($logoPath, 15, 12, 12, 12);
        }

        // Título alineado al lado del logo
        $pdf->SetXY(30, 13);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(0, 8, 'Idéalo - Reporte de Pedidos', 0, 1, 'L');
        
        // Subtítulo con fecha y hora de generación
        $pdf->SetX(30);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        
        $pdf->Ln(8);

        
        // CABECERA DE LA TABLA
       
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(100, 116, 139);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(15, 9, 'ID', 1, 0, 'C', true);
        $pdf->Cell(30, 9, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(67, 9, 'Cliente', 1, 0, 'L', true);
        $pdf->Cell(55, 9, 'Producto', 1, 0, 'L', true);
        $pdf->Cell(20, 9, 'Cant.', 1, 0, 'C', true);
        $pdf->Cell(33, 9, 'Estado', 1, 0, 'C', true);
        $pdf->Cell(47, 9, 'Total ($)', 1, 1, 'R', true);

        // FILAS DE DATOS
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(30, 30, 30);

        $totalGeneral = 0;
        if (empty($pedidos)) {
            $pdf->Cell(267, 8, 'No se encontraron pedidos con los parámetros seleccionados.', 1, 1, 'C');
        } else {
            foreach ($pedidos as $p) {
                $totalGeneral += (float)$p['monto_total'];
                $cliente = trim(($p['nombre_razon_social'] ?? '') . ' ' . ($p['apellido'] ?? ''));

                $pdf->Cell(15, 8, '#' . $p['id_pedido'], 1, 0, 'C');
                $pdf->Cell(30, 8, $p['fecha_creacion'], 1, 0, 'C');
                $pdf->Cell(67, 8, substr($cliente, 0, 32), 1, 0, 'L');
                $pdf->Cell(55, 8, substr($p['nombre_producto'] ?? 'N/A', 0, 28), 1, 0, 'L');
                $pdf->Cell(20, 8, $p['cantidad'], 1, 0, 'C');
                $pdf->Cell(33, 8, ucfirst($p['estado_pedido']), 1, 0, 'C');
                $pdf->Cell(47, 8, number_format($p['monto_total'], 2), 1, 1, 'R');
            }

            // Fila de Total Acumulado
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(220, 9, 'Monto Total Acumulado:', 1, 0, 'R');
            $pdf->Cell(47, 9, '$ ' . number_format($totalGeneral, 2), 1, 1, 'R');
        }

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
Control de peticiones POST (Registrar, Editar, Inhabilitar)
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
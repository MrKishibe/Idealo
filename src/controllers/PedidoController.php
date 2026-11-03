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

        require_once __DIR__ . '/../helpers/ReportePdf.php';

        $periodo = ($filtros['fecha_inicio'] !== '' ? $filtros['fecha_inicio'] : 'Sin límite')
            . ' - '
            . ($filtros['fecha_fin'] !== '' ? $filtros['fecha_fin'] : 'Sin límite');
        $estadosFiltro = $filtros['estado'];
        $estadosEtiqueta = is_array($estadosFiltro)
            ? implode(', ', array_map('strval', $estadosFiltro))
            : (is_scalar($estadosFiltro) ? (string) $estadosFiltro : '');
        $subtitulo = 'Detalle de pedidos | Período: ' . $periodo
            . ' | Estado: ' . ($estadosEtiqueta !== '' ? $estadosEtiqueta : 'Todos')
            . ' | Vista: ' . $filtros['filtro'];

        // Instanciar TCPDF en formato Horizontal (Landscape 'L')
        $pdf = new \Idealo\Helpers\ReportePdf('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Idéalo');
        $pdf->SetTitle('reporte de pedidos');
        $pdf->setReportDetails('Reporte de Pedidos', $subtitulo);
        $pdf->AddPage();

        $totalGeneral = 0;
        $conteosEstado = [];
        foreach ($pedidos as $p) {
            $estado = strtolower(trim((string) ($p['estado_pedido'] ?? '')));
            $conteosEstado[$estado] = ($conteosEstado[$estado] ?? 0) + 1;
        }
        $pdf->addSummaryCards([
            ['label' => 'Total de pedidos', 'value' => (string) count($pedidos)],
            ['label' => 'Pendientes', 'value' => (string) ($conteosEstado['pendiente'] ?? 0)],
            ['label' => 'Planificados', 'value' => (string) ($conteosEstado['planificado'] ?? 0)]
        ]);

        $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="4" cellspacing="0" width="100%">
                    <thead><tr bgcolor="#174B55">
                        <th width="6%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
                        <th width="11%" align="center"><font color="#FFFFFF"><b>Fecha</b></font></th>
                        <th width="25%" align="left"><font color="#FFFFFF"><b>Cliente</b></font></th>
                        <th width="20%" align="left"><font color="#FFFFFF"><b>Producto</b></font></th>
                        <th width="7%" align="center"><font color="#FFFFFF"><b>Cant.</b></font></th>
                        <th width="13%" align="center"><font color="#FFFFFF"><b>Estado</b></font></th>
                        <th width="18%" align="right"><font color="#FFFFFF"><b>Total ($)</b></font></th>
                    </tr></thead><tbody>';
        if (empty($pedidos)) {
            $html .= '<tr bgcolor="#F6F9FA"><td colspan="7" align="center">No se encontraron pedidos con los parámetros seleccionados.</td></tr>';
        } else {
            foreach (array_values($pedidos) as $index => $p) {
                $totalGeneral += (float)$p['monto_total'];
                $cliente = trim(($p['nombre_razon_social'] ?? '') . ' ' . ($p['apellido'] ?? ''));
                $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';
                $html .= '<tr bgcolor="' . $fondoFila . '">
                    <td width="6%" align="center">#' . htmlspecialchars((string) ($p['id_pedido'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="11%" align="center">' . htmlspecialchars((string) ($p['fecha_creacion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="25%">' . htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="20%">' . htmlspecialchars((string) ($p['nombre_producto'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="7%" align="center">' . htmlspecialchars((string) ($p['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="13%" align="center">' . htmlspecialchars(ucfirst((string) ($p['estado_pedido'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="18%" align="right">$ ' . number_format((float) ($p['monto_total'] ?? 0), 2) . '</td>
                </tr>';
            }
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(2);
        $pdf->addTotalBanner('Monto Total Acumulado', '$ ' . number_format($totalGeneral, 2));

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
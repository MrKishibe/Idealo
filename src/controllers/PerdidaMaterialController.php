<?php
use Idealo\Models\PerdidaMaterialModel;
$model = new PerdidaMaterialModel();
$rutaVista  = __DIR__ . '/../view/perdida_material/perdida_material.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // CASO A: Guardar nueva pérdida de material
    if (isset($_POST["accion"]) && $_POST["accion"] === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $result = $model->guardarPerdidaMaterial($_POST);
            if (!$result) {
                throw new \RuntimeException('No se pudo registrar la pérdida de material. Verifique los datos e intente nuevamente.');
            }
            echo json_encode([
                'success' => true, 
                'message' => 'Pérdida de material registrada con éxito.',
                'evento' => 'guardar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage(),
                'evento' => 'guardar',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    // CASO B: Editar pérdida de material
    if (isset($_POST["accion"]) && $_POST["accion"] === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $result = $model->editarPerdida($_POST);
            if (!$result) {
                throw new \RuntimeException('No se pudo actualizar la pérdida de material. Verifique los datos e intente nuevamente.');
            }
            echo json_encode([
                'success' => true, 
                'message' => 'Pérdida de material actualizada con éxito.',
                'evento' => 'editar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage(),
                'evento' => 'editar',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $perdidas = $model->listarPerdidasMateriales();
    echo json_encode([
        'success' => true,
        'data' => $perdidas
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'reporte') {
    if (ob_get_length()) ob_clean();
    
    require_once __DIR__ . '/../../vendor/autoload.php';

    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';
    $estadoProduccion = $_GET['estado_produccion'] ?? '';
    if (!is_string($fechaDesde) || !is_string($fechaHasta) ||
        !is_string($estadoProduccion)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Los filtros del reporte no son válidos.');
    }

    $fechaDesde = trim($fechaDesde);
    $fechaHasta = trim($fechaHasta);
    $estadoProduccion = trim($estadoProduccion);
    $esFechaValida = static function (string $fecha): bool {
        if ($fecha === '') {
            return true;
        }

        $fechaParseada = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $fechaParseada !== false && $fechaParseada->format('Y-m-d') === $fecha;
    };

    if (!$esFechaValida($fechaDesde) || !$esFechaValida($fechaHasta) ||
        ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El rango de fechas no es válido.');
    }

    $ordenes = $model->obtenerOrdenesProduccion();
    $estadosDisponibles = [
        'planificado' => 'Planificado',
        'en proceso' => 'En Proceso',
        'finalizado' => 'Finalizado',
        'inactiva' => 'Inactiva',
        'en espera' => 'en espera'
    ];
    foreach ($ordenes as $orden) {
        $estado = trim($orden['estado_de_produccion'] ?? '');
        if ($estado !== '') {
            $estadosDisponibles[strtolower($estado)] = $estado;
        }
    }

    $estadoNormalizado = strtolower($estadoProduccion);
    if ($estadoNormalizado !== '' && $estadoNormalizado !== 'activas' &&
        !isset($estadosDisponibles[$estadoNormalizado])) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El estado de producción seleccionado no es válido.');
    }

    $perdidas = array_values(array_filter(
        $model->listarPerdidasMateriales(),
        static function ($perdida) use ($fechaDesde, $fechaHasta, $estadoNormalizado) {
            $fecha = $perdida['fecha_de_registro'] ?? '';
            $estado = strtolower((string) ($perdida['estado_de_produccion'] ?? ''));

            return ($fechaDesde === '' || $fecha >= $fechaDesde)
                && ($fechaHasta === '' || $fecha <= $fechaHasta)
                && ($estadoNormalizado === ''
                    || ($estadoNormalizado === 'activas' ? $estado !== 'inactiva' : $estado === $estadoNormalizado));
        }
    ));

    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idealo 2024');
    $pdf->SetTitle('Reporte de Pérdidas de Material');
    $pdf->SetMargins(12, 15, 12);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    $etiquetaEstado = $estadoNormalizado === ''
        ? 'Todos'
        : ($estadoNormalizado === 'activas' ? 'Activas' : $estadosDisponibles[$estadoNormalizado]);
    $rangoFechas = ($fechaDesde !== '' ? date('d/m/Y', strtotime($fechaDesde)) : 'Sin límite')
        . ' - '
        . ($fechaHasta !== '' ? date('d/m/Y', strtotime($fechaHasta)) : 'Sin límite');

    $html = '
    <h2 style="text-align:center; color:#333;">Reporte de Pérdidas de Material</h2>
    <p><strong>Fecha de registro:</strong> ' . htmlspecialchars($rangoFechas, ENT_QUOTES, 'UTF-8') . '<br>
       <strong>Estado de producción:</strong> ' . htmlspecialchars($etiquetaEstado, ENT_QUOTES, 'UTF-8') . '</p>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; font-family:sans-serif; font-size:10px;">
        <thead>
            <tr style="background-color:#dc3545; color:white; font-weight:bold; text-align:center;">
                <th width="8%">ID</th>
                <th width="9%">Cant.</th>
                <th width="14%">Costo unit.</th>
                <th width="24%">Producción / Pedido</th>
                <th width="27%">Motivo</th>
                <th width="18%">Fecha</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($perdidas)) {
        $html .= '<tr><td colspan="6" style="text-align:center;">No hay pérdidas que coincidan con los filtros seleccionados.</td></tr>';
    } else {
        foreach ($perdidas as $p) {
            $produccionLabel = 'Orden #' . $p['id_produccion'];
            if (!empty($p['descripcion_pedido'])) {
                $produccionLabel .= ' - ' . $p['descripcion_pedido'];
            }
            
            $html .= '<tr style="text-align:center;">
                        <td>' . htmlspecialchars((string) $p['id_perdida_material'], ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars((string) $p['cantidad_perdida'], ENT_QUOTES, 'UTF-8') . '</td>
                        <td>$' . number_format((float) $p['costo_unitario'], 2, '.', ',') . '</td>
                        <td>' . htmlspecialchars($produccionLabel, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($p['motivo'] ?? 'Sin motivo', ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars((string) $p['fecha_de_registro'], ENT_QUOTES, 'UTF-8') . '</td>
                      </tr>';
        }
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Reporte_Perdidas_Material.pdf', 'I');
    exit;
}

// Cargar datos para la vista siempre que se renderice la página
$perdidas = $model->listarPerdidasMateriales();
$ordenes = $model->obtenerOrdenesProduccion();
$estadosProduccion = [
    'planificado' => 'Planificado',
    'en proceso' => 'En Proceso',
    'finalizado' => 'Finalizado',
    'inactiva' => 'Inactiva',
    'en espera' => 'en espera'
];
foreach ($ordenes as $orden) {
    $estado = trim($orden['estado_de_produccion'] ?? '');
    if ($estado !== '') {
        $estadosProduccion[strtolower($estado)] = $estado;
    }
}
require_once $rutaVista;
?>
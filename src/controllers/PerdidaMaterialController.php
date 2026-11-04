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

    $consumos = $model->obtenerConsumosMaterial();
    $estadosDisponibles = [
        'planificado' => 'Planificado',
        'en proceso' => 'En Proceso',
        'finalizado' => 'Finalizado',
        'inactiva' => 'Inactiva',
        'en espera' => 'en espera'
    ];
    foreach ($consumos as $consumo) {
        $estado = trim($consumo['estado_de_produccion'] ?? '');
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

    require_once __DIR__ . '/../helpers/ReportePdf.php';

    // Instancia de la clase unificada ReportePdf (genera el reporte vertical corporativo de forma automática)
    $pdf = new \Idealo\Helpers\ReportePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idealo');
    $pdf->SetTitle('Reporte de Pérdidas de Material');

    $etiquetaEstado = $estadoNormalizado === ''
        ? 'Todos'
        : ($estadoNormalizado === 'activas' ? 'Activas' : $estadosDisponibles[$estadoNormalizado]);
    $rangoFechas = ($fechaDesde !== '' ? date('d/m/Y', strtotime($fechaDesde)) : 'Sin límite')
        . ' - '
        . ($fechaHasta !== '' ? date('d/m/Y', strtotime($fechaHasta)) : 'Sin límite');

    $pdf->setReportDetails(
        'Reporte de Pérdidas de Material',
        'Detalle de pérdidas | Registro: ' . $rangoFechas . ' | Estado de producción: ' . $etiquetaEstado
    );
    $pdf->AddPage();

    $montoTotalPerdidas = array_sum(array_map(
        static function ($perdida) {
            return (float) ($perdida['costo_total_perdida'] ?? 0);
        },
        $perdidas
    ));

    // Tarjetas de métricas superiores (KPIs) adaptadas al ancho vertical
    $pdf->addSummaryCards([
        ['label' => 'Total de registros', 'value' => (string) count($perdidas)],
        ['label' => 'Estado aplicado', 'value' => $etiquetaEstado],
        ['label' => 'Costo acumulado', 'value' => '$ ' . number_format($montoTotalPerdidas, 2, '.', ',')]
    ]);

    // Tabla con proporciones calculadas para sumar exactamente el 100% en formato vertical (Carta)
    $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="4" cellspacing="0" width="100%">
        <thead><tr bgcolor="#1e293b">
            <th width="8%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
            <th width="12%" align="right"><font color="#FFFFFF"><b>Cant.</b></font></th>
            <th width="16%" align="right"><font color="#FFFFFF"><b>Costo total</b></font></th>
            <th width="20%" align="left"><font color="#FFFFFF"><b>Materia prima</b></font></th>
            <th width="24%" align="left"><font color="#FFFFFF"><b>Motivo</b></font></th>
            <th width="20%" align="center"><font color="#FFFFFF"><b>Fecha</b></font></th>
        </tr></thead><tbody>';

    if (empty($perdidas)) {
        $html .= '<tr bgcolor="#F8FAFC"><td colspan="6" align="center">No hay pérdidas que coincidan con los filtros seleccionados.</td></tr>';
    } else {
        foreach (array_values($perdidas) as $index => $p) {
            $materiaPrimaLabel = $p['nombre_materia_prima'] ?? 'Materia prima';
            if (!empty($p['unidad_de_medida'])) {
                $materiaPrimaLabel .= ' (' . $p['unidad_de_medida'] . ')';
            }
            $fechaRegistro = !empty($p['fecha_de_registro'])
                ? date('d/m/Y', strtotime($p['fecha_de_registro']))
                : 'N/A';
            $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F8FAFC';
            
            $html .= '<tr bgcolor="' . $fondoFila . '">
                        <td width="8%" align="center">' . htmlspecialchars((string) ($p['id_perdida_material'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="12%" align="right">' . htmlspecialchars((string) ($p['cantidad_perdida'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="16%" align="right">$ ' . number_format((float) ($p['costo_total_perdida'] ?? 0), 2, '.', ',') . '</td>
                        <td width="20%">' . htmlspecialchars($materiaPrimaLabel, ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="24%">' . htmlspecialchars($p['motivo'] ?? 'Sin motivo', ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="20%" align="center">' . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . '</td>
                      </tr>';
        }
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(2);
    // Banner inferior de total acumulado corporativo en verde claro
    $pdf->addTotalBanner('Monto Total Acumulado', '$ ' . number_format($montoTotalPerdidas, 2, '.', ','));
    $pdf->Output('Reporte_Perdidas_Material.pdf', 'I');
    exit;
}

// Cargar datos para la vista siempre que se renderice la página
$perdidas = $model->listarPerdidasMateriales();
$consumos = $model->obtenerConsumosMaterial();
$ordenesPorId = [];
$materiasPrimasPorId = [];
foreach ($consumos as $consumo) {
    $idProduccion = (string) $consumo['id_produccion'];
    if (!isset($ordenesPorId[$idProduccion])) {
        $ordenesPorId[$idProduccion] = [
            'id_produccion' => $consumo['id_produccion'],
            'estado_de_produccion' => $consumo['estado_de_produccion'],
            'descripcion_pedido' => $consumo['descripcion_pedido']
        ];
    }

    $idMateriaPrima = (string) $consumo['id_materia_prima'];
    if (!isset($materiasPrimasPorId[$idMateriaPrima])) {
        $materiasPrimasPorId[$idMateriaPrima] = [
            'id_materia_prima' => $consumo['id_materia_prima'],
            'nombre_materia_prima' => $consumo['nombre_materia_prima'],
            'unidad_de_medida' => $consumo['unidad_de_medida']
        ];
    }
}
$ordenes = array_values($ordenesPorId);
$materiasPrimas = array_values($materiasPrimasPorId);
$consumosJson = htmlspecialchars(
    json_encode(
        $consumos,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    ),
    ENT_QUOTES,
    'UTF-8'
);
$estadosProduccion = [
    'planificado' => 'Planificado',
    'en proceso' => 'En Proceso',
    'finalizado' => 'Finalizado',
    'inactiva' => 'Inactiva',
    'en espera' => 'en espera'
];
foreach ($consumos as $consumo) {
    $estado = trim($consumo['estado_de_produccion'] ?? '');
    if ($estado !== '') {
        $estadosProduccion[strtolower($estado)] = $estado;
    }
}
require_once $rutaVista;
?>
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

    $pdf = new class('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false) extends \TCPDF {
        public function Footer(): void
        {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 8.5);
            $this->SetTextColor(100, 116, 139);
            $this->SetDrawColor(203, 213, 225);
            $this->SetLineWidth(0.2);
            $this->Line(15, $this->GetY(), 195, $this->GetY());
            $this->Ln(2);
            $this->Cell(95, 5, 'Idéalo - Sistema de Gestión | Impreso el ' . date('d/m/Y H:i'), 0, 0, 'L');
            $this->Cell(85, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    };
    $pdf->SetCreator('Idealo');
    $pdf->SetTitle('Reporte de Pérdidas de Material');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(15, 12, 15);
    $pdf->SetAutoPageBreak(true, 18);

    $etiquetaEstado = $estadoNormalizado === ''
        ? 'Todos'
        : ($estadoNormalizado === 'activas' ? 'Activas' : $estadosDisponibles[$estadoNormalizado]);
    $rangoFechas = ($fechaDesde !== '' ? date('d/m/Y', strtotime($fechaDesde)) : 'Sin límite')
        . ' al '
        . ($fechaHasta !== '' ? date('d/m/Y', strtotime($fechaHasta)) : 'Sin límite');

    $pdf->AddPage();

    $montoTotalPerdidas = array_sum(array_map(
        static function ($perdida) {
            return (float) ($perdida['costo_total_perdida'] ?? 0);
        },
        $perdidas
    ));

    $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
    if (file_exists($logoPath)) {
        $pdf->ImageSVG($logoPath, 15, 10, 14, 14);
    }

    $pdf->SetXY(32, 10);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(100, 7, 'Idéalo  -  Reporte de Pérdidas', 0, 0, 'L');

    $usuarioActual = $_SESSION['nombre_usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? 'Administrador';
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY(135, 11);
    $pdf->Cell(60, 7, 'Usuario: ' . $usuarioActual, 0, 1, 'R');

    $pdf->SetXY(32, 17);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(0, 5, 'Periodo: ' . $rangoFechas . ' | Estado de producción: ' . $etiquetaEstado, 0, 1, 'L');
    $pdf->Ln(4);

    $yCard = $pdf->GetY();
    $pdf->SetLineWidth(0.3);

    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->Rect(15, $yCard, 57, 14, 'DF');
    $pdf->SetXY(17, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(25, 5, 'Registros:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(28, 5, (string) count($perdidas), 0, 1, 'R');

    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(187, 247, 208);
    $pdf->Rect(74, $yCard, 57, 14, 'DF');
    $pdf->SetXY(76, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(25, 5, 'Acumulado:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(20, 83, 45);
    $pdf->Cell(28, 5, '$ ' . number_format($montoTotalPerdidas, 2, '.', ','), 0, 1, 'R');

    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->Rect(133, $yCard, 62, 14, 'DF');
    $pdf->SetXY(135, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(20, 5, 'Estado:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(51, 65, 85);
    $pdf->Cell(38, 5, substr($etiquetaEstado, 0, 18), 0, 1, 'R');

    $pdf->Ln(18);

    $html = '<table cellpadding="2.5" cellspacing="0" width="100%">
        <thead><tr bgcolor="#1E293B">
            <th width="7%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
            <th width="10%" align="center"><font color="#FFFFFF"><b>Cant.</b></font></th>
            <th width="16%" align="right"><font color="#FFFFFF"><b>Costo total</b></font></th>
            <th width="22%" align="left"><font color="#FFFFFF"><b>Materia prima</b></font></th>
            <th width="29%" align="left"><font color="#FFFFFF"><b>Motivo</b></font></th>
            <th width="16%" align="center"><font color="#FFFFFF"><b>Fecha</b></font></th>
        </tr></thead><tbody>';

    if (empty($perdidas)) {
        $html .= '<tr bgcolor="#F8FAFC"><td colspan="6" align="center"><font color="#64748B">No hay pérdidas que coincidan con los filtros seleccionados.</font></td></tr>';
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
                <td width="7%" align="center" border="B">' . htmlspecialchars((string) ($p['id_perdida_material'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="10%" align="center" border="B">' . htmlspecialchars((string) ($p['cantidad_perdida'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="16%" align="right" border="B"><font color="#166534">$ ' . number_format((float) ($p['costo_total_perdida'] ?? 0), 2, '.', ',') . '</font></td>
                <td width="22%" border="B">' . htmlspecialchars($materiaPrimaLabel, ENT_QUOTES, 'UTF-8') . '</td>
                <td width="29%" border="B">' . htmlspecialchars($p['motivo'] ?? 'Sin motivo', ENT_QUOTES, 'UTF-8') . '</td>
                <td width="16%" align="center" border="B">' . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>';
        }
    }

    $html .= '</tbody></table>';
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Ln(5);
    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(187, 247, 208);
    $pdf->SetLineWidth(0.4);
    $pdf->SetFont('helvetica', 'B', 10.5);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(135, 10, 'Monto Total Acumulado:', 1, 0, 'R', true);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(20, 83, 45);
    $pdf->Cell(45, 10, '$ ' . number_format($montoTotalPerdidas, 2, '.', ','), 1, 1, 'R', true);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
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
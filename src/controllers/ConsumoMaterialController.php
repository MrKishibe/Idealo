<?php

use Idealo\Models\ConsumoMaterialModel;

$model = new ConsumoMaterialModel();
$rutaVista = __DIR__ . '/../view/Consumo_material/listar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $resultado = $model->guardarConsumoMaterial($_POST);
            if (!$resultado) {
                throw new \RuntimeException('No se pudo registrar el consumo de material. Verifique los datos e intente nuevamente.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Consumo de material registrado con éxito.',
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

    if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $resultado = $model->editarConsumo($_POST);
            if (!$resultado) {
                throw new \RuntimeException('No se pudo actualizar el consumo de material. Verifique los datos e intente nuevamente.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Consumo de material actualizado con éxito.',
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

if (isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $data = $model->listarConsumosMateriales();
        echo json_encode([
            'success' => true,
            'data' => $data,
            'evento' => 'listar',
            'estado' => 'completado'
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'evento' => 'listar',
            'estado' => 'error',
            'validacion' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] === 'obtenerCostoMateriaPrima') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $costoUnitario = $model->obtenerCostoMateriaPrima($_GET['id_materia_prima'] ?? null);
        echo json_encode([
            'success' => true,
            'costo_unitario' => $costoUnitario
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] === 'reporte') {
    if (ob_get_length()) ob_clean();
    
    require_once __DIR__ . '/../../vendor/autoload.php';

    $consumos = $model->listarConsumosMateriales();
    $idMateriaPrima = $_GET['id_materia_prima'] ?? '';
    $estadoProduccion = $_GET['estado_produccion'] ?? '';
    if (!is_string($idMateriaPrima) || !is_string($estadoProduccion)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Los filtros del reporte no son válidos.');
    }

    $idMateriaPrima = trim($idMateriaPrima);
    $estadoProduccion = trim($estadoProduccion);
    $estadoNormalizado = strtolower($estadoProduccion);
    $materiasDisponibles = [];
    $estadosDisponibles = [
        'planificado' => 'Planificado',
        'en proceso' => 'En Proceso',
        'finalizado' => 'Finalizado',
        'inactiva' => 'Inactiva',
        'en espera' => 'en espera'
    ];
    foreach ($consumos as $consumo) {
        $idMateria = (string) ($consumo['id_materia_prima'] ?? '');
        if ($idMateria !== '') {
            $materiasDisponibles[$idMateria] = [
                'nombre' => $consumo['nombre_materia_prima'] ?? 'Materia prima',
                'unidad' => $consumo['unidad_de_medida'] ?? ''
            ];
        }

        $estado = trim($consumo['estado_de_produccion'] ?? '');
        if ($estado !== '') {
            $estadosDisponibles[strtolower($estado)] = $estado;
        }
    }

    if ($idMateriaPrima !== '' &&
        (!ctype_digit($idMateriaPrima) || !isset($materiasDisponibles[$idMateriaPrima]))) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('La materia prima seleccionada no es válida.');
    }
    if ($estadoNormalizado !== '' && $estadoNormalizado !== 'activas' &&
        !isset($estadosDisponibles[$estadoNormalizado])) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El estado de producción seleccionado no es válido.');
    }

    $consumos = array_values(array_filter($consumos, static function ($consumo) use ($idMateriaPrima, $estadoNormalizado) {
        $estado = strtolower((string) ($consumo['estado_de_produccion'] ?? ''));
        return ($idMateriaPrima === '' || (string) ($consumo['id_materia_prima'] ?? '') === $idMateriaPrima)
            && ($estadoNormalizado === ''
                || ($estadoNormalizado === 'activas' ? $estado !== 'inactiva' : $estado === $estadoNormalizado));
    }));

    // Métricas y totales para las tarjetas superiores y resumen
    $totalRegistros = count($consumos);
    $montoTotalAcumulado = 0;
    foreach ($consumos as $c) {
        $montoTotalAcumulado += ((float) ($c['costo_unitario'] ?? 0) * (float) ($c['cantidad_usada'] ?? 0));
    }

    $usuarioActual = $_SESSION['nombre_usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? 'Administrador';

    // Clase extendida de TCPDF para pie de página y paginación en vertical
    class MYPDF extends \TCPDF {
        public function Footer() {
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
    }

    // Orientación VERTICAL ('P')
    $pdf = new MYPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idéalo');
    $pdf->SetAuthor('Idéalo');
    $pdf->SetTitle('Reporte de Consumo de Material');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(15, 12, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    // Encabezado Corporativo y Logo
    $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
    if (file_exists($logoPath)) {
        $pdf->ImageSVG($logoPath, 15, 10, 14, 14);
    }

    $pdf->SetXY(32, 10);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(100, 7, 'Idéalo  -  Reporte de Consumos', 0, 0, 'L');
    
    // Usuario dinámico
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY(135, 11);
    $pdf->Cell(60, 7, 'Usuario: ' . $usuarioActual, 0, 1, 'R');
    
    $pdf->SetXY(32, 17);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(0, 5, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1, 'L');

    $pdf->Ln(4);

    $etiquetaMateria = $idMateriaPrima === ''
        ? 'Todas'
        : $materiasDisponibles[$idMateriaPrima]['nombre'] . ' (' . $materiasDisponibles[$idMateriaPrima]['unidad'] . ')';
    $etiquetaEstado = $estadoNormalizado === ''
        ? 'Todos'
        : ($estadoNormalizado === 'activas' ? 'Activas' : $estadosDisponibles[$estadoNormalizado]);
    if (strtolower($etiquetaEstado) === 'en espera') {
        $etiquetaEstado = 'Pendiente (En espera)';
    }

    // Tarjetas de Métricas Superiores (Ajustadas a formato vertical de 180 mm útil)
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
    $pdf->Cell(28, 5, $totalRegistros, 0, 1, 'R');

    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(187, 247, 208);
    $pdf->Rect(74, $yCard, 57, 14, 'DF');
    $pdf->SetXY(76, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(25, 5, 'Acumulado:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(20, 83, 45);
    $pdf->Cell(28, 5, '$ ' . number_format($montoTotalAcumulado, 2, '.', ','), 0, 1, 'R');

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

    // Cabecera de la Tabla (Suma exacta = 180 mm)
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(30, 41, 59);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(12, 8.5, 'ID', 1, 0, 'C', true);
    $pdf->Cell(45, 8.5, 'Materia Prima', 1, 0, 'L', true);
    $pdf->Cell(25, 8.5, 'Costo Unit.', 1, 0, 'R', true);
    $pdf->Cell(28, 8.5, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(30, 8.5, 'Costo Total', 1, 0, 'R', true);
    $pdf->Cell(40, 8.5, 'Orden / Descripción', 1, 1, 'L', true);

    $pdf->SetFont('helvetica', '', 9);
    $fill = false;

    if (empty($consumos)) {
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(180, 10, 'No hay consumos que coincidan con los filtros seleccionados.', 1, 1, 'C');
    } else {
        foreach ($consumos as $c) {
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(30, 41, 59);

            $costoTotal = (float) $c['costo_unitario'] * (float) $c['cantidad_usada'];
            $ordenLabel = 'OP-' . str_pad((string) $c['id_produccion'], 4, '0', STR_PAD_LEFT);
            if (!empty($c['descripcion_de_consumo'])) {
                $ordenLabel .= ' - ' . $c['descripcion_de_consumo'];
            }

            $pdf->Cell(12, 8, '#' . htmlspecialchars((string) $c['id_consumo_material'], ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
            $pdf->Cell(45, 8, substr(htmlspecialchars($c['nombre_materia_prima'] ?? 'Sin material', ENT_QUOTES, 'UTF-8'), 0, 22), 'B', 0, 'L', true);
            $pdf->Cell(25, 8, '$ ' . number_format((float) $c['costo_unitario'], 2, '.', ','), 'B', 0, 'R', true);
            $pdf->Cell(28, 8, htmlspecialchars((string) $c['cantidad_usada'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($c['unidad_de_medida'] ?? '', ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
            
            // Costo Total en Verde Corporativo
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(22, 101, 52);
            $pdf->Cell(30, 8, '$ ' . number_format($costoTotal, 2, '.', ','), 'B', 0, 'R', true);
            
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(40, 8, substr(htmlspecialchars($ordenLabel, ENT_QUOTES, 'UTF-8'), 0, 25), 'B', 1, 'L', true);

            $fill = !$fill;
        }

        // Bloque Inferior de Monto Total Acumulado en Verde
        $pdf->Ln(5);
        $pdf->SetFillColor(240, 253, 244);
        $pdf->SetDrawColor(187, 247, 208);
        $pdf->SetLineWidth(0.4);
        
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor(22, 101, 52);
        $pdf->Cell(135, 10, 'Monto Total Acumulado:', 1, 0, 'R', true);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(20, 83, 45);
        $pdf->Cell(45, 10, '$ ' . number_format($montoTotalAcumulado, 2, '.', ','), 1, 1, 'R', true);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    $pdf->Output('Reporte_Consumo_Material.pdf', 'I');
    exit;
}

if (!file_exists($rutaVista)) {
    header('HTTP/1.1 404 Not Found');
    die('Error 404: No existe la vista requerida en: <strong>' . htmlspecialchars($rutaVista) . '</strong>');
}

$consumos = $model->listarConsumosMateriales();
$ordenes = $model->obtenerOrdenesProduccion();
$materias = $model->obtenerMateriaPrima();
$materiasReporte = [];
$estadosProduccionReporte = [
    'planificado' => 'Planificado',
    'en proceso' => 'En Proceso',
    'finalizado' => 'Finalizado',
    'inactiva' => 'Inactiva',
    'en espera' => 'en espera'
];
foreach ($consumos as $consumo) {
    $idMateria = (string) ($consumo['id_materia_prima'] ?? '');
    if ($idMateria !== '') {
        $materiasReporte[$idMateria] = [
            'nombre' => $consumo['nombre_materia_prima'] ?? 'Materia prima',
            'unidad' => $consumo['unidad_de_medida'] ?? ''
        ];
    }

    $estado = trim($consumo['estado_de_produccion'] ?? '');
    if ($estado !== '') {
        $estadosProduccionReporte[strtolower($estado)] = $estado;
    }
}

require_once $rutaVista;
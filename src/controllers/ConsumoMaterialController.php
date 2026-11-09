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

    $etiquetaMateria = $idMateriaPrima === ''
        ? 'Todas'
        : $materiasDisponibles[$idMateriaPrima]['nombre'] . ' (' . $materiasDisponibles[$idMateriaPrima]['unidad'] . ')';
    $etiquetaEstado = $estadoNormalizado === ''
        ? 'Todos'
        : ($estadoNormalizado === 'activas' ? 'Activas' : $estadosDisponibles[$estadoNormalizado]);
    if (strtolower($etiquetaEstado) === 'en espera') {
        $etiquetaEstado = 'Pendiente (En espera)';
    }

    require_once __DIR__ . '/../helpers/ReportePdf.php';
    $pdf = new \Idealo\Helpers\ReportePdf('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setCompactReportDetails(
        'Reporte de Consumos',
        'Materia: ' . $etiquetaMateria . ' | Estado: ' . $etiquetaEstado
    );
    $pdf->AddPage();
    $pdf->addCompactReportHeader();
    $pdf->addMetricCards([
        ['label' => 'Registros:', 'value' => (string) $totalRegistros, 'width' => 57],
        ['label' => 'Acumulado:', 'value' => '$ ' . number_format($montoTotalAcumulado, 2, '.', ','), 'width' => 57],
        ['label' => 'Estado:', 'value' => substr($etiquetaEstado, 0, 18), 'width' => 62],
    ]);
    $pdf->addTableHeader([
        ['width' => 50, 'label' => 'Materia Prima'],
        ['width' => 25, 'label' => 'Costo Unit.', 'align' => 'R'],
        ['width' => 28, 'label' => 'Cantidad', 'align' => 'C'],
        ['width' => 30, 'label' => 'Costo Total', 'align' => 'R'],
        ['width' => 47, 'label' => 'Orden / Descripción'],
    ]);

    if (empty($consumos)) {
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(180, 10, 'No hay consumos que coincidan con los filtros seleccionados.', 1, 1, 'C');
    } else {
        foreach (array_values($consumos) as $index => $c) {
            $costoTotal = (float) $c['costo_unitario'] * (float) $c['cantidad_usada'];
            $ordenLabel = 'OP-' . str_pad((string) $c['id_produccion'], 4, '0', STR_PAD_LEFT);
            if (!empty($c['descripcion_de_consumo'])) {
                $ordenLabel .= ' - ' . $c['descripcion_de_consumo'];
            }

            $pdf->addTableRow([
                ['width' => 50, 'text' => substr((string) ($c['nombre_materia_prima'] ?? 'Sin material'), 0, 28)],
                ['width' => 25, 'text' => '$ ' . number_format((float) $c['costo_unitario'], 2, '.', ','), 'align' => 'R'],
                ['width' => 28, 'text' => (string) $c['cantidad_usada'] . ' ' . (string) ($c['unidad_de_medida'] ?? ''), 'align' => 'C'],
                [
                    'width' => 30,
                    'text' => '$ ' . number_format($costoTotal, 2, '.', ','),
                    'align' => 'R',
                    'fontStyle' => 'B',
                    'textColor' => [22, 101, 52],
                ],
                [
                    'width' => 47,
                    'text' => substr($ordenLabel, 0, 30),
                    'fontSize' => 8.5,
                    'textColor' => [71, 85, 105],
                ],
            ], $index % 2 !== 0);
        }

        $pdf->Ln(5);
        $pdf->addTotalBanner('Monto Total Acumulado', '$ ' . number_format($montoTotalAcumulado, 2, '.', ','));
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
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

    require_once __DIR__ . '/../helpers/ReportePdf.php';

    $pdf = new \Idealo\Helpers\ReportePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idealo 2024');
    $pdf->SetTitle('Reporte de Consumos de Material');

    $etiquetaMateria = $idMateriaPrima === ''
        ? 'Todas'
        : $materiasDisponibles[$idMateriaPrima]['nombre'] . ' (' . $materiasDisponibles[$idMateriaPrima]['unidad'] . ')';
    $etiquetaEstado = $estadoNormalizado === ''
        ? 'Todos'
        : ($estadoNormalizado === 'activas' ? 'Activas' : $estadosDisponibles[$estadoNormalizado]);
    if (strtolower($etiquetaEstado) === 'en espera') {
        $etiquetaEstado = 'Pendiente (En espera)';
    }

    $pdf->setReportDetails(
        'Reporte de Consumo de Materiales',
        'Detalle de consumos | Materia prima: ' . $etiquetaMateria . ' | Estado de producción: ' . $etiquetaEstado
    );
    $pdf->AddPage();

    $totalConsumido = 0;
    foreach ($consumos as $consumo) {
        $totalConsumido += (float) $consumo['costo_unitario'] * (float) $consumo['cantidad_usada'];
    }
    $pdf->addSummaryCards([
        ['label' => 'Total de registros', 'value' => (string) count($consumos)],
        ['label' => 'Materia prima', 'value' => $etiquetaMateria],
        ['label' => 'Costo consumido', 'value' => '$ ' . number_format($totalConsumido, 2, '.', ',')]
    ]);

    $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="4" cellspacing="0" width="100%">
        <thead><tr bgcolor="#174B55">
            <th width="7%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
            <th width="21%" align="left"><font color="#FFFFFF"><b>Materia Prima</b></font></th>
            <th width="13%" align="right"><font color="#FFFFFF"><b>Costo Unit.</b></font></th>
            <th width="13%" align="right"><font color="#FFFFFF"><b>Cantidad</b></font></th>
            <th width="14%" align="right"><font color="#FFFFFF"><b>Costo Total</b></font></th>
            <th width="32%" align="left"><font color="#FFFFFF"><b>Orden / Descripción</b></font></th>
        </tr></thead><tbody>';

    if (empty($consumos)) {
        $html .= '<tr bgcolor="#F3F7F8"><td colspan="6" align="center">No hay consumos que coincidan con los filtros seleccionados.</td></tr>';
    } else {
        foreach (array_values($consumos) as $index => $c) {
            $costoTotal = (float) $c['costo_unitario'] * (float) $c['cantidad_usada'];
            $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';

            $ordenLabel = htmlspecialchars('OP-' . str_pad((string) $c['id_produccion'], 4, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8');
            if (!empty($c['descripcion_de_consumo'])) {
                $ordenLabel .= '<br /><font color="#647780">' . htmlspecialchars($c['descripcion_de_consumo'], ENT_QUOTES, 'UTF-8') . '</font>';
            }
            
            $html .= '<tr bgcolor="' . $fondoFila . '">
                        <td width="7%" align="center">' . htmlspecialchars((string) $c['id_consumo_material'], ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="21%">' . htmlspecialchars($c['nombre_materia_prima'] ?? 'Sin material', ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="13%" align="right">$ ' . number_format((float) $c['costo_unitario'], 2, '.', ',') . '</td>
                        <td width="13%" align="right">' . htmlspecialchars((string) $c['cantidad_usada'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($c['unidad_de_medida'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="14%" align="right"><font color="#267348"><b>$ ' . number_format($costoTotal, 2, '.', ',') . '</b></font></td>
                        <td width="32%">' . $ordenLabel . '</td>
                      </tr>';
        }
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(2);
    $pdf->addTotalBanner('Monto Total Acumulado', '$ ' . number_format($totalConsumido, 2, '.', ','));
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

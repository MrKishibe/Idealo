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

if (isset($_GET['accion']) && $_GET['accion'] === 'reporte') {
    if (ob_get_length()) ob_clean();
    
    // Asegúrate de que esta ruta apunte correctamente a tu librería TCPDF
   require_once __DIR__ . '/../../vendor/autoload.php';

    $consumos = $model->listarConsumosMateriales();

    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idealo 2024');
    $pdf->SetTitle('Reporte de Consumos de Material');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    // Estructura y estilos de la tabla
    $html = '
    <h2 style="text-align:center; color:#333;">Reporte de Consumo de Materiales</h2>
    <br><br>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; font-family:sans-serif; font-size:10px;">
        <thead>
            <tr style="background-color:#0d6efd; color:white; font-weight:bold; text-align:center;">
                <th width="8%">ID</th>
                <th width="22%">Materia Prima</th>
                <th width="15%">Costo Unit.</th>
                <th width="15%">Cantidad</th>
                <th width="15%">Costo Total</th>
                <th width="25%">Orden / Descripción</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($consumos)) {
        $html .= '<tr><td colspan="6" style="text-align:center;">No hay consumos registrados.</td></tr>';
    } else {
        foreach ($consumos as $c) {
            // Cálculo del costo total como lo haces en tu JS
            $costoTotal = floatval($c['costo_unitario']) * floatval($c['cantidad_usada']);
            
            $ordenLabel = 'OP-' . str_pad($c['id_produccion'], 4, '0', STR_PAD_LEFT);
            if (!empty($c['descripcion_de_consumo'])) {
                $ordenLabel .= '<br><span style="font-size:8px; color:#555;">' . $c['descripcion_de_consumo'] . '</span>';
            }
            
            $html .= '<tr style="text-align:center;">
                        <td>'.$c['id_consumo_material'].'</td>
                        <td><strong>'.$c['nombre_materia_prima'].'</strong></td>
                        <td>$'.number_format((float)$c['costo_unitario'], 2, '.', ',').'</td>
                        <td>'.$c['cantidad_usada'].' '.$c['unidad_de_medida'].'</td>
                        <td style="color:green; font-weight:bold;">$'.number_format($costoTotal, 2, '.', ',').'</td>
                        <td>'.$ordenLabel.'</td>
                      </tr>';
        }
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
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

require_once $rutaVista;

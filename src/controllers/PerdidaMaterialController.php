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
                'message' => '✅ Pérdida de material registrada con éxito.',
                'evento' => 'guardar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
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
                'message' => '✅ Pérdida de material actualizada con éxito.',
                'evento' => 'editar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
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
    
    // IMPORTANTE: Ajusta esta ruta a donde tengas ubicada tu librería TCPDF
    require_once __DIR__ . '/../../vendor/autoload.php';

    $perdidas = $model->listarPerdidasMateriales();

    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idealo 2024');
    $pdf->SetTitle('Reporte de Pérdidas de Material');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    // Estilos de la tabla
    $html = '
    <h2 style="text-align:center; color:#333;">Reporte de Pérdidas de Material</h2>
    <br><br>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; font-family:sans-serif; font-size:10px;">
        <thead>
            <tr style="background-color:#dc3545; color:white; font-weight:bold; text-align:center;">
                <th width="8%">ID</th>
                <th width="10%">Cant.</th>
                <th width="15%">Costo Unit.</th>
                <th width="25%">Producción / Pedido</th>
                <th width="25%">Motivo / Material</th>
                <th width="17%">Fecha</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($perdidas)) {
        $html .= '<tr><td colspan="6" style="text-align:center;">No hay pérdidas registradas.</td></tr>';
    } else {
        foreach ($perdidas as $p) {
            // Reutilizamos tu lógica para nombrar el pedido
            $produccionLabel = 'Orden #' . $p['id_produccion'];
            if (!empty($p['descripcion_pedido'])) {
                $produccionLabel = $p['descripcion_pedido'];
            }
            
            $html .= '<tr style="text-align:center;">
                        <td>'.$p['id_perdida_material'].'</td>
                        <td>'.$p['cantidad_perdida'].'</td>
                        <td>$'.number_format((float)$p['costo_unitario'], 2, '.', ',').'</td>
                        <td>'.$produccionLabel.'</td>
                        <td>'.$p['motivo'].'</td>
                        <td>'.$p['fecha_de_registro'].'</td>
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
require_once $rutaVista;
?>
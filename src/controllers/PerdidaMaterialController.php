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

// Cargar datos para la vista siempre que se renderice la página
$perdidas = $model->listarPerdidasMateriales();
$ordenes = $model->obtenerOrdenesProduccion();
require_once $rutaVista;
?>
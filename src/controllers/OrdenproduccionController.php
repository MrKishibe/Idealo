<?php
use Idealo\Models\OrdenDeProduccionModel;
$model = new OrdenDeProduccionModel();
$rutaVista  = __DIR__ . '/../view/orden_produccion/listarordenproduccion.php';


// ==========================================
// 2. CONTROL DE PETICIONES POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // CASO A: Guardar nueva orden de producción
    if (isset($_POST["accion"]) && $_POST["accion"] === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->guardarOrden($_POST);
            echo json_encode([
                'success' => true, 
                'message' => '✅ Orden de producción registrada con éxito.',
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
    
    // CASO B: Editar orden de producción
    if (isset($_POST["accion"]) && $_POST["accion"] === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->editarOrden($_POST);
            echo json_encode([
                'success' => true, 
                'message' => '✅ Orden de producción actualizada con éxito.',
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

    if (isset($_GET["accion"]) && $_GET["accion"] === "eliminar" && isset($_GET["id"])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->inactivarOrden($_GET["id"]);
            echo json_encode([
                'success' => true, 
                'message' => '✅ Orden de producción inactivada con éxito.',
                'evento' => 'inactivar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
                'evento' => 'inactivar',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if (isset($_GET["accion"]) && $_GET["accion"] === "listar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $model->listarOrdenProduccion();
            echo json_encode([
                'success' => true, 
                'data' => $data,
                'evento' => 'listar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
                'evento' => 'listar',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}
$detallesPedido = $model->obtenerPedidosParaProduccion();

require_once $rutaVista;

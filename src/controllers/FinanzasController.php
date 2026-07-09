<?php
// FinanzasController.php - Procedimental con validación estricta y eventos (Estilo Cliente)

use Idealo\Models\ControlPagosModel;
use Idealo\Models\CuentaEmpresaModel;
use Idealo\Models\MetodoPagoModel;



$pagosModel = new ControlPagosModel();
$cuentasModel = new CuentaEmpresaModel();
$metodosModel = new MetodoPagoModel();

$action = $_GET['action'] ?? 'pagos'; 

//
//  CONTROL DE PETICIONES POST (CRUD)
// 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    $accion = $_POST['accion'] ?? '';
    $entidad = $_POST['entidad'] ?? ''; // 'cuenta', 'pago', 'metodo'

    // Seleccionar el modelo correspondiente de forma dinámica
    $modelo = match($entidad) {
        'cuenta' => $cuentasModel,
        'pago'   => $pagosModel,
        'metodo' => $metodosModel,
        default  => null
    };

    if (!$modelo) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => '❌ Entidad no válida o no especificada.',
            'evento' => $accion,
            'estado' => 'error'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Guardar registro
    if ($accion === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $metodoGuardar = "guardar" . ucfirst($entidad); // Ej: guardarCuenta, guardarPago
            if (method_exists($modelo, $metodoGuardar)) {
                $modelo->$metodoGuardar($_POST);
                echo json_encode([
                    'success' => true, 
                    'message' => '✅ Registro guardado con éxito.',
                    'evento' => 'guardar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de guardado no implementado para esta entidad.");
            }
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
    
    // Editar registro
    if ($accion === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $metodoEditar = "editar" . ucfirst($entidad); // Ej: editarCuenta, editarPago
            if (method_exists($modelo, $metodoEditar)) {
                $modelo->$metodoEditar($_POST);
                echo json_encode([
                    'success' => true, 
                    'message' => '✅ Registro actualizado con éxito.',
                    'evento' => 'editar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de edición no implementado para esta entidad.");
            }
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

    //  Cambiar estado (Inhabilitar / Activar)
    if ($accion === "cambiar_estado") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = $_POST['id'] ?? null;
            $nuevoEstado = $_POST['nuevo_estado'] ?? 'inhabilitado'; // Asume 'inhabilitado' por defecto
            
            if (!$id) throw new \Exception("ID de registro no proporcionado.");

            if (method_exists($modelo, 'actualizarEstado')) {
                $modelo->actualizarEstado($id, $nuevoEstado);
                $mensaje = ($nuevoEstado === 'inhabilitado') ? '✅ Registro inhabilitado correctamente.' : '✅ Registro activado correctamente.';
                
                echo json_encode([
                    'success' => true, 
                    'message' => $mensaje,
                    'evento' => 'cambiar_estado',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de actualización de estado no implementado.");
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
                'evento' => 'cambiar_estado',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    exit;
}

//
// 3. CONTROL DE PETICIONES GET (CARGA DE DATOS)
// 
switch ($action) {
    case 'pagos':
        $pagos = $pagosModel->listarPagos();
        $pedidos = $pagosModel->obtenerPedidosActivos();
        $metodos = $pagosModel->obtenerMetodosPago();
        break;
    case 'cuentas':
        $cuentas = $cuentasModel->listarCuentas();
        $metodos = $cuentasModel->obtenerMetodosPago();
        break;
    case 'metodos':
        $metodos = $metodosModel->listarMetodos();
        break;
}

// 
// 4. VERIFICACIÓN Y CARGA DE LA VISTA
// 
$rutaVista = __DIR__ . '/../view/finanzas/' . $action . '.php';

if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;
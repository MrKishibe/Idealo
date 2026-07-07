<?php
use Idealo\Models\ControlPagosModel;
use Idealo\Models\CuentaEmpresaModel;
use Idealo\Models\MetodoPagoModel;

require_once __DIR__ . '/../models/ControlPagosModel.php';
require_once __DIR__ . '/../models/CuentaEmpresaModel.php';
require_once __DIR__ . '/../models/MetodoPagoModel.php';

$pagosModel = new ControlPagosModel();
$cuentasModel = new CuentaEmpresaModel();
$metodosModel = new MetodoPagoModel();

$action = $_GET['action'] ?? 'pagos';

// ==========================================
// CENTRALIZADOR DE ACCIONES (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $accion = $_POST['accion'] ?? '';
        $entidad = $_POST['entidad'] ?? '';
        $id = $_POST['id'] ?? null;
        $resultado = false;

        // 1. CAMBIAR ESTADO
        if ($accion === "cambiar_estado") {
            $nuevoEstado = $_POST['nuevo_estado'];
            $modelo = match($entidad) {
                'cuenta' => $cuentasModel,
                'pago'   => $pagosModel,
                'metodo' => $metodosModel,
                default  => null
            };

            if ($modelo && method_exists($modelo, 'actualizarEstado')) {
                $resultado = $modelo->actualizarEstado($id, $nuevoEstado);
            }
            
            echo json_encode(['success' => (bool)$resultado, 'message' => $resultado ? 'Estado actualizado correctamente.' : 'Error al actualizar el estado.']);
            exit;
        }

        // 2. GUARDAR O EDITAR
        if ($accion === "guardar" || $accion === "editar") {
            $modelo = match($entidad) {
                'cuenta' => $cuentasModel,
                'pago'   => $pagosModel,
                'metodo' => $metodosModel,
                default  => null
            };

            $metodoAccion = ($accion === "guardar") ? "guardar" . ucfirst($entidad) : "editar" . ucfirst($entidad);
            
            // Verificación dinámica del método en el modelo
            if ($modelo && method_exists($modelo, $metodoAccion)) {
                $resultado = $modelo->$metodoAccion($_POST);
            }

            echo json_encode(['success' => (bool)$resultado, 'message' => $resultado ? 'Operación exitosa.' : 'Error al procesar el registro.']);
            exit;
        }

    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => '❌ Error interno: ' . $e->getMessage()]);
        exit;
    }
}

// ==========================================
// CARGA DE DATOS (GET)
// ==========================================
$datos = [];
switch ($action) {
    case 'pagos':
        $pagos = $pagosModel->listarTodos();
        $pedidos = $pagosModel->obtenerPedidosActivos();
        $metodos = $pagosModel->obtenerMetodosPago();
        break;
    case 'cuentas':
        $cuentas = $cuentasModel->listarCuentas();
        $metodos = $cuentasModel->obtenerMetodosPago();
        break;
    case 'metodos':
        $metodos = $metodosModel->listarTodos();
        break;
}

$rutaVista = __DIR__ . '/../view/finanzas/' . $action . '.php';
if (!file_exists($rutaVista)) die("Error 404: No existe la vista.");
require_once $rutaVista;
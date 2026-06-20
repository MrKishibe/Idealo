<?php

namespace Idealo\Controllers;

use Idealo\Models\ControlPagosModel;
use Idealo\Models\CuentaEmpresaModel;
use Idealo\Models\MetodoPagoModel;
use Exception;

require_once dirname(__DIR__) . '/models/ControlPagosModel.php';
require_once dirname(__DIR__) . '/models/CuentaEmpresaModel.php';
require_once dirname(__DIR__) . '/models/MetodoPagoModel.php';

class FinanzasController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ControlPagosModel();
    }

    // ==========================================
    // SECCIÓN: CONTROL DE PAGOS
    // ==========================================

    public function pagos() {
        $pagos = $this->model->listarTodos();
        $metodos = $this->model->obtenerMetodosPago();
        $pedidos = $this->model->obtenerPedidosActivos();
        
        require_once dirname(__DIR__) . '/view/finanzas/pagos.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id_pago = $_POST['id_pago'] ?? '';
                
                $datos = [
                    'id_pago'            => $id_pago,
                    'id_pedido'          => $_POST['id_pedido'] ?? '',
                    'monto_pago'         => $_POST['monto_pago'] ?? '',
                    'id_metodo_de_pago'  => $_POST['id_metodo_de_pago'] ?? '',
                    'referencia'         => $_POST['referencia'] ?? '',
                    'fecha_pago'         => $_POST['fecha_pago'] ?? '',
                    'id_usuario'         => $_SESSION['id_usuario'] ?? 1
                ];

                if (empty($id_pago)) {
                    $res = $this->model->guardar($datos);
                } else {
                    $res = $this->model->editar($datos);
                }

                echo json_encode(['status' => $res ? 'success' : 'error']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function inhabilitar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id_pago = intval($_POST['id_pago'] ?? 0);
                $estado = intval($_POST['estado'] ?? 0);
                
                $res = $this->model->cambiarEstado($id_pago, $estado);
                echo json_encode(['status' => $res ? 'success' : 'error']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    // ==========================================
    // SECCIÓN: CUENTAS BANCARIAS
    // ==========================================

    public function cuentas() {
        $cuentaModel = new CuentaEmpresaModel();
        $cuentas = $cuentaModel->listarTodas();
        $metodos = $cuentaModel->obtenerMetodosPago();
        
        require_once dirname(__DIR__) . '/view/finanzas/cuentas.php';
    }

    public function guardarCuenta() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cuentaModel = new CuentaEmpresaModel();
                $id_cuenta = $_POST['id_cuenta'] ?? '';
                
                $datos = [
                    'id_cuenta'          => $id_cuenta,
                    'id_metodo_de_pago'  => $_POST['id_metodo_de_pago'] ?? '',
                    'tipo_cuenta'        => $_POST['tipo_cuenta'] ?? '',
                    'identificador'      => $_POST['identificador'] ?? '',
                    'titular'            => $_POST['titular'] ?? ''
                ];

                if (empty($id_cuenta)) {
                    $res = $cuentaModel->guardar($datos);
                } else {
                    $res = $cuentaModel->editar($datos);
                }

                echo json_encode(['status' => $res ? 'success' : 'error']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function inhabilitarCuenta() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cuentaModel = new CuentaEmpresaModel();
                $id_cuenta = intval($_POST['id_cuenta'] ?? 0);
                $estado = intval($_POST['estado'] ?? 0);
                
                $res = $cuentaModel->cambiarEstado($id_cuenta, $estado);
                echo json_encode(['status' => $res ? 'success' : 'error']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    // ==========================================
    // SECCIÓN: MÉTODOS DE PAGO (NUEVO)
    // ==========================================

    public function metodos() {
        $metodoModel = new MetodoPagoModel();
        $metodos = $metodoModel->listarTodos();
        
        require_once dirname(__DIR__) . '/view/finanzas/metodos.php';
    }

    public function guardarMetodo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $metodoModel = new MetodoPagoModel();
                $id_metodo = $_POST['id_metodo_de_pago'] ?? '';
                
                $datos = [
                    'id_metodo_de_pago'     => $id_metodo,
                    'nombre_metodo_de_pago' => $_POST['nombre_metodo_de_pago'] ?? ''
                ];

                if (empty($id_metodo)) {
                    $res = $metodoModel->guardar($datos);
                } else {
                    $res = $metodoModel->editar($datos);
                }

                echo json_encode(['status' => $res ? 'success' : 'error']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function inhabilitarMetodo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $metodoModel = new MetodoPagoModel();
                $id_metodo = intval($_POST['id_metodo_de_pago'] ?? 0);
                $estado = intval($_POST['estado'] ?? 0);
                
                $res = $metodoModel->cambiarEstado($id_metodo, $estado);
                echo json_encode(['status' => $res ? 'success' : 'error']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
}
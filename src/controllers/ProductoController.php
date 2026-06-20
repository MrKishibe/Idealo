<?php

namespace Idealo\Controllers;

use Idealo\Models\ProductoModel;
use Exception;

require_once dirname(__DIR__) . '/models/ProductoModel.php';

class ProductoController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ProductoModel();
    }

    public function listar() {
        // Carga el catálogo completo para que DataTables pueda alternar
        $productos = $this->model->listarTodos();
        
        $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        if (is_dir($basePath . 'productos')) {
            $vista = $basePath . 'productos' . DIRECTORY_SEPARATOR . 'listar.php';
        } else {
            $vista = $basePath . 'producto' . DIRECTORY_SEPARATOR . 'listar.php';
        }
        require_once $vista;
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                $datos = [
                    'id_producto'      => $_POST['id_producto'] ?? '',
                    'nombre_producto'  => $_POST['nombre_producto'] ?? '',
                    'tipo_de_producto' => $_POST['tipo_de_producto'] ?? '',
                    'status_producto'  => $_POST['status_producto'] ?? 'activo'
                ];

                $resultado = $this->model->guardar($datos);

                if (isset($resultado['error'])) {
                    echo json_encode(['status' => 'error', 'message' => $resultado['error']]);
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'El producto fue procesado correctamente.']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function cambiarEstado() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $id = intval($_POST['id_producto'] ?? 0);
            $nuevoEstado = $_POST['status_producto'] ?? 'inactivo';

            $resultado = $this->model->getCambiarEstado($id, $nuevoEstado);

            if (isset($resultado['error'])) {
                echo json_encode(['status' => 'error', 'message' => $resultado['error']]);
            } else {
                $msg = ($nuevoEstado === 'activo') ? 'Producto activado con éxito.' : 'Producto inactivado con éxito.';
                echo json_encode(['status' => 'success', 'message' => $msg]);
            }
            exit;
        }
    }
}
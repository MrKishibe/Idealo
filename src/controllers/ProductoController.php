<?php

namespace Idealo\Controllers;

use Idealo\Models\ProductoModel;
use Exception;

require_once dirname(__DIR__) . '/models/ProductoModel.php';

class ProductoController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ProductoModel();
    }

    public function listar()
    {
        $productos = $this->model->listarTodos();

        $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        if (is_dir($basePath . 'productos')) {
            $vista = $basePath . 'productos' . DIRECTORY_SEPARATOR . 'listar.php';
        } else {
            $vista = $basePath . 'producto' . DIRECTORY_SEPARATOR . 'listar.php';
        }
        require_once $vista;
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $datos = [
                    'id_producto'      => $_POST['id_producto'] ?? '',
                    'nombre_producto'  => $_POST['nombre_producto'] ?? '',
                    'tipo_de_producto' => $_POST['tipo_de_producto'] ?? '',
                    'status_producto'  => $_POST['status_producto'] ?? 'activo',
                    'detalle_material' => $_POST['detalle_material'] ?? '',
                    'color'            => $_POST['color'] ?? '',
                    'tipo_de_prenda'   => $_POST['tipo_de_prenda'] ?? '',
                    'tallas'           => $_POST['tallas'] ?? []
                ];

                $resultado = $this->model->guardar($datos);

                if (isset($resultado['error'])) {
                    echo json_encode(['status' => 'error', 'message' => $resultado['error']], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'El producto y sus variantes fueron guardados correctamente.'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    public function cambiarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $id = intval($_POST['id_producto'] ?? 0);
                $nuevoEstado = $_POST['status_producto'] ?? 'inactivo';

                $resultado = $this->model->getCambiarEstado($id, $nuevoEstado);

                if (isset($resultado['error'])) {
                    echo json_encode(['status' => 'error', 'message' => $resultado['error']], JSON_UNESCAPED_UNICODE);
                } else {
                    $msg = ($nuevoEstado === 'activo') ? 'Producto activado con éxito.' : 'Producto inactivado con éxito.';
                    echo json_encode(['status' => 'success', 'message' => $msg], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }
}

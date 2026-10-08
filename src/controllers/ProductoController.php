<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/ProductoModel.php';

use Idealo\Models\ProductoModel;


$action = $_GET['action'] ?? 'listar';

if ($action === 'listar') {
    $productoModel = new ProductoModel();
    $productos = $productoModel->listarTodos();

    require_once __DIR__ . '/../view/producto/listar.php';
    exit();
}

if ($action === 'listarProductosAjax') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $productoModel = new ProductoModel();
        $productos = $productoModel->listarTodos();

        echo json_encode([
            'status' => 'success',
            'productos' => $productos
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'guardar' || $action === 'editar') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $esEdicion = !empty($_POST['id_producto']);

        $productoModel = new ProductoModel();
        // El modelo recibe el array completo: id_producto (edición), nombre, tipo,
        // característica (material/color/prenda), status y tallas[].
        $resultado = $productoModel->guardar($_POST);

        $mensajePorDefecto = $esEdicion
            ? 'Producto actualizado con éxito.'
            : 'Producto registrado con éxito.';

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? $resultado['error'] ?? $mensajePorDefecto,
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'cambiarEstado') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idProducto = intval($_POST['id_producto'] ?? 0);
        $estado = trim($_POST['status_producto'] ?? '');

        if ($idProducto <= 0) {
            throw new Exception('El ID del producto no es válido.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new Exception('El estado del producto no es válido.');
        }

        $productoModel = new ProductoModel();
        $resultado = $productoModel->getCambiarEstado($idProducto, $estado);

        $mensaje = $estado === 'activo'
            ? 'Producto reactivado con éxito.'
            : 'Producto inactivado con éxito.';

        echo json_encode([
            'status'  => !empty($resultado['success']) ? 'success' : 'error',
            'message' => $resultado['message'] ?? $resultado['error'] ?? $mensaje,
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

if ($action === 'verificarNombreProducto') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $nombreProducto = trim($_POST['nombre_producto'] ?? '');
        $idProducto = isset($_POST['id_producto']) && $_POST['id_producto'] !== '' ? intval($_POST['id_producto']) : null;

        $productoModel = new ProductoModel();
        if (!method_exists($productoModel, 'existeNombreProducto')) {
            echo json_encode(['status' => 'success', 'existe' => false]);
            exit();
        }

        $existe = $productoModel->existeNombreProducto($nombreProducto, $idProducto);

        echo json_encode([
            'status' => 'success',
            'existe' => $existe
        ]);
        exit();
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Acción no encontrada.'
]);
exit();

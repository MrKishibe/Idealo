<?php

use Idealo\Models\ClienteModel;

$model = new ClienteModel();

// Manejo de peticiones POST (Guardar, Editar, Verificar Duplicado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // CASO A: Guardar nuevo cliente
    if (isset($_POST["accion"]) && $_POST["accion"] === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->guardar($_POST);
            echo json_encode([
                'success' => true, 
                'message' => 'Cliente registrado con éxito.',
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
    
    // CASO B: Editar cliente
    if (isset($_POST["accion"]) && $_POST["accion"] === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->editar($_POST);
            echo json_encode([
                'success' => true, 
                'message' => 'Cliente actualizado con éxito.',
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

    // CASO C: Verificar documento (duplicado)
    if (isset($_POST["numero_de_documento"])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        $numDoc = trim($_POST["numero_de_documento"]);
        $existe = $model->existeDocumento($numDoc);
        
        if ($existe) {
            echo json_encode([
                'duplicado' => true,
                'id_cliente_duplicado' => $existe['id_cliente'],
                'mensaje' => 'El número de documento ya está registrado.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'duplicado' => false,
                'mensaje' => 'El número de documento es válido.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    exit;
}

// CASO D: Inhabilitar cliente (GET)
if (isset($_GET["id"]) && isset($_GET["accion"]) && $_GET["accion"] === "eliminar") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $id = (int)$_GET["id"];
        $model->cambiarEstado($id, 'inactivo');
        echo json_encode([
            'success' => true, 
            'message' => 'Cliente inhabilitado correctamente.',
            'evento' => 'eliminar',
            'estado' => 'completado'
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'evento' => 'eliminar',
            'estado' => 'error'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// CASO E: AJAX - Listar clientes activos e inactivos
if (isset($_GET["ajax"]) && $_GET["ajax"] === "listar") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $activos = $model->listarPorEstado('activo');
    $inactivos = $model->listarPorEstado('inactivo');

    if (!is_array($activos)) $activos = [];
    if (!is_array($inactivos)) $inactivos = [];

    echo json_encode([
        'clientes' => array_merge($activos, $inactivos),
        'total' => count($activos) + count($inactivos),
        'activos' => count($activos),
        'inactivos' => count($inactivos),
        'evento' => 'listar',
        'estado' => 'completado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Carga de la vista principal si no es una llamada asíncrona
$rutaVista = __DIR__ . '/../view/cliente/listar.php';

if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;
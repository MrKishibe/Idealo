<?php

use Idealo\Models\ClienteModel;

require_once __DIR__ . '/../helpers/AuthorizationHelper.php';
\Idealo\Helpers\AuthorizationHelper::enforce('cliente');

$model = new ClienteModel();

// Manejo de peticiones POST (Guardar, Editar, Verificar Duplicado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // CASO A: Guardar nuevo cliente
    if (isset($_POST["accion"]) && $_POST["accion"] === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            // Validar duplicado por tipo + documento antes de guardar
            $numDoc = trim($_POST["numero_de_documento"] ?? '');
            $tipoDoc = trim($_POST["tipo_de_documento"] ?? '');
            $correo = trim($_POST["correo"] ?? '');

            if (method_exists($model, 'existeDocumentoPorTipo') && $model->existeDocumentoPorTipo($numDoc, $tipoDoc)) {
                throw new \Exception('El número de documento ya está registrado para este tipo de documento.');
            }

            if (!empty($correo) && method_exists($model, 'existeCorreo') && $model->existeCorreo($correo)) {
                throw new \Exception('El correo electrónico ya se encuentra registrado por otro cliente.');
            }

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
            $numDoc = trim($_POST["numero_de_documento"] ?? '');
            $tipoDoc = trim($_POST["tipo_de_documento"] ?? '');
            $correo = trim($_POST["correo"] ?? '');
            $idCliente = (int)($_POST["id_cliente"] ?? 0);

            if (method_exists($model, 'existeDocumentoPorTipo') && $model->existeDocumentoPorTipo($numDoc, $tipoDoc, $idCliente)) {
                throw new \Exception('El número de documento ya está registrado para este tipo de documento.');
            }

            if (!empty($correo) && method_exists($model, 'existeCorreo') && $model->existeCorreo($correo, $idCliente)) {
                throw new \Exception('El correo electrónico ya se encuentra registrado por otro cliente.');
            }

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

    // CASO C: Verificar documento y correo mediante AJAX en tiempo real
    if (isset($_POST["numero_de_documento"]) || isset($_POST["correo"])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        $numDoc = trim($_POST["numero_de_documento"] ?? '');
        $tipoDoc = trim($_POST["tipo_de_documento"] ?? '');
        $correo = trim($_POST["correo"] ?? '');
        $idClienteExcluir = isset($_POST["id_cliente"]) ? (int)$_POST["id_cliente"] : 0;
        
        $duplicadoDoc = false;
        $duplicadoCorreo = false;
        $mensaje = '';

        if ($numDoc !== '' && $tipoDoc !== '') {
            $duplicadoDoc = method_exists($model, 'existeDocumentoPorTipo') 
                ? $model->existeDocumentoPorTipo($numDoc, $tipoDoc, $idClienteExcluir) 
                : false;
            if ($duplicadoDoc) {
                $mensaje = 'El número de documento ya está registrado bajo este mismo tipo.';
            }
        }

        if (!$duplicadoDoc && $correo !== '') {
            $duplicadoCorreo = method_exists($model, 'existeCorreo') 
                ? $model->existeCorreo($correo, $idClienteExcluir) 
                : false;
            if ($duplicadoCorreo) {
                $mensaje = 'El correo electrónico ya está registrado.';
            }
        }
        
        echo json_encode([
            'duplicado' => ($duplicadoDoc || $duplicadoCorreo),
            'duplicado_documento' => $duplicadoDoc,
            'duplicado_correo' => $duplicadoCorreo,
            'mensaje' => $mensaje ?: 'Datos válidos.'
        ], JSON_UNESCAPED_UNICODE);
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

// Carga de la vista principal
$rutaVista = __DIR__ . '/../view/cliente/listar.php';
require_once $rutaVista;
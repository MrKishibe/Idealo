<?php
// TipoMateriaPrimaController.php - Procedimental con validación estricta de archivos, mensajes y eventos

// Definición de rutas absolutas para verificación de dependencias
$rutaConfig = dirname(dirname(__DIR__)) . '/Config/Database.php';
$rutaPropiaModelo = __DIR__ . '/../Models/TipoMateriaPrimaModel.php';
$rutaVista  = __DIR__ . '/../view/tipo_materia_prima/listar.php';

// =========================================================================
// 1. VERIFICACIÓN DE ARCHIVOS CRÍTICOS DEL SISTEMA
// =========================================================================
if (!file_exists($rutaConfig)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No se encontró el archivo de configuración requerido en: <strong>" . htmlspecialchars($rutaConfig) . "</strong>");
}

if (!file_exists($rutaPropiaModelo)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No se encontró el archivo del Modelo requerido en: <strong>" . htmlspecialchars($rutaPropiaModelo) . "</strong>");
}

// Carga segura de dependencias una vez confirmada su existencia
require_once $rutaConfig;
require_once $rutaPropiaModelo;

use Idealo\Models\TipoMateriaPrimaModel;

// =========================================================================
// 2. CONTROL DE PETICIONES POST
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // CASO A: Cambios desde Modales (Editar completo o Cambio de estado)
    if (isset($_POST['id_accion']) && isset($_POST['nuevo_estado'])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        $id = intval($_POST['id_accion']);
        $nuevoEstado = $_POST['nuevo_estado'];

        // Sub-caso: Si incluye 'nombre', significa que es una EDICIÓN COMPLETA
        if (isset($_POST['nombre'])) {
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion'] ?? '');

            $validacion = TipoMateriaPrimaModel::validarDatos($nombre, $descripcion, $nuevoEstado, $id);
            
            if ($validacion === true) {
                $resultado = TipoMateriaPrimaModel::getActualizarDatos($id);
                
                if (isset($resultado['existoso'])) {
                    echo json_encode([
                        'success' => true,
                        'message' => '✅ ' . $resultado['existoso'],
                        'evento' => 'editar',
                        'estado' => 'completado'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => '❌ ' . ($resultado['error'] ?? "Error interno al guardar los cambios."),
                        'evento' => 'editar',
                        'estado' => 'error'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '❌ ' . $validacion['error'],
                    'evento' => 'editar',
                    'estado' => 'error_validacion'
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        // Flujo rápido: Mutación de estado ordinario directo desde la tabla
        $respuesta = TipoMateriaPrimaModel::getCambiarEstado($id, $nuevoEstado);

        if (isset($respuesta['existoso'])) {
            echo json_encode([
                'success' => true,
                'message' => '✅ Estado actualizado con éxito.',
                'evento' => 'cambiar_estado',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '❌ ' . ($respuesta['error'] ?? "Error al cambiar el estado."),
                'evento' => 'cambiar_estado',
                'estado' => 'error'
              ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // CASO B: Registrar nuevo tipo de material
    if (isset($_POST['nombre']) && !isset($_POST['id_accion'])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $status = 'Activo'; 

        $validacion = TipoMateriaPrimaModel::validarDatos($nombre, $descripcion, $status);

        if ($validacion === true) {
            $resultado = TipoMateriaPrimaModel::getRegistrarDatos();
            
            if (isset($resultado['existoso'])) {
                echo json_encode([
                    'success' => true,
                    'message' => '✅ Material registrado con éxito.',
                    'id' => $resultado['id'],
                    'nombre' => $nombre,
                    'descripcion' => $descripcion ?: 'Sin especificaciones',
                    'evento' => 'guardar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '❌ ' . ($resultado['error'] ?? "Error interno al guardar en el sistema."),
                    'evento' => 'guardar',
                    'estado' => 'error'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => '❌ ' . $validacion['error'],
                'evento' => 'guardar',
                'estado' => 'error_validacion'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

// =========================================================================
// 3. CONTROL DE PETICIONES GET (AJAX / CARGA ORDINARIA)
// =========================================================================

// CASO A: AJAX - Listado estructurado y métricas unificadas
if (isset($_GET["ajax"]) && $_GET["ajax"] === "listar") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $materiales = TipoMateriaPrimaModel::consultarMateriales();
    
    if (is_array($materiales) && isset($materiales['error'])) {
        echo json_encode([
            'success' => false,
            'message' => '❌ ' . $materiales['error'],
            'evento' => 'listar',
            'estado' => 'error'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($materiales)) $materiales = [];

    // Uso corregido de 'status_tipo_materia'
    $activos = array_filter($materiales, function($m) { return $m['status_tipo_materia'] === 'Activo'; });
    $inactivos = array_filter($materiales, function($m) { return $m['status_tipo_materia'] === 'Inactivo'; });

    echo json_encode([
        'materiales' => array_values($materiales),
        'total' => count($materiales),
        'activos' => count($activos),
        'inactivos' => count($inactivos),
        'evento' => 'listar',
        'estado' => 'completado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// CARGA ORDINARIA: Procesar extracción nativa para renderizado directo en servidor
$materiales = TipoMateriaPrimaModel::consultarMateriales();

if (is_array($materiales) && isset($materiales['error'])) {
    die("Error Crítico de Datos: " . htmlspecialchars($materiales['error']));
}

if (!is_array($materiales)) {
    $materiales = array();
}

// =========================================================================
// 4. VERIFICACIÓN Y CARGA DE LA VISTA
// =========================================================================
if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;
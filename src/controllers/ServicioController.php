<?php

// Definición de rutas absolutas del ecosistema para validación estructural
$rutaConfigDatabase = dirname(dirname(__DIR__)) . '/Config/Database.php';
$rutaPropiaModelo  = __DIR__ . '/../Models/ServicioModel.php';
$rutaVistaListar    = __DIR__ . '/../view/servicio/listar.php';

// =========================================================================
// 1. VERIFICACIÓN DE DEPENDENCIAS FÍSICAS EN EL DISCO
// =========================================================================
if (!file_exists($rutaConfigDatabase)) {
    header("HTTP/1.1 404 Not Found");
    die("Error Crítico: No se encontró la configuración de la BD en: <strong>" . htmlspecialchars($rutaConfigDatabase) . "</strong>");
}

if (!file_exists($rutaPropiaModelo)) {
    header("HTTP/1.1 404 Not Found");
    die("Error Crítico: El archivo del modelo requerido no existe en: <strong>" . htmlspecialchars($rutaPropiaModelo) . "</strong>");
}

// Carga segura de los recursos
require_once $rutaConfigDatabase;
require_once $rutaPropiaModelo;

use Idealo\Models\ServicioModel;

// Captura dinámica de acciones si se manejan mediante la URL clásica (?action=nombre)
$action = $_GET['action'] ?? 'listar';

// =========================================================================
// 2. ENRUTADOR PROCEDIMENTAL DE ACCIONES
// =========================================================================

// ACCIÓN: Listar Vista Normal (Renderizado desde el servidor)
if ($action === 'listar' && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax'])) {
    if (!file_exists($rutaVistaListar)) {
        header("HTTP/1.1 404 Not Found");
        die("Error Crítico: La vista solicitada no fue localizada en: <strong>" . htmlspecialchars($rutaVistaListar) . "</strong>");
    }
    require_once $rutaVistaListar;
    exit;
}

// ACCIÓN: Listar datos estructurados para peticiones AJAX
if ($action === 'listarServiciosAjax' || (isset($_GET['ajax']) && $_GET['ajax'] === 'true')) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $activos = ServicioModel::listarPorEstado('activo');
    $inactivos = ServicioModel::listarPorEstado('inactivo');

    if (!is_array($activos)) $activos = [];
    if (!is_array($inactivos)) $inactivos = [];

    echo json_encode(array_merge($activos, $inactivos), JSON_UNESCAPED_UNICODE);
    exit;
}

// ACCIÓN: Registrar un nuevo servicio
if ($action === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $nombre = trim($_POST['nombre_servicio'] ?? '');
    $status = 'activo';

    $validacion = ServicioModel::validarDatos($nombre, $status);

    if ($validacion === true) {
        $resultado = ServicioModel::getRegistrarDatos($nombre, $status);
        
        if (isset($resultado['exitoso'])) {
            echo json_encode(['success' => true, 'message' => '✅ Servicio registrado con éxito.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ ' . ($resultado['error'] ?? 'Error interno en el servidor.')], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '❌ ' . $validacion['error']], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ACCIÓN: Editar datos completos de un servicio existente
if ($action === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $id = intval($_POST['id_servicio'] ?? 0);
    $nombre = trim($_POST['nombre_servicio'] ?? '');
    $status = $_POST['status_servicio'] ?? 'activo';

    if ($id === 0) {
        echo json_encode(['success' => false, 'message' => '❌ ID de servicio no válido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $validacion = ServicioModel::validarDatos($nombre, $status, $id);

    if ($validacion === true) {
        $resultado = ServicioModel::getActualizarDatos($id, $nombre, $status);
        
        if (isset($resultado['exitoso'])) {
            echo json_encode(['success' => true, 'message' => '✅ Servicio actualizado con éxito.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ ' . ($resultado['error'] ?? 'Error al actualizar los datos.')], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '❌ ' . $validacion['error']], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ACCIÓN: Inhabilitar un servicio (Borrado lógico)
if ($action === 'eliminar') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        $resultado = ServicioModel::getCambiarEstado($id, 'inactivo');
        
        if (isset($resultado['exitoso'])) {
            echo json_encode(['success' => true, 'message' => '✅ Servicio inhabilitado correctamente.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ ' . ($resultado['error'] ?? 'Error al cambiar el estado.')], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '❌ ID no proporcionado o inválido.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
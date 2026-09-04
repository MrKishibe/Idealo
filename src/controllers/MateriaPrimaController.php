<?php

use Idealo\Models\MateriaPrimaModel;

$rutaVista = __DIR__ . '/../view/materia_prima/listar.php';

/*
|--------------------------------------------------------------------------
| Función exclusiva para el módulo Materia Prima
|--------------------------------------------------------------------------
*/
if (!function_exists('responderJSONMateriaPrima')) {
    function responderJSONMateriaPrima(array $respuesta): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $respuesta,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CONTROL DE PETICIONES POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {

    /*
    |--------------------------------------------------------------------------
    | Editar registro o cambiar estado
    |--------------------------------------------------------------------------
    */
    if (
        isset($_POST['id_accion']) &&
        isset($_POST['nuevo_estado'])
    ) {
        $id = filter_var(
            $_POST['id_accion'],
            FILTER_VALIDATE_INT
        );

        $nuevoEstado = trim($_POST['nuevo_estado']);

        if ($id === false || $id <= 0) {
            responderJSONMateriaPrima([
                'success' => false,
                'message' =>
                    '❌ El identificador de la materia prima no es válido.',
                'evento' => 'validacion',
                'estado' => 'error_validacion'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Edición completa
        |--------------------------------------------------------------------------
        */
        if (isset($_POST['nombre'])) {
            $nombre = trim($_POST['nombre']);
            $idTipoMateria = filter_var(
                $_POST['id_tipo_materia_prima'] ?? 0,
                FILTER_VALIDATE_INT
            );
            $costoUnitario = $_POST['costo_unitario'] ?? 0;
            $stockActual = $_POST['stock_actual'] ?? 0;
            $stockMinimo = $_POST['stock_minimo'] ?? 0;
            $unidadMedida = trim($_POST['unidad_de_medida'] ?? '');

            $validacion = MateriaPrimaModel::validarDatos(
                $nombre,
                $idTipoMateria,
                $costoUnitario,
                $stockActual,
                $stockMinimo,
                $unidadMedida,
                $nuevoEstado,
                $id
            );

            if ($validacion !== true) {
                responderJSONMateriaPrima([
                    'success' => false,
                    'message' => '❌ ' . $validacion['error'],
                    'evento' => 'editar',
                    'estado' => 'error_validacion'
                ]);
            }

            $resultado =
                MateriaPrimaModel::getActualizarDatos($id);

            if (isset($resultado['exitoso'])) {
                responderJSONMateriaPrima([
                    'success' => true,
                    'message' => '✅ ' . $resultado['exitoso'],
                    'evento' => 'editar',
                    'estado' => 'completado'
                ]);
            }

            responderJSONMateriaPrima([
                'success' => false,
                'message' => '❌ ' . (
                    $resultado['error'] ??
                    'Error interno al actualizar la materia prima.'
                ),
                'evento' => 'editar',
                'estado' => 'error'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Cambio rápido de estado
        |--------------------------------------------------------------------------
        */
        $respuesta =
            MateriaPrimaModel::getCambiarEstado(
                $id,
                $nuevoEstado
            );

        if (isset($respuesta['exitoso'])) {
            responderJSONMateriaPrima([
                'success' => true,
                'message' => '✅ Estado actualizado con éxito.',
                'evento' => 'cambiar_estado',
                'estado' => 'completado'
            ]);
        }

        responderJSONMateriaPrima([
            'success' => false,
            'message' => '❌ ' . (
                $respuesta['error'] ??
                'Error al cambiar el estado.'
            ),
            'evento' => 'cambiar_estado',
            'estado' => 'error'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar nueva materia prima
    |--------------------------------------------------------------------------
    */
    if (
        isset($_POST['nombre']) &&
        !isset($_POST['id_accion'])
    ) {
        $nombre = trim($_POST['nombre']);
        $idTipoMateria = filter_var(
            $_POST['id_tipo_materia_prima'] ?? 0,
            FILTER_VALIDATE_INT
        );
        $costoUnitario = $_POST['costo_unitario'] ?? 0;
        $stockActual = $_POST['stock_actual'] ?? 0;
        $stockMinimo = $_POST['stock_minimo'] ?? 0;
        $unidadMedida = trim($_POST['unidad_de_medida'] ?? '');
        $status = 'Activo';

        $validacion = MateriaPrimaModel::validarDatos(
            $nombre,
            $idTipoMateria,
            $costoUnitario,
            $stockActual,
            $stockMinimo,
            $unidadMedida,
            $status
        );

        if ($validacion !== true) {
            responderJSONMateriaPrima([
                'success' => false,
                'message' => '❌ ' . $validacion['error'],
                'evento' => 'guardar',
                'estado' => 'error_validacion'
            ]);
        }

        $resultado =
            MateriaPrimaModel::getRegistrarDatos();

        if (isset($resultado['exitoso'])) {
            responderJSONMateriaPrima([
                'success' => true,
                'message' =>
                    '✅ Materia prima registrada con éxito.',
                'id' => $resultado['id'],
                'nombre' => $nombre,
                'evento' => 'guardar',
                'estado' => 'completado'
            ]);
        }

        responderJSONMateriaPrima([
            'success' => false,
            'message' => '❌ ' . (
                $resultado['error'] ??
                'Error interno al guardar la materia prima.'
            ),
            'evento' => 'guardar',
            'estado' => 'error'
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| Carga AJAX
|--------------------------------------------------------------------------
*/
if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'listar'
) {
    $materiasPrimas = MateriaPrimaModel::consultarMateriasPrimas();

    if (
        is_array($materiasPrimas) &&
        isset($materiasPrimas['error'])
    ) {
        responderJSONMateriaPrima([
            'success' => false,
            'message' => '❌ ' . $materiasPrimas['error'],
            'evento' => 'listar',
            'estado' => 'error'
        ]);
    }

    if (!is_array($materiasPrimas)) {
        $materiasPrimas = [];
    }

    $activos = array_filter(
        $materiasPrimas,
        function ($mp) {
            return (
                ($mp['status_materia_prima'] ?? '') ===
                'Activo'
            );
        }
    );

    $inactivos = array_filter(
        $materiasPrimas,
        function ($mp) {
            return (
                ($mp['status_materia_prima'] ?? '') ===
                'Inactivo'
            );
        }
    );

    responderJSONMateriaPrima([
        'success' => true,
        'materias_primas' => array_values($materiasPrimas),
        'total' => count($materiasPrimas),
        'activos' => count($activos),
        'inactivos' => count($inactivos),
        'evento' => 'listar',
        'estado' => 'completado'
    ]);
}

/*
|--------------------------------------------------------------------------
| Carga AJAX para tipos activos
|--------------------------------------------------------------------------
*/
if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'tipos_activos'
) {
    $tipos = MateriaPrimaModel::consultarTiposActivos();

    if (
        is_array($tipos) &&
        isset($tipos['error'])
    ) {
        responderJSONMateriaPrima([
            'success' => false,
            'message' => '❌ ' . $tipos['error'],
            'evento' => 'tipos_activos',
            'estado' => 'error'
        ]);
    }

    if (!is_array($tipos)) {
        $tipos = [];
    }

    responderJSONMateriaPrima([
        'success' => true,
        'tipos' => array_values($tipos),
        'evento' => 'tipos_activos',
        'estado' => 'completado'
    ]);
}

/*
|--------------------------------------------------------------------------
| Carga normal de la vista
|--------------------------------------------------------------------------
*/
$materiasPrimas = MateriaPrimaModel::consultarMateriasPrimas();

if (
    is_array($materiasPrimas) &&
    isset($materiasPrimas['error'])
) {
    die(
        'Error crítico de datos: ' .
        htmlspecialchars(
            $materiasPrimas['error'],
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

if (!is_array($materiasPrimas)) {
    $materiasPrimas = [];
}

if (!file_exists($rutaVista)) {
    header('HTTP/1.1 404 Not Found');

    die(
        'Error 404: No existe la vista requerida en: <strong>' .
        htmlspecialchars(
            $rutaVista,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</strong>'
    );
}

require_once $rutaVista;
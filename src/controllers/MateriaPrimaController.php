<?php

use Idealo\Models\MateriaPrimaModel;

$rutaVista = __DIR__ . '/../view/materia_prima/listar.php';

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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {

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
                    'El identificador de la materia prima no es válido.',
                'evento' => 'validacion',
                'estado' => 'error_validacion'
            ]);
        }

    
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
                    'message' => $validacion['error'],
                    'evento' => 'editar',
                    'estado' => 'error_validacion'
                ]);
            }

            $resultado =
                MateriaPrimaModel::getActualizarDatos($id);

            if (isset($resultado['exitoso'])) {
                responderJSONMateriaPrima([
                    'success' => true,
                    'message' => $resultado['exitoso'],
                    'evento' => 'editar',
                    'estado' => 'completado'
                ]);
            }

            responderJSONMateriaPrima([
                'success' => false,
                'message' => (
                    $resultado['error'] ??
                    'Error interno al actualizar la materia prima.'
                ),
                'evento' => 'editar',
                'estado' => 'error'
            ]);
        }


        $respuesta =
            MateriaPrimaModel::getCambiarEstado(
                $id,
                $nuevoEstado
            );

        if (isset($respuesta['exitoso'])) {
            responderJSONMateriaPrima([
                'success' => true,
                'message' => 'Estado actualizado con éxito.',
                'evento' => 'cambiar_estado',
                'estado' => 'completado'
            ]);
        }

        responderJSONMateriaPrima([
            'success' => false,
            'message' => (
                $respuesta['error'] ??
                'Error al cambiar el estado.'
            ),
            'evento' => 'cambiar_estado',
            'estado' => 'error'
        ]);
    }

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
                'message' => $validacion['error'],
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
                    'Materia prima registrada con éxito.',
                'id' => $resultado['id'],
                'nombre' => $nombre,
                'evento' => 'guardar',
                'estado' => 'completado'
            ]);
        }

        responderJSONMateriaPrima([
            'success' => false,
            'message' => (
                $resultado['error'] ??
                'Error interno al guardar la materia prima.'
            ),
            'evento' => 'guardar',
            'estado' => 'error'
        ]);
    }
}

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
            'message' => $materiasPrimas['error'],
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
            'message' => $tipos['error'],
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

if (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte") {
    if (ob_get_length()) {
        ob_clean();
    }

    require_once __DIR__ . '/../../vendor/autoload.php';

    // Capturar parámetros enviados por la URL (GET) desde el formulario adaptado
    $tipoFiltroEstado = $_GET['estado_reporte'] ?? 'todos';
    $tipoFiltroMaterial = $_GET['tipo_reporte'] ?? 'todos';

    $operadorCosto = $_GET['operador_costo'] ?? '';
    $valorCosto = isset($_GET['valor_costo']) && trim($_GET['valor_costo']) !== '' ? (float)$_GET['valor_costo'] : null;

    $operadorStockActual = $_GET['operador_stock_actual'] ?? '';
    $valorStockActual = isset($_GET['valor_stock_actual']) && trim($_GET['valor_stock_actual']) !== '' ? (float)$_GET['valor_stock_actual'] : null;

    $operadorStockMinimo = $_GET['operador_stock_minimo'] ?? '';
    $valorStockMinimo = isset($_GET['valor_stock_minimo']) && trim($_GET['valor_stock_minimo']) !== '' ? (float)$_GET['valor_stock_minimo'] : null;

    $idsFiltrados = json_decode($_GET['ids_filtrados'] ?? '[]', true);

    $materiasPrimas = MateriaPrimaModel::consultarMateriasPrimas();
    if (!is_array($materiasPrimas)) {
        $materiasPrimas = [];
    }

    // Función auxiliar para evaluar mayor, igual o menor de forma estricta
    $evaluarCondicion = function ($valorCampo, $operador, $valorComparar) {
        if ($operador === '' || $valorComparar === null) {
            return true;
        }
        $valorCampo = (float)$valorCampo;
        if ($operador === 'mayor') return $valorCampo > $valorComparar;
        if ($operador === 'igual') return abs($valorCampo - $valorComparar) < 0.0001;
        if ($operador === 'menor') return $valorCampo < $valorComparar;
        return true;
    };

    // Filtrar los datos según los parámetros seleccionados
    $datosFiltrados = array_filter($materiasPrimas, function ($mp) use (
        $tipoFiltroEstado,
        $tipoFiltroMaterial,
        $operadorCosto,
        $valorCosto,
        $operadorStockActual,
        $valorStockActual,
        $operadorStockMinimo,
        $valorStockMinimo,
        $idsFiltrados,
        $evaluarCondicion
    ) {
        // 1. Estado
        if ($tipoFiltroEstado !== 'todos' && $mp['status_materia_prima'] !== $tipoFiltroEstado) {
            return false;
        }

        // 2. Tipo de Material
        if ($tipoFiltroMaterial !== 'todos' && (string)$mp['id_tipo_materia_prima'] !== (string)$tipoFiltroMaterial) {
            return false;
        }

        // 3. Condiciones numéricas
        if (!$evaluarCondicion($mp['costo_unitario'], $operadorCosto, $valorCosto)) return false;
        if (!$evaluarCondicion($mp['stock_actual'], $operadorStockActual, $valorStockActual)) return false;
        if (!$evaluarCondicion($mp['stock_minimo'], $operadorStockMinimo, $valorStockMinimo)) return false;

        // 4. IDs visibles en la tabla principal
        if (!empty($idsFiltrados) && !in_array($mp['id_materia_prima'], $idsFiltrados)) {
            return false;
        }

        return true;
    });

    // Calcular métricas para tarjetas de resumen
    $totalRegistros = count($datosFiltrados);
    $totalActivos = 0;
    $totalInactivos = 0;
    foreach ($datosFiltrados as $item) {
        if (strtolower($item['status_materia_prima']) === 'activo') {
            $totalActivos++;
        } else {
            $totalInactivos++;
        }
    }

    require_once __DIR__ . '/../helpers/ReportePdf.php';
    $pdf = new \Idealo\Helpers\ReportePdf('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setCompactReportDetails(
        'Reporte de Materia Prima',
        'Filtro de estado: ' . ucfirst($tipoFiltroEstado)
    );
    $pdf->AddPage();
    $pdf->addCompactReportHeader();
    $pdf->addMetricCards([
        ['label' => 'Total:', 'value' => (string) $totalRegistros, 'width' => 57],
        ['label' => 'Activos:', 'value' => (string) $totalActivos, 'width' => 57],
        ['label' => 'Inactivos:', 'value' => (string) $totalInactivos, 'width' => 62],
    ]);
    $pdf->addTableHeader([
        ['width' => 48, 'label' => 'Nombre'],
        ['width' => 37, 'label' => 'Tipo'],
        ['width' => 25, 'label' => 'Costo Unit.', 'align' => 'R'],
        ['width' => 28, 'label' => 'Stock Act.', 'align' => 'C'],
        ['width' => 24, 'label' => 'Mín.', 'align' => 'C'],
        ['width' => 18, 'label' => 'Estado', 'align' => 'C'],
    ]);

    // Filas de datos con efecto Cebra
    if (empty($datosFiltrados)) {
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(180, 10, 'No se encontraron materias primas con los parámetros seleccionados.', 1, 1, 'C');
    } else {
        foreach (array_values($datosFiltrados) as $index => $mp) {
            $estadoLower = strtolower((string) ($mp['status_materia_prima'] ?? ''));
            if ($estadoLower === 'activo') {
                $colorEstado = [16, 185, 129];
            } else {
                $colorEstado = [239, 68, 68];
            }

            $pdf->addTableRow([
                ['width' => 48, 'text' => substr((string) ($mp['nombre_materia_prima'] ?? ''), 0, 26)],
                ['width' => 37, 'text' => substr((string) ($mp['nombre_de_material'] ?? 'Sin tipo'), 0, 20)],
                ['width' => 25, 'text' => number_format((float) ($mp['costo_unitario'] ?? 0), 2), 'align' => 'R'],
                ['width' => 28, 'text' => ($mp['stock_actual'] ?? '') . ' ' . ($mp['unidad_de_medida'] ?? ''), 'align' => 'C'],
                ['width' => 24, 'text' => ($mp['stock_minimo'] ?? '') . ' ' . ($mp['unidad_de_medida'] ?? ''), 'align' => 'C'],
                [
                    'width' => 18,
                    'text' => ucfirst((string) ($mp['status_materia_prima'] ?? '')),
                    'align' => 'C',
                    'fontStyle' => 'B',
                    'fontSize' => 9,
                    'textColor' => $colorEstado,
                ],
            ], $index % 2 !== 0, 8);
        }
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    $pdf->Output('reporte de materia prima.pdf', 'I');
    exit;
}

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
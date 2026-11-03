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

    // Función auxiliar para evaluar mayor, igual o menor de forma estricta (ignorando filtros no seleccionados)
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

        // 3. Condiciones numéricas (solo se evalúan si el usuario rellenó los campos)
        if (!$evaluarCondicion($mp['costo_unitario'], $operadorCosto, $valorCosto)) return false;
        if (!$evaluarCondicion($mp['stock_actual'], $operadorStockActual, $valorStockActual)) return false;
        if (!$evaluarCondicion($mp['stock_minimo'], $operadorStockMinimo, $valorStockMinimo)) return false;

        // 4. IDs visibles en la tabla principal de DataTables
        if (!empty($idsFiltrados) && !in_array($mp['id_materia_prima'], $idsFiltrados)) {
            return false;
        }

        return true;
    });

    require_once __DIR__ . '/../helpers/ReportePdf.php';

    $pdf = new \Idealo\Helpers\ReportePdf('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Idéalo');
    $pdf->SetTitle('Reporte de Materia Prima');

    $etiquetaTipo = $tipoFiltroMaterial === 'todos' ? 'Todos' : (string) $tipoFiltroMaterial;
    $detalleFiltros = 'Estado: ' . ucfirst((string) $tipoFiltroEstado) . ' | Tipo: ' . $etiquetaTipo;
    foreach ([
        ['Costo', $operadorCosto, $valorCosto],
        ['Stock actual', $operadorStockActual, $valorStockActual],
        ['Stock mínimo', $operadorStockMinimo, $valorStockMinimo]
    ] as [$etiqueta, $operador, $valor]) {
        if ($operador !== '' && $valor !== null) {
            $detalleFiltros .= ' | ' . $etiqueta . ' ' . $operador . ' ' . $valor;
        }
    }

    $pdf->setReportDetails('Reporte de Materia Prima', 'Inventario filtrado | ' . $detalleFiltros);
    $pdf->AddPage();

    $costoPromedio = count($datosFiltrados) > 0
        ? array_sum(array_map(
            static function ($materia) {
                return (float) ($materia['costo_unitario'] ?? 0);
            },
            $datosFiltrados
        )) / count($datosFiltrados)
        : 0;
    $pdf->addSummaryCards([
        ['label' => 'Total de registros', 'value' => (string) count($datosFiltrados)],
        ['label' => 'Estado aplicado', 'value' => ucfirst((string) $tipoFiltroEstado)],
        ['label' => 'Costo unitario promedio', 'value' => '$ ' . number_format($costoPromedio, 2, '.', ',')]
    ]);

    $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="5" cellspacing="0" width="100%">
        <thead><tr bgcolor="#174B55">
            <th width="24%" align="left"><font color="#FFFFFF"><b>Nombre</b></font></th>
            <th width="17%" align="left"><font color="#FFFFFF"><b>Tipo</b></font></th>
            <th width="13%" align="right"><font color="#FFFFFF"><b>Costo Unit. ($)</b></font></th>
            <th width="15%" align="center"><font color="#FFFFFF"><b>Stock Actual</b></font></th>
            <th width="15%" align="center"><font color="#FFFFFF"><b>Stock Mínimo</b></font></th>
            <th width="16%" align="center"><font color="#FFFFFF"><b>Estado</b></font></th>
        </tr></thead><tbody>';

    if (empty($datosFiltrados)) {
        $html .= '<tr bgcolor="#F3F7F8"><td colspan="6" align="center">No se encontraron materias primas con los parámetros seleccionados.</td></tr>';
    } else {
        foreach (array_values($datosFiltrados) as $index => $mp) {
            $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';
            $html .= '<tr bgcolor="' . $fondoFila . '">
                <td width="24%">' . htmlspecialchars((string) ($mp['nombre_materia_prima'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="17%">' . htmlspecialchars((string) ($mp['nombre_de_material'] ?? 'Sin tipo'), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="13%" align="right">$ ' . number_format((float) ($mp['costo_unitario'] ?? 0), 2) . '</td>
                <td width="15%" align="center">' . htmlspecialchars((string) ($mp['stock_actual'] ?? ''), ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) ($mp['unidad_de_medida'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="15%" align="center">' . htmlspecialchars((string) ($mp['stock_minimo'] ?? ''), ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) ($mp['unidad_de_medida'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="16%" align="center">' . htmlspecialchars(ucfirst((string) ($mp['status_materia_prima'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>';
        }
    }
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');

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
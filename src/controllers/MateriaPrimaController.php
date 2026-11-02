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

    $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Idéalo');
    $pdf->SetTitle('Reporte de Materia Prima');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Encabezado corporativo
    $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
    if (file_exists($logoPath)) {
        $pdf->ImageSVG($logoPath, 15, 12, 12, 12);
    }

    $pdf->SetXY(30, 13);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(0, 8, 'Idéalo - Reporte Parametrizado de Materia Prima', 0, 1, 'L');

    $pdf->SetX(30);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s') . ' | Estado: ' . ucfirst($tipoFiltroEstado), 0, 1, 'L');

    $pdf->Ln(8);

    // Cabecera de la tabla PDF
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(100, 116, 139);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(65, 9, 'Nombre', 1, 0, 'L', true);
    $pdf->Cell(45, 9, 'Tipo', 1, 0, 'L', true);
    $pdf->Cell(35, 9, 'Costo Unit. ($)', 1, 0, 'R', true);
    $pdf->Cell(40, 9, 'Stock Actual', 1, 0, 'C', true);
    $pdf->Cell(40, 9, 'Stock Mínimo', 1, 0, 'C', true);
    $pdf->Cell(42, 9, 'Estado', 1, 1, 'C', true);

    // Filas de datos
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(30, 30, 30);

    if (empty($datosFiltrados)) {
        $pdf->Cell(267, 8, 'No se encontraron materias primas con los parámetros seleccionados.', 1, 1, 'C');
    } else {
        foreach ($datosFiltrados as $mp) {
            $pdf->Cell(65, 8, substr($mp['nombre_materia_prima'], 0, 35), 1, 0, 'L');
            $pdf->Cell(45, 8, substr($mp['nombre_de_material'] ?? 'Sin tipo', 0, 25), 1, 0, 'L');
            $pdf->Cell(35, 8, number_format((float)$mp['costo_unitario'], 2), 1, 0, 'R');
            $pdf->Cell(40, 8, $mp['stock_actual'] . ' ' . $mp['unidad_de_medida'], 1, 0, 'C');
            $pdf->Cell(40, 8, $mp['stock_minimo'] . ' ' . $mp['unidad_de_medida'], 1, 0, 'C');
            $pdf->Cell(42, 8, ucfirst($mp['status_materia_prima']), 1, 1, 'C');
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
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

    $usuarioActual = $_SESSION['nombre_usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? 'Administrador';

    // Clase extendida para el pie de página automático
    class MYPDF extends \TCPDF {
        public function Footer() {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 8.5);
            $this->SetTextColor(100, 116, 139);
            
            $this->SetDrawColor(203, 213, 225);
            $this->SetLineWidth(0.2);
            // Ancho adaptado a página vertical (195 mm)
            $this->Line(15, $this->GetY(), 195, $this->GetY());

            $this->Ln(2);
            $this->Cell(95, 5, 'Idéalo - Sistema de Gestión | Impreso el ' . date('d/m/Y H:i'), 0, 0, 'L');
            $this->Cell(85, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }

    // Cambiado a 'P' (Portrait / Vertical)
    $pdf = new MYPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Idéalo');
    $pdf->SetTitle('Reporte de Materia Prima');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(15, 12, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    // Encabezado corporativo
    $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
    if (file_exists($logoPath)) {
        $pdf->ImageSVG($logoPath, 15, 10, 14, 14);
    }

    $pdf->SetXY(32, 10);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(100, 7, 'Idéalo  -  Reporte de Materia Prima', 0, 0, 'L');
    
    // Usuario posicionado hacia adentro
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY(135, 11);
    $pdf->Cell(60, 7, 'Usuario: ' . $usuarioActual, 0, 1, 'R');
    
    $pdf->SetXY(32, 17);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(0, 5, 'Filtro de estado: ' . ucfirst($tipoFiltroEstado), 0, 1, 'L');

    $pdf->Ln(4);

    // Tarjetas de métricas superiores (KPIs) distribuidas en formato vertical (ancho total ~180 mm)
    $yCard = $pdf->GetY();
    $pdf->SetLineWidth(0.3);

    // Tarjeta 1: Total (57 mm)
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->Rect(15, $yCard, 57, 14, 'DF');
    $pdf->SetXY(17, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(28, 5, 'Total:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(25, 5, $totalRegistros, 0, 1, 'R');

    // Tarjeta 2: Activos (57 mm)
    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(187, 247, 208);
    $pdf->Rect(74, $yCard, 57, 14, 'DF');
    $pdf->SetXY(76, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(25, 5, 'Activos:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(20, 83, 45);
    $pdf->Cell(28, 5, $totalActivos, 0, 1, 'R');

    // Tarjeta 3: Inactivos (62 mm)
    $pdf->SetFillColor(254, 242, 242);
    $pdf->SetDrawColor(254, 202, 202);
    $pdf->Rect(133, $yCard, 62, 14, 'DF');
    $pdf->SetXY(135, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(185, 28, 28);
    $pdf->Cell(28, 5, 'Inactivos:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(153, 27, 27);
    $pdf->Cell(30, 5, $totalInactivos, 0, 1, 'R');

    $pdf->Ln(18);

    // Cabecera de la tabla PDF (Anchos proporcionales para sumar exactamente 180 mm)
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(30, 41, 59);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(48, 8.5, 'Nombre', 1, 0, 'L', true);
    $pdf->Cell(37, 8.5, 'Tipo', 1, 0, 'L', true);
    $pdf->Cell(25, 8.5, 'Costo Unit.', 1, 0, 'R', true);
    $pdf->Cell(28, 8.5, 'Stock Act.', 1, 0, 'C', true);
    $pdf->Cell(24, 8.5, 'Mín.', 1, 0, 'C', true);
    $pdf->Cell(18, 8.5, 'Estado', 1, 1, 'C', true);

    // Filas de datos con efecto Cebra
    $pdf->SetFont('helvetica', '', 9.5);
    $fill = false;

    if (empty($datosFiltrados)) {
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(180, 10, 'No se encontraron materias primas con los parámetros seleccionados.', 1, 1, 'C');
    } else {
        foreach ($datosFiltrados as $mp) {
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(30, 41, 59);

            $pdf->Cell(48, 8, substr($mp['nombre_materia_prima'], 0, 26), 'B', 0, 'L', true);
            $pdf->Cell(37, 8, substr($mp['nombre_de_material'] ?? 'Sin tipo', 0, 20), 'B', 0, 'L', true);
            $pdf->Cell(25, 8, number_format((float)$mp['costo_unitario'], 2), 'B', 0, 'R', true);
            $pdf->Cell(28, 8, $mp['stock_actual'] . ' ' . $mp['unidad_de_medida'], 'B', 0, 'C', true);
            $pdf->Cell(24, 8, $mp['stock_minimo'] . ' ' . $mp['unidad_de_medida'], 'B', 0, 'C', true);
            
            $estadoLower = strtolower($mp['status_materia_prima']);
            $pdf->SetFont('helvetica', 'B', 9);
            if ($estadoLower === 'activo') {
                $pdf->SetTextColor(16, 185, 129);
            } else {
                $pdf->SetTextColor(239, 68, 68);
            }
            
            $pdf->Cell(18, 8, ucfirst($mp['status_materia_prima']), 'B', 1, 'C', true);
            $pdf->SetFont('helvetica', '', 9.5);

            $fill = !$fill;
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
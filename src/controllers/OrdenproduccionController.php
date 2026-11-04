<?php
use Idealo\Models\OrdenDeProduccionModel;
$model = new OrdenDeProduccionModel();
$rutaVista  = __DIR__ . '/../view/orden_produccion/listarordenproduccion.php';
$estadosProduccion = ['Planificado', 'En Proceso', 'Finalizado', 'Inactiva', 'en espera'];

// ==========================================
// 1. CONTROL DE PETICIONES POST (Guardar, Editar)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // CASO A: Guardar nueva orden de producción
    if (isset($_POST["accion"]) && $_POST["accion"] === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->guardarOrden($_POST);
            echo json_encode([
                'success' => true, 
                'message' => 'Orden de producción registrada con éxito.',
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
    
    // CASO B: Editar orden de producción
    if (isset($_POST["accion"]) && $_POST["accion"] === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model->editarOrden($_POST);
            echo json_encode([
                'success' => true, 
                'message' => 'Orden de producción actualizada con éxito.',
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
} // Cierre del bloque POST

// ==========================================
// 2. CONTROL DE PETICIONES GET (Listar, Eliminar, Reportes)
// ==========================================

if (isset($_GET["accion"]) && $_GET["accion"] === "eliminar" && isset($_GET["id"])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $model->inactivarOrden($_GET["id"]);
        echo json_encode([
            'success' => true, 
            'message' => 'Orden de producción inactivada con éxito.',
            'evento' => 'inactivar',
            'estado' => 'completado'
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'evento' => 'inactivar',
            'estado' => 'error',
            'validacion' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (isset($_GET["accion"]) && $_GET["accion"] === "listar") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $data = $model->listarOrdenProduccion();
        echo json_encode([
            'success' => true, 
            'data' => $data,
            'evento' => 'listar',
            'estado' => 'completado'
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'evento' => 'listar',
            'estado' => 'error',
            'validacion' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (isset($_GET["accion"]) && $_GET["accion"] === "obtener_empleados" && isset($_GET["id"])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $empleadosAsignados = $model->obtenerEmpleadosPorOrden($_GET["id"]);
        echo json_encode([
            'success' => true, 
            'data' => $empleadosAsignados,
            'evento' => 'obtener_empleados',
            'estado' => 'completado'
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ==========================================
// REPORTE PDF VERTICAL CON ESTRUCTURA UNIFICADA
// ==========================================
if (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte") {
    if (ob_get_length()) ob_clean(); 
    
    require_once __DIR__ . '/../../vendor/autoload.php';

    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';
    if (!is_string($fechaDesde) || !is_string($fechaHasta)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El rango de fechas no es válido.');
    }
    $fechaDesde = trim($fechaDesde);
    $fechaHasta = trim($fechaHasta);
    $esFechaValida = static function (string $fecha): bool {
        if ($fecha === '') {
            return true;
        }

        $fechaParseada = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $fechaParseada !== false && $fechaParseada->format('Y-m-d') === $fecha;
    };

    if (!$esFechaValida($fechaDesde) || !$esFechaValida($fechaHasta) ||
        ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El rango de fechas no es válido.');
    }

    $estadosSolicitados = $_GET['estados'] ?? $estadosProduccion;
    if (!is_array($estadosSolicitados)) {
        $estadosSolicitados = [$estadosSolicitados];
    }

    $estadosPermitidos = array_fill_keys(array_map('strtolower', $estadosProduccion), true);
    $estadosFiltrados = [];
    foreach ($estadosSolicitados as $estadoSolicitado) {
        if (!is_string($estadoSolicitado)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            exit('El estado seleccionado no es válido.');
        }
        $estadoNormalizado = strtolower(trim($estadoSolicitado));
        if (!isset($estadosPermitidos[$estadoNormalizado])) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            exit('El estado seleccionado no es válido.');
        }
        $estadosFiltrados[$estadoNormalizado] = true;
    }

    $todasLasOrdenes = $model->listarOrdenProduccion();
    $ordenesFiltradas = array_filter($todasLasOrdenes, function ($orden) use ($estadosFiltrados, $fechaDesde, $fechaHasta) {
        $estado = strtolower(trim($orden['estado_de_produccion'] ?? ''));
        $fechaInicio = $orden['fecha_de_inicio'] ?? '';

        return isset($estadosFiltrados[$estado])
            && ($fechaDesde === '' || $fechaInicio >= $fechaDesde)
            && ($fechaHasta === '' || $fechaInicio <= $fechaHasta);
    });

    // Métricas para tarjetas de resumen
    $totalOrdenes = count($ordenesFiltradas);
    $conteosEstado = [];
    foreach ($ordenesFiltradas as $orden) {
        $estado = strtolower(trim((string) ($orden['estado_de_produccion'] ?? '')));
        $conteosEstado[$estado] = ($conteosEstado[$estado] ?? 0) + 1;
    }

    $totalPlanificadas = $conteosEstado['planificado'] ?? 0;
    $totalEnProceso = $conteosEstado['en proceso'] ?? 0;
    $totalFinalizadas = $conteosEstado['finalizado'] ?? 0;

    $usuarioActual = $_SESSION['nombre_usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? 'Administrador';

    // Clase extendida para el pie de página automático con numeración en vertical (ancho útil 180 mm)
    class MYPDF extends \TCPDF {
        public function Footer() {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 8.5);
            $this->SetTextColor(100, 116, 139);
            
            $this->SetDrawColor(203, 213, 225);
            $this->SetLineWidth(0.2);
            $this->Line(15, $this->GetY(), 195, $this->GetY());

            $this->Ln(2);
            $this->Cell(95, 5, 'Idéalo - Sistema de Gestión | Impreso el ' . date('d/m/Y H:i'), 0, 0, 'L');
            $this->Cell(85, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }

    // Orientación VERTICAL ('P')
    $pdf = new MYPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Idéalo');
    $pdf->SetAuthor('Idéalo');
    $pdf->SetTitle('Reporte de Producción');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(15, 12, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    // Encabezado corporativo y Logo
    $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
    if (file_exists($logoPath)) {
        $pdf->ImageSVG($logoPath, 15, 10, 14, 14);
    }

    $pdf->SetXY(32, 10);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(100, 7, 'Idéalo  -  Reporte de Producción', 0, 0, 'L');
    
    // Usuario posicionado hacia adentro
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY(135, 11);
    $pdf->Cell(60, 7, 'Usuario: ' . $usuarioActual, 0, 1, 'R');
    
    $rangoFechas = ($fechaDesde !== '' ? date('d/m/Y', strtotime($fechaDesde)) : 'Sin límite')
        . ' al '
        . ($fechaHasta !== '' ? date('d/m/Y', strtotime($fechaHasta)) : 'Sin límite');

    $pdf->SetXY(32, 17);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(0, 5, 'Periodo: ' . $rangoFechas, 0, 1, 'L');

    $pdf->Ln(4);

    // Tarjetas de métricas superiores (KPIs) distribuidas en formato vertical (ancho total ~180 mm)
    $yCard = $pdf->GetY();
    $pdf->SetLineWidth(0.3);

    // Tarjeta 1: Total (42 mm)
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->Rect(15, $yCard, 42, 14, 'DF');
    $pdf->SetXY(16, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(22, 5, 'Total:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(18, 5, $totalOrdenes, 0, 1, 'R');

    // Tarjeta 2: Planificadas (42 mm)
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->Rect(59, $yCard, 42, 14, 'DF');
    $pdf->SetXY(60, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(22, 5, 'Planif:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(51, 65, 85);
    $pdf->Cell(18, 5, $totalPlanificadas, 0, 1, 'R');

    // Tarjeta 3: En Proceso (42 mm)
    $pdf->SetFillColor(224, 242, 254);
    $pdf->SetDrawColor(186, 230, 253);
    $pdf->Rect(103, $yCard, 42, 14, 'DF');
    $pdf->SetXY(104, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(3, 105, 161);
    $pdf->Cell(22, 5, 'Proceso:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(12, 74, 110);
    $pdf->Cell(18, 5, $totalEnProceso, 0, 1, 'R');

    // Tarjeta 4: Finalizadas (48 mm)
    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(187, 247, 208);
    $pdf->Rect(147, $yCard, 48, 14, 'DF');
    $pdf->SetXY(148, $yCard + 3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(24, 5, 'Finalizadas:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(20, 83, 45);
    $pdf->Cell(21, 5, $totalFinalizadas, 0, 1, 'R');

    $pdf->Ln(18);

    // Cabecera de la tabla PDF (Suma exacta = 180 mm)
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(30, 41, 59);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(12, 8.5, 'ID', 1, 0, 'C', true);
    $pdf->Cell(25, 8.5, 'Inicio', 1, 0, 'C', true);
    $pdf->Cell(25, 8.5, 'Fin', 1, 0, 'C', true);
    $pdf->Cell(85, 8.5, 'Descripción del Pedido', 1, 0, 'L', true);
    $pdf->Cell(33, 8.5, 'Estado', 1, 1, 'C', true);

    // Filas de datos con efecto Cebra
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;

    if (empty($ordenesFiltradas)) {
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(180, 10, 'No hay órdenes para mostrar.', 1, 1, 'C');
    } else {
        foreach (array_values($ordenesFiltradas) as $orden) {
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(30, 41, 59);

            $inicio = !empty($orden['fecha_de_inicio']) ? date('d/m/Y', strtotime($orden['fecha_de_inicio'])) : 'N/A';
            $fin = !empty($orden['fecha_terminado']) ? date('d/m/Y', strtotime($orden['fecha_terminado'])) : 'N/A';
            $descripcion = htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8');
            $estado = ucfirst(strtolower($orden['estado_de_produccion'] ?? 'Sin estado'));

            $pdf->Cell(12, 8, '#' . htmlspecialchars((string) ($orden['id_produccion'] ?? ''), ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
            $pdf->Cell(25, 8, $inicio, 'B', 0, 'C', true);
            $pdf->Cell(25, 8, $fin, 'B', 0, 'C', true);
            $pdf->Cell(85, 8, substr($descripcion, 0, 45), 'B', 0, 'L', true);
            
            $estadoLower = strtolower($orden['estado_de_produccion'] ?? '');
            $pdf->SetFont('helvetica', 'B', 8.5);
            if ($estadoLower === 'finalizado') {
                $pdf->SetTextColor(16, 185, 129);
            } elseif ($estadoLower === 'en proceso') {
                $pdf->SetTextColor(14, 165, 233);
            } else {
                $pdf->SetTextColor(180, 83, 9);
            }

            $pdf->Cell(33, 8, $estado, 'B', 1, 'C', true);
            $pdf->SetFont('helvetica', '', 9);

            $fill = !$fill;
        }
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    $pdf->Output('Reporte_Produccion.pdf', 'I'); 
    exit;
}

// ==========================================
// 3. CARGA DE LA VISTA
// ==========================================
if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}
$detallesPedido = $model->obtenerPedidosParaProduccion();
$empleados = $model->obtenerEmpleadosActivos();

require_once $rutaVista;
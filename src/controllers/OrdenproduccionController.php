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

    require_once __DIR__ . '/../helpers/ReportePdf.php';
    $pdf = new \Idealo\Helpers\ReportePdf('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setCompactReportDetails(
        'Reporte de Producción',
        'Periodo: ' . ($fechaDesde !== '' ? date('d/m/Y', strtotime($fechaDesde)) : 'Sin límite')
            . ' al '
            . ($fechaHasta !== '' ? date('d/m/Y', strtotime($fechaHasta)) : 'Sin límite')
    );
    $pdf->AddPage();
    $pdf->addCompactReportHeader();
    $pdf->addMetricCards([
        ['label' => 'Total:', 'value' => (string) $totalOrdenes, 'width' => 42],
        ['label' => 'Planificadas:', 'value' => (string) $totalPlanificadas, 'width' => 42],
        ['label' => 'En Proceso:', 'value' => (string) $totalEnProceso, 'width' => 42],
        ['label' => 'Finalizadas:', 'value' => (string) $totalFinalizadas, 'width' => 48],
    ]);
    $pdf->addTableHeader([
        ['width' => 25, 'label' => 'Inicio', 'align' => 'C'],
        ['width' => 25, 'label' => 'Fin', 'align' => 'C'],
        ['width' => 97, 'label' => 'Descripción del Pedido', 'align' => 'L'],
        ['width' => 33, 'label' => 'Estado', 'align' => 'C'],
    ]);

    if (empty($ordenesFiltradas)) {
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(180, 10, 'No hay órdenes para mostrar.', 1, 1, 'C');
    } else {
        foreach (array_values($ordenesFiltradas) as $index => $orden) {
            $inicio = !empty($orden['fecha_de_inicio']) ? date('d/m/Y', strtotime($orden['fecha_de_inicio'])) : 'N/A';
            $fin = !empty($orden['fecha_terminado']) ? date('d/m/Y', strtotime($orden['fecha_terminado'])) : 'N/A';
            $descripcion = htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8');
            $estado = ucfirst(strtolower($orden['estado_de_produccion'] ?? 'Sin estado'));
            $estadoLower = strtolower((string) ($orden['estado_de_produccion'] ?? ''));
            if ($estadoLower === 'finalizado') {
                $colorEstado = [16, 185, 129];
            } elseif ($estadoLower === 'en proceso') {
                $colorEstado = [14, 165, 233];
            } else {
                $colorEstado = [180, 83, 9];
            }
            $pdf->addTableRow([
                ['width' => 25, 'text' => $inicio, 'align' => 'C'],
                ['width' => 25, 'text' => $fin, 'align' => 'C'],
                ['width' => 97, 'text' => substr($descripcion, 0, 55), 'align' => 'L'],
                [
                    'width' => 33,
                    'text' => $estado,
                    'align' => 'C',
                    'fontStyle' => 'B',
                    'fontSize' => 8.5,
                    'textColor' => $colorEstado,
                ],
            ], $index % 2 !== 0);
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
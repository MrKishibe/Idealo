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
// NUEVO: GENERAR REPORTE PDF CON TCPDF
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

    require_once __DIR__ . '/../helpers/ReportePdf.php';

    $pdf = new \Idealo\Helpers\ReportePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('TCPDF');
    $pdf->SetAuthor('Sistema de Gestión');
    $pdf->SetTitle('Reporte de Producción');
    $etiquetaEstados = count($estadosFiltrados) === count($estadosProduccion)
        ? 'Todos'
        : implode(', ', array_map(static function ($estado) use ($estadosProduccion) {
            foreach ($estadosProduccion as $estadoDisponible) {
                if (strtolower($estadoDisponible) === $estado) {
                    return $estadoDisponible;
                }
            }
            return $estado;
        }, array_keys($estadosFiltrados)));
    $rangoFechas = ($fechaDesde !== '' ? date('d/m/Y', strtotime($fechaDesde)) : 'Sin límite')
        . ' - '
        . ($fechaHasta !== '' ? date('d/m/Y', strtotime($fechaHasta)) : 'Sin límite');
    $pdf->setReportDetails(
        'Reporte de Órdenes de Producción',
        'Detalle de producción | Estados: ' . $etiquetaEstados . ' | Inicio: ' . $rangoFechas
    );
    $pdf->AddPage();

    $conteosEstado = [];
    foreach ($ordenesFiltradas as $orden) {
        $estado = strtolower(trim((string) ($orden['estado_de_produccion'] ?? '')));
        $conteosEstado[$estado] = ($conteosEstado[$estado] ?? 0) + 1;
    }
    $pdf->addSummaryCards([
        ['label' => 'Total de órdenes', 'value' => (string) count($ordenesFiltradas)],
        ['label' => 'Planificadas', 'value' => (string) ($conteosEstado['planificado'] ?? 0)],
        ['label' => 'En proceso', 'value' => (string) ($conteosEstado['en proceso'] ?? 0)],
        ['label' => 'Finalizadas', 'value' => (string) ($conteosEstado['finalizado'] ?? 0)]
    ]);

    $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="5" cellspacing="0" width="100%">
                <thead><tr bgcolor="#174B55">
                    <th width="10%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
                    <th width="17%" align="center"><font color="#FFFFFF"><b>Inicio</b></font></th>
                    <th width="17%" align="center"><font color="#FFFFFF"><b>Fin</b></font></th>
                    <th width="36%" align="left"><font color="#FFFFFF"><b>Descripción del Pedido</b></font></th>
                    <th width="20%" align="center"><font color="#FFFFFF"><b>Estado</b></font></th>
                </tr></thead><tbody>';

    if (empty($ordenesFiltradas)) {
        $html .= '<tr bgcolor="#F3F7F8"><td colspan="5" align="center">No hay órdenes para mostrar.</td></tr>';
    } else {
        foreach (array_values($ordenesFiltradas) as $index => $orden) {
            $inicio = !empty($orden['fecha_de_inicio']) ? date('d/m/Y', strtotime($orden['fecha_de_inicio'])) : 'N/A';
            $fin = !empty($orden['fecha_terminado']) ? date('d/m/Y', strtotime($orden['fecha_terminado'])) : 'N/A';
            $descripcion = htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8');
            $estado = htmlspecialchars($orden['estado_de_produccion'] ?? 'Sin estado', ENT_QUOTES, 'UTF-8');
            $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';

            $html .= '<tr bgcolor="' . $fondoFila . '">
                        <td width="10%" align="center">#' . htmlspecialchars((string) ($orden['id_produccion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                        <td width="17%" align="center">' . $inicio . '</td>
                        <td width="17%" align="center">' . $fin . '</td>
                        <td width="36%">' . $descripcion . '</td>
                        <td width="20%" align="center">' . $estado . '</td>
                    </tr>';
        }
    }
    $html .= '</tbody></table>';

    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($html, true, false, true, false, '');

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
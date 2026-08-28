<?php
use Idealo\Models\OrdenDeProduccionModel;
$model = new OrdenDeProduccionModel();
$rutaVista  = __DIR__ . '/../view/orden_produccion/listarordenproduccion.php';

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
                'message' => '✅ Orden de producción registrada con éxito.',
                'evento' => 'guardar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
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
                'message' => '✅ Orden de producción actualizada con éxito.',
                'evento' => 'editar',
                'estado' => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '❌ ' . $e->getMessage(),
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
            'message' => '✅ Orden de producción inactivada con éxito.',
            'evento' => 'inactivar',
            'estado' => 'completado'
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => '❌ ' . $e->getMessage(),
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
            'message' => '❌ ' . $e->getMessage(),
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
            'message' => '❌ ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ==========================================
// NUEVO: GENERAR REPORTE PDF CON TCPDF
// ==========================================
if (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte") {
    if (ob_get_length()) ob_clean(); 
    
    // NOTA: Ajusta esta ruta si te dice que no encuentra el autoload.php
    require_once __DIR__ . '/../../vendor/autoload.php';

    $estadoFiltro = $_GET['estado'] ?? 'activas';
    $verInactivas = ($estadoFiltro === 'inactivas');

    // Reutilizamos el modelo para traer las órdenes
    $todasLasOrdenes = $model->listarOrdenProduccion();

    // Filtramos
    $ordenesFiltradas = array_filter($todasLasOrdenes, function($orden) use ($verInactivas) {
        $esInactiva = (strtolower($orden['estado_de_produccion'] ?? '') === 'inactiva');
        return $verInactivas ? $esInactiva : !$esInactiva;
    });

    // Inicializamos TCPDF
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('TCPDF');
    $pdf->SetAuthor('Sistema de Gestión');
    $pdf->SetTitle('Reporte de Producción');
    $pdf->SetMargins(15, 15, 15);
    $pdf->setPrintHeader(false); 
    $pdf->setPrintFooter(false); 
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $tituloReporte = $verInactivas ? 'Reporte de Órdenes Inactivas' : 'Reporte de Órdenes Activas';
    $pdf->Cell(0, 10, $tituloReporte, 0, 1, 'C');
    $pdf->Ln(5); 

    // Construimos la tabla
    $html = '<table border="1" cellpadding="5">
                <tr style="background-color:#343a40; color:#ffffff; font-weight:bold; text-align:center;">
                    <th width="10%">ID</th>
                    <th width="20%">Inicio</th>
                    <th width="20%">Fin</th>
                    <th width="30%">Descripción del Pedido</th>
                    <th width="20%">Estado</th>
                </tr>';

    if (empty($ordenesFiltradas)) {
        $html .= '<tr><td colspan="5" align="center">No hay órdenes para mostrar.</td></tr>';
    } else {
        foreach ($ordenesFiltradas as $orden) {
            $inicio = !empty($orden['fecha_de_inicio']) ? date('d/m/Y', strtotime($orden['fecha_de_inicio'])) : 'N/A';
            $fin = !empty($orden['fecha_terminado']) ? date('d/m/Y', strtotime($orden['fecha_terminado'])) : 'N/A';
            $descripcion = htmlspecialchars($orden['descripcion_pedido'] ?? 'Sin descripción');
            $estado = htmlspecialchars($orden['estado_de_produccion'] ?? 'Sin estado');

            $html .= '<tr>
                        <td align="center">#' . $orden['id_produccion'] . '</td>
                        <td align="center">' . $inicio . '</td>
                        <td align="center">' . $fin . '</td>
                        <td>' . $descripcion . '</td>
                        <td align="center">' . $estado . '</td>
                    </tr>';
        }
    }
    
    $html .= '</table>';

    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($html, true, false, true, false, '');

    // Imprime el PDF en la pestaña
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
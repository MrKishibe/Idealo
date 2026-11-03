<?php
// FinanzasController.php - Procedimental con validación estricta y eventos (Estilo Cliente)

use Idealo\Models\ControlPagosModel;
use Idealo\Models\CuentaEmpresaModel;
use Idealo\Models\MetodoPagoModel;

$pagosModel = new ControlPagosModel();
$cuentasModel = new CuentaEmpresaModel();
$metodosModel = new MetodoPagoModel();

$action = $_GET['action'] ?? 'pagos'; 

//
//  CRUD
// 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    $accion = $_POST['accion'] ?? '';
    $entidad = $_POST['entidad'] ?? ''; 

  
    $modelo = match($entidad) {
        'cuenta' => $cuentasModel,
        'pago'   => $pagosModel,
        'metodo' => $metodosModel,
        default  => null
    };

    if (!$modelo) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => ' Entidad no válida o no especificada.',
            'evento' => $accion,
            'estado' => 'error'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // guardar registro
    if ($accion === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $metodoGuardar = "guardar" . ucfirst($entidad); // Ej: guardarCuenta, guardarPago
            if (method_exists($modelo, $metodoGuardar)) {
                $modelo->$metodoGuardar($_POST);
                echo json_encode([
                    'success' => true, 
                    'message' => ' Registro guardado con éxito.',
                    'evento' => 'guardar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de guardado no implementado para esta entidad.");
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => ' ' . $e->getMessage(),
                'evento' => 'guardar',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // editar registro
    if ($accion === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $metodoEditar = "editar" . ucfirst($entidad); // Ej: editarCuenta, editarPago
            if (method_exists($modelo, $metodoEditar)) {
                $modelo->$metodoEditar($_POST);
                echo json_encode([
                    'success' => true, 
                    'message' => ' Registro actualizado con éxito.',
                    'evento' => 'editar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de edición no implementado para esta entidad.");
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => '' . $e->getMessage(),
                'evento' => 'editar',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // cambiar estado 
    if ($accion === "cambiar_estado") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = $_POST['id'] ?? null;
            $nuevoEstado = $_POST['nuevo_estado'] ?? 'inhabilitado'; 
            
            if (!$id) throw new \Exception("ID de registro no proporcionado.");

            if (method_exists($modelo, 'actualizarEstado')) {
                $modelo->actualizarEstado($id, $nuevoEstado);
                $mensaje = ($nuevoEstado === 'inhabilitado') ? ' Registro inhabilitado correctamente.' : ' Registro activado correctamente.';
                
                echo json_encode([
                    'success' => true, 
                    'message' => $mensaje,
                    'evento' => 'cambiar_estado',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de actualización de estado no implementado.");
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => ' ' . $e->getMessage(),
                'evento' => 'cambiar_estado',
                'estado' => 'error',
                'validacion' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    exit;
}

//
//  GET CARGA DE DATOS Y REPORTES
// 

if (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte") {
    if (ob_get_length()) ob_clean(); 
    
    require_once __DIR__ . '/../../vendor/autoload.php';

    $tipoReporte = $_GET['tipo'] ?? 'cuentas';
    $estadoFiltro = $_GET['estado'] ?? 'activos';
    $verInhabilitados = ($estadoFiltro === 'inhabilitados');

    if ($tipoReporte === 'cuentas') {
        $tituloReporte = $verInhabilitados ? 'Reporte de Cuentas Inhabilitadas' : 'Reporte de Cuentas Activas';
    } elseif ($tipoReporte === 'pagos') {
        $tituloReporte = $verInhabilitados ? 'Reporte de Pagos Inhabilitados' : 'Reporte de Pagos Activos';
    } else {
        $tituloReporte = $verInhabilitados ? 'Reporte de Métodos Inhabilitados' : 'Reporte de Métodos Activos';
    }

    require_once __DIR__ . '/../helpers/ReportePdf.php';
    $pdf = new \Idealo\Helpers\ReportePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('TCPDF');
    $pdf->SetAuthor('Sistema de Gestión - Idealo');
    $pdf->SetTitle('Reporte Financiero');
    $pdf->setReportDetails($tituloReporte, 'Detalle financiero | Estado: ' . ($verInhabilitados ? 'Inhabilitados' : 'Activos'));
    $pdf->AddPage();

    if ($tipoReporte === 'cuentas') {
        $todasLasCuentas = $cuentasModel->listarCuentas();
        $cuentasFiltradas = array_filter($todasLasCuentas, function($cta) use ($verInhabilitados) {
            $esInhabilitado = (strtolower($cta['estado_cuenta'] ?? '') === 'inhabilitado');
            return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
        });

        $pdf->addSummaryCards([
            ['label' => 'Total de cuentas', 'value' => (string) count($cuentasFiltradas)],
            ['label' => 'Estado aplicado', 'value' => $verInhabilitados ? 'Inhabilitadas' : 'Activas']
        ]);
        $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="5" cellspacing="0" width="100%">
                    <thead><tr bgcolor="#174B55">
                        <th width="15%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
                        <th width="35%" align="left"><font color="#FFFFFF"><b>Titular</b></font></th>
                        <th width="30%" align="left"><font color="#FFFFFF"><b>Identificador</b></font></th>
                        <th width="20%" align="left"><font color="#FFFFFF"><b>Tipo</b></font></th>
                    </tr></thead><tbody>';

        if (empty($cuentasFiltradas)) {
            $html .= '<tr bgcolor="#F3F7F8"><td colspan="4" align="center">No hay cuentas para mostrar.</td></tr>';
        } else {
            foreach (array_values($cuentasFiltradas) as $index => $cuenta) {
                $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';
                $html .= '<tr bgcolor="' . $fondoFila . '">
                            <td width="15%" align="center">#' . htmlspecialchars((string) ($cuenta['id_cuenta'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="35%">' . htmlspecialchars($cuenta['titular'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="30%">' . htmlspecialchars($cuenta['identificador'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="20%">' . htmlspecialchars($cuenta['tipo_cuenta'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>';
            }
        }
        $html .= '</tbody></table>';
    } elseif ($tipoReporte === 'pagos') {
        $todosLosPagos = $pagosModel->listarPagos();
        $pagosFiltrados = array_filter($todosLosPagos, function($pago) use ($verInhabilitados) {
            $esInhabilitado = (strtolower($pago['estado'] ?? '') === 'inhabilitado');
            return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
        });

        $montoTotalPagos = array_sum(array_map(
            static function ($pago) {
                return (float) ($pago['monto_pago'] ?? 0);
            },
            $pagosFiltrados
        ));
        $pdf->addSummaryCards([
            ['label' => 'Total de pagos', 'value' => (string) count($pagosFiltrados)],
            ['label' => 'Estado aplicado', 'value' => $verInhabilitados ? 'Inhabilitados' : 'Activos'],
            ['label' => 'Monto abonado', 'value' => '$ ' . number_format($montoTotalPagos, 2, '.', ',')]
        ]);
        $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="5" cellspacing="0" width="100%">
                    <thead><tr bgcolor="#174B55">
                        <th width="10%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
                        <th width="20%" align="center"><font color="#FFFFFF"><b>Referencia</b></font></th>
                        <th width="15%" align="center"><font color="#FFFFFF"><b>Pedido</b></font></th>
                        <th width="20%" align="right"><font color="#FFFFFF"><b>Monto Abonado</b></font></th>
                        <th width="20%" align="left"><font color="#FFFFFF"><b>Método</b></font></th>
                        <th width="15%" align="center"><font color="#FFFFFF"><b>Estado</b></font></th>
                    </tr></thead><tbody>';

        if (empty($pagosFiltrados)) {
            $html .= '<tr bgcolor="#F3F7F8"><td colspan="6" align="center">No hay pagos para mostrar.</td></tr>';
        } else {
            foreach (array_values($pagosFiltrados) as $index => $pago) {
                $estadoPago = ucfirst($pago['estado'] ?? 'procesado');
                $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';
                $html .= '<tr bgcolor="' . $fondoFila . '">
                            <td width="10%" align="center">#' . htmlspecialchars((string) ($pago['id_pago'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="20%" align="center">' . htmlspecialchars($pago['referencia'] ?: 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="15%" align="center">#' . htmlspecialchars((string) ($pago['id_pedido'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="20%" align="right">$ ' . number_format((float) ($pago['monto_pago'] ?? 0), 2) . '</td>
                            <td width="20%">' . htmlspecialchars($pago['nombre_metodo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="15%" align="center">' . htmlspecialchars($estadoPago, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>';
            }
        }
        $html .= '</tbody></table>';
    } else {
        $todosLosMetodos = $metodosModel->listarMetodos();
        $metodosFiltrados = array_filter($todosLosMetodos, function($met) use ($verInhabilitados) {
            $esInhabilitado = (strtolower($met['status_metodo_de_pago'] ?? '') === 'inhabilitado');
            return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
        });

        $pdf->addSummaryCards([
            ['label' => 'Total de métodos', 'value' => (string) count($metodosFiltrados)],
            ['label' => 'Estado aplicado', 'value' => $verInhabilitados ? 'Inhabilitados' : 'Activos']
        ]);
        $html = '<table border="1" bordercolor="#DCE5E8" cellpadding="5" cellspacing="0" width="100%">
                    <thead><tr bgcolor="#174B55">
                        <th width="20%" align="center"><font color="#FFFFFF"><b>ID</b></font></th>
                        <th width="60%" align="left"><font color="#FFFFFF"><b>Nombre del Método</b></font></th>
                        <th width="20%" align="center"><font color="#FFFFFF"><b>Estado</b></font></th>
                    </tr></thead><tbody>';

        if (empty($metodosFiltrados)) {
            $html .= '<tr bgcolor="#F3F7F8"><td colspan="3" align="center">No hay métodos para mostrar.</td></tr>';
        } else {
            foreach (array_values($metodosFiltrados) as $index => $met) {
                $estadoMet = ucfirst($met['status_metodo_de_pago'] ?? 'activo');
                $fondoFila = $index % 2 === 0 ? '#FFFFFF' : '#F3F7F8';
                $html .= '<tr bgcolor="' . $fondoFila . '">
                            <td width="20%" align="center">#' . htmlspecialchars((string) ($met['id_metodo_de_pago'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="60%">' . htmlspecialchars($met['nombre_metodo_de_pago'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                            <td width="20%" align="center">' . htmlspecialchars($estadoMet, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>';
            }
        }
        $html .= '</tbody></table>';
    }

    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($html, true, false, true, false, '');
    if ($tipoReporte === 'pagos') {
        $pdf->Ln(2);
        $pdf->addTotalBanner('Monto Total Acumulado', '$ ' . number_format($montoTotalPagos, 2, '.', ','));
    }

    $pdf->Output('Reporte_Financiero.pdf', 'I'); 
    exit;
}

switch ($action) {
    case 'pagos':
        $pagos = $pagosModel->listarPagos();
        $pedidos = $pagosModel->obtenerPedidosActivos();
        $metodos = $pagosModel->obtenerMetodosPago();
        break;
    case 'cuentas':
        $cuentas = $cuentasModel->listarCuentas();
        $metodos = $cuentasModel->obtenerMetodosPago();
        break;
    case 'metodos':
        $metodos = $metodosModel->listarMetodos();
        break;
}

// 
//  CARGA DE LA VISTA
// 
$rutaVista = __DIR__ . '/../view/finanzas/' . $action . '.php';

if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;
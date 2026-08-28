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
// 1. CONTROL DE PETICIONES POST (CRUD)
// 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    $accion = $_POST['accion'] ?? '';
    $entidad = $_POST['entidad'] ?? ''; // 'cuenta', 'pago', 'metodo'

    // Seleccionar el modelo correspondiente de forma dinámica
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
            'message' => '❌ Entidad no válida o no especificada.',
            'evento' => $accion,
            'estado' => 'error'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Guardar registro
    if ($accion === "guardar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $metodoGuardar = "guardar" . ucfirst($entidad); // Ej: guardarCuenta, guardarPago
            if (method_exists($modelo, $metodoGuardar)) {
                $modelo->$metodoGuardar($_POST);
                echo json_encode([
                    'success' => true, 
                    'message' => '✅ Registro guardado con éxito.',
                    'evento' => 'guardar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de guardado no implementado para esta entidad.");
            }
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
    
    // Editar registro
    if ($accion === "editar") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $metodoEditar = "editar" . ucfirst($entidad); // Ej: editarCuenta, editarPago
            if (method_exists($modelo, $metodoEditar)) {
                $modelo->$metodoEditar($_POST);
                echo json_encode([
                    'success' => true, 
                    'message' => '✅ Registro actualizado con éxito.',
                    'evento' => 'editar',
                    'estado' => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception("Método de edición no implementado para esta entidad.");
            }
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

    // Cambiar estado (Inhabilitar / Activar)
    if ($accion === "cambiar_estado") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = $_POST['id'] ?? null;
            $nuevoEstado = $_POST['nuevo_estado'] ?? 'inhabilitado'; 
            
            if (!$id) throw new \Exception("ID de registro no proporcionado.");

            if (method_exists($modelo, 'actualizarEstado')) {
                $modelo->actualizarEstado($id, $nuevoEstado);
                $mensaje = ($nuevoEstado === 'inhabilitado') ? '✅ Registro inhabilitado correctamente.' : '✅ Registro activado correctamente.';
                
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
                'message' => '❌ ' . $e->getMessage(),
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
// 2. CONTROL DE PETICIONES GET (CARGA DE DATOS Y REPORTES)
// 

// GENERAR REPORTE PDF CON TCPDF PARA FINANZAS
if (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte") {
    if (ob_get_length()) ob_clean(); 
    
    require_once __DIR__ . '/../../vendor/autoload.php';

    $tipoReporte = $_GET['tipo'] ?? 'cuentas';
    $estadoFiltro = $_GET['estado'] ?? 'activos';
    $verInhabilitados = ($estadoFiltro === 'inhabilitados');

    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('TCPDF');
    $pdf->SetAuthor('Sistema de Gestión - Idealo');
    $pdf->SetTitle('Reporte Financiero');
    $pdf->SetMargins(15, 15, 15);
    $pdf->setPrintHeader(false); 
    $pdf->setPrintFooter(false); 
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    
    if ($tipoReporte === 'cuentas') {
        $tituloReporte = $verInhabilitados ? 'Reporte de Cuentas Inhabilitadas' : 'Reporte de Cuentas Activas';
    } else {
        $tituloReporte = $verInhabilitados ? 'Reporte de Métodos Inhabilitados' : 'Reporte de Métodos Activos';
    }
    
    $pdf->Cell(0, 10, $tituloReporte, 0, 1, 'C');
    $pdf->Ln(5); 

    if ($tipoReporte === 'cuentas') {
        $todasLasCuentas = $cuentasModel->listarCuentas();
        $cuentasFiltradas = array_filter($todasLasCuentas, function($cta) use ($verInhabilitados) {
            $esInhabilitado = (strtolower($cta['estado_cuenta'] ?? '') === 'inhabilitado');
            return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
        });

        $html = '<table border="1" cellpadding="5">
                    <tr style="background-color:#343a40; color:#ffffff; font-weight:bold; text-align:center;">
                        <th width="15%">ID</th>
                        <th width="35%">Titular</th>
                        <th width="30%">Identificador</th>
                        <th width="20%">Tipo</th>
                    </tr>';

        if (empty($cuentasFiltradas)) {
            $html .= '<tr><td colspan="4" align="center">No hay cuentas para mostrar.</td></tr>';
        } else {
            foreach ($cuentasFiltradas as $cuenta) {
                $html .= '<tr>
                            <td align="center">#' . ($cuenta['id_cuenta'] ?? '') . '</td>
                            <td>' . htmlspecialchars($cuenta['titular'] ?? 'N/A') . '</td>
                            <td><code>' . htmlspecialchars($cuenta['identificador'] ?? 'N/A') . '</code></td>
                            <td>' . htmlspecialchars($cuenta['tipo_cuenta'] ?? 'N/A') . '</td>
                        </tr>';
            }
        }
        $html .= '</table>';
    } else {
        $todosLosMetodos = $metodosModel->listarMetodos();
        $metodosFiltrados = array_filter($todosLosMetodos, function($met) use ($verInhabilitados) {
            $esInhabilitado = (strtolower($met['status_metodo_de_pago'] ?? '') === 'inhabilitado');
            return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
        });

        $html = '<table border="1" cellpadding="5">
                    <tr style="background-color:#343a40; color:#ffffff; font-weight:bold; text-align:center;">
                        <th width="20%">ID</th>
                        <th width="60%">Nombre del Método</th>
                        <th width="20%">Estado</th>
                    </tr>';

        if (empty($metodosFiltrados)) {
            $html .= '<tr><td colspan="3" align="center">No hay métodos para mostrar.</td></tr>';
        } else {
            foreach ($metodosFiltrados as $met) {
                $estadoMet = ucfirst($met['status_metodo_de_pago'] ?? 'activo');
                $html .= '<tr>
                            <td align="center">#' . ($met['id_metodo_de_pago'] ?? '') . '</td>
                            <td>' . htmlspecialchars($met['nombre_metodo_de_pago'] ?? 'N/A') . '</td>
                            <td align="center">' . $estadoMet . '</td>
                        </tr>';
            }
        }
        $html .= '</table>';
    }

    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($html, true, false, true, false, '');

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
// 3. VERIFICACIÓN Y CARGA DE LA VISTA
// 
$rutaVista = __DIR__ . '/../view/finanzas/' . $action . '.php';

if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;
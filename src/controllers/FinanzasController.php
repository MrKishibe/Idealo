<?php
use Idealo\Models\ControlPagosModel;
use Idealo\Models\CuentaEmpresaModel;
use Idealo\Models\MetodoPagoModel;

if (!function_exists('responderJSON')) {
    function responderJSON($success, $message, $evento = '', $extra = []) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
            'evento'  => $evento,
            'estado'  => $success ? 'completado' : 'error'
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('subirComprobanteArchivo')) {
    function subirComprobanteArchivo() {
        if (!isset($_FILES['comprobante_pago']) || $_FILES['comprobante_pago']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $directorio_destino = __DIR__ . '/../../assets/uploads/comprobantes/';
        $directorio_publico = 'assets/uploads/comprobantes/';
        
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        $archivo_tmp = $_FILES['comprobante_pago']['tmp_name'];
        $nombre_original = $_FILES['comprobante_pago']['name'];
        $tamanio = $_FILES['comprobante_pago']['size'];
        $tipo = mime_content_type($archivo_tmp);

        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($tipo, $tipos_permitidos)) {
            throw new \Exception("Tipo de archivo no permitido. Sube imágenes o PDF.");
        }
        if ($tamanio > (2 * 1024 * 1024)) {
            throw new \Exception("El archivo supera los 2MB permitidos.");
        }

        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
        $nombre_nuevo = 'comprobante_' . time() . '_' . uniqid() . '.' . $extension; 
        
        if (move_uploaded_file($archivo_tmp, $directorio_destino . $nombre_nuevo)) {
            return $directorio_publico . $nombre_nuevo;
        }
        
        throw new \Exception("Error al intentar guardar el archivo en el servidor.");
    }
}

if (!function_exists('procesarAccionCRUD')) {
    function procesarAccionCRUD($modelo, $accion, $entidad) {
        try {
            if ($entidad === 'pago') {
                $rutaArchivo = subirComprobanteArchivo();
                if ($rutaArchivo !== null) {
                    $_POST['comprobante_pago'] = $rutaArchivo;
                }
            }

            if ($accion === 'guardar') {
                $metodo = "guardar" . ucfirst($entidad);
                if (!method_exists($modelo, $metodo)) throw new \Exception("Método no implementado.");
                $modelo->$metodo($_POST);
                responderJSON(true, 'Registro guardado con éxito.', 'guardar');
            }

            if ($accion === 'editar') {
                $metodo = "editar" . ucfirst($entidad);
                if (!method_exists($modelo, $metodo)) throw new \Exception("Método no implementado.");
                $modelo->$metodo($_POST);
                responderJSON(true, 'Registro actualizado con éxito.', 'editar');
            }

            if ($accion === 'cambiar_estado') {
                $id = $_POST['id'] ?? null;
                $nuevoEstado = $_POST['nuevo_estado'] ?? 'inhabilitado';
                if (!$id) throw new \Exception("ID de registro no proporcionado.");
                
                if (method_exists($modelo, 'actualizarEstado')) {
                    $modelo->actualizarEstado($id, $nuevoEstado);
                } elseif (method_exists($modelo, 'cambiarEstadoPago')) {
                    $modelo->cambiarEstadoPago($id, $nuevoEstado);
                } else {
                    throw new \Exception("Método de estado no implementado.");
                }

                $msj = ($nuevoEstado === 'inhabilitado') ? 'Registro inhabilitado correctamente.' : 'Registro activado correctamente.';
                responderJSON(true, $msj, 'cambiar_estado');
            }
        } catch (\Exception $e) {
            responderJSON(false, $e->getMessage(), $accion, ['validacion' => $e->getMessage()]);
        }
    }
}

if (!function_exists('generarReportePDF')) {
    function generarReportePDF($cuentasModel, $pagosModel, $metodosModel) {
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

        $etiquetaEstado = $verInhabilitados ? 'Inhabilitados' : 'Activos';
        require_once __DIR__ . '/../helpers/ReportePdf.php';
        $pdf = new \Idealo\Helpers\ReportePdf('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setCompactReportDetails(
            $tituloReporte,
            'Detalle financiero | Estado: ' . $etiquetaEstado
        );
        $pdf->AddPage();
        $pdf->addCompactReportHeader();

        if ($tipoReporte === 'cuentas') {
            $todasLasCuentas = $cuentasModel->listarCuentas();
            $cuentasFiltradas = array_filter($todasLasCuentas, function($cta) use ($verInhabilitados) {
                $esInhabilitado = (strtolower($cta['estado'] ?? '') === 'inhabilitado');
                return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
            });

            $pdf->addMetricCards([
                ['label' => 'Total de cuentas:', 'value' => (string) count($cuentasFiltradas), 'width' => 88],
                ['label' => 'Estado aplicado:', 'value' => $etiquetaEstado, 'width' => 88],
            ]);
            $pdf->addTableHeader([
                ['width' => 70, 'label' => 'Titular'],
                ['width' => 70, 'label' => 'Identificador'],
                ['width' => 40, 'label' => 'Tipo'],
            ]);

            if (empty($cuentasFiltradas)) {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell(180, 10, 'No hay cuentas para mostrar.', 1, 1, 'C');
            } else {
                foreach (array_values($cuentasFiltradas) as $index => $cuenta) {
                    $pdf->addTableRow([
                        ['width' => 70, 'text' => substr((string) ($cuenta['titular'] ?? 'N/A'), 0, 34)],
                        ['width' => 70, 'text' => substr((string) ($cuenta['identificador'] ?? 'N/A'), 0, 34)],
                        ['width' => 40, 'text' => substr((string) ($cuenta['tipo_cuenta'] ?? 'N/A'), 0, 20)],
                    ], $index % 2 !== 0, 8);
                }
            }

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

            $pdf->addMetricCards([
                ['label' => 'Total pagos:', 'value' => (string) count($pagosFiltrados), 'width' => 57],
                ['label' => 'Estado:', 'value' => $etiquetaEstado, 'width' => 57],
                ['label' => 'Abonado:', 'value' => '$ ' . number_format($montoTotalPagos, 2, '.', ','), 'width' => 62],
            ]);
            $pdf->addTableHeader([
                ['width' => 38, 'label' => 'Referencia', 'align' => 'C'],
                ['width' => 22, 'label' => 'Pedido', 'align' => 'C'],
                ['width' => 40, 'label' => 'Monto', 'align' => 'R'],
                ['width' => 60, 'label' => 'Método'],
                ['width' => 20, 'label' => 'Estado', 'align' => 'C'],
            ]);

            if (empty($pagosFiltrados)) {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell(180, 10, 'No hay pagos para mostrar.', 1, 1, 'C');
            } else {
                foreach (array_values($pagosFiltrados) as $index => $pago) {
                    $pdf->addTableRow([
                        ['width' => 38, 'text' => (string) ($pago['referencia'] ?: 'N/A'), 'align' => 'C'],
                        ['width' => 22, 'text' => '#' . (string) ($pago['id_pedido'] ?? ''), 'align' => 'C'],
                        [
                            'width' => 40,
                            'text' => '$ ' . number_format((float) ($pago['monto_pago'] ?? 0), 2, '.', ','),
                            'align' => 'R',
                            'fontStyle' => 'B',
                            'textColor' => [22, 101, 52],
                        ],
                        ['width' => 60, 'text' => substr((string) ($pago['nombre_metodo'] ?? 'N/A'), 0, 28), 'textColor' => [71, 85, 105]],
                        ['width' => 20, 'text' => ucfirst((string) ($pago['estado'] ?? 'procesado')), 'align' => 'C'],
                    ], $index % 2 !== 0, 8);
                }

                $pdf->Ln(5);
                $pdf->addTotalBanner('Monto Total Acumulado', '$ ' . number_format($montoTotalPagos, 2, '.', ','));
            }

        } else {
            $todosLosMetodos = $metodosModel->listarMetodos();
            $metodosFiltrados = array_filter($todosLosMetodos, function($met) use ($verInhabilitados) {
                $esInhabilitado = (strtolower($met['estado'] ?? '') === 'inhabilitado');
                return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
            });

            $pdf->addMetricCards([
                ['label' => 'Total de métodos:', 'value' => (string) count($metodosFiltrados), 'width' => 88],
                ['label' => 'Estado aplicado:', 'value' => $etiquetaEstado, 'width' => 88],
            ]);
            $pdf->addTableHeader([
                ['width' => 140, 'label' => 'Nombre del Método'],
                ['width' => 40, 'label' => 'Estado', 'align' => 'C'],
            ]);

            if (empty($metodosFiltrados)) {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell(180, 10, 'No hay métodos para mostrar.', 1, 1, 'C');
            } else {
                foreach (array_values($metodosFiltrados) as $index => $met) {
                    $pdf->addTableRow([
                        ['width' => 140, 'text' => substr((string) ($met['nombre_metodo_de_pago'] ?? 'N/A'), 0, 60)],
                        ['width' => 40, 'text' => ucfirst((string) ($met['estado'] ?? 'activo')), 'align' => 'C'],
                    ], $index % 2 !== 0, 8);
                }
            }
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        $pdf->Output('Reporte_Financiero.pdf', 'I'); 
        exit;
    }
}

$pagosModel = new ControlPagosModel();
$cuentasModel = new CuentaEmpresaModel();
$metodosModel = new MetodoPagoModel();

$action = $_GET['action'] ?? 'pagos'; 

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
        responderJSON(false, 'Entidad no válida o no especificada.', $accion);
    }

    procesarAccionCRUD($modelo, $accion, $entidad);
}

if (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte") {
    generarReportePDF($cuentasModel, $pagosModel, $metodosModel);
}

if (isset($_GET["ajax"]) && $_GET["ajax"] === "listar") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $datos = [];
    if ($action === 'pagos') {
        $datos = $pagosModel->listarPagos();
    } elseif ($action === 'cuentas') {
        $datos = $cuentasModel->listarCuentas();
    } elseif ($action === 'metodos') {
        $datos = $metodosModel->listarMetodos();
    }

    echo json_encode([
        'datos' => is_array($datos) ? $datos : [],
        'total' => is_array($datos) ? count($datos) : 0,
        'evento' => 'listar',
        'estado' => 'completado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'pagos':
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

$rutaVista = __DIR__ . '/../view/finanzas/' . $action . '.php';

if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;
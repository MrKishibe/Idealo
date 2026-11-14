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
    function subirComprobanteArchivo($pagosModel, $idPedido) {
        if (!isset($_FILES['comprobante_pago']) || $_FILES['comprobante_pago']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $error = $_FILES['comprobante_pago']['error'];
        if ($error !== UPLOAD_ERR_OK) {
            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception("El archivo excede el tamaño máximo permitido por el servidor.");
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception("El archivo se subió parcialmente.");
                default:
                    throw new \Exception("Error en la subida del archivo (Código PHP: {$error}).");
            }
        }

        $directorio_destino = __DIR__ . '/../../assets/uploads/comprobantes/';
        $directorio_publico = 'assets/uploads/comprobantes/';
        
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        $archivo_tmp = $_FILES['comprobante_pago']['tmp_name'];
        $nombre_original = $_FILES['comprobante_pago']['name'];
        $tamanio = $_FILES['comprobante_pago']['size'];

        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!in_array($extension, $extensiones_permitidas)) {
            throw new \Exception("Extensión de archivo no permitida. Sube JPG, PNG, WEBP o PDF.");
        }

        if ($tamanio > (2 * 1024 * 1024)) {
            throw new \Exception("El archivo supera los 2MB permitidos.");
        }

        $nombreCliente = 'cliente';
        if ($pagosModel && $idPedido) {
            $pedidosActivos = $pagosModel->obtenerPedidosActivos();
            foreach ($pedidosActivos as $ped) {
                if (isset($ped['id_pedido']) && (int)$ped['id_pedido'] === (int)$idPedido) {
                    $razonSocial = $ped['nombre_razon_social'] ?? 'cliente';
                    $nombreCliente = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(trim($razonSocial)));
                    break;
                }
            }
        }

        $nombre_nuevo = 'comprobante_' . $nombreCliente . '_' . time() . '.' . $extension; 
        $ruta_destino_completa = $directorio_destino . $nombre_nuevo;

        if (move_uploaded_file($archivo_tmp, $ruta_destino_completa)) {
            return $directorio_publico . $nombre_nuevo;
        }
        
        throw new \Exception("No se pudo mover el archivo subido a la carpeta de destinos en el servidor.");
    }
}

if (!function_exists('procesarAccionCRUD')) {
    function procesarAccionCRUD($modelo, $accion, $entidad, $pagosModel) {
        try {
            if ($entidad === 'pago') {
                $idPedido = $_POST['id_pedido'] ?? null;
                $rutaArchivo = subirComprobanteArchivo($pagosModel, $idPedido);
                if ($rutaArchivo !== null) {
                    $_POST['comprobante_de_pago'] = $rutaArchivo;
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

        $estadoFiltro  = $_GET['estado_reporte'] ?? $_GET['estado'] ?? 'activos';
        $fechaDesde    = $_GET['fecha_desde'] ?? '';
        $fechaHasta    = $_GET['fecha_hasta'] ?? '';
        $idMetodo      = $_GET['id_metodo'] ?? '';

        $verInhabilitados = ($estadoFiltro === 'inhabilitados');
        $incluirTodos     = ($estadoFiltro === 'todos');

        $etiquetaEstado = $incluirTodos ? 'Todos' : ($verInhabilitados ? 'Inhabilitados' : 'Activos');
        $tituloReporte  = 'Reporte de Pagos (' . $etiquetaEstado . ')';

        require_once __DIR__ . '/../helpers/ReportePdf.php';
        $pdf = new \Idealo\Helpers\ReportePdf('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $subtituloDetalle = 'Detalle financiero de pagos | Estado: ' . $etiquetaEstado;
        if (!empty($fechaDesde) || !empty($fechaHasta)) {
            $subtituloDetalle .= ' | Rango: ' . ($fechaDesde ?: 'Inicio') . ' al ' . ($fechaHasta ?: 'Hoy');
        }
        if (!empty($idMetodo)) {
            $subtituloDetalle .= ' | Método ID: ' . $idMetodo;
        }

        $pdf->setCompactReportDetails($tituloReporte, $subtituloDetalle);
        $pdf->AddPage();
        $pdf->addCompactReportHeader();

        $todosLosPagos = $pagosModel->listarPagos();
        $pagosFiltrados = array_filter($todosLosPagos, function($pago) use ($estadoFiltro, $fechaDesde, $fechaHasta, $idMetodo) {
            $estadoItem = strtolower($pago['estado'] ?? $pago['status_pago'] ?? 'procesado');
            $esInh = ($estadoItem === 'inhabilitado' || $estadoItem === 'inactivo');

            if ($estadoFiltro === 'activos' && $esInh) return false;
            if ($estadoFiltro === 'inhabilitados' && !$esInh) return false;

            // Filtrar por Método de Pago
            if (!empty($idMetodo) && (int)($pago['id_metodo_de_pago'] ?? 0) !== (int)$idMetodo) {
                return false;
            }

            // Filtrar por Rango de Fechas
            if (!empty($fechaDesde) || !empty($fechaHasta)) {
                $fechaPagoStr = substr(trim($pago['fecha_pago'] ?? ''), 0, 10);
                if (!empty($fechaPagoStr)) {
                    if (!empty($fechaDesde) && $fechaPagoStr < $fechaDesde) return false;
                    if (!empty($fechaHasta) && $fechaPagoStr > $fechaHasta) return false;
                }
            }

            return true;
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
            $pdf->Cell(180, 10, 'No hay pagos para mostrar con los filtros seleccionados.', 1, 1, 'C');
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

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        $pdf->Output('Reporte_Pagos.pdf', 'I'); 
        exit;
    }
}
$pagosModel = new ControlPagosModel();
$cuentasModel = new CuentaEmpresaModel();
$metodosModel = new MetodoPagoModel();

$action = $_GET['action'] ?? 'pagos'; 

// Interceptar solicitud de generación de reporte para evitar error 404
if ($action === 'generarReporte' || (isset($_GET["accion"]) && $_GET["accion"] === "generar_reporte")) {
    generarReportePDF($cuentasModel, $pagosModel, $metodosModel);
}

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

    procesarAccionCRUD($modelo, $accion, $entidad, $pagosModel);
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
        $metodos = method_exists($cuentasModel, 'obtenerMetodosPago') ? $cuentasModel->obtenerMetodosPago() : $metodosModel->listarMetodos();
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
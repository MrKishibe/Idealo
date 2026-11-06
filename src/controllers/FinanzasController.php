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
        $usuarioActual = $_SESSION['nombre_usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? 'Administrador';

        if (!class_exists('MYPDF')) {
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
        }

        $pdf = new MYPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Idéalo');
        $pdf->SetAuthor('Idéalo');
        $pdf->SetTitle('Reporte Financiero');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(15, 12, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();

        $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
        if (file_exists($logoPath)) {
            $pdf->ImageSVG($logoPath, 15, 10, 14, 14);
        }

        $pdf->SetXY(32, 10);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(100, 7, 'Idéalo  -  ' . $tituloReporte, 0, 0, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(135, 11);
        $pdf->Cell(60, 7, 'Usuario: ' . $usuarioActual, 0, 1, 'R');
        
        $pdf->SetXY(32, 17);
        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 5, 'Detalle financiero | Estado: ' . $etiquetaEstado, 0, 1, 'L');

        $pdf->Ln(4);

        if ($tipoReporte === 'cuentas') {
            $todasLasCuentas = $cuentasModel->listarCuentas();
            $cuentasFiltradas = array_filter($todasLasCuentas, function($cta) use ($verInhabilitados) {
                $esInhabilitado = (strtolower($cta['estado'] ?? '') === 'inhabilitado');
                return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
            });

            $yCard = $pdf->GetY();
            $pdf->SetLineWidth(0.3);

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Rect(15, $yCard, 88, 14, 'DF');
            $pdf->SetXY(18, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(45, 5, 'Total de cuentas:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(38, 5, count($cuentasFiltradas), 0, 1, 'R');

            $pdf->SetFillColor(241, 245, 249);
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->Rect(107, $yCard, 88, 14, 'DF');
            $pdf->SetXY(110, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(40, 5, 'Estado aplicado:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 9.5);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(42, 5, $etiquetaEstado, 0, 1, 'R');

            $pdf->Ln(18);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(30, 41, 59);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(15, 8.5, 'ID', 1, 0, 'C', true);
            $pdf->Cell(65, 8.5, 'Titular', 1, 0, 'L', true);
            $pdf->Cell(60, 8.5, 'Identificador', 1, 0, 'L', true);
            $pdf->Cell(40, 8.5, 'Tipo', 1, 1, 'L', true);

            $pdf->SetFont('helvetica', '', 9.5);
            $fill = false;

            if (empty($cuentasFiltradas)) {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell(180, 10, 'No hay cuentas para mostrar.', 1, 1, 'C');
            } else {
                foreach (array_values($cuentasFiltradas) as $cuenta) {
                    $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
                    $pdf->SetTextColor(30, 41, 59);

                    $pdf->Cell(15, 8, '#' . htmlspecialchars((string) ($cuenta['id_cuenta'] ?? ''), ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
                    $pdf->Cell(65, 8, substr(htmlspecialchars($cuenta['titular'] ?? 'N/A', ENT_QUOTES, 'UTF-8'), 0, 30), 'B', 0, 'L', true);
                    $pdf->Cell(60, 8, substr(htmlspecialchars($cuenta['identificador'] ?? 'N/A', ENT_QUOTES, 'UTF-8'), 0, 25), 'B', 0, 'L', true);
                    $pdf->Cell(40, 8, substr(htmlspecialchars($cuenta['tipo_cuenta'] ?? 'N/A', ENT_QUOTES, 'UTF-8'), 0, 18), 'B', 1, 'L', true);

                    $fill = !$fill;
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

            $yCard = $pdf->GetY();
            $pdf->SetLineWidth(0.3);

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Rect(15, $yCard, 57, 14, 'DF');
            $pdf->SetXY(17, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(28, 5, 'Total pagos:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(25, 5, count($pagosFiltrados), 0, 1, 'R');

            $pdf->SetFillColor(254, 243, 199);
            $pdf->SetDrawColor(252, 211, 77);
            $pdf->Rect(74, $yCard, 57, 14, 'DF');
            $pdf->SetXY(76, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(180, 83, 9);
            $pdf->Cell(22, 5, 'Estado:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 9.5);
            $pdf->SetTextColor(146, 64, 14);
            $pdf->Cell(31, 5, $etiquetaEstado, 0, 1, 'R');

            $pdf->SetFillColor(240, 253, 244);
            $pdf->SetDrawColor(187, 247, 208);
            $pdf->Rect(133, $yCard, 62, 14, 'DF');
            $pdf->SetXY(135, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(22, 101, 52);
            $pdf->Cell(25, 5, 'Abonado:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(20, 83, 45);
            $pdf->Cell(33, 5, '$ ' . number_format($montoTotalPagos, 2, '.', ','), 0, 1, 'R');

            $pdf->Ln(18);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(30, 41, 59);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(15, 8.5, 'ID', 1, 0, 'C', true);
            $pdf->Cell(38, 8.5, 'Referencia', 1, 0, 'C', true);
            $pdf->Cell(22, 8.5, 'Pedido', 1, 0, 'C', true);
            $pdf->Cell(40, 8.5, 'Monto', 1, 0, 'R', true);
            $pdf->Cell(45, 8.5, 'Método', 1, 0, 'L', true);
            $pdf->Cell(20, 8.5, 'Estado', 1, 1, 'C', true);

            $pdf->SetFont('helvetica', '', 9.5);
            $fill = false;

            if (empty($pagosFiltrados)) {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell(180, 10, 'No hay pagos para mostrar.', 1, 1, 'C');
            } else {
                foreach (array_values($pagosFiltrados) as $pago) {
                    $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
                    $pdf->SetTextColor(30, 41, 59);

                    $estadoPago = ucfirst($pago['estado'] ?? 'procesado');

                    $pdf->Cell(15, 8, '#' . htmlspecialchars((string) ($pago['id_pago'] ?? ''), ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
                    $pdf->Cell(38, 8, htmlspecialchars($pago['referencia'] ?: 'N/A', ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
                    $pdf->Cell(22, 8, '#' . htmlspecialchars((string) ($pago['id_pedido'] ?? ''), ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
                    
                    $pdf->SetFont('helvetica', 'B', 9.5);
                    $pdf->SetTextColor(22, 101, 52);
                    $pdf->Cell(40, 8, '$ ' . number_format((float) ($pago['monto_pago'] ?? 0), 2, '.', ','), 'B', 0, 'R', true);
                    
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetTextColor(71, 85, 105);
                    $pdf->Cell(45, 8, substr(htmlspecialchars($pago['nombre_metodo'] ?? 'N/A', ENT_QUOTES, 'UTF-8'), 0, 18), 'B', 0, 'L', true);
                    $pdf->Cell(20, 8, $estadoPago, 'B', 1, 'C', true);
                    $pdf->SetFont('helvetica', '', 9.5);

                    $fill = !$fill;
                }

                $pdf->Ln(5);
                $pdf->SetFillColor(240, 253, 244);
                $pdf->SetDrawColor(187, 247, 208);
                $pdf->SetLineWidth(0.4);
                
                $pdf->SetFont('helvetica', 'B', 10.5);
                $pdf->SetTextColor(22, 101, 52);
                $pdf->Cell(135, 10, 'Monto Total Acumulado:', 1, 0, 'R', true);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->SetTextColor(20, 83, 45);
                $pdf->Cell(45, 10, '$ ' . number_format($montoTotalPagos, 2, '.', ','), 1, 1, 'R', true);
            }

        } else {
            $todosLosMetodos = $metodosModel->listarMetodos();
            $metodosFiltrados = array_filter($todosLosMetodos, function($met) use ($verInhabilitados) {
                $esInhabilitado = (strtolower($met['estado'] ?? '') === 'inhabilitado');
                return $verInhabilitados ? $esInhabilitado : !$esInhabilitado;
            });

            $yCard = $pdf->GetY();
            $pdf->SetLineWidth(0.3);

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Rect(15, $yCard, 88, 14, 'DF');
            $pdf->SetXY(18, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(45, 5, 'Total de métodos:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(38, 5, count($metodosFiltrados), 0, 1, 'R');

            $pdf->SetFillColor(241, 245, 249);
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->Rect(107, $yCard, 88, 14, 'DF');
            $pdf->SetXY(110, $yCard + 3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(40, 5, 'Estado aplicado:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 9.5);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(42, 5, $etiquetaEstado, 0, 1, 'R');

            $pdf->Ln(18);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(30, 41, 59);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(20, 8.5, 'ID', 1, 0, 'C', true);
            $pdf->Cell(120, 8.5, 'Nombre del Método', 1, 0, 'L', true);
            $pdf->Cell(40, 8.5, 'Estado', 1, 1, 'C', true);

            $pdf->SetFont('helvetica', '', 9.5);
            $fill = false;

            if (empty($metodosFiltrados)) {
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell(180, 10, 'No hay métodos para mostrar.', 1, 1, 'C');
            } else {
                foreach (array_values($metodosFiltrados) as $met) {
                    $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
                    $pdf->SetTextColor(30, 41, 59);

                    $estadoMet = ucfirst($met['estado'] ?? 'activo');

                    $pdf->Cell(20, 8, '#' . htmlspecialchars((string) ($met['id_metodo_de_pago'] ?? ''), ENT_QUOTES, 'UTF-8'), 'B', 0, 'C', true);
                    $pdf->Cell(120, 8, substr(htmlspecialchars($met['nombre_metodo_de_pago'] ?? 'N/A', ENT_QUOTES, 'UTF-8'), 0, 50), 'B', 0, 'L', true);
                    $pdf->Cell(40, 8, $estadoMet, 'B', 1, 'C', true);

                    $fill = !$fill;
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
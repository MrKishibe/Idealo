<?php

use Idealo\Models\PedidoModel;

$model = new PedidoModel();
$rutaVista = __DIR__ . '/../view/pedido/listarpedido.php';

/*
|--------------------------------------------------------------------------
| Sesión (necesaria para el usuario del reporte)
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Respuesta JSON estándar
|--------------------------------------------------------------------------
*/
$responderJson = static function (
    array $respuesta,
    int $codigoHttp = 200
): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($codigoHttp);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
};

/*
|--------------------------------------------------------------------------
| Respuesta de error JSON
|--------------------------------------------------------------------------
*/
$responderError = static function (
    Throwable $e,
    string $evento
) use (
    $responderJson
): void {
    $responderJson(
        [
            'success' => false,
            'message' => $e->getMessage(),
            'evento' => $evento
        ],
        400
    );
};

/*
|--------------------------------------------------------------------------
| Utilidades de entrada
|--------------------------------------------------------------------------
*/
$textoPost = static function (string $campo): string {
    return trim((string) ($_POST[$campo] ?? ''));
};

$enteroPost = static function (string $campo): int {
    return (int) ($_POST[$campo] ?? 0);
};

$decimalPost = static function (string $campo): float {
    return (float) ($_POST[$campo] ?? 0);
};

/*
|--------------------------------------------------------------------------
| Normalización de texto para búsquedas (sin acentos, minúsculas)
|--------------------------------------------------------------------------
*/
$normalizar = static function (string $texto): string {
    $texto = mb_strtolower(trim($texto), 'UTF-8');

    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u'
    ]);
};

$crearDatosPedido = static function () use (
    $textoPost,
    $enteroPost,
    $decimalPost
): array {
    return [
        'pedido' => [
            'fecha_creacion' => $textoPost('fecha_creacion'),
            'fecha_entrega' => $textoPost('fecha_entrega'),
            'id_cliente' => $enteroPost('id_cliente'),
            'id_tipo_pedido' => $enteroPost('id_tipo_pedido'),
            'descripcion' => $textoPost('descripcion'),
            'descuento_divisa' => $decimalPost('descuento_divisa'),
            'estado_pedido' => $textoPost('estado_pedido')
        ],
        'detalle' => [
            'id_producto_caracteristica' => $enteroPost('id_producto_caracteristica'),
            'id_servicio' => $enteroPost('id_servicio'),
            'cantidad' => $enteroPost('cantidad'),
            'costo_mano_de_obra' => $decimalPost('costo_mano_de_obra'),
            'costo_materiales' => $decimalPost('costo_materiales'),
            'descuento_producto' => $decimalPost('descuento_producto'),
            'metodo_servicio' => $textoPost('metodo_servicio')
        ]
    ];
};

/*
|--------------------------------------------------------------------------
| AJAX: Buscar clientes (autocompletado Select2)
|--------------------------------------------------------------------------
| Se apoya en obtenerClientes() y filtra en PHP, así no hay que tocar el
| modelo. Si algún día la tabla crece demasiado, muévelo a SQL.
*/
if (($_GET['accion'] ?? '') === 'buscar_clientes') {
    try {
        $termino = $normalizar((string) ($_GET['q'] ?? ''));
        $pagina = max(1, (int) ($_GET['page'] ?? 1));
        $porPagina = 20;

        $clientes = $model->obtenerClientes();
        $coincidencias = [];

        foreach ($clientes as $cliente) {
            $nombreCompleto = trim(
                ($cliente['nombre_razon_social'] ?? '') . ' ' . ($cliente['apellido'] ?? '')
            );
            $documento = (string) ($cliente['numero_de_documento'] ?? '');

            $texto = $normalizar($nombreCompleto . ' ' . $documento);

            if ($termino === '' || mb_strpos($texto, $termino) !== false) {
                $coincidencias[] = [
                    'id_cliente' => (int) $cliente['id_cliente'],
                    'nombre_razon_social' => $cliente['nombre_razon_social'] ?? '',
                    'apellido' => $cliente['apellido'] ?? '',
                    'numero_de_documento' => $documento
                ];
            }
        }

        $total = count($coincidencias);
        $desde = ($pagina - 1) * $porPagina;
        $datos = array_slice($coincidencias, $desde, $porPagina);

        $responderJson([
            'success' => true,
            'data' => $datos,
            'total' => $total,
            'more' => ($desde + $porPagina) < $total
        ]);
    } catch (Throwable $e) {
        $responderError($e, 'buscar_clientes');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener características según el producto seleccionado
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'caracteristicas_por_producto') {
    try {
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        if ($idProducto <= 0) throw new Exception('El producto seleccionado no es válido.');
        $caracteristicas = $model->obtenerCaracteristicasPorProducto($idProducto);
        $responderJson(['success' => true, 'data' => $caracteristicas]);
    } catch (Throwable $e) {
        $responderError($e, 'caracteristicas_por_producto');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener colores según el producto seleccionado
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'colores_por_producto') {
    try {
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        if ($idProducto <= 0) throw new Exception('El producto seleccionado no es válido.');

        $colores = $model->obtenerColoresPorProducto($idProducto);

        $responderJson(['success' => true, 'data' => $colores]);
    } catch (Throwable $e) {
        $responderError($e, 'colores_por_producto');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener tallas según el producto y color seleccionados
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'tallas_por_producto_y_color') {
    try {
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        $color = trim((string) ($_GET['color'] ?? ''));
        if ($idProducto <= 0 || $color === '') throw new Exception('Parámetros no válidos para cargar las tallas.');

        $tallas = $model->obtenerTallasPorProductoYColor($idProducto, $color);

        $responderJson(['success' => true, 'data' => $tallas]);
    } catch (Throwable $e) {
        $responderError($e, 'tallas_por_producto_y_color');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Listar pedidos (activos o inhabilitados)
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'listar') {
    try {
        $filtro = strtolower(trim((string) ($_GET['filtro'] ?? 'activos')));
        if (!in_array($filtro, ['activos', 'inhabilitados'], true)) {
            $filtro = 'activos';
        }
        $pedidos = $model->listarPedidos($filtro);
        $responderJson(['success' => true, 'data' => $pedidos]);
    } catch (Throwable $e) {
        $responderError($e, 'listar');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Obtener un pedido específico por ID
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'obtener') {
    try {
        $idPedido = (int) ($_GET['id'] ?? 0);
        if ($idPedido <= 0) throw new Exception('El identificador del pedido no es válido.');
        $pedido = $model->obtenerPedido($idPedido);
        if (!$pedido) throw new Exception('El pedido solicitado no existe.');
        $responderJson(['success' => true, 'data' => $pedido]);
    } catch (Throwable $e) {
        $responderError($e, 'obtener');
    }
}

/*
|--------------------------------------------------------------------------
| AJAX: Generar Reporte PDF Vertical, Numerado y con Usuario Dinámico
|--------------------------------------------------------------------------
*/
if (($_GET['accion'] ?? '') === 'generarreportepdf') {
    try {
        $filtros = [
            'reporte_todo'   => trim($_GET['reporte_todo'] ?? ''),
            'fecha_inicio'   => trim($_GET['fecha_inicio'] ?? ''),
            'fecha_fin'      => trim($_GET['fecha_fin'] ?? ''),
            'id_tipo_pedido' => $_GET['id_tipo_pedido'] ?? [],
            'id_servicio'    => $_GET['id_servicio'] ?? [],
            'estado'         => $_GET['estado'] ?? [],
            'cantidad'       => trim($_GET['cantidad'] ?? ''),
            'operador_monto' => trim($_GET['operador_monto'] ?? ''),
            'monto'          => trim($_GET['monto'] ?? ''),
            'ids_filtrados'  => json_decode($_GET['ids_filtrados'] ?? '[]', true),
            'filtro'         => trim($_GET['filtro'] ?? 'activos')
        ];

        $pedidos = $model->listarPedidosParaReporte($filtros);

        // Métricas para las tarjetas de resumen y contadores desglosados
        $totalPedidos = count($pedidos);
        $totalPendientes = 0;
        $totalEnProceso = 0;
        $totalPlanificados = 0;
        $totalEntregadosRealizados = 0;
        $montoTotalAcumulado = 0;

        foreach ($pedidos as $p) {
            $montoTotalAcumulado += (float)$p['monto_total'];
            $est = strtolower(trim($p['estado_pedido'] ?? ''));
            if ($est === 'pendiente') {
                $totalPendientes++;
            } elseif ($est === 'en proceso') {
                $totalEnProceso++;
            } elseif ($est === 'planificado') {
                $totalPlanificados++;
            } elseif ($est === 'entregado' || $est === 'realizado') {
                $totalEntregadosRealizados++;
            }
        }

        // Obtener el nombre del usuario de la sesión actual de forma dinámica
        $usuarioActual = $_SESSION['nombre_usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? 'Administrador';

        // Extender TCPDF para personalizar el pie de página con numeración automática
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

        // Orientación VERTICAL ('P')
        $pdf = new MYPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Idéalo');
        $pdf->SetTitle('Reporte de Pedidos');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(15, 12, 15);
        $pdf->SetAutoPageBreak(TRUE, 18);
        $pdf->AddPage();

        // -------------------------------------------------------------------------
        // ENCABEZADO Y BRANDING CORPORATIVO
        // -------------------------------------------------------------------------
        $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
        if (file_exists($logoPath)) {
            $pdf->ImageSVG($logoPath, 15, 10, 14, 14);
        }

        $pdf->SetXY(32, 10);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(100, 7, 'Idéalo  -  Reporte de Pedidos', 0, 0, 'L');

        // Usuario posicionado hacia adentro
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(135, 11);
        $pdf->Cell(60, 7, 'Usuario: ' . $usuarioActual, 0, 1, 'R');

        // Subtítulo del período seleccionado
        $periodoTexto = (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin']))
            ? "Detalle de transacciones: {$filtros['fecha_inicio']} al {$filtros['fecha_fin']}"
            : "Detalle general de transacciones registradas";

        $pdf->SetXY(32, 17);
        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 5, $periodoTexto, 0, 1, 'L');

        $pdf->Ln(4);

        // -------------------------------------------------------------------------
        // TARJETAS DE MÉTRICAS / RESUMEN SUPERIOR (5 Contadores adaptados a vertical)
        // -------------------------------------------------------------------------
        $yCard = $pdf->GetY();
        $pdf->SetLineWidth(0.3);

        // Tarjeta 1: Total
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Rect(15, $yCard, 34, 14, 'DF');
        $pdf->SetXY(16, $yCard + 3);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(18, 5, 'Total:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(14, 5, $totalPedidos, 0, 1, 'R');

        // Tarjeta 2: Pendientes
        $pdf->SetFillColor(254, 243, 199);
        $pdf->SetDrawColor(252, 211, 77);
        $pdf->Rect(51, $yCard, 34, 14, 'DF');
        $pdf->SetXY(52, $yCard + 3);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(180, 83, 9);
        $pdf->Cell(18, 5, 'Pendientes:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(146, 64, 14);
        $pdf->Cell(14, 5, $totalPendientes, 0, 1, 'R');

        // Tarjeta 3: En Proceso
        $pdf->SetFillColor(224, 242, 254);
        $pdf->SetDrawColor(186, 230, 253);
        $pdf->Rect(87, $yCard, 34, 14, 'DF');
        $pdf->SetXY(88, $yCard + 3);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(3, 105, 161);
        $pdf->Cell(18, 5, 'Procesados:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(12, 74, 110);
        $pdf->Cell(14, 5, $totalEnProceso, 0, 1, 'R');

        // Tarjeta 4: Planificados
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(123, $yCard, 34, 14, 'DF');
        $pdf->SetXY(124, $yCard + 3);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(18, 5, 'Planificados:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(14, 5, $totalPlanificados, 0, 1, 'R');

        // Tarjeta 5: Completados
        $pdf->SetFillColor(240, 253, 244);
        $pdf->SetDrawColor(187, 247, 208);
        $pdf->Rect(159, $yCard, 36, 14, 'DF');
        $pdf->SetXY(160, $yCard + 3);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(22, 101, 52);
        $pdf->Cell(19, 5, 'Completados:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(20, 83, 45);
        $pdf->Cell(15, 5, $totalEntregadosRealizados, 0, 1, 'R');

        $pdf->Ln(18);

        // -------------------------------------------------------------------------
        // CABECERA DE LA TABLA PRINCIPAL (Suma exacta = 180 mm)
        // -------------------------------------------------------------------------
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(30, 41, 59);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(12, 8.5, 'ID', 1, 0, 'C', true);
        $pdf->Cell(25, 8.5, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(45, 8.5, 'Cliente', 1, 0, 'L', true);
        $pdf->Cell(45, 8.5, 'Producto', 1, 0, 'L', true);
        $pdf->Cell(15, 8.5, 'Cant.', 1, 0, 'C', true);
        $pdf->Cell(20, 8.5, 'Estado', 1, 0, 'C', true);
        $pdf->Cell(18, 8.5, 'Total', 1, 1, 'R', true);

        // -------------------------------------------------------------------------
        // FILAS DE DATOS DE LA TABLA
        // -------------------------------------------------------------------------
        $pdf->SetFont('helvetica', '', 9);
        $fill = false;

        if (empty($pedidos)) {
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 10, 'No se encontraron registros con los parámetros seleccionados.', 1, 1, 'C');
        } else {
            foreach ($pedidos as $p) {
                $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
                $pdf->SetTextColor(30, 41, 59);

                $cliente = trim(($p['nombre_razon_social'] ?? '') . ' ' . ($p['apellido'] ?? ''));

                $pdf->Cell(12, 8, '#' . $p['id_pedido'], 'B', 0, 'C', true);

                $fechaFormateada = !empty($p['fecha_creacion']) ? date('d/m/Y', strtotime($p['fecha_creacion'])) : '-';
                $pdf->Cell(25, 8, $fechaFormateada, 'B', 0, 'C', true);

                $pdf->Cell(45, 8, substr($cliente, 0, 22), 'B', 0, 'L', true);
                $pdf->Cell(45, 8, substr($p['nombre_producto'] ?? 'N/A', 0, 22), 'B', 0, 'L', true);
                $pdf->Cell(15, 8, $p['cantidad'], 'B', 0, 'C', true);

                $estadoLower = strtolower($p['estado_pedido']);
                $pdf->SetFont('helvetica', 'B', 8.5);
                if ($estadoLower === 'pendiente') {
                    $pdf->SetTextColor(180, 83, 9);
                } elseif ($estadoLower === 'entregado' || $estadoLower === 'realizado') {
                    $pdf->SetTextColor(16, 185, 129);
                } else {
                    $pdf->SetTextColor(14, 165, 233);
                }

                $pdf->Cell(20, 8, ucfirst($p['estado_pedido']), 'B', 0, 'C', true);

                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetTextColor(15, 23, 42);
                $pdf->Cell(18, 8, number_format($p['monto_total'], 2), 'B', 1, 'R', true);

                $fill = !$fill;
            }

            // -------------------------------------------------------------------------
            // BLOQUE INFERIOR DE MONTO TOTAL ACUMULADO
            // -------------------------------------------------------------------------
            $pdf->Ln(5);
            $pdf->SetFillColor(240, 253, 244);
            $pdf->SetDrawColor(187, 247, 208);
            $pdf->SetLineWidth(0.4);

            $pdf->SetFont('helvetica', 'B', 10.5);
            $pdf->SetTextColor(22, 101, 52);
            $pdf->Cell(135, 10, 'Monto Total Acumulado:', 1, 0, 'R', true);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(20, 83, 45);
            $pdf->Cell(45, 10, '$ ' . number_format($montoTotalAcumulado, 2), 1, 1, 'R', true);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        $pdf->Output('reporte de pedidos.pdf', 'I');
        exit;

    } catch (Throwable $e) {
        $responderError($e, 'generarreportepdf');
    }
}

/*
|--------------------------------------------------------------------------
| Control de peticiones POST (Registrar, Editar, Inhabilitar)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? $_POST['action'] ?? ''));
    try {
        if ($accion === 'guardar') {
            $datos = $crearDatosPedido();
            $datos['pedido']['estado_pedido'] = 'pendiente';
            $idPedido = $model->guardarPedido($datos);
            $responderJson(['success' => true, 'message' => 'Pedido registrado correctamente.', 'id_pedido' => $idPedido]);
        } elseif ($accion === 'editar') {
            $datos = $crearDatosPedido();
            $idPedido = $enteroPost('id_pedido');
            if ($idPedido <= 0) throw new Exception('El identificador del pedido no es válido.');
            $datos['id_pedido'] = $idPedido;
            $model->editarPedido($datos);
            $responderJson(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
        } elseif ($accion === 'inhabilitar') {
            $idPedido = $enteroPost('id_pedido');
            if ($idPedido <= 0) throw new Exception('El identificador del pedido no es válido.');
            $model->inhabilitarPedido($idPedido);
            $responderJson(['success' => true, 'message' => 'Pedido inhabilitado correctamente.']);
        } else {
            throw new Exception('La acción solicitada no es válida.');
        }
    } catch (Throwable $e) {
        $responderError($e, $accion !== '' ? $accion : 'post');
    }
}

/*
|--------------------------------------------------------------------------
| Cargar la vista principal del módulo
|--------------------------------------------------------------------------
*/
if (!file_exists($rutaVista)) {
    http_response_code(404);
    die('No existe la vista de gestión de pedidos.');
}

try {
    $clientes = $model->obtenerClientes();
    $tiposPedido = $model->obtenerTiposPedido();
    $productos = $model->obtenerProductosActivos();
    $servicios = $model->obtenerServicios();
    require_once $rutaVista;
} catch (Throwable $e) {
    http_response_code(500);
    die('Error al cargar el módulo de pedidos: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
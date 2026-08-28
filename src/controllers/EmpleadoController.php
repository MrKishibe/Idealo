<?php

use Idealo\Models\EmpleadoModel;

$rutaVista = __DIR__ . '/../view/empleado/listar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {


    if (isset($_POST['id_accion']) && isset($_POST['nuevo_estado'])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $id = intval($_POST['id_accion']);
        $nuevoEstado = $_POST['nuevo_estado'];

        if (isset($_POST['nombres'])) {
            $nombres   = trim($_POST['nombres']);
            $apellidos = trim($_POST['apellidos'] ?? '');
            $cedula    = trim($_POST['cedula'] ?? '');
            $telefono  = trim($_POST['telefono'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $cargo     = trim($_POST['cargo'] ?? '');
            $salario   = trim($_POST['salario'] ?? '0.00');

            $validacion = EmpleadoModel::validarDatos($nombres, $apellidos, $cedula, $telefono, $direccion, $cargo, $salario, $nuevoEstado, $id);

            if ($validacion === true) {
                $resultado = EmpleadoModel::getActualizarDatos($id);

                if (isset($resultado['existoso'])) {
                    echo json_encode([
                        'success' => true,
                        'message' => '✅ ' . $resultado['existoso'],
                        'evento'  => 'editar',
                        'estado'  => 'completado'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => '❌ ' . ($resultado['error'] ?? "Error interno al guardar los cambios."),
                        'evento'  => 'editar',
                        'estado'  => 'error'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '❌ ' . $validacion['error'],
                    'evento'  => 'editar',
                    'estado'  => 'error_validacion'
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        $respuesta = EmpleadoModel::getCambiarEstado($id, $nuevoEstado);

        if (isset($respuesta['existoso'])) {
            echo json_encode([
                'success' => true,
                'message' => '✅ Estado actualizado con éxito.',
                'evento'  => 'cambiar_estado',
                'estado'  => 'completado'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '❌ ' . ($respuesta['error'] ?? "Error al cambiar el estado."),
                'evento'  => 'cambiar_estado',
                'estado'  => 'error'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if (isset($_POST['cedula']) && !isset($_POST['id_accion'])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $nombres   = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cedula    = trim($_POST['cedula']);
        $telefono  = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $cargo     = trim($_POST['cargo'] ?? '');
        $salario   = trim($_POST['salario'] ?? '0.00');
        $status    = 'Activo';

        $validacion = EmpleadoModel::validarDatos($nombres, $apellidos, $cedula, $telefono, $direccion, $cargo, $salario, $status);

        if ($validacion === true) {
            $resultado = EmpleadoModel::getRegistrarDatos();

            if (isset($resultado['existoso'])) {
                echo json_encode([
                    'success'   => true,
                    'message'   => '✅ Empleado registrado con éxito.',
                    'id'        => $resultado['id'],
                    'nombres'   => $nombres,
                    'apellidos' => $apellidos,
                    'cedula'    => $cedula,
                    'cargo'     => $cargo,
                    'evento'    => 'guardar',
                    'estado'    => 'completado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '❌ ' . ($resultado['error'] ?? "Error interno al guardar en el sistema."),
                    'evento'  => 'guardar',
                    'estado'  => 'error'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => '❌ ' . $validacion['error'],
                'evento'  => 'guardar',
                'estado'  => 'error_validacion'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}


if (isset($_GET["ajax"]) && $_GET["ajax"] === "listar") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $empleados = EmpleadoModel::consultarEmpleados();

    if (is_array($empleados) && isset($empleados['error'])) {
        echo json_encode([
            'success' => false,
            'message' => '❌ ' . $empleados['error'],
            'evento'  => 'listar',
            'estado'  => 'error'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($empleados)) $empleados = [];

    $activos   = array_filter($empleados, function ($e) {
        return $e['status_empleado'] === 'Activo';
    });
    $inactivos = array_filter($empleados, function ($e) {
        return $e['status_empleado'] === 'Inactivo';
    });

    echo json_encode([
        'empleados' => array_values($empleados),
        'total'     => count($empleados),
        'activos'   => count($activos),
        'inactivos' => count($inactivos),
        'evento'    => 'listar',
        'estado'    => 'completado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$empleados = EmpleadoModel::consultarEmpleados();

if (is_array($empleados) && isset($empleados['error'])) {
    die("Error Crítico de Datos: " . htmlspecialchars($empleados['error']));
}

if (!is_array($empleados)) {
    $empleados = array();
}

if (!file_exists($rutaVista)) {
    header("HTTP/1.1 404 Not Found");
    die("Error 404: No existe la vista requerida en: <strong>" . htmlspecialchars($rutaVista) . "</strong>");
}

require_once $rutaVista;

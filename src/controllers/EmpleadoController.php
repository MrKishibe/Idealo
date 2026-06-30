<?php

namespace Idealo\Controllers;

use Idealo\Models\EmpleadoModel;

class EmpleadoController
{
    private $model;

    public function __construct()
    {
        require_once __DIR__ . '/../models/EmpleadoModel.php';
        $this->model = new EmpleadoModel();
    }

    public function listar()
    {
        $empleados = $this->model->listarTodos(true);
        require_once __DIR__ . '/../view/empleado/listar.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'cedula'    => $_POST['cedula'] ?? '',
                'nombres'   => $_POST['nombres'] ?? '',
                'apellidos' => $_POST['apellidos'] ?? '',
                'telefono'  => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'salario'   => $_POST['salario'] ?? 0.00,
                'cargo'     => $_POST['cargo'] ?? 'Costurero'
            ];

            $resultado = $this->model->guardar($datos);

            if ($this->isAjax()) {
                echo json_encode([
                    "success" => $resultado,
                    "message" => $resultado ? "Empleado registrado con éxito" : "Error al registrar el empleado"
                ]);
                exit;
            }
        }
        header('Location: index.php?controller=empleado&action=listar');
        exit;
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'id_empleado' => $_POST['id_empleado'] ?? '',
                'nombres'     => $_POST['nombres'] ?? '',
                'apellidos'   => $_POST['apellidos'] ?? '',
                'telefono'    => $_POST['telefono'] ?? '',
                'direccion'   => $_POST['direccion'] ?? '',
                'cargo'       => $_POST['cargo'] ?? '',
                'salario'     => $_POST['salario'] ?? 0.00
            ];

            $resultado = $this->model->editar($datos);

            if ($this->isAjax()) {
                echo json_encode([
                    "success" => $resultado,
                    "message" => $resultado ? "Datos del empleado actualizados" : "Error al actualizar los datos"
                ]);
                exit;
            }
            header('Location: index.php?controller=empleado&action=listar');
            exit;
        } else {
            if (isset($_GET['id'])) {
                $empleado = $this->model->obtenerPorId($_GET['id']);
                if ($empleado) {
                    if ($this->isAjax()) {
                        echo json_encode(["success" => true, "data" => $empleado]);
                        exit;
                    }
                    require_once __DIR__ . '/../view/empleado/editar.php';
                    return;
                }
            }
            header('Location: index.php?controller=empleado&action=listar');
            exit;
        }
    }

    public function cambiarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_empleado = $_POST['id_empleado'] ?? '';
            $nuevo_estado = $_POST['status_empleado'] ?? ''; // 'activo' o 'inactivo'

            if (!empty($id_empleado) && !empty($nuevo_estado)) {
                $resultado = $this->model->cambiarEstado($id_empleado, $nuevo_estado);
                echo json_encode([
                    "success" => $resultado,
                    "message" => $resultado ? "El estado del empleado ha sido actualizado" : "Error al cambiar el estado"
                ]);
                exit;
            }
        }
        echo json_encode(["success" => false, "message" => "Petición no válida"]);
        exit;
    }

    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $this->model->inactivar($_GET['id']);
        }
        header('Location: index.php?controller=empleado&action=listar');
        exit;
    }

    private function isAjax()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            || isset($_POST['ajax']) || isset($_GET['ajax']);
    }
}

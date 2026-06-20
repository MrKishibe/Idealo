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
        // Trae los registros con el alias correcto listo para usar en la vista
        $usuarios = $this->model->listarTodos();
        
        // Carga la vista compartida de usuarios
        require_once __DIR__ . '/../view/usuario/listarusuario.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'cedula_usuario' => $_POST['cedula_usuario'] ?? '',
                'id_rol'         => $_POST['id_rol'] ?? '',
                'contrasena'     => $_POST['contrasena'] ?? $_POST['cedula_usuario'],
                'nombres'        => $_POST['nombres'] ?? '',
                'apellidos'      => $_POST['apellidos'] ?? '',
                'telefono'       => $_POST['telefono'] ?? '',
                'direccion'      => $_POST['direccion'] ?? '',
                'salario'        => $_POST['salario'] ?? 0.00,
                'cargo'          => $_POST['cargo'] ?? 'Operador'
            ];
            $this->model->guardar($datos);
        }
        header('Location: index.php?controller=empleado&action=listar');
        exit;
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'id_usuario'     => $_POST['id_usuario'] ?? '',
                'cedula_usuario' => $_POST['cedula_usuario'] ?? '',
                'id_rol'         => $_POST['id_rol'] ?? '',
                'nombres'        => $_POST['nombres'] ?? '',
                'apellidos'      => $_POST['apellidos'] ?? '',
                'telefono'       => $_POST['telefono'] ?? '',
                'direccion'      => $_POST['direccion'] ?? '',
                'cargo'          => $_POST['cargo'] ?? '',
                'salario'        => $_POST['salario'] ?? 0.00
            ];
            $this->model->editar($datos);
        }
        header('Location: index.php?controller=empleado&action=listar');
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
}
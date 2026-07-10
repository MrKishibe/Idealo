<?php

namespace Idealo\Controllers;

use Idealo\Models\UsuarioModel;

use Idealo\Config\Database;
use PDO;

class UsuarioController
{
    private $model;

    public function __construct()
    {
        // Cargar manualmente el modelo para entornos sin composer dump-autoload actualizado
        require_once __DIR__ . '/../models/Usuario.php';
        $this->model = new UsuarioModel();
    }

    public function listar()
    {
        $empleados = $this->model->listarActivos();
        require_once __DIR__ . '/../view/usuario/listar.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'cedula_usuario' => $_POST['cedula_usuario'],
                'nombres' => $_POST['nombres'],
                'apellidos' => $_POST['apellidos'],
                'telefono' => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'contrasena' => $_POST['contrasena'],
                'cargo' => $_POST['cargo'] ?? 'Personal',
                'salario' => $_POST['salario'] ?? 0.00,
                'id_rol' => $_POST['id_rol'] ?? 2
            ];

            $resultado = $this->model->guardar($datos);

            if (isset($_POST['origen']) && $_POST['origen'] === 'login') {
                if ($resultado) {
                    header('Location: index.php?controller=auth&action=login&registro=exito');
                } else {
                    header('Location: index.php?controller=auth&action=login&registro=error');
                }
                exit;
            }
        }
        header('Location: index.php?controller=usuario&action=listar');
        exit;
    }

    public function registrar()
    {
        $this->guardar();
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'id_usuario' => $_POST['id_usuario'],
                'cedula_usuario' => $_POST['cedula_usuario'],
                'nombres' => $_POST['nombres'],
                'apellidos' => $_POST['apellidos'],
                'telefono' => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'cargo' => $_POST['cargo'],
                'salario' => $_POST['salario'],
                'id_rol' => $_POST['id_rol']
            ];
            $this->model->editar($datos);
        }
        header('Location: index.php?controller=usuario&action=listar');
        exit;
    }

    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $this->model->inactivar($_GET['id']);
        }
        header('Location: index.php?controller=usuario&action=listar');
        exit;
    }
}

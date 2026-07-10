<?php

namespace Idealo\Controllers;

// Si usas el namespace del modelo, asegúrate de que coincida
use Idealo\Models\UsuarioModel;

class GestionUsuarioController
{
    private $model;

    public function __construct()
    {
        // EL REQUIRE DEBE IR AQUÍ (Dentro del constructor)
        require_once __DIR__ . '/../models/GestionUsuarioModel.php';
        $this->model = new UsuarioModel();
    }

    /**
     * Muestra la lista de usuarios cargando la vista correspondiente
     */
    public function listar()
    {
        // Ajusta 'listarTodos()' por el nombre real de tu método (ej: listar())


        // EL REQUIRE DE LA VISTA VA AQUÍ (Dentro de la función listar)
        require_once __DIR__ . '/../view/usuario/listarusuario.php';
    }

    /**
     * Procesa el formulario de registro de un nuevo usuario
     */
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'cedula_usuario' => $_POST['cedula_usuario'] ?? '',
                'id_rol'         => $_POST['id_rol'] ?? '',
                'status_usuario' => $_POST['status_usuario'] ?? 'activo'
            ];

            $this->model->guardar($datos);
        }

        header('Location: index.php?controller=gestionUsuario&action=listar');
        exit;
    }

    /**
     * Procesa la actualización de los datos de un usuario existente
     */
    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'id_usuario'     => $_POST['id_usuario'] ?? '',
                'cedula_usuario' => $_POST['cedula_usuario'] ?? '',
                'id_rol'         => $_POST['id_rol'] ?? '',
                'status_usuario' => $_POST['status_usuario'] ?? 'activo'
            ];

            $this->model->editar($datos);
        }

        header('Location: index.php?controller=gestionUsuario&action=listar');
        exit;
    }

    /**
     * Desactiva o elimina un usuario según el ID recibido por URL
     */
    public function eliminar()
    {
        if (isset($_GET['id'])) {
            $this->model->inactivar($_GET['id']);
        }

        header('Location: index.php?controller=gestionUsuario&action=listar');
        exit;
    }
}

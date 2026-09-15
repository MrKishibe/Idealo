<?php

namespace Idealo\Controllers;

use Idealo\Models\UsuarioModel;
use Exception;

require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new UsuarioModel();
    }

    public function listar()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: index.php');
            exit;
        }

        $usuarios = $this->model->listarTodos();
        $roles    = $this->model->listarRoles();

        require_once dirname(__DIR__) . '/view/usuario/listar.php';
    }

    public function perfil()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: index.php');
            exit;
        }

        $perfil = $this->model->obtenerPerfil((int)$_SESSION['usuario']);

        require_once dirname(__DIR__) . '/view/usuario/perfil.php';
    }

    public function actualizarPerfil()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                if (!isset($_SESSION['usuario'])) {
                    throw new Exception("[Validación] La sesión ha expirado, vuelve a iniciar sesión.");
                }

                $nombreUsuario = $_POST['nombre_usuario'] ?? '';

                $resultado = $this->model->cambiarNombreUsuario((int)$_SESSION['usuario'], $nombreUsuario);

                $_SESSION['nombre_usuario'] = $nombreUsuario;

                echo json_encode([
                    'status'  => 'success',
                    'message' => $resultado['message']
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    public function cambiarContrasena()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                if (!isset($_SESSION['usuario'])) {
                    throw new Exception("[Validación] La sesión ha expirado, vuelve a iniciar sesión.");
                }

                $resultado = $this->model->cambiarContrasena(
                    (int)$_SESSION['usuario'],
                    $_POST['contrasena_actual'] ?? '',
                    $_POST['contrasena_nueva'] ?? ''
                );

                echo json_encode([
                    'status'  => 'success',
                    'message' => $resultado['message']
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $datos = [
                    'nombre_usuario' => $_POST['nombre_usuario'] ?? '',
                    'contrasena'     => $_POST['contrasena'] ?? '',
                    'id_rol'         => $_POST['id_rol'] ?? 0
                ];

                $resultado = $this->model->guardar($datos);

                echo json_encode([
                    'status'  => 'success',
                    'message' => $resultado['message']
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $datos = [
                    'id_usuario'     => $_POST['id_usuario'] ?? 0,
                    'nombre_usuario' => $_POST['nombre_usuario'] ?? '',
                    'contrasena'     => $_POST['contrasena'] ?? '',
                    'id_rol'         => $_POST['id_rol'] ?? 0
                ];

                $resultado = $this->model->editar($datos);

                echo json_encode([
                    'status'  => 'success',
                    'message' => $resultado['message']
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    public function cambiarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                $id         = intval($_POST['id_usuario'] ?? 0);
                $nuevoEstado = strtolower($_POST['status_usuario'] ?? 'inactivo');

                if (
                    $nuevoEstado === 'inactivo'
                    && isset($_SESSION['usuario'])
                    && intval($_SESSION['usuario']) === $id
                ) {
                    throw new Exception("[Validación] No puedes inactivar tu propia cuenta.");
                }

                $resultado = $this->model->cambiarEstado($id, $nuevoEstado);

                echo json_encode([
                    'status'  => 'success',
                    'message' => $resultado['message']
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }
}

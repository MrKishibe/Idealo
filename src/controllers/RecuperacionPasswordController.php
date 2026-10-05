<?php

namespace Idealo\Controllers;

require_once __DIR__ . '/../../config/smtp_config.php';
require_once __DIR__ . '/../../config/mailer.php';
require_once __DIR__ . '/../models/RecuperacionPasswordModel.php';

use Idealo\Config\Mailer;
use Idealo\Models\RecuperacionPasswordModel;
use Exception;

class RecuperacionPasswordController
{
    private $model;

    public function __construct()
    {
        $this->model = new RecuperacionPasswordModel();
    }

    public function solicitar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $mensaje = '';
        $tipo = '';
        $mostrarFormulario = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $correo = trim($_POST['correo'] ?? '');
                
                if (empty($correo)) {
                    throw new Exception('El correo electrónico es obligatorio.');
                }

                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('El formato del correo electrónico no es válido.');
                }

                $usuario = $this->model->obtenerUsuarioPorCorreo($correo);

                if ($usuario) {
                    $codigo = $this->model->generarCodigo();
                    $token = $this->model->generarToken();
                    
                    $this->model->crearSolicitudRecuperacion($usuario['id_usuario'], $correo, $codigo, $token, 5);
                    
                    $mailer = new Mailer();
                    if ($mailer->enviarCodigoRecuperacion($correo, $codigo)) {
                        $_SESSION['recuperacion_correo'] = $correo;
                        header('Location: index.php?controller=recuperacionPassword&action=verificar');
                        exit();
                    } else {
                        throw new Exception('No se pudo enviar el correo de recuperación. Inténtalo nuevamente.');
                    }
                } else {
                    $mensaje = 'Si el correo existe en nuestro sistema, recibirás un código de recuperación.';
                    $tipo = 'info';
                }
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = 'danger';
            }
        }

        require_once __DIR__ . '/../view/auth/recuperar_solicitar.php';
    }

    public function verificar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['recuperacion_correo'])) {
            header('Location: index.php?controller=recuperacionPassword&action=solicitar');
            exit();
        }

        $correo = $_SESSION['recuperacion_correo'];
        $mensaje = '';
        $tipo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $codigo = trim($_POST['codigo'] ?? '');
                
                if (empty($codigo)) {
                    throw new Exception('El código de verificación es obligatorio.');
                }

                $solicitud = $this->model->validarCodigo($correo, $codigo);
                
                if ($solicitud) {
                    $_SESSION['recuperacion_token'] = $solicitud['token'];
                    header('Location: index.php?controller=recuperacionPassword&action=reestablecer');
                    exit();
                } else {
                    throw new Exception('Código inválido.');
                }
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = 'danger';
            }
        }

        require_once __DIR__ . '/../view/auth/recuperar_verificar.php';
    }

    public function reestablecer()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['recuperacion_token'])) {
            header('Location: index.php?controller=recuperacionPassword&action=solicitar');
            exit();
        }

        $token = $_SESSION['recuperacion_token'];
        $mensaje = '';
        $tipo = '';

        try {
            $solicitud = $this->model->validarToken($token);
            if (!$solicitud) {
                unset($_SESSION['recuperacion_token']);
                unset($_SESSION['recuperacion_correo']);
                header('Location: index.php?controller=recuperacionPassword&action=solicitar');
                exit();
            }
        } catch (Exception $e) {
            unset($_SESSION['recuperacion_token']);
            unset($_SESSION['recuperacion_correo']);
            $mensaje = $e->getMessage();
            $tipo = 'danger';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($mensaje)) {
            try {
                $contrasena = $_POST['contrasena'] ?? '';
                $confirmarContrasena = $_POST['confirmar_contrasena'] ?? '';

                if (empty($contrasena) || empty($confirmarContrasena)) {
                    throw new Exception('Ambos campos de contraseña son obligatorios.');
                }

                if (strlen($contrasena) < 6) {
                    throw new Exception('La contraseña debe tener al menos 6 caracteres.');
                }

                if ($contrasena !== $confirmarContrasena) {
                    throw new Exception('Las contraseñas no coinciden.');
                }

                $solicitud = $this->model->validarToken($token);
                if ($solicitud) {
                    $this->model->cambiarContrasena($solicitud['id_usuario'], $contrasena);
                    $this->model->marcarComoUsado($solicitud['id_recuperacion']);
                    
                    unset($_SESSION['recuperacion_token']);
                    unset($_SESSION['recuperacion_correo']);
                    
                    header('Location: index.php?controller=auth&action=login&reset=ok');
                    exit();
                }
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = 'danger';
            }
        }

        require_once __DIR__ . '/../view/auth/recuperar_reestablecer.php';
    }
}
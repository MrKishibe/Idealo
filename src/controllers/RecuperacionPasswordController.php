<?php

namespace Idealo\Controllers;

require_once __DIR__ . '/../../config/smtp_config.php';
require_once __DIR__ . '/../../config/mailer.php';
require_once __DIR__ . '/../models/RecuperacionPasswordModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

use Idealo\Config\Mailer;
use Idealo\Models\RecuperacionPasswordModel;
use Idealo\Models\UsuarioModel;
use Exception;

class RecuperacionPasswordController
{
    private $model;
    private $usuarioModel;

    public function __construct()
    {
        $this->model = new RecuperacionPasswordModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function solicitar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $mensaje = '';
        $tipo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $correo = trim($_POST['correo'] ?? '');
                
                if (empty($correo)) {
                    throw new Exception('El correo electrónico es obligatorio.');
                }

                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('El formato del correo electrónico no es válido.');
                }

                $usuario = $this->usuarioModel->obtenerUsuarioPorCorreo($correo);

                if ($usuario) {
                    $codigo = $this->model->generarCodigo();
                    $token = $this->model->generarToken();
                    
                    $_SESSION['recuperacion_codigo'] = $codigo;
                    $_SESSION['recuperacion_token'] = $token;
                    $_SESSION['recuperacion_correo'] = $correo;
                    $_SESSION['recuperacion_id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['recuperacion_expira'] = time() + (5 * 60);
                    $_SESSION['recuperacion_usado'] = false;
                    
                    $mailer = new Mailer();
                    if ($mailer->enviarCodigoRecuperacion($correo, $codigo)) {
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

        if (!isset($_SESSION['recuperacion_codigo']) || !isset($_SESSION['recuperacion_correo'])) {
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

                if (isset($_SESSION['recuperacion_usado']) && $_SESSION['recuperacion_usado']) {
                    throw new Exception('Este código ya fue utilizado.');
                }

                if (!isset($_SESSION['recuperacion_expira']) || time() > $_SESSION['recuperacion_expira']) {
                    throw new Exception('Este código ha expirado.');
                }

                if (isset($_SESSION['recuperacion_codigo']) && $codigo === $_SESSION['recuperacion_codigo']) {
                    $_SESSION['recuperacion_verificado'] = true;
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

        if (!isset($_SESSION['recuperacion_verificado']) || !isset($_SESSION['recuperacion_id_usuario'])) {
            header('Location: index.php?controller=recuperacionPassword&action=solicitar');
            exit();
        }

        if (isset($_SESSION['recuperacion_expira']) && time() > $_SESSION['recuperacion_expira']) {
            $this->limpiarSesion();
            header('Location: index.php?controller=recuperacionPassword&action=solicitar');
            exit();
        }

        $mensaje = '';
        $tipo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

                $this->usuarioModel->cambiarContrasenaSimple($_SESSION['recuperacion_id_usuario'], $contrasena);
                $_SESSION['recuperacion_usado'] = true;
                
                $this->limpiarSesion();
                
                header('Location: index.php?controller=auth&action=login&reset=ok');
                exit();
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = 'danger';
            }
        }

        require_once __DIR__ . '/../view/auth/recuperar_reestablecer.php';
    }

    private function limpiarSesion()
    {
        unset($_SESSION['recuperacion_codigo']);
        unset($_SESSION['recuperacion_token']);
        unset($_SESSION['recuperacion_correo']);
        unset($_SESSION['recuperacion_id_usuario']);
        unset($_SESSION['recuperacion_expira']);
        unset($_SESSION['recuperacion_usado']);
        unset($_SESSION['recuperacion_verificado']);
    }
}
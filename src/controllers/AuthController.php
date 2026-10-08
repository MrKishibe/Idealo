<?php

namespace Idealo\Controllers;

require_once __DIR__ . "/../models/UsuarioModel.php";
require_once __DIR__ . "/../../config/smtp_config.php";
require_once __DIR__ . "/../../config/mailer.php";
require_once __DIR__ . "/../helpers/RecuperacionPasswordHelper.php";

use Idealo\Models\UsuarioModel;
use Idealo\Config\Mailer;
use Idealo\Helpers\RecuperacionPasswordHelper;


class AuthController
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION["usuario"])) {
            header("Location: index.php?controller=auth&action=dashboard");
            exit;
        }

        $error = "";
        $success = "";

        if (isset($_GET["reset"]) && $_GET["reset"] === "ok") {
            $success = "Contraseña restablecida correctamente. Inicia sesión con tu nueva contraseña.";
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $credencial = trim($_POST["credencial"] ?? $_POST["cedula_usuario"] ?? "");
            $contrasena = $_POST["contrasena"] ?? "";

            if (empty($credencial) || empty($contrasena)) {
                $error = "Usuario/Correo y contraseña son obligatorios.";
            } else {
                $usuario = $this->usuarioModel->obtenerPorCredencial($credencial);

                if ($usuario && password_verify($contrasena, $usuario["contrasena"])) {
                    if ($usuario["status_usuario"] === "activo") {
                        $_SESSION["usuario"] = $usuario["id_usuario"];
                        $_SESSION["rol"] = $usuario["id_rol"];
                        $_SESSION["nombre_usuario"] = $usuario["nombre_usuario"];
                        $_SESSION["nombre_rol"] = $usuario["tipo_de_usuario"] ?? "";
                        header("Location: index.php?controller=auth&action=dashboard");
                        exit;
                    } else {
                        $error = "Tu cuenta está inactiva. Contacta al administrador.";
                    }
                } else {
                    $error = "Credenciales incorrectas.";
                }
            }
        }

        require_once __DIR__ . "/../view/auth/login.php";
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: index.php?controller=auth&action=login");
        exit;
    }

    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION["usuario"])) {
            header("Location: index.php?controller=auth&action=login");
            exit;
        }

        $nombreUsuario = $_SESSION["nombre_usuario"] ?? "";
        $rolUsuario = $_SESSION["nombre_rol"] ?? "";
        $total_empleados = 0;
        $pedidos_counts = [
            'pendiente' => 0,
            'en proceso' => 0,
            'completado' => 0,
            'cancelado' => 0,
        ];
        $materia_bajo_stock = [];
        $pedidos_recientes = [];

        try {
            $pdo = \Idealo\Config\Database::connect();

            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM empleado WHERE status_empleado = 'activo'");
            $total_empleados = (int)($stmt->fetch()['total'] ?? 0);

            $stmt = $pdo->query("SELECT estado_pedido, COUNT(*) AS total FROM pedido GROUP BY estado_pedido");
            foreach ($stmt->fetchAll() as $fila) {
                $clave = mb_strtolower(trim($fila['estado_pedido']), 'UTF-8');
                $pedidos_counts[$clave] = (int)$fila['total'];
            }

            $stmt = $pdo->query("SELECT nombre_materia_prima, stock_actual, stock_minimo
                                  FROM materia_prima
                                  WHERE stock_actual < stock_minimo
                                    AND status_materia_prima = 'disponible'
                                  ORDER BY (stock_actual - stock_minimo) ASC");
            $materia_bajo_stock = $stmt->fetchAll();

            $stmt = $pdo->query("SELECT p.id_pedido, c.nombre_razon_social, p.fecha_creacion, p.monto_total, p.estado_pedido
                                  FROM pedido p
                                  INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                                  ORDER BY p.id_pedido DESC
                                  LIMIT 5");
            $pedidos_recientes = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // Si una tabla falta, el dashboard se renderiza con valores por defecto.
            error_log("dashboard: " . $e->getMessage());
        }

        require_once __DIR__ . "/../view/dashboard.php";
    }

    public function recuperar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION["usuario"])) {
            header("Location: index.php?controller=auth&action=dashboard");
            exit;
        }

        $mensaje = "";
        $tipo = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try {
                $correo = trim($_POST["correo"] ?? "");

                if (empty($correo)) {
                    throw new Exception("El correo electrónico es obligatorio.");
                }

                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("El formato del correo electrónico no es válido.");
                }

                $usuario = $this->usuarioModel->obtenerUsuarioPorCorreo($correo);

                if ($usuario) {
                    $codigo = RecuperacionPasswordHelper::generarCodigo();
                    $codigoHash = RecuperacionPasswordHelper::generarCodigoHash($codigo);
                    $token = RecuperacionPasswordHelper::generarToken();
                    $tokenHash = RecuperacionPasswordHelper::generarTokenHash($token);

                    $_SESSION["recuperacion_codigo_hash"] = $codigoHash;
                    $_SESSION["recuperacion_token_hash"] = $tokenHash;
                    $_SESSION["recuperacion_token"] = $token;
                    $_SESSION["recuperacion_correo"] = $correo;
                    $_SESSION["recuperacion_id_usuario"] = $usuario["id_usuario"];
                    $_SESSION["recuperacion_expira"] = time() + (5 * 60);
                    $_SESSION["recuperacion_usado"] = false;
                    $_SESSION["recuperacion_verificado"] = false;

                    $mailer = new Mailer();
                    if ($mailer->enviarCodigoRecuperacion($correo, $codigo)) {
                        header("Location: index.php?controller=auth&action=verificarRecuperacion");
                        exit;
                    } else {
                        throw new Exception("No se pudo enviar el correo de recuperación. Inténtalo nuevamente.");
                    }
                } else {
                    $mensaje = "Si el correo existe en nuestro sistema, recibirás un código de recuperación.";
                    $tipo = "info";
                }
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = "danger";
            }
        }

        require_once __DIR__ . "/../view/auth/recuperar_solicitar.php";
    }

    public function verificarRecuperacion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION["usuario"])) {
            header("Location: index.php?controller=auth&action=dashboard");
            exit;
        }

        if (!isset($_SESSION["recuperacion_codigo_hash"]) || !isset($_SESSION["recuperacion_correo"])) {
            header("Location: index.php?controller=auth&action=recuperar");
            exit;
        }

        $correo = $_SESSION["recuperacion_correo"];
        $mensaje = "";
        $tipo = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try {
                $codigo = trim($_POST["codigo"] ?? "");

                if (empty($codigo)) {
                    throw new Exception("El código de verificación es obligatorio.");
                }

                if (isset($_SESSION["recuperacion_usado"]) && $_SESSION["recuperacion_usado"]) {
                    throw new Exception("Este código ya fue utilizado.");
                }

                if (!isset($_SESSION["recuperacion_expira"]) || time() > $_SESSION["recuperacion_expira"]) {
                    throw new Exception("Este código ha expirado.");
                }

                $codigoHash = $_SESSION["recuperacion_codigo_hash"] ?? "";

                if (RecuperacionPasswordHelper::verificarCodigo($codigo, $codigoHash)) {
                    $_SESSION["recuperacion_verificado"] = true;
                    header("Location: index.php?controller=auth&action=reestablecerRecuperacion");
                    exit;
                } else {
                    throw new Exception("Código inválido.");
                }
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = "danger";
            }
        }

        require_once __DIR__ . "/../view/auth/recuperar_verificar.php";
    }

    public function reestablecerRecuperacion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION["usuario"])) {
            header("Location: index.php?controller=auth&action=dashboard");
            exit;
        }

        if (!isset($_SESSION["recuperacion_verificado"]) || !isset($_SESSION["recuperacion_id_usuario"])) {
            header("Location: index.php?controller=auth&action=recuperar");
            exit;
        }

        if (isset($_SESSION["recuperacion_expira"]) && time() > $_SESSION["recuperacion_expira"]) {
            RecuperacionPasswordHelper::limpiarSesionRecuperacion();
            header("Location: index.php?controller=auth&action=recuperar");
            exit;
        }

        $mensaje = "";
        $tipo = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try {
                $contrasena = $_POST["contrasena"] ?? "";
                $confirmarContrasena = $_POST["confirmar_contrasena"] ?? "";

                if (empty($contrasena) || empty($confirmarContrasena)) {
                    throw new Exception("Ambos campos de contraseña son obligatorios.");
                }

                if (strlen($contrasena) < 6) {
                    throw new Exception("La contraseña debe tener al menos 6 caracteres.");
                }

                if ($contrasena !== $confirmarContrasena) {
                    throw new Exception("Las contraseñas no coinciden.");
                }

                $this->usuarioModel->cambiarContrasenaSimple($_SESSION["recuperacion_id_usuario"], $contrasena);
                $_SESSION["recuperacion_usado"] = true;
                RecuperacionPasswordHelper::limpiarSesionRecuperacion();
                header("Location: index.php?controller=auth&action=login&reset=ok");
                exit;
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo = "danger";
            }
        }

        require_once __DIR__ . "/../view/auth/recuperar_reestablecer.php";
    }
}





















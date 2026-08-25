<?php

namespace Idealo\Controllers;

class AuthController
{
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['usuario'])) {
            header("Location: index.php?controller=auth&action=dashboard");
            exit();
        }

        $error_login = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo = new \PDO("mysql:host=localhost;dbname=idealo;charset=utf8mb4", "root", "");
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $usuario_input = $_POST['cedula_usuario'] ?? $_POST['nombre_usuario'] ?? '';
                $contrasena = $_POST['contrasena'] ?? '';

                $sql = "SELECT u.id_usuario, u.nombre_usuario, u.contrasena, u.id_rol, 
                               r.tipo_de_usuario 
                        FROM usuario u 
                        LEFT JOIN roles r ON u.id_rol = r.id_rol 
                        WHERE u.nombre_usuario = ? AND u.status_usuario = 'activo'";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$usuario_input]);
                $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {

                    $_SESSION['usuario']        = $usuario['id_usuario'];
                    $_SESSION['rol']            = $usuario['id_rol'];
                    $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
                    $_SESSION['nombre_rol']     = !empty($usuario['tipo_de_usuario']) ? $usuario['tipo_de_usuario'] : 'Sin Rol Asignado';

                    header("Location: index.php?controller=auth&action=dashboard");
                    exit();
                } else {
                    $error_login = "Credenciales incorrectas o el usuario no existe/está inactivo.";
                }
            } catch (\PDOException $e) {
                $error_login = "Error de conexión: " . $e->getMessage();
            }
        }

        require_once __DIR__ . '/../view/auth/login.php';
    }

    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php?controller=auth&action=login");
            exit();
        }

        $nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
        $rolUsuario    = $_SESSION['nombre_rol'] ?? 'Invitado';

        try {
            $pdo = new \PDO("mysql:host=localhost;dbname=idealo;charset=utf8mb4", "root", "");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmtEmpleados = $pdo->query("SELECT COUNT(*) AS total FROM empleado WHERE status_empleado = 'activo'");
            $total_empleados = $stmtEmpleados->fetch(\PDO::FETCH_ASSOC)['total'];

            $stmtPedidosStatus = $pdo->query("SELECT estado_pedido, COUNT(*) AS total FROM pedido GROUP BY estado_pedido");
            $pedidos_por_estado = $stmtPedidosStatus->fetchAll(\PDO::FETCH_ASSOC);

            $pedidos_counts = [
                'pendiente' => 0,
                'en proceso' => 0,
                'completado' => 0,
                'cancelado' => 0
            ];
            foreach ($pedidos_por_estado as $p) {
                $estado_lowercase = mb_strtolower($p['estado_pedido'], 'UTF-8');
                if (array_key_exists($estado_lowercase, $pedidos_counts)) {
                    $pedidos_counts[$estado_lowercase] = $p['total'];
                }
            }

            $stmtBajoStock = $pdo->query("SELECT nombre_materia_prima, stock_actual, stock_minimo FROM materia_prima WHERE stock_actual <= stock_minimo AND status_materia_prima = 'disponible'");
            $materia_bajo_stock = $stmtBajoStock->fetchAll(\PDO::FETCH_ASSOC);

            $stmtRecientes = $pdo->query("SELECT p.id_pedido, c.nombre_razon_social, p.fecha_creacion, p.monto_total, p.estado_pedido FROM pedido p JOIN cliente c ON p.id_cliente = c.id_cliente ORDER BY p.id_pedido DESC LIMIT 5");
            $pedidos_recientes = $stmtRecientes->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            die("Error en el dashboard: " . $e->getMessage());
        }

        require_once __DIR__ . '/../view/dashboard.php';
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        header("Location: index.php?controller=auth&action=login");
        exit();
    }
}

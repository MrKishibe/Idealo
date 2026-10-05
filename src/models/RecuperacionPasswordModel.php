<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';

use Idealo\Config\Database;
use Exception;

class RecuperacionPasswordModel extends Database
{
    private $conex;

    public function __construct()
    {
        $this->conex = parent::connect();
    }

    public function generarCodigo(): string
    {
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function obtenerUsuarioPorCorreo(string $correo): ?array
    {
        $sql = "SELECT id_usuario, nombre_usuario, correo FROM usuario WHERE correo = :correo AND status_usuario = 'activo'";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function crearSolicitudRecuperacion(int $idUsuario, string $correo, string $codigo, string $token, int $minutosExpiracion = 5): array
    {
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime("+$minutosExpiracion minutes"));
        
        $sql = "INSERT INTO recuperacion_password (id_usuario, correo, codigo, token, fecha_expiracion)
                VALUES (:id_usuario, :correo, :codigo, :token, :fecha_expiracion)";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':correo' => $correo,
            ':codigo' => $codigo,
            ':token' => $token,
            ':fecha_expiracion' => $fechaExpiracion
        ]);

        return [
            'id_recuperacion' => (int)$this->conex->lastInsertId(),
            'token' => $token,
            'codigo' => $codigo,
            'fecha_expiracion' => $fechaExpiracion
        ];
    }

    public function validarCodigo(string $correo, string $codigo): ?array
    {
        $sql = "SELECT r.id_recuperacion, r.id_usuario, r.token, r.fecha_expiracion, r.usado
                FROM recuperacion_password r
                WHERE r.correo = :correo AND r.codigo = :codigo
                ORDER BY r.id_recuperacion DESC
                LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([
            ':correo' => $correo,
            ':codigo' => $codigo
        ]);
        $resultado = $stmt->fetch();
        if (!$resultado) {
            return null;
        }

        if ($resultado['usado']) {
            throw new Exception('Este código ya fue utilizado.');
        }

        if (strtotime($resultado['fecha_expiracion']) < time()) {
            throw new Exception('Este código ha expirado.');
        }

        return $resultado;
    }

    public function validarToken(string $token): ?array
    {
        $sql = "SELECT r.id_recuperacion, r.id_usuario, r.correo, r.fecha_expiracion, r.usado
                FROM recuperacion_password r
                WHERE r.token = :token
                LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':token' => $token]);
        $resultado = $stmt->fetch();
        if (!$resultado) {
            return null;
        }

        if ($resultado['usado']) {
            throw new Exception('Este enlace ya fue utilizado.');
        }

        if (strtotime($resultado['fecha_expiracion']) < time()) {
            throw new Exception('Este enlace ha expirado.');
        }

        return $resultado;
    }

    public function marcarComoUsado(int $idRecuperacion): bool
    {
        $sql = "UPDATE recuperacion_password SET usado = 1 WHERE id_recuperacion = :id_recuperacion";
        $stmt = $this->conex->prepare($sql);
        return $stmt->execute([':id_recuperacion' => $idRecuperacion]);
    }

    public function cambiarContrasena(int $idUsuario, string $contrasena): bool
    {
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $sql = "UPDATE usuario SET contrasena = :contrasena WHERE id_usuario = :id_usuario";
        $stmt = $this->conex->prepare($sql);
        return $stmt->execute([
            ':contrasena' => $hash,
            ':id_usuario' => $idUsuario
        ]);
    }
}
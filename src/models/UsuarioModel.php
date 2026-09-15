<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';

use Idealo\Config\Database;
use Exception;

class UsuarioModel extends Database
{
    private $conex;

    public $expNombreUsuario = '/^[a-zA-Z0-9_]{3,20}$/';

    public function __construct()
    {
        $this->conex = parent::connect();
    }

    public function listarRoles(): array
    {
        $sql = "SELECT id_rol, tipo_de_usuario
                FROM roles
                WHERE status_roles = 'activo'
                ORDER BY id_rol ASC";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarTodos(): array
    {
        $sql = "SELECT u.id_usuario,
                       u.nombre_usuario,
                       u.status_usuario,
                       u.id_rol,
                       r.tipo_de_usuario
                FROM usuario u
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                ORDER BY u.id_usuario DESC";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function guardar(array $datos): array
    {
        $nombreUsuario = trim($datos['nombre_usuario'] ?? '');
        $contrasena    = $datos['contrasena'] ?? '';
        $idRol         = intval($datos['id_rol'] ?? 0);

        $this->validarNombreUsuario($nombreUsuario);
        $this->validarContrasena($contrasena);

        if (!$this->rolExiste($idRol)) {
            throw new Exception("[Validación] El rol seleccionado no es válido.");
        }

        if ($this->existeNombreUsuario($nombreUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nombreUsuario}' ya está registrado.");
        }

        $hash = password_hash($contrasena, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuario (nombre_usuario, contrasena, status_usuario, id_rol)
                VALUES (:nombre_usuario, :contrasena, 'activo', :id_rol)";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([
            ':nombre_usuario' => $nombreUsuario,
            ':contrasena'     => $hash,
            ':id_rol'         => $idRol
        ]);

        return [
            'success'    => true,
            'message'    => 'Usuario registrado con éxito.',
            'id_usuario' => (int)$this->conex->lastInsertId()
        ];
    }

    public function editar(array $datos): array
    {
        $idUsuario     = intval($datos['id_usuario'] ?? 0);
        $nombreUsuario = trim($datos['nombre_usuario'] ?? '');
        $contrasena    = $datos['contrasena'] ?? '';
        $idRol         = intval($datos['id_rol'] ?? 0);

        if ($idUsuario <= 0) {
            throw new Exception("[Validación] El ID del usuario es obligatorio para editar.");
        }

        $this->validarNombreUsuario($nombreUsuario);

        if (!$this->rolExiste($idRol)) {
            throw new Exception("[Validación] El rol seleccionado no es válido.");
        }

        if ($this->existeNombreUsuario($nombreUsuario, $idUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nombreUsuario}' ya está registrado.");
        }

        if (!empty($contrasena)) {
            $this->validarContrasena($contrasena);
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);
            $sql = "UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        contrasena = :contrasena,
                        id_rol = :id_rol
                    WHERE id_usuario = :id_usuario";
            $params = [
                ':nombre_usuario' => $nombreUsuario,
                ':contrasena'     => $hash,
                ':id_rol'         => $idRol,
                ':id_usuario'     => $idUsuario
            ];
        } else {
            $sql = "UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        id_rol = :id_rol
                    WHERE id_usuario = :id_usuario";
            $params = [
                ':nombre_usuario' => $nombreUsuario,
                ':id_rol'         => $idRol,
                ':id_usuario'     => $idUsuario
            ];
        }

        $stmt = $this->conex->prepare($sql);
        $stmt->execute($params);

        return [
            'success' => true,
            'message' => 'Usuario actualizado con éxito.'
        ];
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        if ($id <= 0) {
            throw new Exception("[Validación] El ID del usuario no es válido.");
        }

        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new Exception("[Validación] El estado del usuario no es válido.");
        }

        $sql = "UPDATE usuario
                SET status_usuario = :estado
                WHERE id_usuario = :id_usuario";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([
            ':estado'      => $estado,
            ':id_usuario'  => $id
        ]);

        $mensaje = $estado === 'activo'
            ? 'Usuario activado con éxito.'
            : 'Usuario inactivado con éxito.';

        return [
            'success' => true,
            'message' => $mensaje
        ];
    }

    public function obtenerPerfil(int $idUsuario): ?array
    {
        $sql = "SELECT u.id_usuario,
                       u.nombre_usuario,
                       u.status_usuario,
                       u.id_rol,
                       r.tipo_de_usuario,
                       e.nombres,
                       e.apellidos,
                       e.cedula,
                       e.telefono,
                       e.cargo
                FROM usuario u
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                LEFT JOIN acceso_empleado ae ON ae.id_usuario = u.id_usuario
                LEFT JOIN empleado e ON ae.id_empleado = e.id_empleado
                WHERE u.id_usuario = :id_usuario
                LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $perfil = $stmt->fetch();
        return $perfil ?: null;
    }

    public function cambiarContrasena(int $idUsuario, string $contrasenaActual, string $nuevaContrasena): array
    {
        $sql = "SELECT contrasena FROM usuario WHERE id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $fila = $stmt->fetch();

        if (!$fila) {
            throw new Exception("[Validación] El usuario no existe.");
        }

        if (!password_verify($contrasenaActual, $fila['contrasena'])) {
            throw new Exception("[Validación] La contraseña actual es incorrecta.");
        }

        $this->validarContrasena($nuevaContrasena);

        $hash = password_hash($nuevaContrasena, PASSWORD_BCRYPT);
        $sql = "UPDATE usuario
                SET contrasena = :contrasena
                WHERE id_usuario = :id_usuario";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([
            ':contrasena' => $hash,
            ':id_usuario' => $idUsuario
        ]);

        return [
            'success' => true,
            'message' => 'Contraseña actualizada con éxito.'
        ];
    }

    public function cambiarNombreUsuario(int $idUsuario, string $nuevoNombre): array
    {
        $nuevoNombre = trim($nuevoNombre);

        $this->validarNombreUsuario($nuevoNombre);

        if ($this->existeNombreUsuario($nuevoNombre, $idUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nuevoNombre}' ya está registrado.");
        }

        $sql = "UPDATE usuario
                SET nombre_usuario = :nombre_usuario
                WHERE id_usuario = :id_usuario";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([
            ':nombre_usuario' => $nuevoNombre,
            ':id_usuario'     => $idUsuario
        ]);

        return [
            'success' => true,
            'message' => 'Perfil actualizado con éxito.'
        ];
    }

    public function existeNombreUsuario(string $nombre, ?int $exceptuarId = null): bool
    {
        if ($exceptuarId !== null && $exceptuarId > 0) {
            $sql = "SELECT id_usuario
                    FROM usuario
                    WHERE nombre_usuario = :nombre
                      AND id_usuario <> :id
                    LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':id'     => $exceptuarId
            ]);
        } else {
            $sql = "SELECT id_usuario
                    FROM usuario
                    WHERE nombre_usuario = :nombre
                    LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':nombre' => $nombre]);
        }

        return (bool)$stmt->fetch();
    }

    private function rolExiste(int $idRol): bool
    {
        if ($idRol <= 0) {
            return false;
        }
        $sql = "SELECT id_rol FROM roles WHERE id_rol = :id_rol LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':id_rol' => $idRol]);
        return (bool)$stmt->fetch();
    }

    private function validarNombreUsuario(string $nombre): void
    {
        if (!preg_match($this->expNombreUsuario, $nombre)) {
            throw new Exception("[Validación] El nombre de usuario debe tener entre 3 y 20 caracteres (letras, números o guiones bajos).");
        }
    }

    private function validarContrasena(string $contrasena): void
    {
        if (strlen($contrasena) < 6 || strlen($contrasena) > 255) {
            throw new Exception("[Validación] La contraseña debe tener al menos 6 caracteres.");
        }
    }
}
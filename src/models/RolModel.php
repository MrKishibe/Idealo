<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';

use Idealo\Config\Database;
use PDO;
use PDOException;
use Exception;

class RolModel extends Database
{
    private $conex;

    public $expNombreRol = '/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,50}$/u';

    public function __construct()
    {
        $this->conex = parent::connect();
    }

    public function listarTodos(): array
    {
        try {
            $sql = "SELECT id_rol, tipo_de_usuario, status_roles
                    FROM roles
                    ORDER BY id_rol DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            error_log('Error al listar roles: ' . $error->getMessage());
            return [];
        }
    }

    public function guardar(array $datos): array
    {
        $nombreRol = trim($datos['tipo_de_usuario'] ?? '');

        $this->validarNombre($nombreRol);

        if ($this->existeNombre($nombreRol)) {
            throw new Exception("[Validación] El rol '{$nombreRol}' ya está registrado.");
        }

        try {
            $sql = "INSERT INTO roles (tipo_de_usuario, status_roles)
                    VALUES (:tipo_de_usuario, 'activo')";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':tipo_de_usuario' => $nombreRol
            ]);

            return [
                'success' => true,
                'message' => 'Rol registrado con éxito.',
                'id_rol'  => (int)$this->conex->lastInsertId()
            ];
        } catch (PDOException $error) {
            error_log('Error al guardar rol: ' . $error->getMessage());
            throw new Exception('No se pudo registrar el rol. Intente nuevamente.');
        }
    }

    public function editar(array $datos): array
    {
        $idRol     = intval($datos['id_rol'] ?? 0);
        $nombreRol = trim($datos['tipo_de_usuario'] ?? '');

        if ($idRol <= 0) {
            throw new Exception("[Validación] El ID del rol es obligatorio para editar.");
        }

        $this->validarNombre($nombreRol);

        if ($this->existeNombre($nombreRol, $idRol)) {
            throw new Exception("[Validación] El rol '{$nombreRol}' ya está registrado.");
        }

        try {
            $sql = "UPDATE roles
                    SET tipo_de_usuario = :tipo_de_usuario
                    WHERE id_rol = :id_rol";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':tipo_de_usuario' => $nombreRol,
                ':id_rol'          => $idRol
            ]);

            return [
                'success' => true,
                'message' => 'Rol actualizado con éxito.'
            ];
        } catch (PDOException $error) {
            error_log('Error al editar rol: ' . $error->getMessage());
            throw new Exception('No se pudo actualizar el rol. Intente nuevamente.');
        }
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        if ($id <= 0) {
            throw new Exception("[Validación] El ID del rol no es válido.");
        }

        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new Exception("[Validación] El estado del rol no es válido.");
        }

        try {
            $sql = "UPDATE roles
                    SET status_roles = :estado
                    WHERE id_rol = :id_rol";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':estado' => $estado,
                ':id_rol' => $id
            ]);

            $mensaje = $estado === 'activo'
                ? 'Rol activado con éxito.'
                : 'Rol inactivado con éxito.';

            return [
                'success' => true,
                'message' => $mensaje
            ];
        } catch (PDOException $error) {
            error_log('Error al cambiar el estado del rol: ' . $error->getMessage());
            throw new Exception('No se pudo cambiar el estado del rol. Intente nuevamente.');
        }
    }

    public function existeNombre(string $nombre, ?int $exceptuarId = null): bool
    {
        try {
            if ($exceptuarId !== null && $exceptuarId > 0) {
                $sql = "SELECT id_rol
                        FROM roles
                        WHERE tipo_de_usuario = :nombre
                          AND id_rol <> :id
                        LIMIT 1";
                $stmt = $this->conex->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':id'     => $exceptuarId
                ]);
            } else {
                $sql = "SELECT id_rol
                        FROM roles
                        WHERE tipo_de_usuario = :nombre
                        LIMIT 1";
                $stmt = $this->conex->prepare($sql);
                $stmt->execute([':nombre' => $nombre]);
            }

            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            error_log('Error al verificar el nombre del rol: ' . $error->getMessage());
            throw new Exception('No se pudo verificar el nombre del rol. Intente nuevamente.');
        }
    }

    private function validarNombre(string $nombre): void
    {
        if (!preg_match($this->expNombreRol, $nombre)) {
            throw new Exception("[Validación] El nombre del rol debe tener entre 3 y 50 caracteres (solo letras y espacios).");
        }
    }
}

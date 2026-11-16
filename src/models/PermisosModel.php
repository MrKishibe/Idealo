<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use InvalidArgumentException;
use PDO;

class PermisosModel extends Database {
    private $id_permiso;
    private $nombre_permiso;
    private $status_permiso;
    private $pdo;

    public function __construct() {
        $this->pdo = new Database();
    }

    public function getIdPermiso() { return $this->id_permiso; }
    public function setIdPermiso($id_permiso) { $this->id_permiso = $id_permiso; }

    public function getNombrePermiso() { return $this->nombre_permiso; }
    public function setNombrePermiso($nombre_permiso) { $this->nombre_permiso = $nombre_permiso; }

    public function getStatusPermiso() { return $this->status_permiso; }
    public function setStatusPermiso($status_permiso) { $this->status_permiso = $status_permiso; }

    public function listarPermisos(): array {
        $sql = "SELECT id_permiso, nombre_permiso, status_permiso
                FROM permiso
                ORDER BY id_permiso";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPermisosPorRol(int $id_rol): array {
        if ($id_rol <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un rol válido.');
        }

        $sql = "SELECT p.id_permiso, p.nombre_permiso, p.status_permiso
                FROM permisos_rol pr
                INNER JOIN permiso p ON p.id_permiso = pr.id_permiso
                WHERE pr.id_rol = :id_rol
                ORDER BY p.id_permiso";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute([':id_rol' => $id_rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarPermiso(int $id_permiso): bool {
        if ($id_permiso <= 0) {
            throw new InvalidArgumentException('Debe indicar un permiso válido.');
        }

        return $this->dbEliminarPermiso($id_permiso);
    }

    private function dbEliminarPermiso(int $id_permiso): bool {
        $sql = "UPDATE permiso
                SET status_permiso = 'inactivo'
                WHERE id_permiso = :id_permiso";
        $stmt = $this->pdo->connect()->prepare($sql);
        return $stmt->execute([':id_permiso' => $id_permiso]);
    }

    private function registrarPermisoRol(int $id_rol, int $id_permiso): bool {
        $sql = "INSERT INTO permisos_rol (id_rol, id_permiso)
                SELECT :id_rol, :id_permiso
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM permisos_rol
                    WHERE id_rol = :id_rol_existente
                        AND id_permiso = :id_permiso_existente
                )";
        $stmt = $this->pdo->connect()->prepare($sql);

        return $stmt->execute([
            ':id_rol' => $id_rol,
            ':id_permiso' => $id_permiso,
            ':id_rol_existente' => $id_rol,
            ':id_permiso_existente' => $id_permiso
        ]);
    }

    protected function validar(array &$datos): void {
        $id_rol = $datos['id_rol'] ?? null;
        $id_permisos = $datos['id_permisos'] ?? null;
        $regexId = '/^[1-9]\d*$/D';

        if ((!is_int($id_rol) && !is_string($id_rol))
            || !preg_match($regexId, (string) $id_rol)) {
            throw new InvalidArgumentException('Debe indicar un ID de rol válido.');
        }

        if (!is_array($id_permisos) || $id_permisos === []) {
            throw new InvalidArgumentException('Debe seleccionar al menos un permiso.');
        }

        $ids_unicos = [];
        foreach ($id_permisos as $id_permiso) {
            if ((!is_int($id_permiso) && !is_string($id_permiso))
                || !preg_match($regexId, (string) $id_permiso)) {
                throw new InvalidArgumentException('Todos los permisos deben tener un ID válido.');
            }
            $ids_unicos[(int) $id_permiso] = (int) $id_permiso;
        }

        $datos['id_rol'] = (int) $id_rol;
        $datos['id_permisos'] = array_values($ids_unicos);

        $conn = $this->pdo->connect();
        $stmtRol = $conn->prepare('SELECT 1 FROM roles WHERE id_rol = :id_rol');
        $stmtRol->execute([':id_rol' => $datos['id_rol']]);
        if (!$stmtRol->fetchColumn()) {
            throw new InvalidArgumentException('El rol seleccionado no existe.');
        }

        $placeholders = [];
        $parametros = [];
        foreach ($datos['id_permisos'] as $indice => $id_permiso) {
            $placeholder = ':id_permiso_' . $indice;
            $placeholders[] = $placeholder;
            $parametros[$placeholder] = $id_permiso;
        }

        $sqlPermisos = 'SELECT COUNT(*) FROM permiso WHERE id_permiso IN ('
            . implode(', ', $placeholders) . ')';
        $stmtPermisos = $conn->prepare($sqlPermisos);
        $stmtPermisos->execute($parametros);

        if ((int) $stmtPermisos->fetchColumn() !== count($datos['id_permisos'])) {
            throw new InvalidArgumentException('Uno o más permisos seleccionados no existen.');
        }
    }

    public function asignarPermisosRol(array $datos): bool {
        $this->validar($datos);
        $conn = $this->pdo->connect();

        try {
            $conn->beginTransaction();

            foreach ($datos['id_permisos'] as $id_permiso) {
                if (!$this->registrarPermisoRol($datos['id_rol'], $id_permiso)) {
                    throw new \RuntimeException('No se pudo asignar uno de los permisos al rol.');
                }
            }

            $conn->commit();
            return true;
        } catch (\Throwable $error) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $error;
        }
    }
}
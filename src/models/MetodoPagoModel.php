<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class MetodoPagoModel extends Database
{
    public function __construct() {}

    protected function getDb(): PDO
    {
        return self::connect();
    }

    private function validar(array &$datos, bool $esEdicion = false): void
    {
        if ($esEdicion && empty($datos['id_metodo_de_pago'])) {
            throw new Exception("❌ El ID del método de pago es obligatorio para editar.");
        }

        $datos['nombre_metodo_de_pago'] = trim($datos['nombre_metodo_de_pago'] ?? '');
        if (empty($datos['nombre_metodo_de_pago'])) {
            throw new Exception("❌ El nombre del método de pago es obligatorio.");
        }

        if (strlen($datos['nombre_metodo_de_pago']) < 3 || strlen($datos['nombre_metodo_de_pago']) > 50) {
            throw new Exception("❌ El nombre debe tener entre 3 y 50 caracteres.");
        }
    }

    public function listarTodos(): array
    {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo, 
                       status_metodo_de_pago AS estado 
                FROM metodo_de_pago 
                ORDER BY id_metodo_de_pago DESC";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(array $datos): bool
    {
        $this->validar($datos, false);

        $sql = "INSERT INTO metodo_de_pago (nombre_metodo_de_pago, status_metodo_de_pago) 
                VALUES (:nombre_metodo_de_pago, 'activo')";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':nombre_metodo_de_pago' => $datos['nombre_metodo_de_pago']
        ]);
    }

    public function editar(array $datos): bool
    {
        $this->validar($datos, true);

        $sql = "UPDATE metodo_de_pago 
                SET nombre_metodo_de_pago = :nombre_metodo_de_pago 
                WHERE id_metodo_de_pago = :id_metodo_de_pago";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':nombre_metodo_de_pago' => $datos['nombre_metodo_de_pago'],
            ':id_metodo_de_pago'     => $datos['id_metodo_de_pago']
        ]);
    }

    public function cambiarEstado(int $id, int $nuevoEstado): bool
    {
        $statusString = ($nuevoEstado === 1) ? 'activo' : 'inhabilitado';
        $sql = "UPDATE metodo_de_pago SET status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id_metodo_de_pago";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':estado'             => $statusString, 
            ':id_metodo_de_pago' => $id
        ]);
    }
}
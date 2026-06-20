<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class ControlPagosModel extends Database
{
    private const REGEX_MONTO = '/^[0-9]+(\.[0-9]{1,2})?$/';
    private const REGEX_REFERENCIA = '/^[a-zA-Z0-9-]{3,30}$/';

    public function __construct() {}

    protected function getDb(): PDO
    {
        return self::connect();
    }

    private function validar(array &$datos, bool $esEdicion = false): void
    {
        if ($esEdicion && empty($datos['id_pago'])) {
            throw new Exception("❌ El ID del pago es obligatorio para editar.");
        }

        if (empty($datos['id_pedido']) || !filter_var($datos['id_pedido'], FILTER_VALIDATE_INT)) {
            throw new Exception("❌ El número de pedido asociado es obligatorio.");
        }

        $datos['monto_pago'] = trim($datos['monto_pago']);
        if (empty($datos['monto_pago'])) {
            throw new Exception("❌ El monto del pago es obligatorio.");
        }
        if (!preg_match(self::REGEX_MONTO, $datos['monto_pago']) || floatval($datos['monto_pago']) <= 0) {
            throw new Exception("❌ El monto debe ser un número positivo válido.");
        }

        if (empty($datos['id_metodo_de_pago']) || !filter_var($datos['id_metodo_de_pago'], FILTER_VALIDATE_INT)) {
            throw new Exception("❌ El método de pago seleccionado no es válido.");
        }

        $datos['referencia'] = trim($datos['referencia']);
        if (!empty($datos['referencia'])) {
            if (!preg_match(self::REGEX_REFERENCIA, $datos['referencia'])) {
                throw new Exception("❌ La referencia no tiene un formato válido.");
            }
        } else {
            $datos['referencia'] = null;
        }

        if (empty($datos['fecha_pago'])) {
            throw new Exception("❌ La fecha de pago es obligatoria.");
        }
    }

    public function listarTodos(): array
    {
        $sql = "SELECT cp.id_pago, cp.monto_abonado AS monto_pago, cp.referencia, cp.fecha_pago, 
                       cp.status_pago AS estado, cp.id_pedido, cp.id_metodo_de_pago,
                       p.id_pedido, mp.nombre_metodo_de_pago AS nombre_metodo, 'Sistema' AS nombre_usuario
                FROM pago cp
                LEFT JOIN pedido p ON cp.id_pedido = p.id_pedido
                LEFT JOIN metodo_de_pago mp ON cp.id_metodo_de_pago = mp.id_metodo_de_pago
                ORDER BY cp.id_pago DESC";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(array $datos): bool
    {
        $this->validar($datos, false);

        $sql = "INSERT INTO pago (id_pedido, monto_abonado, id_metodo_de_pago, referencia, fecha_pago, status_pago) 
                VALUES (:id_pedido, :monto_pago, :id_metodo_de_pago, :referencia, :fecha_pago, 'procesado')";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':id_pedido'         => $datos['id_pedido'],
            ':monto_pago'        => $datos['monto_pago'],
            ':id_metodo_de_pago' => $datos['id_metodo_de_pago'],
            ':referencia'        => $datos['referencia'],
            ':fecha_pago'        => $datos['fecha_pago']
        ]);
    }

    public function editar(array $datos): bool
    {
        $this->validar($datos, true);

        $sql = "UPDATE pago 
                SET id_pedido = :id_pedido, monto_abonado = :monto_pago, id_metodo_de_pago = :id_metodo_de_pago, 
                    referencia = :referencia, fecha_pago = :fecha_pago 
                WHERE id_pago = :id_pago";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':id_pedido'         => $datos['id_pedido'],
            ':monto_pago'        => $datos['monto_pago'],
            ':id_metodo_de_pago' => $datos['id_metodo_de_pago'],
            ':referencia'        => $datos['referencia'],
            ':fecha_pago'        => $datos['fecha_pago'],
            ':id_pago'           => $datos['id_pago']
        ]);
    }

    public function cambiarEstado(int $id, int $nuevoEstado): bool
    {
        $statusString = ($nuevoEstado === 1) ? 'procesado' : 'inhabilitado';
        $sql = "UPDATE pago SET status_pago = :estado WHERE id_pago = :id_pago";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':estado'  => $statusString, 
            ':id_pago' => $id
        ]);
    }

    public function obtenerMetodosPago(): array
    {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo 
                FROM metodo_de_pago 
                WHERE status_metodo_de_pago = 'activo'";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPedidosActivos(): array
    {
        $sql = "SELECT id_pedido FROM pedido";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
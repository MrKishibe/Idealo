<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;

class Pedido
{
    private $pdo;

    public function __construct($db)
    {
        $this->pdo = $db;
    }

    public function guardarPedido($idCliente, $tipoPedido, $montoTotal, $detalles)
    {
        try {
            // Control estricto de transacciones
            $this->pdo->beginTransaction();

            $query = "INSERT INTO pedido (fecha_creacion, id_tipo_pedido, estado_pedido, monto_total, id_cliente) 
                      VALUES (CURDATE(), :id_tipo, 'pendiente', :monto, :id_cliente)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':id_tipo' => $tipoPedido,
                ':monto' => $montoTotal,
                ':id_cliente' => $idCliente
            ]);

            $idPedido = $this->pdo->lastInsertId();

            $queryDetalle = "INSERT INTO detalle_pedido (cantidad, id_producto_caracteristica, id_pedido, id_servicio) 
                             VALUES (:cantidad, :id_prod_carac, :id_pedido, :id_servicio)";
            $stmtDetalle = $this->pdo->prepare($queryDetalle);

            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    ':cantidad' => $detalle['cantidad'],
                    ':id_prod_carac' => $detalle['id_producto_caracteristica'],
                    ':id_pedido' => $idPedido,
                    ':id_servicio' => $detalle['id_servicio']
                ]);
            }

            // Si todo es exitoso se confirma a la Base de Datos
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            // Ante cualquier anomalía, regresamos al estado inicial
            $this->pdo->rollBack();
            return false;
        }
    }
}

<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class OrdenDeProduccionModel extends Database {
    private $fecha_de_inicio;
    private $fecha_terminado; // Corregido según BD
    private $id_detalle_pedido; // Reemplaza a monto_total para hacer la relación
    private $estado_de_produccion;
    private $pdo;

    function __construct(){
        $this->pdo = new Database();
    }

    public function getFechaDeInicio(){ return $this->fecha_de_inicio; }
    public function setFechaDeInicio($fecha_de_inicio){ $this->fecha_de_inicio = $fecha_de_inicio; }

    public function getFechaTerminado(){ return $this->fecha_terminado; }
    public function setFechaTerminado($fecha_terminado){ $this->fecha_terminado = $fecha_terminado; }

    public function getIdDetallePedido(){ return $this->id_detalle_pedido; }
    public function setIdDetallePedido($id_detalle_pedido){ $this->id_detalle_pedido = $id_detalle_pedido; }

    public function getEstadoDeProduccion(){ return $this->estado_de_produccion; }
    public function setEstadoDeProduccion($estado_de_produccion){ $this->estado_de_produccion = $estado_de_produccion; }
    
    // Método actualizado con JOIN para traer la descripción del pedido
    public function listarOrdenProduccion(){
        $sql = "SELECT 
                    op.id_produccion, 
                    op.fecha_de_inicio, 
                    op.fecha_terminado, 
                    op.estado_de_produccion,
                    op.id_detalle_pedido,
                    p.descripcion AS descripcion_pedido,
                    dp.cantidad
                FROM orden_de_produccion op
                INNER JOIN detalle_pedido dp ON op.id_detalle_pedido = dp.id_detalle_pedido
                INNER JOIN pedido p ON dp.id_pedido = p.id_pedido";
        
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPedidosParaProduccion() {
        $sql = "SELECT dp.id_detalle_pedido, p.descripcion 
                FROM detalle_pedido dp
                INNER JOIN pedido p ON dp.id_pedido = p.id_pedido
                WHERE p.estado_pedido = 'pendiente'"; 
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function registrarOrdenProduccion(): bool {
        $sql = "INSERT INTO orden_de_produccion (fecha_de_inicio, fecha_terminado, estado_de_produccion, id_detalle_pedido) 
                VALUES (:fecha_de_inicio, :fecha_terminado, :estado_de_produccion, :id_detalle_pedido)";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->bindParam(':fecha_de_inicio', $this->fecha_de_inicio);
        $stmt->bindParam(':fecha_terminado', $this->fecha_terminado);
        $stmt->bindParam(':estado_de_produccion', $this->estado_de_produccion);
        $stmt->bindParam(':id_detalle_pedido', $this->id_detalle_pedido);
        return $stmt->execute();
    }

    private function editarOrdenProduccion($id_produccion): bool {
        // Corregido el WHERE id_produccion
        $sql = "UPDATE orden_de_produccion 
                SET fecha_de_inicio = :fecha_de_inicio, 
                    fecha_terminado = :fecha_terminado, 
                    estado_de_produccion = :estado_de_produccion,
                    id_detalle_pedido = :id_detalle_pedido
                WHERE id_produccion = :id_produccion";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->bindParam(':fecha_de_inicio', $this->fecha_de_inicio);
        $stmt->bindParam(':fecha_terminado', $this->fecha_terminado);
        $stmt->bindParam(':estado_de_produccion', $this->estado_de_produccion);
        $stmt->bindParam(':id_detalle_pedido', $this->id_detalle_pedido);
        $stmt->bindParam(':id_produccion', $id_produccion);
        return $stmt->execute();
    }

    private function inactivarOrdenProduccion($id_produccion): bool {
        // Corregido el WHERE id_produccion
        $sql = "UPDATE orden_de_produccion SET estado_de_produccion = 'Inactiva' WHERE id_produccion = :id_produccion";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->bindParam(':id_produccion', $id_produccion);
        return $stmt->execute();
    }

    protected function validar(array &$datos, bool $esEdicion = false): void {
        // Validaciones futuras
    }

    public function guardarOrden(array $datos): bool {
        $this->validar($datos, false);

        $this->setFechaDeInicio($datos['fecha_de_inicio'] ?? null);
        $this->setFechaTerminado($datos['fecha_terminado'] ?? null);
        $this->setIdDetallePedido($datos['id_detalle_pedido'] ?? null);
        $this->setEstadoDeProduccion($datos['estado_de_produccion'] ?? 'en espera');

        return $this->registrarOrdenProduccion();
    }

    public function editarOrden(array $datos): bool {
        $id_produccion = $datos['id_produccion'] ?? null;
        if ($id_produccion === null) {
            throw new \InvalidArgumentException('El id de la orden de producción es obligatorio para editar.');
        }

        $this->setFechaDeInicio($datos['fecha_de_inicio'] ?? null);
        $this->setFechaTerminado($datos['fecha_terminado'] ?? null);
        $this->setIdDetallePedido($datos['id_detalle_pedido'] ?? null);
        $this->setEstadoDeProduccion($datos['estado_de_produccion'] ?? null);

        return $this->editarOrdenProduccion($id_produccion);
    }

    public function inactivarOrden(int $id_produccion): bool {
        return $this->inactivarOrdenProduccion($id_produccion);
    }
}
?>
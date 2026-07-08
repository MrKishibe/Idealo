<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class PerdidaMaterialModel extends Database {

    private $cantidad_perdida;
    private $fecha_de_registro;
    private $motivo;
    private $costo_unitario;
    private $id_produccion;
    private $pdo;

    public function __construct(){
        $this->pdo = new Database();
    }

    public function getCantidadPerdida(){ return $this->cantidad_perdida; }
    public function setCantidadPerdida($cantidad_perdida){ $this->cantidad_perdida = $cantidad_perdida; }

    public function getFechaDeRegistro(){ return $this->fecha_de_registro; }
    public function setFechaDeRegistro($fecha_de_registro){ $this->fecha_de_registro = $fecha_de_registro; }

    public function getMotivo(){ return $this->motivo; }
    public function setMotivo($motivo){ $this->motivo = $motivo; }

    public function getCostoUnitario(){ return $this->costo_unitario; }
    public function setCostoUnitario($costo_unitario){ $this->costo_unitario = $costo_unitario; }

    public function getIdProduccion(){ return $this->id_produccion; }
    public function setIdProduccion($id_produccion){ $this->id_produccion = $id_produccion; }


    public function listarPerdidasMateriales(){
        $sql = "SELECT 
                    pm.id_perdida AS id_perdida_material, 
                    pm.cantidad_perdida, 
                    pm.fecha_de_registro, 
                    pm.motivo, 
                    pm.costo_unitario, 
                    pm.id_produccion,
                    op.fecha_de_inicio,
                    op.fecha_terminado,
                    op.estado_de_produccion,
                    dp.cantidad AS cantidad_detalle,
                    p.descripcion AS descripcion_pedido
                FROM perdida_material pm
                INNER JOIN orden_de_produccion op ON pm.id_produccion = op.id_produccion
                LEFT JOIN detalle_pedido dp ON op.id_detalle_pedido = dp.id_detalle_pedido
                LEFT JOIN pedido p ON dp.id_pedido = p.id_pedido";
        
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOrdenesProduccion() {
        $sql = "SELECT 
                    op.id_produccion, 
                    op.fecha_de_inicio, 
                    op.fecha_terminado, 
                    op.estado_de_produccion,
                    dp.cantidad AS cantidad_detalle,
                    p.descripcion AS descripcion_pedido
                FROM orden_de_produccion op
                LEFT JOIN detalle_pedido dp ON op.id_detalle_pedido = dp.id_detalle_pedido
                LEFT JOIN pedido p ON dp.id_pedido = p.id_pedido";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function registrarPerdidaMaterial(): bool {
        try {
            $sql = "INSERT INTO perdida_material (cantidad_perdida, fecha_de_registro, motivo, costo_unitario, id_produccion) 
                    VALUES (:cantidad_perdida, :fecha_de_registro, :motivo, :costo_unitario, :id_produccion)";
            $stmt = $this->pdo->connect()->prepare($sql);
            $stmt->bindParam(':cantidad_perdida', $this->cantidad_perdida);
            $stmt->bindParam(':fecha_de_registro', $this->fecha_de_registro);
            $stmt->bindParam(':motivo', $this->motivo);
            $stmt->bindParam(':costo_unitario', $this->costo_unitario);
            $stmt->bindParam(':id_produccion', $this->id_produccion);
            return $stmt->execute();
        } catch (Exception $e) {
            // Manejo de errores
            error_log("Error al registrar pérdida de material: " . $e->getMessage());
            return false;
        }
    }

    private function editarPerdidaMaterial($id_perdida): bool {
        try {
            $sql = "UPDATE perdida_material 
                    SET cantidad_perdida = :cantidad_perdida, 
                        fecha_de_registro = :fecha_de_registro, 
                        motivo = :motivo, 
                        costo_unitario = :costo_unitario, 
                        id_produccion = :id_produccion
                    WHERE id_perdida = :id_perdida_material";
            $stmt = $this->pdo->connect()->prepare($sql);
            $stmt->bindParam(':cantidad_perdida', $this->cantidad_perdida);
            $stmt->bindParam(':fecha_de_registro', $this->fecha_de_registro);
            $stmt->bindParam(':motivo', $this->motivo);
            $stmt->bindParam(':costo_unitario', $this->costo_unitario);
            $stmt->bindParam(':id_produccion', $this->id_produccion);
            $stmt->bindParam(':id_perdida_material', $id_perdida);
            return $stmt->execute();
        } catch (Exception $e) {
            // Manejo de errores
            error_log("Error al editar pérdida de material: " . $e->getMessage());
            return false;
        }
    }

    protected function validarPerdidaMaterial(array $datos = [], bool $throwException = false): bool {
        // Validar que la cantidad de pérdida no sea negativa
        if ($this->cantidad_perdida < 0) {
            return false;
        }
        // Validar que el costo unitario no sea negativo
        if ($this->costo_unitario < 0) {
            return false;
        }
        // Validar que la fecha de registro no sea futura
        $fecha_actual = date('Y-m-d');
        if ($this->fecha_de_registro > $fecha_actual) {
            return false;
        }
        return true;
    }

    public function guardarPerdidaMaterial(array $datos, $id_perdida = null): bool {
        $this->setCantidadPerdida($datos['cantidad_perdida'] ?? null);
        $this->setFechaDeRegistro($datos['fecha_de_registro'] ?? null);
        $this->setMotivo($datos['motivo'] ?? null);
        $this->setCostoUnitario($datos['costo_unitario'] ?? null);
        $this->setIdProduccion($datos['id_produccion'] ?? null);

        if (!$this->validarPerdidaMaterial()) {
            return false;
        }

        return $this->registrarPerdidaMaterial();

    }

    public function editarPerdida(array $datos): bool {
        $id_perdida = $datos['id_perdida_material'] ?? null;
        if ($id_perdida === null) {
            throw new \InvalidArgumentException('El id de la pérdida de material es obligatorio para editar.');
        }

        $this->setCantidadPerdida($datos['cantidad_perdida'] ?? null);
        $this->setFechaDeRegistro($datos['fecha_de_registro'] ?? null);
        $this->setMotivo($datos['motivo'] ?? null);
        $this->setCostoUnitario($datos['costo_unitario'] ?? null);
        $this->setIdProduccion($datos['id_produccion'] ?? null);

        return $this->editarPerdidaMaterial($id_perdida);
    }
}
?>
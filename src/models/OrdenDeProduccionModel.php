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

   public function __construct(){
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
    
    // Nueva función para listar los trabajadores en el modal
    public function obtenerEmpleadosActivos() {
        $sql = "SELECT id_empleado, nombres, apellidos, cargo FROM empleado WHERE status_empleado = 'activo'";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerEmpleadosPorOrden($id_produccion) {
        $conn = $this->pdo->connect();
        // Solo traemos el ID del empleado desde la tabla puente
        $sql = "SELECT id_empleado FROM asignacion_produccion WHERE id_produccion = :id_produccion";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_produccion' => $id_produccion]);
        
        // FETCH_COLUMN devuelve un arreglo plano y fácil de leer en JS, ej: [1, 4, 5]
        return $stmt->fetchAll(\PDO::FETCH_COLUMN); 
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
    
    private function registrarOrdenProduccion() {
        $sql = "INSERT INTO orden_de_produccion (fecha_de_inicio, fecha_terminado, estado_de_produccion, id_detalle_pedido) 
                VALUES (:fecha_de_inicio, :fecha_terminado, :estado_de_produccion, :id_detalle_pedido)";
        
        // Es vital guardar la conexión en una variable ($conn) para poder obtener el lastInsertId()
        $conn = $this->pdo->connect(); 
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fecha_de_inicio', $this->fecha_de_inicio);
        $stmt->bindParam(':fecha_terminado', $this->fecha_terminado);
        $stmt->bindParam(':estado_de_produccion', $this->estado_de_produccion);
        $stmt->bindParam(':id_detalle_pedido', $this->id_detalle_pedido);
        
        if ($stmt->execute()) {
            return $conn->lastInsertId(); // Retornamos el ID de la orden recién creada
        }
        return false;
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
        $id_detalle_pedido = $datos['id_detalle_pedido'] ?? null;
        $id_produccion = $datos['id_produccion'] ?? null;

        if (empty($id_detalle_pedido)) {
            throw new Exception('El detalle de pedido es obligatorio.');
        }

        if ($esEdicion && (empty($id_produccion) || !is_numeric($id_produccion))) {
            throw new \InvalidArgumentException('El id de la orden de producción es obligatorio para editar.');
        }

        $sql = "SELECT COUNT(*) FROM orden_de_produccion WHERE id_detalle_pedido = :id_detalle_pedido";

        if ($esEdicion) {
            $sql .= " AND id_produccion != :id_produccion";
        }

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->bindValue(':id_detalle_pedido', $id_detalle_pedido, PDO::PARAM_INT);

        if ($esEdicion) {
            $stmt->bindValue(':id_produccion', (int)$id_produccion, PDO::PARAM_INT);
        }

        $stmt->execute();
        $existe = (int)$stmt->fetchColumn();

        if ($existe > 0) {
            throw new Exception('Ya existe una orden de producción para este detalle de pedido.');
        }
    }

    public function guardarOrden(array $datos): bool {
        $this->validar($datos, false);

        $this->setFechaDeInicio($datos['fecha_de_inicio'] ?? null);
        $this->setFechaTerminado($datos['fecha_terminado'] ?? null);
        $this->setIdDetallePedido($datos['id_detalle_pedido'] ?? null);
        $this->setEstadoDeProduccion($datos['estado_de_produccion'] ?? 'Planificado');

        // Guardamos la orden y capturamos su nuevo ID
        $id_produccion = $this->registrarOrdenProduccion();

        if ($id_produccion) {
            // Si el usuario seleccionó empleados, los guardamos en la tabla puente
            if (!empty($datos['empleados']) && is_array($datos['empleados'])) {
                $conn = $this->pdo->connect();
                $sqlAsig = "INSERT INTO asignacion_produccion (id_produccion, id_empleado) VALUES (:id_produccion, :id_empleado)";
                $stmtAsig = $conn->prepare($sqlAsig);
                
                foreach ($datos['empleados'] as $id_empleado) {
                    $stmtAsig->execute([
                        ':id_produccion' => $id_produccion,
                        ':id_empleado' => $id_empleado
                    ]);
                }
            }
            return true;
        }
        return false;
    }

   public function editarOrden(array $datos): bool {
        $id_produccion = $datos['id_produccion'] ?? null;

        if ($id_produccion === null) {
            throw new \InvalidArgumentException('El id de la orden de producción es obligatorio para editar.');
        }

        // Se mantiene tu validación intacta
        $this->validar($datos, true);
        
        $this->setFechaDeInicio($datos['fecha_de_inicio'] ?? null);
        $this->setFechaTerminado($datos['fecha_terminado'] ?? null);
        $this->setIdDetallePedido($datos['id_detalle_pedido'] ?? null);
        $this->setEstadoDeProduccion($datos['estado_de_produccion'] ?? null);

        // 1. Ejecutamos tu función original para actualizar los datos básicos
        $editado = $this->editarOrdenProduccion($id_produccion);

        // 2. Si la tabla principal se actualizó bien, procedemos con los trabajadores
        if ($editado) {
            $conn = $this->pdo->connect();

            // Limpiamos los trabajadores antiguos de la tabla puente
            $sqlDelete = "DELETE FROM asignacion_produccion WHERE id_produccion = :id_produccion";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->execute([':id_produccion' => $id_produccion]);

            // Insertamos los nuevos trabajadores seleccionados (si hay alguno marcado)
            if (!empty($datos['empleados']) && is_array($datos['empleados'])) {
                $sqlInsert = "INSERT INTO asignacion_produccion (id_produccion, id_empleado) VALUES (:id_produccion, :id_empleado)";
                $stmtInsert = $conn->prepare($sqlInsert);
                
                foreach ($datos['empleados'] as $id_empleado) {
                    $stmtInsert->execute([
                        ':id_produccion' => $id_produccion,
                        ':id_empleado' => $id_empleado
                    ]);
                }
            }
            return true;
        }

        return false;
    }

    public function inactivarOrden(int $id_produccion): bool {
        return $this->inactivarOrdenProduccion($id_produccion);
    }
}
?>
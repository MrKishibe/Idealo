<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;
use InvalidArgumentException;

class ConsumoMaterialModel extends Database {
    private $id_consumo_material;
    private $costo_unitario;
    private $descripcion_de_consumo;
    private $cantidad_usada;
    private $id_materia_prima;
    private $id_produccion;
    private $pdo;

    public function __construct(){
        $this->pdo = new Database();
    }

    public function getIdConsumoMaterial(){ return $this->id_consumo_material; }
    public function setIdConsumoMaterial($id_consumo_material){ $this->id_consumo_material = $id_consumo_material; }

    public function getCostoUnitario(){ return $this->costo_unitario; }
    public function setCostoUnitario($costo_unitario){ $this->costo_unitario = $costo_unitario; }

    public function getDescripcionDeConsumo(){ return $this->descripcion_de_consumo; }
    public function setDescripcionDeConsumo($descripcion_de_consumo){ $this->descripcion_de_consumo = $descripcion_de_consumo; }

    public function getCantidadUsada(){ return $this->cantidad_usada; }
    public function setCantidadUsada($cantidad_usada){ $this->cantidad_usada = $cantidad_usada; }

    public function getIdMateriaPrima(){ return $this->id_materia_prima; }
    public function setIdMateriaPrima($id_materia_prima){ $this->id_materia_prima = $id_materia_prima; }

    public function getIdProduccion(){ return $this->id_produccion; }
    public function setIdProduccion($id_produccion){ $this->id_produccion = $id_produccion; }

    public function listarConsumosMateriales(){
        $sql = "SELECT 
                    cm.id_consumo_material,
                    cm.costo_unitario,
                    cm.descripcion_de_consumo,
                    cm.cantidad_usada,
                    cm.id_materia_prima,
                    cm.id_produccion,
                    mp.nombre_materia_prima,
                    mp.unidad_de_medida,
                    op.estado_de_produccion,
                    dp.cantidad AS cantidad_detalle,
                    p.descripcion AS descripcion_pedido
                FROM consumo_material cm
                INNER JOIN materia_prima mp ON cm.id_materia_prima = mp.id_materia_prima
                INNER JOIN orden_de_produccion op ON cm.id_produccion = op.id_produccion
                LEFT JOIN detalle_pedido dp ON op.id_detalle_pedido = dp.id_detalle_pedido
                LEFT JOIN pedido p ON dp.id_pedido = p.id_pedido
                ORDER BY cm.id_consumo_material DESC";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerConsumoMaterialPorId($id_consumo_material) {
        $sql = "SELECT *
                FROM consumo_material
                WHERE id_consumo_material = :id_consumo_material";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute([':id_consumo_material' => $id_consumo_material]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerOrdenesProduccion() {
        $sql = "SELECT 
                    op.id_produccion,
                    op.estado_de_produccion,
                    dp.cantidad AS cantidad_detalle,
                    p.descripcion AS descripcion_pedido
                FROM orden_de_produccion op
                LEFT JOIN detalle_pedido dp ON op.id_detalle_pedido = dp.id_detalle_pedido
                LEFT JOIN pedido p ON dp.id_pedido = p.id_pedido
                WHERE op.estado_de_produccion != 'Cancelado'";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMateriaPrima() {
        $sql = "SELECT id_materia_prima, nombre_materia_prima, costo_unitario, stock_actual, unidad_de_medida
                FROM materia_prima
                WHERE status_materia_prima = 'Activo' AND stock_actual > 0";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function registrarConsumoMaterial(): bool {
        $sql = "INSERT INTO consumo_material (costo_unitario, descripcion_de_consumo, cantidad_usada, id_materia_prima, id_produccion)
                VALUES (:costo_unitario, :descripcion_de_consumo, :cantidad_usada, :id_materia_prima, :id_produccion)";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->bindValue(':costo_unitario', (float) $this->costo_unitario, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion_de_consumo', $this->descripcion_de_consumo ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_usada', (int) $this->cantidad_usada, PDO::PARAM_INT);
        $stmt->bindValue(':id_materia_prima', (int) $this->id_materia_prima, PDO::PARAM_INT);
        $stmt->bindValue(':id_produccion', (int) $this->id_produccion, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function editarConsumoMaterial($id_consumo_material): bool {
        $sql = "UPDATE consumo_material
                SET costo_unitario = :costo_unitario,
                    descripcion_de_consumo = :descripcion_de_consumo,
                    cantidad_usada = :cantidad_usada,
                    id_materia_prima = :id_materia_prima,
                    id_produccion = :id_produccion
                WHERE id_consumo_material = :id_consumo_material";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->bindValue(':costo_unitario', (float) $this->costo_unitario, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion_de_consumo', $this->descripcion_de_consumo ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_usada', (int) $this->cantidad_usada, PDO::PARAM_INT);
        $stmt->bindValue(':id_materia_prima', (int) $this->id_materia_prima, PDO::PARAM_INT);
        $stmt->bindValue(':id_produccion', (int) $this->id_produccion, PDO::PARAM_INT);
        $stmt->bindValue(':id_consumo_material', (int) $id_consumo_material, PDO::PARAM_INT);

        return $stmt->execute();
    }

    protected function validar(array &$datos, bool $esEdicion = false): void {
        $camposRequeridos = ['costo_unitario', 'cantidad_usada', 'id_materia_prima', 'id_produccion'];

        foreach ($camposRequeridos as $campo) {
            if (!array_key_exists($campo, $datos) || trim((string) $datos[$campo]) === '') {
                throw new InvalidArgumentException('Faltan campos obligatorios para el consumo de material.');
            }
        }

        if ($esEdicion && (!isset($datos['id_consumo_material']) || !is_numeric($datos['id_consumo_material']))) {
            throw new InvalidArgumentException('El id del consumo de material es obligatorio para editar.');
        }

        if (!is_numeric($datos['cantidad_usada']) || (float) $datos['cantidad_usada'] <= 0) {
            throw new InvalidArgumentException('La cantidad usada debe ser un número mayor a 0.');
        }

        if (!is_numeric($datos['costo_unitario']) || (float) $datos['costo_unitario'] < 0) {
            throw new InvalidArgumentException('El costo unitario debe ser mayor o igual a 0.');
        }

        if (!is_numeric($datos['id_materia_prima']) || (int) $datos['id_materia_prima'] <= 0) {
            throw new InvalidArgumentException('Debe seleccionar una materia prima válida.');
        }

        if (!is_numeric($datos['id_produccion']) || (int) $datos['id_produccion'] <= 0) {
            throw new InvalidArgumentException('Debe seleccionar una orden de producción válida.');
        }
    }

    public function guardarConsumoMaterial(array $datos): bool {
        $this->validar($datos, false);

        $this->setCostoUnitario($datos['costo_unitario'] ?? null);
        $this->setDescripcionDeConsumo($datos['descripcion_de_consumo'] ?? null);
        $this->setCantidadUsada($datos['cantidad_usada'] ?? null);
        $this->setIdMateriaPrima($datos['id_materia_prima'] ?? null);
        $this->setIdProduccion($datos['id_produccion'] ?? null);

        $conn = $this->pdo->connect();

        try {
            $conn->beginTransaction();

            $sqlStock = "SELECT stock_actual FROM materia_prima WHERE id_materia_prima = :id_materia_prima FOR UPDATE";
            $stmtStock = $conn->prepare($sqlStock);
            $stmtStock->execute([':id_materia_prima' => (int) $this->id_materia_prima]);
            $stockActual = $stmtStock->fetchColumn();

            if ($stockActual === false) {
                throw new Exception('La materia prima seleccionada no existe.');
            }

            if ((float) $stockActual < (float) $this->cantidad_usada) {
                throw new Exception('No hay suficiente stock para registrar ese consumo.');
            }

            $guardado = $this->registrarConsumoMaterial();
            if (!$guardado) {
                throw new Exception('No se pudo registrar el consumo de material.');
            }

            $sqlUpdate = "UPDATE materia_prima
                          SET stock_actual = stock_actual - :cantidad_usada
                          WHERE id_materia_prima = :id_materia_prima";

            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':cantidad_usada' => (float) $this->cantidad_usada,
                ':id_materia_prima' => (int) $this->id_materia_prima
            ]);

            $conn->commit();
            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Error en consumo de material: ' . $e->getMessage());
            return false;
        }
    }

    public function editarConsumo(array $datos): bool {
        $id_consumo_material = $datos['id_consumo_material'] ?? null;

        if ($id_consumo_material === null || !is_numeric($id_consumo_material)) {
            throw new InvalidArgumentException('El id del consumo de material es obligatorio para editar.');
        }

        $consumoActual = $this->obtenerConsumoMaterialPorId($id_consumo_material);
        if (!$consumoActual) {
            throw new InvalidArgumentException('El consumo de material no existe.');
        }

        $this->validar($datos, true);

        $this->setIdConsumoMaterial($id_consumo_material);
        $this->setCostoUnitario($datos['costo_unitario'] ?? null);
        $this->setDescripcionDeConsumo($datos['descripcion_de_consumo'] ?? null);
        $this->setCantidadUsada($datos['cantidad_usada'] ?? null);
        $this->setIdMateriaPrima($datos['id_materia_prima'] ?? null);
        $this->setIdProduccion($datos['id_produccion'] ?? null);

        $conn = $this->pdo->connect();

        try {
            $conn->beginTransaction();

            $idMateriaAnterior = (int) ($consumoActual['id_materia_prima'] ?? 0);
            $cantidadAnterior = (float) ($consumoActual['cantidad_usada'] ?? 0);
            $idMateriaNueva = (int) $this->id_materia_prima;
            $cantidadNueva = (float) $this->cantidad_usada;

            if ($idMateriaAnterior > 0) {
                $sqlRestaurar = "UPDATE materia_prima
                                SET stock_actual = stock_actual + :cantidad_anterior
                                WHERE id_materia_prima = :id_materia_prima";

                $stmtRestaurar = $conn->prepare($sqlRestaurar);
                $stmtRestaurar->execute([
                    ':cantidad_anterior' => $cantidadAnterior,
                    ':id_materia_prima' => $idMateriaAnterior
                ]);
            }

            $sqlStock = "SELECT stock_actual FROM materia_prima WHERE id_materia_prima = :id_materia_prima FOR UPDATE";
            $stmtStock = $conn->prepare($sqlStock);
            $stmtStock->execute([':id_materia_prima' => $idMateriaNueva]);
            $stockActual = $stmtStock->fetchColumn();

            if ($stockActual === false) {
                throw new Exception('La materia prima seleccionada no existe.');
            }

            if ((float) $stockActual < $cantidadNueva) {
                throw new Exception('No hay suficiente stock para aplicar el nuevo consumo.');
            }

            $sqlDescontar = "UPDATE materia_prima
                             SET stock_actual = stock_actual - :cantidad_nueva
                             WHERE id_materia_prima = :id_materia_prima";

            $stmtDescontar = $conn->prepare($sqlDescontar);
            $stmtDescontar->execute([
                ':cantidad_nueva' => $cantidadNueva,
                ':id_materia_prima' => $idMateriaNueva
            ]);

            $actualizado = $this->editarConsumoMaterial($id_consumo_material);
            if (!$actualizado) {
                throw new Exception('No se pudo actualizar el consumo de material.');
            }

            $conn->commit();
            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Error al editar consumo de material: ' . $e->getMessage());
            return false;
        }
    }
}
?>
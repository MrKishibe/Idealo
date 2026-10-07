<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use InvalidArgumentException;
use PDO;

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
                    pm.id_consumo_material,
                    cm.cantidad_usada,
                    cm.descripcion_de_consumo,
                    cm.id_produccion,
                    mp.id_materia_prima,
                    mp.nombre_materia_prima,
                    mp.unidad_de_medida,
                    op.fecha_de_inicio,
                    op.fecha_terminado,
                    op.estado_de_produccion,
                    dp.cantidad AS cantidad_detalle,
                    p.descripcion AS descripcion_pedido
                FROM perdida_material pm
                INNER JOIN consumo_material cm ON pm.id_consumo_material = cm.id_consumo_material
                INNER JOIN materia_prima mp ON cm.id_materia_prima = mp.id_materia_prima
                INNER JOIN orden_de_produccion op ON cm.id_produccion = op.id_produccion
                LEFT JOIN detalle_pedido dp ON op.id_detalle_pedido = dp.id_detalle_pedido
                LEFT JOIN pedido p ON dp.id_pedido = p.id_pedido
                ORDER BY pm.id_perdida DESC";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerConsumosMaterial(){
        $sql = "SELECT
                    cm.id_consumo_material,
                    cm.costo_unitario,
                    cm.descripcion_de_consumo,
                    cm.cantidad_usada,
                    cm.id_materia_prima,
                    cm.id_produccion,
                    mp.nombre_materia_prima,
                    mp.unidad_de_medida,
                    op.fecha_de_inicio,
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

    private function validarDatos(array $datos): array {
        $cantidad = $datos['cantidad_perdida'] ?? null;
        $fecha = $datos['fecha_de_registro'] ?? null;
        $idProduccion = $datos['id_produccion'] ?? null;
        $idMateriaPrima = $datos['id_materia_prima'] ?? null;

        if (!is_numeric($cantidad) || (float) $cantidad <= 0 ||
            (float) $cantidad > PHP_INT_MAX || floor((float) $cantidad) !== (float) $cantidad) {
            throw new InvalidArgumentException('La cantidad perdida debe ser un número entero mayor que 0.');
        }

        if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new InvalidArgumentException('Debe indicar una fecha de registro válida.');
        }

        $fechaValidada = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($fechaValidada === false || $fechaValidada->format('Y-m-d') !== $fecha || $fecha > date('Y-m-d')) {
            throw new InvalidArgumentException('La fecha de registro no puede ser futura y debe ser válida.');
        }

        foreach ([
            'orden de producción' => $idProduccion,
            'materia prima' => $idMateriaPrima
        ] as $campo => $valor) {
            if ((!is_string($valor) && !is_int($valor)) ||
                !ctype_digit((string) $valor) || (int) $valor <= 0) {
                throw new InvalidArgumentException('Debe seleccionar una ' . $campo . ' válida.');
            }
        }

        $motivoEntrada = $datos['motivo'] ?? null;
        if (!is_string($motivoEntrada)) {
            throw new InvalidArgumentException('Debe indicar un motivo válido para la pérdida.');
        }

        $motivo = trim($motivoEntrada);
        if ($motivo === '') {
            throw new InvalidArgumentException('Debe indicar el motivo de la pérdida.');
        }

        return [
            'cantidad_perdida' => (int) $cantidad,
            'fecha_de_registro' => $fecha,
            'motivo' => $motivo,
            'id_produccion' => (int) $idProduccion,
            'id_materia_prima' => (int) $idMateriaPrima
        ];
    }

    private function obtenerConsumoSeleccionado(array $datosValidados) {
        $sql = "SELECT id_consumo_material, costo_unitario
                FROM consumo_material
                WHERE id_produccion = :id_produccion
                    AND id_materia_prima = :id_materia_prima
                ORDER BY id_consumo_material DESC
                LIMIT 1";
        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute([
            ':id_produccion' => $datosValidados['id_produccion'],
            ':id_materia_prima' => $datosValidados['id_materia_prima']
        ]);
        $consumo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($consumo === false) {
            throw new InvalidArgumentException(
                'No existe un consumo registrado para la materia prima y la orden seleccionadas.'
            );
        }

        return $consumo;
    }

    public function guardarPerdidaMaterial(array $datos): bool {
        $datosValidados = $this->validarDatos($datos);
        $consumo = $this->obtenerConsumoSeleccionado($datosValidados);
        $sql = "INSERT INTO perdida_material
                    (cantidad_perdida, fecha_de_registro, motivo, costo_unitario, id_consumo_material)
                VALUES
                    (:cantidad_perdida, :fecha_de_registro, :motivo, :costo_unitario, :id_consumo_material)";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute([
            ':cantidad_perdida' => $datosValidados['cantidad_perdida'],
            ':fecha_de_registro' => $datosValidados['fecha_de_registro'],
            ':motivo' => $datosValidados['motivo'],
            ':costo_unitario' => $consumo['costo_unitario'],
            ':id_consumo_material' => $consumo['id_consumo_material']
        ]);

        return true;
    }

    public function editarPerdida(array $datos): bool {
        $idPerdida = $datos['id_perdida_material'] ?? null;
        if ((!is_string($idPerdida) && !is_int($idPerdida)) ||
            !ctype_digit((string) $idPerdida) || (int) $idPerdida <= 0) {
            throw new InvalidArgumentException('El id de la pérdida de material es obligatorio para editar.');
        }

        $datosValidados = $this->validarDatos($datos);
        $consumo = $this->obtenerConsumoSeleccionado($datosValidados);
        $sql = "UPDATE perdida_material
                SET cantidad_perdida = :cantidad_perdida,
                    fecha_de_registro = :fecha_de_registro,
                    motivo = :motivo,
                    costo_unitario = :costo_unitario,
                    id_consumo_material = :id_consumo_material
                WHERE id_perdida = :id_perdida";

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute([
            ':cantidad_perdida' => $datosValidados['cantidad_perdida'],
            ':fecha_de_registro' => $datosValidados['fecha_de_registro'],
            ':motivo' => $datosValidados['motivo'],
            ':costo_unitario' => $consumo['costo_unitario'],
            ':id_consumo_material' => $consumo['id_consumo_material'],
            ':id_perdida' => (int) $idPerdida
        ]);

        if ($stmt->rowCount() === 0) {
            $consultaPerdida = $this->pdo->connect()->prepare(
                "SELECT id_perdida FROM perdida_material WHERE id_perdida = :id_perdida"
            );
            $consultaPerdida->execute([':id_perdida' => (int) $idPerdida]);
            if ($consultaPerdida->fetchColumn() === false) {
                throw new InvalidArgumentException('La pérdida de material que intenta editar no existe.');
            }

        }

        return true;
    }
}
?>

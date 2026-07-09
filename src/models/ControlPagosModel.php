<?php
namespace Idealo\Models;
use Idealo\Config\Database;
use PDO;
use Exception;

class ControlPagosModel extends Database {
    private $pdo;
    public function __construct() { $this->pdo = new Database(); }

    public function listarPagos() {
        $sql = "SELECT cp.id_pago, cp.monto_abonado AS monto_pago, cp.referencia, cp.fecha_pago, cp.status_pago AS estado, cp.id_pedido, cp.id_metodo_de_pago, mp.nombre_metodo_de_pago AS nombre_metodo FROM pago cp LEFT JOIN metodo_de_pago mp ON cp.id_metodo_de_pago = mp.id_metodo_de_pago ORDER BY cp.id_pago DESC";
        $stmt = $this->pdo->connect()->prepare($sql); $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerMetodosPago() {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo FROM metodo_de_pago WHERE status_metodo_de_pago = 'activo'";
        $stmt = $this->pdo->connect()->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerPedidosActivos() {
        $sql = "SELECT id_pedido FROM pedido";
        $stmt = $this->pdo->connect()->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    protected function validar(&$datos, $esEdicion = false) {
        if ($esEdicion && empty($datos['id_pago'])) throw new Exception("El ID del pago es obligatorio.");
        if (empty($datos['id_pedido'])) throw new Exception("El número de pedido es obligatorio.");
        if (empty($datos['monto_pago'])) throw new Exception("El monto debe ser válido.");
    }
    public function guardarPago($datos) {
        $this->validar($datos, false);
        $sql = "INSERT INTO pago (id_pedido, monto_abonado, id_metodo_de_pago, referencia, fecha_pago, status_pago) VALUES (:id_pedido, :monto_abonado, :id_metodo_de_pago, :referencia, :fecha_pago, 'procesado')";
        return $this->pdo->connect()->prepare($sql)->execute([':id_pedido' => $datos['id_pedido'], ':monto_abonado' => $datos['monto_pago'], ':id_metodo_de_pago' => $datos['id_metodo_de_pago'], ':referencia' => $datos['referencia'] ?? null, ':fecha_pago' => $datos['fecha_pago']]);
    }
    public function editarPago($datos) {
        $this->validar($datos, true);
        $sql = "UPDATE pago SET id_pedido = :id_pedido, monto_abonado = :monto_abonado, id_metodo_de_pago = :id_metodo_de_pago, referencia = :referencia, fecha_pago = :fecha_pago WHERE id_pago = :id_pago";
        return $this->pdo->connect()->prepare($sql)->execute([':id_pedido' => $datos['id_pedido'], ':monto_abonado' => $datos['monto_pago'], ':id_metodo_de_pago' => $datos['id_metodo_de_pago'], ':referencia' => $datos['referencia'] ?? null, ':fecha_pago' => $datos['fecha_pago'], ':id_pago' => $datos['id_pago']]);
    }
    public function inactivarPago($id_pago) {
        return $this->pdo->connect()->prepare("UPDATE pago SET status_pago = 'inhabilitado' WHERE id_pago = :id")->execute([':id' => $id_pago]);
    }
    public function habilitarPago($id_pago) {
        return $this->pdo->connect()->prepare("UPDATE pago SET status_pago = 'procesado' WHERE id_pago = :id")->execute([':id' => $id_pago]);
    }
public function actualizarEstado($id, $estado) {
        $sql = "UPDATE pago SET status_pago = :estado WHERE id_pago = :id";
        return $this->pdo->connect()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
}
}
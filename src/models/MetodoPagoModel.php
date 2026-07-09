<?php
namespace Idealo\Models;
use Idealo\Config\Database;
use PDO;
use Exception;

class MetodoPagoModel extends Database {
    private $pdo;
    public function __construct() { $this->pdo = new Database(); }

    public function listarMetodos() {
        $sql = "SELECT * FROM metodo_de_pago ORDER BY id_metodo_de_pago DESC";
        $stmt = $this->pdo->connect()->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function guardarMetodo($datos) {
        if(empty($datos['nombre_metodo_de_pago'])) throw new Exception("Nombre requerido.");
        $sql = "INSERT INTO metodo_de_pago (nombre_metodo_de_pago, status_metodo_de_pago) VALUES (:nombre, 'activo')";
        return $this->pdo->connect()->prepare($sql)->execute([':nombre' => $datos['nombre_metodo_de_pago']]);
    }
    public function editarMetodo($datos) {
        if(empty($datos['id_metodo_de_pago'])) throw new Exception("ID requerido.");
        $sql = "UPDATE metodo_de_pago SET nombre_metodo_de_pago = :nombre WHERE id_metodo_de_pago = :id";
        return $this->pdo->connect()->prepare($sql)->execute([':nombre' => $datos['nombre_metodo_de_pago'], ':id' => $datos['id_metodo_de_pago']]);
    }
    public function inactivarMetodo($id) {
        return $this->pdo->connect()->prepare("UPDATE metodo_de_pago SET status_metodo_de_pago = 'inhabilitado' WHERE id_metodo_de_pago = :id")->execute([':id' => $id]);
    }
    public function habilitarMetodo($id) {
        return $this->pdo->connect()->prepare("UPDATE metodo_de_pago SET status_metodo_de_pago = 'activo' WHERE id_metodo_de_pago = :id")->execute([':id' => $id]);
    }
   public function actualizarEstado($id, $estado) {
        $sql = "UPDATE metodo_de_pago SET status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id";
        return $this->pdo->connect()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
}
}
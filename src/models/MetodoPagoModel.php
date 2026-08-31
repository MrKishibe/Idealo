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
        $stmt = $this->pdo->connect()->prepare($sql); 
        $stmt->execute(); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarMetodo($datos) {
        $nombre = trim($datos['nombre_metodo_de_pago'] ?? '');
        
        if(empty($nombre)) {
            throw new Exception("El nombre del método de pago es requerido.");
        }
        
        // Validación de doble cara (Solo letras, espacios y acentos, sin números ni símbolos)
        if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{3,50}$/u', $nombre)) {
            throw new Exception("El nombre solo debe contener letras (de 3 a 50 caracteres), sin números ni símbolos.");
        }

        $sql = "INSERT INTO metodo_de_pago (nombre_metodo_de_pago, status_metodo_de_pago) VALUES (:nombre, 'activo')";
        return $this->pdo->connect()->prepare($sql)->execute([':nombre' => $nombre]);
    }

    public function editarMetodo($datos) {
        $id = $datos['id_metodo_de_pago'] ?? null;
        $nombre = trim($datos['nombre_metodo_de_pago'] ?? '');

        if(empty($id)) {
            throw new Exception("ID de método requerido.");
        }
        if(empty($nombre)) {
            throw new Exception("El nombre del método de pago es requerido.");
        }

        // Validación de doble cara (Servidor - Solo letras, espacios y acentos, sin números ni símbolos)
        if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{3,50}$/u', $nombre)) {
            throw new Exception("El nombre solo debe contener letras (de 3 a 50 caracteres), sin números ni símbolos.");
        }

        $sql = "UPDATE metodo_de_pago SET nombre_metodo_de_pago = :nombre WHERE id_metodo_de_pago = :id";
        return $this->pdo->connect()->prepare($sql)->execute([':nombre' => $nombre, ':id' => $id]);
    }

    public function actualizarEstado($id, $estado) {
        $sql = "UPDATE metodo_de_pago SET status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id";
        return $this->pdo->connect()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
    }
}
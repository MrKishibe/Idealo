<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;
use Exception;

class MetodoPagoModel extends Database
{
    private ?int $id_metodo_de_pago = null;
    private string $nombre_metodo_de_pago = '';
    private string $status_metodo_de_pago = 'activo';

    public function __construct() {}

    protected function getDb(): PDO
    {
        try {
            return self::connect();
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
    }

    private function mapearAtributos(array $datos): void
    {
        $this->id_metodo_de_pago = isset($datos['id_metodo_de_pago']) ? (int)$datos['id_metodo_de_pago'] : null;
        $this->nombre_metodo_de_pago = trim($datos['nombre_metodo_de_pago'] ?? '');
        $this->status_metodo_de_pago = trim($datos['status_metodo_de_pago'] ?? 'activo');
    }

    private function validar(bool $esEdicion = false): void
    {
        if ($esEdicion && empty($this->id_metodo_de_pago)) {
            throw new Exception("ID de método requerido.");
        }
        
        if (empty($this->nombre_metodo_de_pago)) {
            throw new Exception("El nombre del método de pago es requerido.");
        }
        
        if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{3,50}$/u', $this->nombre_metodo_de_pago)) {
            throw new Exception("El nombre solo debe contener letras (de 3 a 50 caracteres), sin números ni símbolos.");
        }

        if ($esEdicion) {
            $estadosValidos = ['activo', 'inactivo', 'inhabilitado'];
            if (!in_array(strtolower($this->status_metodo_de_pago), $estadosValidos)) {
                throw new Exception("El estado del método de pago no es válido.");
            }
        }
    }

    public function listarMetodos(): array
    {
        return $this->dbListarMetodos();
    }

    public function guardarMetodo(array $datos): bool
    {
        try {
            $this->mapearAtributos($datos);
            $this->validar(false);
            return $this->dbGuardarMetodo();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function editarMetodo(array $datos): bool
    {
        try {
            $this->mapearAtributos($datos);
            $this->validar(true);
            return $this->dbEditarMetodo();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function actualizarEstado(int $id, string $estado): bool
    {
        return $this->dbActualizarEstado($id, $estado);
    }

    private function dbListarMetodos(): array
    {
        try {
            $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago, status_metodo_de_pago AS estado FROM metodo_de_pago ORDER BY id_metodo_de_pago DESC";
            $stmt = $this->getDb()->prepare($sql); 
            $stmt->execute(); 
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al listar métodos: " . $e->getMessage());
        }
    }

    private function dbGuardarMetodo(): bool
    {
        try {
            $sql = "INSERT INTO metodo_de_pago (nombre_metodo_de_pago, status_metodo_de_pago) VALUES (:nombre, 'activo')";
            return $this->getDb()->prepare($sql)->execute([':nombre' => $this->nombre_metodo_de_pago]);
        } catch (PDOException $e) {
            throw new Exception("Error al guardar método: " . $e->getMessage());
        }
    }

    private function dbEditarMetodo(): bool
    {
        try {
            $sql = "UPDATE metodo_de_pago SET nombre_metodo_de_pago = :nombre, status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id";
            return $this->getDb()->prepare($sql)->execute([
                ':nombre' => $this->nombre_metodo_de_pago, 
                ':estado' => $this->status_metodo_de_pago, 
                ':id'     => $this->id_metodo_de_pago
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error al editar método: " . $e->getMessage());
        }
    }

    private function dbActualizarEstado(int $id, string $estado): bool
    {
        try {
            $sql = "UPDATE metodo_de_pago SET status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id";
            return $this->getDb()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar estado: " . $e->getMessage());
        }
    }
}
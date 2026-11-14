<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class MetodoPagoModel extends Database
{
    // Atributos internos que representan el estado del método de pago
    private ?int $id_metodo_de_pago = null;
    private string $nombre_metodo_de_pago = '';
    private string $status_metodo_de_pago = 'activo';

    public function __construct()
    {
        // Hereda conexión de Database
    }

    protected function getDb(): PDO
    {
        return self::connect();
    }

    /**
     * Mapea un array asociativo de datos hacia los atributos privados de la clase
     */
    private function mapearAtributos(array $datos): void
    {
        $this->id_metodo_de_pago = isset($datos['id_metodo_de_pago']) ? (int)$datos['id_metodo_de_pago'] : null;
        $this->nombre_metodo_de_pago = trim($datos['nombre_metodo_de_pago'] ?? '');
        $this->status_metodo_de_pago = trim($datos['status_metodo_de_pago'] ?? 'activo');
    }

    /**
     * Valida los datos cargados actualmente en los atributos del objeto
     */
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

    // =========================================================================
    // MÉTODOS PÚBLICOS (FACHADA / ENTRADA DE FLUJO)
    // =========================================================================

    public function listarMetodos(): array
    {
        return $this->dbListarMetodos();
    }

    public function guardarMetodo(array $datos): bool
    {
        $this->mapearAtributos($datos);
        $this->validar(false);
        return $this->dbGuardarMetodo();
    }

    public function editarMetodo(array $datos): bool
    {
        $this->mapearAtributos($datos);
        $this->validar(true);
        return $this->dbEditarMetodo();
    }

    public function actualizarEstado(int $id, string $estado): bool
    {
        return $this->dbActualizarEstado($id, $estado);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS DE PERSISTENCIA (SÓLO ESTOS TOCAN LA BASE DE DATOS)
    // =========================================================================

    private function dbListarMetodos(): array
    {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago, status_metodo_de_pago AS estado FROM metodo_de_pago ORDER BY id_metodo_de_pago DESC";
        $stmt = $this->getDb()->prepare($sql); 
        $stmt->execute(); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function dbGuardarMetodo(): bool
    {
        $sql = "INSERT INTO metodo_de_pago (nombre_metodo_de_pago, status_metodo_de_pago) VALUES (:nombre, 'activo')";
        return $this->getDb()->prepare($sql)->execute([':nombre' => $this->nombre_metodo_de_pago]);
    }

    private function dbEditarMetodo(): bool
    {
        $sql = "UPDATE metodo_de_pago SET nombre_metodo_de_pago = :nombre, status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id";
        return $this->getDb()->prepare($sql)->execute([
            ':nombre' => $this->nombre_metodo_de_pago, 
            ':estado' => $this->status_metodo_de_pago, 
            ':id'     => $this->id_metodo_de_pago
        ]);
    }

    private function dbActualizarEstado(int $id, string $estado): bool
    {
        $sql = "UPDATE metodo_de_pago SET status_metodo_de_pago = :estado WHERE id_metodo_de_pago = :id";
        return $this->getDb()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
    }
}
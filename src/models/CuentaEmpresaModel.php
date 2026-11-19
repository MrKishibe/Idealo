<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;
use Exception;

class CuentaEmpresaModel extends Database
{
    private const REGEX_TITULAR = '/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s\.\-]{3,60}$/';
    private const REGEX_IDENTIFICADOR = '/^[0-9]{20}$/';

    private ?int $id_cuenta = null;
    private ?int $id_metodo_de_pago = null;
    private string $tipo_cuenta = '';
    private string $titular = '';
    private string $identificador = '';
    private string $status_cuenta_empresa = 'activo';

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
        $this->id_cuenta = isset($datos['id_cuenta']) ? (int)$datos['id_cuenta'] : null;
        $this->id_metodo_de_pago = isset($datos['id_metodo_de_pago']) ? (int)$datos['id_metodo_de_pago'] : null;
        $this->tipo_cuenta = trim($datos['tipo_cuenta'] ?? '');
        $this->titular = trim($datos['titular'] ?? '');
        $this->identificador = trim($datos['identificador'] ?? '');
        $this->status_cuenta_empresa = trim($datos['status_cuenta_empresa'] ?? 'activo');
    }

    private function validar(bool $esEdicion = false): void
    {
        if ($esEdicion && empty($this->id_cuenta)) {
            throw new Exception("El ID de cuenta es obligatorio para editar.");
        }
        
        if (!preg_match(self::REGEX_TITULAR, $this->titular)) {
            throw new Exception("El nombre del titular contiene caracteres inválidos o no cumple la longitud esperada.");
        }

        if (!preg_match(self::REGEX_IDENTIFICADOR, $this->identificador)) {
            throw new Exception("El número de cuenta debe tener exactamente 20 dígitos numéricos.");
        }

        $id_excluir = $esEdicion ? $this->id_cuenta : null;
        if ($this->dbExisteCuenta($this->identificador, $id_excluir)) {
            throw new Exception("Ya existe una cuenta registrada con este número.");
        }

        if ($esEdicion) {
            $estadosValidos = ['activo', 'inhabilitado', 'inactivo'];
            if (!in_array(strtolower($this->status_cuenta_empresa), $estadosValidos)) {
                throw new Exception("El estatus de la cuenta proporcionado no es válido.");
            }
        }
    }

    public function listarCuentas(): array
    {
        return $this->dbListarCuentas();
    }
    
    public function obtenerMetodosPago(): array
    {
        return $this->dbObtenerMetodosPago();
    }
    
    public function guardarCuenta(array $datos): bool
    {
        try {
            $this->mapearAtributos($datos);
            $this->validar(false);
            return $this->dbGuardarCuenta();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function editarCuenta(array $datos): bool
    {
        try {
            $this->mapearAtributos($datos);
            $this->validar(true);
            return $this->dbEditarCuenta();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function actualizarEstado(int $id, string $estado): bool
    {
        return $this->dbActualizarEstado($id, $estado);
    }

    public function existeCuenta(string $identificador, ?int $id_excluir = null): bool
    {
        return $this->dbExisteCuenta($identificador, $id_excluir);
    }

    private function dbListarCuentas(): array
    {
        try {
            $sql = "SELECT c.id_cuenta, c.id_metodo_de_pago, c.tipo_cuenta, c.titular, c.identificador, c.status_cuenta_empresa AS estado, mp.nombre_metodo_de_pago AS nombre_metodo 
                    FROM cuenta_empresa c 
                    LEFT JOIN metodo_de_pago mp ON c.id_metodo_de_pago = mp.id_metodo_de_pago 
                    ORDER BY c.id_cuenta DESC";
            $stmt = $this->getDb()->prepare($sql); 
            $stmt->execute(); 
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al listar cuentas: " . $e->getMessage());
        }
    }
    
    private function dbObtenerMetodosPago(): array
    {
        try {
            $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo 
                    FROM metodo_de_pago WHERE status_metodo_de_pago = 'activo'";
            $stmt = $this->getDb()->prepare($sql); 
            $stmt->execute(); 
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener métodos: " . $e->getMessage());
        }
    }
    
    private function dbGuardarCuenta(): bool
    {
        try {
            $sql = "INSERT INTO cuenta_empresa (id_metodo_de_pago, tipo_cuenta, titular, identificador, status_cuenta_empresa) 
                    VALUES (:metodo, :tipo, :titular, :identificador, 'activo')";
            return $this->getDb()->prepare($sql)->execute([
                ':metodo'        => $this->id_metodo_de_pago, 
                ':tipo'          => $this->tipo_cuenta, 
                ':titular'       => $this->titular, 
                ':identificador' => $this->identificador
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error al guardar cuenta: " . $e->getMessage());
        }
    }
    
    private function dbEditarCuenta(): bool
    {
        try {
            $sql = "UPDATE cuenta_empresa 
                    SET id_metodo_de_pago = :metodo, tipo_cuenta = :tipo, titular = :titular, identificador = :identificador, status_cuenta_empresa = :status 
                    WHERE id_cuenta = :id";
            return $this->getDb()->prepare($sql)->execute([
                ':metodo'        => $this->id_metodo_de_pago, 
                ':tipo'          => $this->tipo_cuenta, 
                ':titular'       => $this->titular, 
                ':identificador' => $this->identificador,
                ':status'        => $this->status_cuenta_empresa,
                ':id'            => $this->id_cuenta
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error al editar cuenta: " . $e->getMessage());
        }
    }
    
    private function dbActualizarEstado(int $id, string $estado): bool
    {
        try {
            $sql = "UPDATE cuenta_empresa SET status_cuenta_empresa = :estado WHERE id_cuenta = :id";
            return $this->getDb()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar estado: " . $e->getMessage());
        }
    }

    private function dbExisteCuenta(string $identificador, ?int $id_excluir = null): bool
    {
        try {
            $sql = "SELECT id_cuenta FROM cuenta_empresa WHERE identificador = :identificador";
            $params = [':identificador' => $identificador];
            
            if ($id_excluir) {
                $sql .= " AND id_cuenta != :id";
                $params[':id'] = $id_excluir;
            }
            
            $stmt = $this->getDb()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Error al verificar existencia de cuenta: " . $e->getMessage());
        }
    }
}
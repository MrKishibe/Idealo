<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class ClienteModel extends Database
{
    // Expresiones regulares de validación
    private const REGEX_DOC = '/^[VvEeJjGgCc0-9][- ]?[0-9]{7,10}$/';
    private const REGEX_TEXTO = '/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s\.]{3,50}$/';
    private const REGEX_CORREO = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    private const REGEX_TELEFONO = '/^(0414|0424|0412|0416|02)[0-9]{7}$/';

    // Atributos internos que representan el estado del Cliente
    private ?int $id_cliente = null;
    private string $tipo_de_documento = '';
    private string $numero_de_documento = '';
    private string $nombre_razon_social = '';
    private string $apellido = '';
    private string $direccion = '';
    private string $correo = '';
    private string $telefono = '';
    private string $status_cliente = 'activo';

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
        $this->id_cliente = isset($datos['id_cliente']) ? (int)$datos['id_cliente'] : null;
        $this->tipo_de_documento = trim($datos['tipo_de_documento'] ?? '');
        $this->numero_de_documento = trim($datos['numero_de_documento'] ?? '');
        $this->nombre_razon_social = trim($datos['nombre_razon_social'] ?? '');
        $this->apellido = trim($datos['apellido'] ?? '');
        $this->direccion = trim($datos['direccion'] ?? '');
        $this->correo = trim($datos['correo'] ?? '');
        $this->telefono = trim($datos['telefono'] ?? '');
        $this->status_cliente = trim($datos['status_cliente'] ?? 'activo');
    }

    /**
     * Valida los datos cargados actualmente en los atributos del objeto
     */
    private function validar(bool $esEdicion = false): void
    {
        if ($esEdicion && empty($this->id_cliente)) {
            throw new Exception("❌ [Validación] El ID del cliente es obligatorio para editar.");
        }

        $tiposValidos = ['natural', 'extranjero', 'juridico', 'jurídico'];
        if (!in_array(strtolower($this->tipo_de_documento), $tiposValidos)) {
            $tipoDocumentoDesc = empty($this->tipo_de_documento) ? '(vacío)' : $this->tipo_de_documento;
            throw new Exception("❌ [Validación] El tipo de documento '{$tipoDocumentoDesc}' no es válido.");
        }

        if (empty($this->numero_de_documento)) {
            throw new Exception("❌ [Validación] El número de documento es obligatorio.");
        }
        if (!preg_match(self::REGEX_DOC, $this->numero_de_documento)) {
            throw new Exception("❌ [Validación] El número de documento '{$this->numero_de_documento}' no tiene un formato válido.");
        }

        // Validación de duplicados llamando al método privado de persistencia
        $clienteExistente = $this->dbExisteDocumento($this->numero_de_documento);
        if ($clienteExistente) {
            $idExistente = $clienteExistente['id_cliente'] ?? null;
            if (!$esEdicion) {
                throw new Exception("❌ [Validación] El número de documento '{$this->numero_de_documento}' ya se encuentra registrado.");
            } else {
                if ($idExistente !== null && intval($idExistente) !== $this->id_cliente) {
                    throw new Exception("❌ [Validación] La cédula '{$this->numero_de_documento}' ya pertenece a otro cliente.");
                }
            }
        }

        if (empty($this->nombre_razon_social)) {
            throw new Exception("❌ [Validación] El nombre o razón social es obligatorio.");
        }
        if (!preg_match(self::REGEX_TEXTO, $this->nombre_razon_social)) {
            throw new Exception("❌ [Validación] El nombre '{$this->nombre_razon_social}' es inválido.");
        }

        $tipoDocUnificado = strtolower($this->tipo_de_documento);
        if ($tipoDocUnificado !== 'juridico' && $tipoDocUnificado !== 'jurídico') {
            if (empty($this->apellido)) {
                throw new Exception("❌ [Validación] El apellido es obligatorio para personas naturales.");
            }
            if (!preg_match(self::REGEX_TEXTO, $this->apellido)) {
                throw new Exception("❌ [Validación] El apellido '{$this->apellido}' es inválido.");
            }
        } else {
            $this->apellido = ''; 
        }

        if (empty($this->correo)) {
            throw new Exception("❌ [Validación] El correo electrónico es obligatorio.");
        }
        if (!preg_match(self::REGEX_CORREO, $this->correo)) {
            throw new Exception("❌ [Validación] El correo '{$this->correo}' tiene un formato incorrecto.");
        }

        if (empty($this->telefono)) {
            throw new Exception("❌ [Validación] El teléfono es obligatorio.");
        }
        
        $telefonoSanitizado = preg_replace('/[^0-9]/', '', $this->telefono);
        if (!preg_match(self::REGEX_TELEFONO, $telefonoSanitizado)) {
            throw new Exception("❌ [Validación] El teléfono no tiene un formato válido de Venezuela.");
        }
        $this->telefono = $telefonoSanitizado;

        if (empty($this->direccion) || strlen($this->direccion) < 5) {
            throw new Exception("❌ [Validación] La dirección es obligatoria (mínimo 5 caracteres).");
        }

        if ($esEdicion) {
            $estadosValidos = ['activo', 'inactivo'];
            if (!in_array(strtolower($this->status_cliente), $estadosValidos)) {
                throw new Exception("❌ [Validación] El estado proporcionado no es válido.");
            }
        }
    }

    // =========================================================================
    // MÉTODOS PÚBLICOS (FACHADA / ENTRADA DE FLUJO)
    // =========================================================================

    public function listarPorEstado(string $estado): array
    {
        return $this->dbListarPorEstado($estado);
    }

    public function guardar(array $datos): bool
    {
        $this->mapearAtributos($datos);
        $this->validar(false);
        return $this->dbGuardar();
    }

    public function editar(array $datos): bool
    {
        $this->mapearAtributos($datos);
        $this->validar(true);
        return $this->dbEditar();
    }

    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        return $this->dbCambiarEstado($id, $nuevoEstado);
    }

    public function existeDocumento(string $numDoc)
    {
        return $this->dbExisteDocumento($numDoc);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS DE PERSISTENCIA (SÓLO ESTOS TOCAN LA BASE DE DATOS)
    // =========================================================================

    private function dbListarPorEstado(string $estado): array
    {
        $sql = "SELECT id_cliente, tipo_de_documento, numero_de_documento, nombre_razon_social, apellido, direccion, correo, telefono, status_cliente
                FROM cliente WHERE status_cliente = :estado ORDER BY id_cliente DESC";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':estado' => $estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function dbGuardar(): bool
    {
        $sql = "INSERT INTO cliente (tipo_de_documento, numero_de_documento, nombre_razon_social, apellido, direccion, correo, telefono, status_cliente)
                VALUES (:tipo_de_documento, :numero_de_documento, :nombre_razon_social, :apellido, :direccion, :correo, :telefono, 'activo')";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':tipo_de_documento'   => $this->tipo_de_documento,
            ':numero_de_documento' => $this->numero_de_documento,
            ':nombre_razon_social' => $this->nombre_razon_social,
            ':apellido'            => $this->apellido,
            ':direccion'           => $this->direccion,
            ':correo'              => $this->correo,
            ':telefono'            => $this->telefono
        ]);
    }

    private function dbEditar(): bool
    {
        $sql = "UPDATE cliente SET tipo_de_documento = :tipo_de_documento, numero_de_documento = :numero_de_documento,
                nombre_razon_social = :nombre_razon_social, apellido = :apellido, direccion = :direccion,
                correo = :correo, telefono = :telefono, status_cliente = :status_cliente WHERE id_cliente = :id_cliente";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':tipo_de_documento'   => $this->tipo_de_documento,
            ':numero_de_documento' => $this->numero_de_documento,
            ':nombre_razon_social' => $this->nombre_razon_social,
            ':apellido'            => $this->apellido,
            ':direccion'           => $this->direccion,
            ':correo'              => $this->correo,
            ':telefono'            => $this->telefono,
            ':status_cliente'      => $this->status_cliente, 
            ':id_cliente'          => $this->id_cliente
        ]);
    }

    private function dbCambiarEstado(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE cliente SET status_cliente = :estado WHERE id_cliente = :id_cliente";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':estado' => $nuevoEstado, ':id_cliente' => $id]);
    }

    private function dbExisteDocumento(string $numDoc)
    {
        $sql = "SELECT id_cliente, numero_de_documento FROM cliente WHERE numero_de_documento = :num_doc LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':num_doc' => trim($numDoc)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
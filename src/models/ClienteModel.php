<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class ClienteModel extends Database
{
    private const REGEX_DOC = '/^[VvEeJjGgCc0-9][- ]?[0-9]{7,10}$/';
    private const REGEX_TEXTO = '/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s\.]{3,50}$/';
    private const REGEX_CORREO = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    private const REGEX_TELEFONO = '/^(0414|0424|0412|0416|02)[0-9]{7}$/';

    public function __construct()
    {
        // Hereda conexión de Database
    }

    protected function getDb(): PDO
    {
        return self::connect();
    }

    private function validar(array &$datos, bool $esEdicion = false): void
    {
        if ($esEdicion && empty($datos['id_cliente'])) {
            throw new Exception("❌ [Validación] El ID del cliente es obligatorio para editar.");
        }

        $tiposValidos = ['natural', 'extranjero', 'juridico', 'jurídico'];
        $tipoDocumento = empty($datos['tipo_de_documento']) ? '(vacío)' : $datos['tipo_de_documento'];
        if (!in_array(strtolower(trim($datos['tipo_de_documento'])), $tiposValidos)) {
            throw new Exception("❌ [Validación] El tipo de documento '{$tipoDocumento}' no es válido.");
        }

        $datos['numero_de_documento'] = trim($datos['numero_de_documento']);
        $numDoc = $datos['numero_de_documento'];
        if (empty($numDoc)) {
            throw new Exception("❌ [Validación] El número de documento es obligatorio.");
        }
        if (!preg_match(self::REGEX_DOC, $numDoc)) {
            throw new Exception("❌ [Validación] El número de documento '{$numDoc}' no tiene un formato válido.");
        }

        $clienteExistente = $this->existeDocumento($datos['numero_de_documento']);
        if ($clienteExistente) {
            $idExistente = $clienteExistente['id_cliente'] ?? null;
            if (!$esEdicion) {
                throw new Exception("❌ [Validación] El número de documento '{$numDoc}' ya se encuentra registrado.");
            } else {
                if ($idExistente !== null && intval($idExistente) !== intval($datos['id_cliente'])) {
                    throw new Exception("❌ [Validación] La cédula '{$numDoc}' ya pertenece a otro cliente.");
                }
            }
        }

        $datos['nombre_razon_social'] = trim($datos['nombre_razon_social']);
        $nombre = $datos['nombre_razon_social'];
        if (empty($nombre)) {
            throw new Exception("❌ [Validación] El nombre o razón social es obligatorio.");
        }
        if (!preg_match(self::REGEX_TEXTO, $nombre)) {
            throw new Exception("❌ [Validación] El nombre '{$nombre}' es inválido.");
        }

        $tipoDocUnificado = strtolower(trim($datos['tipo_de_documento']));
        if ($tipoDocUnificado !== 'juridico' && $tipoDocUnificado !== 'jurídico') {
            $datos['apellido'] = trim($datos['apellido']);
            $apellido = $datos['apellido'];
            if (empty($apellido)) {
                throw new Exception("❌ [Validación] El apellido es obligatorio para personas naturales.");
            }
            if (!preg_match(self::REGEX_TEXTO, $apellido)) {
                throw new Exception("❌ [Validación] El apellido '{$apellido}' es inválido.");
            }
        } else {
            $datos['apellido'] = ''; 
        }

        $datos['correo'] = trim($datos['correo']);
        $correo = $datos['correo'];
        if (empty($correo)) {
            throw new Exception("❌ [Validación] El correo electrónico es obligatorio.");
        }
        if (!preg_match(self::REGEX_CORREO, $correo)) {
            throw new Exception("❌ [Validación] El correo '{$correo}' tiene un formato incorrecto.");
        }

        if (empty($datos['telefono'])) {
            throw new Exception("❌ [Validación] El teléfono es obligatorio.");
        }
        
        $telefonoSanitizado = preg_replace('/[^0-9]/', '', $datos['telefono']);
        if (!preg_match(self::REGEX_TELEFONO, $telefonoSanitizado)) {
            throw new Exception("❌ [Validación] El teléfono no tiene un formato válido de Venezuela.");
        }
        $datos['telefono'] = $telefonoSanitizado;

        $datos['direccion'] = trim($datos['direccion']);
        if (empty($datos['direccion']) || strlen($datos['direccion']) < 5) {
            throw new Exception("❌ [Validación] La dirección es obligatoria (mínimo 5 caracteres).");
        }

        if ($esEdicion) {
            $estadosValidos = ['activo', 'inactivo'];
            if (!in_array(strtolower($datos['status_cliente']), $estadosValidos)) {
                throw new Exception("❌ [Validación] El estado proporcionado no es válido.");
            }
        }
    }

    public function listarPorEstado(string $estado): array
    {
        $sql = "SELECT id_cliente, tipo_de_documento, numero_de_documento, nombre_razon_social, apellido, direccion, correo, telefono, status_cliente
                FROM cliente WHERE status_cliente = :estado ORDER BY id_cliente DESC";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':estado' => $estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(array $datos): bool
    {
        // Al arrojar Exception desde validar(), el flujo se corta directamente hacia el controlador de forma segura
        $this->validar($datos, false);

        $sql = "INSERT INTO cliente (tipo_de_documento, numero_de_documento, nombre_razon_social, apellido, direccion, correo, telefono, status_cliente)
                VALUES (:tipo_de_documento, :numero_de_documento, :nombre_razon_social, :apellido, :direccion, :correo, :telefono, 'activo')";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':tipo_de_documento'   => $datos['tipo_de_documento'],
            ':numero_de_documento' => $datos['numero_de_documento'],
            ':nombre_razon_social' => $datos['nombre_razon_social'],
            ':apellido'            => $datos['apellido'],
            ':direccion'           => $datos['direccion'],
            ':correo'              => $datos['correo'],
            ':telefono'            => $datos['telefono']
        ]);
    }

    public function editar(array $datos): bool
    {
        $this->validar($datos, true);

        $sql = "UPDATE cliente SET tipo_de_documento = :tipo_de_documento, numero_de_documento = :numero_de_documento,
                nombre_razon_social = :nombre_razon_social, apellido = :apellido, direccion = :direccion,
                correo = :correo, telefono = :telefono, status_cliente = :status_cliente WHERE id_cliente = :id_cliente";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':tipo_de_documento'   => $datos['tipo_de_documento'],
            ':numero_de_documento' => $datos['numero_de_documento'],
            ':nombre_razon_social' => $datos['nombre_razon_social'],
            ':apellido'            => $datos['apellido'],
            ':direccion'           => $datos['direccion'],
            ':correo'              => $datos['correo'],
            ':telefono'            => $datos['telefono'],
            ':status_cliente'      => $datos['status_cliente'], 
            ':id_cliente'          => $datos['id_cliente']
        ]);
    }

    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE cliente SET status_cliente = :estado WHERE id_cliente = :id_cliente";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':estado' => $nuevoEstado, ':id_cliente' => $id]);
    }

    public function existeDocumento(string $numDoc)
    {
        $sql = "SELECT id_cliente, numero_de_documento FROM cliente WHERE numero_de_documento = :num_doc LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':num_doc' => trim($numDoc)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
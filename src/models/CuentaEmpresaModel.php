<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class CuentaEmpresaModel extends Database {
    
    // 1. Capa de Validación Backend:
    // Permite letras, números, puntos y guiones para la empresa
    private const REGEX_TITULAR = '/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s\.\-]{3,60}$/';
    
    // EXIGE EXACTAMENTE 20 NÚMEROS (Sin espacios, sin guiones)
    private const REGEX_IDENTIFICADOR = '/^[0-9]{20}$/';

    public function __construct() { 
        // Vacío, sin parent::__construct()
    }

    // Método encapsulado para obtener la conexión
    protected function getDb(): PDO {
        return self::connect();
    }

    // 2. Validación de Doble Existencia
    public function existeCuenta($identificador, $id_excluir = null) {
        $sql = "SELECT id_cuenta FROM cuenta_empresa WHERE identificador = :identificador";
        $params = [':identificador' => $identificador];
        
        if ($id_excluir) {
            $sql .= " AND id_cuenta != :id";
            $params[':id'] = $id_excluir;
        }
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
    
    protected function validar(array &$datos, bool $esEdicion = false): void {
        if ($esEdicion && empty($datos['id_cuenta'])) {
            throw new Exception("❌ El ID de cuenta es obligatorio para editar.");
        }
        
        if (!preg_match(self::REGEX_TITULAR, $datos['titular'] ?? '')) {
            throw new Exception("❌ El nombre del titular contiene caracteres inválidos o no cumple la longitud esperada.");
        }

        if (!preg_match(self::REGEX_IDENTIFICADOR, $datos['identificador'] ?? '')) {
            throw new Exception("❌ El número de cuenta debe tener exactamente 20 dígitos numéricos.");
        }

        // Bloqueo por doble existencia en DB
        $id_excluir = $esEdicion ? $datos['id_cuenta'] : null;
        if ($this->existeCuenta($datos['identificador'], $id_excluir)) {
            throw new Exception("❌ Ya existe una cuenta registrada con este número.");
        }
    }

    public function listarCuentas() {
        $sql = "SELECT c.id_cuenta, c.tipo_cuenta, c.titular, c.identificador, c.status_cuenta_empresa AS estado_cuenta, mp.nombre_metodo_de_pago AS nombre_metodo 
                FROM cuenta_empresa c 
                LEFT JOIN metodo_de_pago mp ON c.id_metodo_de_pago = mp.id_metodo_de_pago 
                ORDER BY c.id_cuenta DESC";
        $stmt = $this->getDb()->prepare($sql); 
        $stmt->execute(); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerMetodosPago() {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo 
                FROM metodo_de_pago WHERE status_metodo_de_pago = 'activo'";
        $stmt = $this->getDb()->prepare($sql); 
        $stmt->execute(); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function guardarCuenta($datos) {
        $this->validar($datos, false);
        $sql = "INSERT INTO cuenta_empresa (id_metodo_de_pago, tipo_cuenta, titular, identificador, status_cuenta_empresa) 
                VALUES (:metodo, :tipo, :titular, :identificador, 'activo')";
        return $this->getDb()->prepare($sql)->execute([
            ':metodo' => $datos['id_metodo_de_pago'], 
            ':tipo' => $datos['tipo_cuenta'], 
            ':titular' => $datos['titular'], 
            ':identificador' => $datos['identificador']
        ]);
    }
    
    public function editarCuenta($datos) {
        $this->validar($datos, true);
        $sql = "UPDATE cuenta_empresa 
                SET id_metodo_de_pago = :metodo, tipo_cuenta = :tipo, titular = :titular, identificador = :identificador 
                WHERE id_cuenta = :id";
        return $this->getDb()->prepare($sql)->execute([
            ':metodo' => $datos['id_metodo_de_pago'], 
            ':tipo' => $datos['tipo_cuenta'], 
            ':titular' => $datos['titular'], 
            ':identificador' => $datos['identificador'], 
            ':id' => $datos['id_cuenta']
        ]);
    }
    
    public function actualizarEstado($id, $estado) {
        $sql = "UPDATE cuenta_empresa SET status_cuenta_empresa = :estado WHERE id_cuenta = :id";
        return $this->getDb()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
    }
}
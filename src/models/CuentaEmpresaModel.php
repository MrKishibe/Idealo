<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class CuentaEmpresaModel extends Database
{
    public function __construct() {}

    protected function getDb(): PDO
    {
        return self::connect();
    }

    /**
     * Valida de forma estricta los formatos según el método de pago seleccionado
     */
    private function validar(array &$datos, bool $esEdicion = false): void
    {
        if ($esEdicion && empty($datos['id_cuenta'])) {
            throw new Exception("❌ El ID de la cuenta es obligatorio para editar.");
        }

        if (empty($datos['id_metodo_de_pago']) || !filter_var($datos['id_metodo_de_pago'], FILTER_VALIDATE_INT)) {
            throw new Exception("❌ Debe seleccionar un método de pago válido.");
        }

        $datos['tipo_cuenta'] = trim($datos['tipo_cuenta'] ?? '');
        if (empty($datos['tipo_cuenta'])) {
            throw new Exception("❌ El tipo de cuenta (Ahorros, Corriente, Pago Móvil) es obligatorio.");
        }

        $datos['titular'] = trim($datos['titular'] ?? '');
        if (empty($datos['titular'])) {
            throw new Exception("❌ El nombre del titular es obligatorio.");
        }
        
        // Formato del titular (letras, números y caracteres de empresas básicos)
        if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ.,\s-]{3,80}$/', $datos['titular'])) {
            throw new Exception("❌ El nombre del titular contiene caracteres inválidos.");
        }

        // Normalización: Quitamos guiones y espacios del número de cuenta/identificador
        $identificadorLimpio = preg_replace('/[\s-]/', '', $datos['identificador'] ?? '');

        // Consultamos el nombre del método de pago para saber qué regla aplicar
        $sql = "SELECT nombre_metodo_de_pago FROM metodo_de_pago WHERE id_metodo_de_pago = :id";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $datos['id_metodo_de_pago']]);
        $metodo = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombreMetodo = $metodo ? strtolower($metodo['nombre_metodo_de_pago']) : '';
        $tipoCuentaLowe = strtolower($datos['tipo_cuenta']);

        // --- REGLAS DE NEGOCIO ---
        
        // 1. Si es Pago Móvil (Validar 11 dígitos numéricos)
        if (strpos($nombreMetodo, 'movil') !== false || strpos($nombreMetodo, 'móvil') !== false || strpos($tipoCuentaLowe, 'movil') !== false || strpos($tipoCuentaLowe, 'móvil') !== false) {
            if (!preg_match('/^[0-9]{11}$/', $identificadorLimpio)) {
                throw new Exception("❌ Para Pago Móvil, el identificador debe tener exactamente 11 dígitos numéricos (Ej: 04141234567).");
            }
        } 
        // 2. Si es Cuenta de Banco / Transferencias (Validar exactamente 20 números)
        else if (strpos($nombreMetodo, 'banco') !== false || strpos($nombreMetodo, 'transferencia') !== false || strpos($tipoCuentaLowe, 'corriente') !== false || strpos($tipoCuentaLowe, 'ahorro') !== false) {
            if (!preg_match('/^[0-9]{20}$/', $identificadorLimpio)) {
                throw new Exception("❌ El número de cuenta bancaria debe tener exactamente 20 dígitos numéricos (sin guiones).");
            }
        } 
        // 3. Respaldo para otros tipos (Zelle, PayPal, etc.)
        else {
            if (strlen($identificadorLimpio) < 3 || strlen($identificadorLimpio) > 40) {
                throw new Exception("❌ El identificador ingresado debe tener entre 3 y 40 caracteres.");
            }
        }

        // Guardamos el identificador ya filtrado y normalizado
        $datos['identificador'] = $identificadorLimpio;
    }

    public function listarTodas(): array
    {
        $sql = "SELECT c.id_cuenta, c.tipo_cuenta, c.identificador, c.titular, c.status_cuenta_empresa AS estado,
                       c.id_metodo_de_pago, m.nombre_metodo_de_pago AS nombre_metodo
                FROM cuenta_empresa c
                INNER JOIN metodo_de_pago m ON c.id_metodo_de_pago = m.id_metodo_de_pago
                ORDER BY c.id_cuenta DESC";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(array $datos): bool
    {
        $this->validar($datos, false);

        $sql = "INSERT INTO cuenta_empresa (id_metodo_de_pago, tipo_cuenta, identificador, titular, status_cuenta_empresa) 
                VALUES (:id_metodo_de_pago, :tipo_cuenta, :identificador, :titular, 'activo')";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':id_metodo_de_pago' => $datos['id_metodo_de_pago'],
            ':tipo_cuenta'       => $datos['tipo_cuenta'],
            ':identificador'     => $datos['identificador'],
            ':titular'           => $datos['titular']
        ]);
    }

    public function editar(array $datos): bool
    {
        $this->validar($datos, true);

        $sql = "UPDATE cuenta_empresa 
                SET id_metodo_de_pago = :id_metodo_de_pago, tipo_cuenta = :tipo_cuenta, 
                    identificador = :identificador, titular = :titular 
                WHERE id_cuenta = :id_cuenta";
        $stmt = $this->getDb()->prepare($sql);
        
        return $stmt->execute([
            ':id_metodo_de_pago' => $datos['id_metodo_de_pago'],
            ':tipo_cuenta'       => $datos['tipo_cuenta'],
            ':identificador'     => $datos['identificador'],
            ':titular'           => $datos['titular'],
            ':id_cuenta'         => $datos['id_cuenta']
        ]);
    }

    public function cambiarEstado(int $id, int $nuevoEstado): bool
    {
        $statusString = ($nuevoEstado === 1) ? 'activo' : 'inhabilitado';
        $sql = "UPDATE cuenta_empresa SET status_cuenta_empresa = :estado WHERE id_cuenta = :id_cuenta";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':estado'    => $statusString, 
            ':id_cuenta' => $id
        ]);
    }

    public function obtenerMetodosPago(): array
    {
        $sql = "SELECT id_metodo_de_pago, text_metodo_de_pago AS nombre_metodo 
                FROM metodo_de_pago 
                WHERE status_metodo_de_pago = 'activo'";
        // Nota: Asegúrate de si tu tabla usa nombre_metodo_de_pago, 
        // aquí pusimos el alias genérico 'nombre_metodo' para acoplar la vista.
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo FROM metodo_de_pago WHERE status_metodo_de_pago = 'activo'";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
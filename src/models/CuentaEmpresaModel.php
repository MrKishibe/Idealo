<?php
namespace Idealo\Models;
use Idealo\Config\Database;
use PDO;
use Exception;

class CuentaEmpresaModel extends Database {
    private $pdo;
    
    public function __construct() { 
        $this->pdo = new Database(); 
    }

    public function listarCuentas() {
        // CORRECCIÓN: Se cambió 'c.estado_cuenta' por 'c.status_cuenta_empresa AS estado_cuenta'
        $sql = "SELECT c.id_cuenta, c.tipo_cuenta, c.titular, c.identificador, c.status_cuenta_empresa AS estado_cuenta, mp.nombre_metodo_de_pago AS nombre_metodo 
                FROM cuenta_empresa c 
                LEFT JOIN metodo_de_pago mp ON c.id_metodo_de_pago = mp.id_metodo_de_pago 
                ORDER BY c.id_cuenta DESC";
        
        $stmt = $this->pdo->connect()->prepare($sql); 
        $stmt->execute(); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerMetodosPago() {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo 
                FROM metodo_de_pago 
                WHERE status_metodo_de_pago = 'activo'";
        
        $stmt = $this->pdo->connect()->prepare($sql); 
        $stmt->execute(); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    protected function validar(&$datos, $esEdicion = false) {
        if ($esEdicion && empty($datos['id_cuenta'])) throw new Exception("ID de cuenta requerido.");
        if (empty($datos['titular'])) throw new Exception("Titular obligatorio.");
    }
    
    public function guardarCuenta($datos) {
        $this->validar($datos, false);
        // CORRECCIÓN: Se reemplazó 'estado_cuenta' por 'status_cuenta_empresa'
        $sql = "INSERT INTO cuenta_empresa (id_metodo_de_pago, tipo_cuenta, titular, identificador, status_cuenta_empresa) 
                VALUES (:metodo, :tipo, :titular, :identificador, 'activo')";
        
        return $this->pdo->connect()->prepare($sql)->execute([
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
                
        return $this->pdo->connect()->prepare($sql)->execute([
            ':metodo' => $datos['id_metodo_de_pago'], 
            ':tipo' => $datos['tipo_cuenta'], 
            ':titular' => $datos['titular'], 
            ':identificador' => $datos['identificador'], 
            ':id' => $datos['id_cuenta']
        ]);
    }
    
    public function inactivarCuenta($id) {
        // CORRECCIÓN: Se reemplazó 'estado_cuenta' por 'status_cuenta_empresa'
        $sql = "UPDATE cuenta_empresa 
                SET status_cuenta_empresa = 'inhabilitado' 
                WHERE id_cuenta = :id";
                
        return $this->pdo->connect()->prepare($sql)->execute([
            ':id' => $id
        ]);
    }
    public function habilitarCuenta($id) {
        return $this->pdo->connect()->prepare("UPDATE cuenta_empresa SET status_cuenta_empresa = 'activo' WHERE id_cuenta = :id")->execute([':id' => $id]);
    }
   public function actualizarEstado($id, $estado) {
        $sql = "UPDATE cuenta_empresa SET status_cuenta_empresa = :estado WHERE id_cuenta = :id";
        return $this->pdo->connect()->prepare($sql)->execute([':estado' => $estado, ':id' => $id]);
}
}
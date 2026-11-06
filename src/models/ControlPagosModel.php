<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class ControlPagosModel extends Database {

    private $pdo;
    
    public function __construct() { 
        $this->pdo = new Database(); 
    }

    public function listarPagos() {
        $sql = "SELECT cp.id_pago, cp.monto_abonado AS monto_pago, cp.referencia, cp.fecha_pago, 
                       cp.status_pago AS estado, cp.comprobante_de_pago, cp.id_pedido, cp.id_metodo_de_pago, 
                       mp.nombre_metodo_de_pago AS nombre_metodo, 
                       c.nombre_razon_social, c.apellido 
                FROM pago cp 
                LEFT JOIN metodo_de_pago mp ON cp.id_metodo_de_pago = mp.id_metodo_de_pago 
                LEFT JOIN pedido p ON cp.id_pedido = p.id_pedido
                LEFT JOIN cliente c ON p.id_cliente = c.id_cliente
                ORDER BY cp.id_pago DESC";
                
        return $this->ejecutarConsultaLectura($sql);
    }
    
    public function obtenerMetodosPago() {
        $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo 
                FROM metodo_de_pago 
                WHERE status_metodo_de_pago = 'activo'";
        return $this->ejecutarConsultaLectura($sql);
    }
    
    public function obtenerPedidosActivos() {
        $sql = "SELECT p.id_pedido, p.monto_total, c.nombre_razon_social, c.apellido 
                FROM pedido p 
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente";
        return $this->ejecutarConsultaLectura($sql);
    }

    public function guardarPago($datos) {
        $this->validar($datos, false);

        $sql = "INSERT INTO pago (id_pedido, monto_abonado, id_metodo_de_pago, referencia, fecha_pago, comprobante_de_pago, status_pago) 
                VALUES (:id_pedido, :monto_abonado, :id_metodo_de_pago, :referencia, :fecha_pago, :comprobante_de_pago, 'procesado')";
        
        $parametros = [
            ':id_pedido'          => $datos['id_pedido'], 
            ':monto_abonado'      => $datos['monto_pago'], 
            ':id_metodo_de_pago'  => $datos['id_metodo_de_pago'], 
            ':referencia'         => $datos['referencia'], 
            ':fecha_pago'         => $datos['fecha_pago'],
            ':comprobante_de_pago'=> $datos['comprobante_pago'] ?? null
        ];

        return $this->ejecutarEscritura($sql, $parametros);
    }
    
    public function editarPago($datos) {
        $this->validar($datos, true);
        return $this->procesarActualizacion($datos);
    }
    
    public function cambiarEstadoPago($id_pago, $nuevoEstado) {
        $sql = "UPDATE pago SET status_pago = :estado WHERE id_pago = :id";
        return $this->ejecutarEscritura($sql, [':estado' => $nuevoEstado, ':id' => $id_pago]);
    }

    public function actualizarEstado($id, $estado) {
        return $this->cambiarEstadoPago($id, $estado);
    }

    private function validar(&$datos, $esEdicion = false) {
        if ($esEdicion && empty($datos['id_pago'])) {
            throw new Exception("El ID del pago es obligatorio.");
        }
        if (empty($datos['id_pedido'])) {
            throw new Exception("El número de pedido es obligatorio.");
        }
        if (empty($datos['monto_pago']) || !is_numeric($datos['monto_pago']) || $datos['monto_pago'] <= 0) {
            throw new Exception("El monto debe ser válido y mayor a 0.");
        }
        if (empty($datos['referencia']) || !preg_match('/^[0-9]{6}$/', $datos['referencia'])) {
            throw new Exception("La referencia es obligatoria y debe contener exactamente 6 números.");
        }

        $this->validarDuplicadoPedido($datos['id_pedido'], $esEdicion ? $datos['id_pago'] : null);
    }

    private function validarDuplicadoPedido($idPedido, $idPagoActual = null) {
        if ($idPagoActual === null) {
            $sql = "SELECT COUNT(*) FROM pago WHERE id_pedido = :id_pedido AND status_pago != 'inhabilitado'";
            $params = [':id_pedido' => $idPedido];
            $msjError = "Este pedido ya tiene un pago registrado.";
        } else {
            $sql = "SELECT COUNT(*) FROM pago WHERE id_pedido = :id_pedido AND id_pago != :id_pago AND status_pago != 'inhabilitado'";
            $params = [':id_pedido' => $idPedido, ':id_pago' => $idPagoActual];
            $msjError = "El pedido seleccionado ya está asociado a otro pago diferente.";
        }

        $stmt = $this->pdo->connect()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception($msjError);
        }
    }

    private function procesarActualizacion($datos) {
        $tieneComprobante = isset($datos['comprobante_pago']);
        $statusPago = $datos['status_pago'] ?? 'procesado';
        
        $sql = "UPDATE pago SET 
                    id_pedido = :id_pedido, 
                    monto_abonado = :monto_abonado, 
                    id_metodo_de_pago = :id_metodo_de_pago, 
                    referencia = :referencia, 
                    fecha_pago = :fecha_pago,
                    status_pago = :status_pago"
                . ($tieneComprobante ? ", comprobante_de_pago = :comprobante_de_pago " : " ") .
                "WHERE id_pago = :id_pago";

        $parametros = [
            ':id_pedido'        => $datos['id_pedido'], 
            ':monto_abonado'    => $datos['monto_pago'], 
            ':id_metodo_de_pago'=> $datos['id_metodo_de_pago'], 
            ':referencia'       => $datos['referencia'], 
            ':fecha_pago'       => $datos['fecha_pago'], 
            ':status_pago'      => $statusPago,
            ':id_pago'          => $datos['id_pago']
        ];

        if ($tieneComprobante) {
            $parametros[':comprobante_de_pago'] = $datos['comprobante_pago'];
        }

        return $this->ejecutarEscritura($sql, $parametros);
    }

    private function ejecutarConsultaLectura($sql) {
        $stmt = $this->pdo->connect()->prepare($sql); 
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ejecutarEscritura($sql, $parametros = []) {
        return $this->pdo->connect()->prepare($sql)->execute($parametros);
    }
}
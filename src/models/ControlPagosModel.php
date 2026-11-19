<?php
namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;
use Exception;

class ControlPagosModel extends Database
{
    private ?int $id_pago = null;
    private ?int $id_pedido = null;
    private ?float $monto_pago = null;
    private ?int $id_metodo_de_pago = null;
    private string $referencia = '';
    private string $fecha_pago = '';
    private ?string $comprobante_de_pago = null;
    private string $status_pago = 'procesado';

    public function __construct() {}

    protected function getDb(): PDO
    {
        try {
            return self::connect();
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    private function mapearAtributos(array $datos): void
    {
        $this->id_pago = isset($datos['id_pago']) ? (int)$datos['id_pago'] : null;
        $this->id_pedido = isset($datos['id_pedido']) ? (int)$datos['id_pedido'] : null;
        $this->monto_pago = isset($datos['monto_pago']) ? (float)$datos['monto_pago'] : null;
        $this->id_metodo_de_pago = isset($datos['id_metodo_de_pago']) ? (int)$datos['id_metodo_de_pago'] : null;
        $this->referencia = trim($datos['referencia'] ?? '');
        
        $fechaCruda = trim($datos['fecha_pago'] ?? '');
        if (strpos($fechaCruda, 'T') !== false) {
            $fechaCruda = explode('T', $fechaCruda)[0];
        }
        $this->fecha_pago = $fechaCruda;

        if (array_key_exists('comprobante_de_pago', $datos)) {
            $this->comprobante_de_pago = $datos['comprobante_de_pago'];
        }
        
        $this->status_pago = trim($datos['status_pago'] ?? 'procesado');
    }

    private function esMetodoEfectivo(int $idMetodo): bool
    {
        try {
            $sql = "SELECT nombre_metodo_de_pago FROM metodo_de_pago WHERE id_metodo_de_pago = :id";
            $stmt = $this->getDb()->prepare($sql);
            $stmt->execute([':id' => $idMetodo]);
            $metodo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($metodo) {
                $nombre = strtolower(trim($metodo['nombre_metodo_de_pago']));
                return str_contains($nombre, 'efectivo');
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Error al verificar el método de pago: " . $e->getMessage());
        }
    }

    private function validar(bool $esEdicion = false): void
    {
        if ($esEdicion && empty($this->id_pago)) {
            throw new Exception("El ID del pago es obligatorio.");
        }
        if (empty($this->id_pedido)) {
            throw new Exception("El número de pedido es obligatorio.");
        }
        if (empty($this->monto_pago) || !is_numeric($this->monto_pago) || $this->monto_pago <= 0) {
            throw new Exception("El monto debe ser válido y mayor a 0.");
        }
        if (empty($this->id_metodo_de_pago)) {
            throw new Exception("El método de pago es obligatorio.");
        }

        $esEfectivo = $this->esMetodoEfectivo($this->id_metodo_de_pago);
        if ($esEfectivo) {
            if (empty($this->referencia)) {
                $this->referencia = 'EFECTIVO';
            }
        } else {
            if (empty($this->referencia) || !preg_match('/^[0-9]{6}$/', $this->referencia)) {
                throw new Exception("La referencia es obligatoria y debe contener exactamente 6 números.");
            }
        }

        $this->dbValidarSumaPagos($this->id_pedido, $this->monto_pago, $esEdicion ? $this->id_pago : null);

        if ($esEdicion) {
            $estadosValidos = ['procesado', 'inhabilitado'];
            if (!in_array(strtolower($this->status_pago), $estadosValidos)) {
                throw new Exception("El estado del pago proporcionado no es válido.");
            }
        }
    }

    private function dbValidarSumaPagos(int $idPedido, float $montoNuevo, ?int $idPagoActual = null): void
    {
        try {
            $sqlPedido = "SELECT monto_total FROM pedido WHERE id_pedido = :id_pedido";
            $stmt = $this->getDb()->prepare($sqlPedido);
            $stmt->execute([':id_pedido' => $idPedido]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                throw new Exception("El pedido seleccionado no existe.");
            }
            $montoTotalPedido = (float)$pedido['monto_total'];

            if ($idPagoActual === null) {
                $sqlSuma = "SELECT SUM(monto_abonado) as total_abonado FROM pago WHERE id_pedido = :id_pedido AND status_pago != 'inhabilitado'";
                $params = [':id_pedido' => $idPedido];
            } else {
                $sqlSuma = "SELECT SUM(monto_abonado) as total_abonado FROM pago WHERE id_pedido = :id_pedido AND id_pago != :id_pago AND status_pago != 'inhabilitado'";
                $params = [':id_pedido' => $idPedido, ':id_pago' => $idPagoActual];
            }

            $stmtSuma = $this->getDb()->prepare($sqlSuma);
            $stmtSuma->execute($params);
            $resultado = $stmtSuma->fetch(PDO::FETCH_ASSOC);
            $totalAbonadoPrevio = (float)($resultado['total_abonado'] ?? 0);

            $sumaTotal = $totalAbonadoPrevio + $montoNuevo;

            if ($sumaTotal > $montoTotalPedido) {
                $disponible = max(0, $montoTotalPedido - $totalAbonadoPrevio);
                throw new Exception("El monto abonado ($" . number_format($montoNuevo, 2) . ") excede el total del pedido. Saldo disponible para abonar: $" . number_format($disponible, 2));
            }
        } catch (PDOException $e) {
            throw new Exception("Error al validar la suma de pagos: " . $e->getMessage());
        }
    }

    public function listarPagos(): array { return $this->dbListarPagos(); }
    public function obtenerMetodosPago(): array { return $this->dbObtenerMetodosPago(); }
    public function obtenerPedidosActivos(): array { return $this->dbObtenerPedidosActivos(); }

    public function guardarPago(array $datos): bool
    {
        try {
            $this->mapearAtributos($datos);
            $this->validar(false);
            return $this->dbGuardarPago();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function editarPago(array $datos): bool
    {
        try {
            $this->mapearAtributos($datos);
            $this->validar(true);
            return $this->dbEditarPago();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function cambiarEstadoPago(int $id_pago, string $nuevoEstado): bool
    {
        return $this->dbCambiarEstadoPago($id_pago, $nuevoEstado);
    }

    public function actualizarEstado(int $id, string $estado): bool
    {
        return $this->cambiarEstadoPago($id, $estado);
    }

    private function dbListarPagos(): array
    {
        try {
            $sql = "SELECT cp.id_pago, cp.monto_abonado AS monto_pago, cp.referencia, cp.fecha_pago, 
                           cp.status_pago AS estado, cp.comprobante_de_pago, cp.id_pedido, cp.id_metodo_de_pago, 
                           mp.nombre_metodo_de_pago AS nombre_metodo, 
                           c.nombre_razon_social, c.apellido, 
                           p.descripcion AS descripcion_pedido
                    FROM pago cp 
                    LEFT JOIN metodo_de_pago mp ON cp.id_metodo_de_pago = mp.id_metodo_de_pago 
                    LEFT JOIN pedido p ON cp.id_pedido = p.id_pedido 
                    LEFT JOIN cliente c ON p.id_cliente = c.id_cliente
                    ORDER BY cp.id_pago DESC";
            return $this->dbEjecutarConsultaLectura($sql);
        } catch (PDOException $e) {
            throw new Exception("Error al listar los pagos: " . $e->getMessage());
        }
    }

    private function dbObtenerMetodosPago(): array
    {
        try {
            $sql = "SELECT id_metodo_de_pago, nombre_metodo_de_pago AS nombre_metodo 
                    FROM metodo_de_pago 
                    WHERE status_metodo_de_pago = 'activo'";
            return $this->dbEjecutarConsultaLectura($sql);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener métodos de pago: " . $e->getMessage());
        }
    }
    
    private function dbObtenerPedidosActivos(): array
    {
        try {
            $sql = "SELECT p.id_pedido, p.monto_total, p.descripcion AS descripcion_pedido, c.nombre_razon_social, c.apellido 
                    FROM pedido p 
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente";
            return $this->dbEjecutarConsultaLectura($sql);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener pedidos activos: " . $e->getMessage());
        }
    }

    private function dbGuardarPago(): bool
    {
        try {
            $sql = "INSERT INTO pago (id_pedido, monto_abonado, id_metodo_de_pago, referencia, fecha_pago, comprobante_de_pago, status_pago) 
                    VALUES (:id_pedido, :monto_abonado, :id_metodo_de_pago, :referencia, :fecha_pago, :comprobante_de_pago, 'procesado')";
            
            $parametros = [
                ':id_pedido'          => $this->id_pedido, 
                ':monto_abonado'      => $this->monto_pago, 
                ':id_metodo_de_pago'  => $this->id_metodo_de_pago, 
                ':referencia'         => $this->referencia, 
                ':fecha_pago'         => $this->fecha_pago,
                ':comprobante_de_pago'=> $this->comprobante_de_pago
            ];

            return $this->dbEjecutarEscritura($sql, $parametros);
        } catch (PDOException $e) {
            throw new Exception("Error al guardar el pago: " . $e->getMessage());
        }
    }
    
    private function dbEditarPago(): bool
    {
        try {
            $actualizarComprobante = ($this->comprobante_de_pago !== null && $this->comprobante_de_pago !== '');
            
            $sql = "UPDATE pago SET 
                        id_pedido = :id_pedido, 
                        monto_abonado = :monto_abonado, 
                        id_metodo_de_pago = :id_metodo_de_pago, 
                        referencia = :referencia, 
                        fecha_pago = :fecha_pago,
                        status_pago = :status_pago"
                    . ($actualizarComprobante ? ", comprobante_de_pago = :comprobante_de_pago " : " ") .
                    "WHERE id_pago = :id_pago";

            $parametros = [
                ':id_pedido'        => $this->id_pedido, 
                ':monto_abonado'    => $this->monto_pago, 
                ':id_metodo_de_pago'=> $this->id_metodo_de_pago, 
                ':referencia'       => $this->referencia, 
                ':fecha_pago'       => $this->fecha_pago, 
                ':status_pago'      => $this->status_pago,
                ':id_pago'          => $this->id_pago
            ];

            if ($actualizarComprobante) {
                $parametros[':comprobante_de_pago'] = $this->comprobante_de_pago;
            }

            return $this->dbEjecutarEscritura($sql, $parametros);
        } catch (PDOException $e) {
            throw new Exception("Error al editar el pago: " . $e->getMessage());
        }
    }
    
    private function dbCambiarEstadoPago(int $id_pago, string $nuevoEstado): bool
    {
        try {
            $sql = "UPDATE pago SET status_pago = :estado WHERE id_pago = :id";
            return $this->dbEjecutarEscritura($sql, [':estado' => $nuevoEstado, ':id' => $id_pago]);
        } catch (PDOException $e) {
            throw new Exception("Error al cambiar estado del pago: " . $e->getMessage());
        }
    }

    private function dbEjecutarConsultaLectura(string $sql): array
    {
        try {
            $stmt = $this->getDb()->prepare($sql); 
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error en lectura: " . $e->getMessage());
        }
    }

    private function dbEjecutarEscritura(string $sql, array $parametros = []): bool
    {
        try {
            return $this->getDb()->prepare($sql)->execute($parametros);
        } catch (PDOException $e) {
            throw new Exception("Error en escritura: " . $e->getMessage());
        }
    }
}
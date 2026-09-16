<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;
use Exception;
use Throwable;

require_once __DIR__ . '/../../config/Database.php';

class PedidoModel extends Database
{
    
    private PDO $pdo;

    private int $idPedido = 0;

    private string $fechaCreacion = '';

    private ?string $fechaEntrega = null;

    private int $idCliente = 0;

    private int $idTipoPedido = 0;

    private ?string $descripcion = null;

    private float $descuentoDivisa = 0.0;

    private string $estadoPedido = 'pendiente';

    private float $montoTotal = 0.0;

    private int $idProductoCaracteristica = 0;

    private int $idServicio = 0;

    private int $cantidad = 1;

    private float $costoManoObra = 0.0;

    private float $costoMateriales = 0.0;

    private float $descuentoProducto = 0.0;

    private ?string $metodoServicio = null;

    public function __construct()
    {
        $this->pdo = self::connect();
    }

    public function listarPedidos(
        string $filtro = 'activos'
    ): array {
        $sql = "
            SELECT
                p.id_pedido,
                p.fecha_creacion,
                p.fecha_entrega,
                p.descripcion,
                p.descuento_divisa,
                p.estado_pedido,
                p.monto_total,
                p.id_cliente,
                p.id_tipo_pedido,

                c.nombre_razon_social,
                c.apellido,
                c.numero_de_documento,

                tp.nombre_tipo_pedido,

                dp.id_detalle_pedido,
                dp.id_producto_caracteristica,
                dp.id_servicio,
                dp.cantidad,
                dp.costo_mano_de_obra,
                dp.costo_materiales,
                dp.descuento_producto,
                dp.metodo_servicio,

                pc.id_producto,
                pc.talla,

                ca.detalle_material,
                ca.color,
                ca.tipo_de_prenda,

                pr.nombre_producto,
                pr.tipo_de_producto,

                s.nombre_servicio

            FROM pedido p

            INNER JOIN cliente c
                ON c.id_cliente = p.id_cliente

            INNER JOIN tipo_de_pedido tp
                ON tp.id_tipo_pedido = p.id_tipo_pedido

            LEFT JOIN detalle_pedido dp
                ON dp.id_pedido = p.id_pedido

            LEFT JOIN producto_caracteristica pc
                ON pc.id_producto_caracteristica =
                    dp.id_producto_caracteristica

            LEFT JOIN caracteristica ca
                ON ca.id_caracteristica =
                    pc.id_caracteristica

            LEFT JOIN producto pr
                ON pr.id_producto =
                    pc.id_producto

            LEFT JOIN servicio s
                ON s.id_servicio =
                    dp.id_servicio
        ";

        if ($filtro === 'inhabilitados') {
            $sql .= "
                WHERE LOWER(p.estado_pedido) = 'inhabilitado'
            ";
        } else {
            $sql .= "
                WHERE LOWER(p.estado_pedido) <> 'inhabilitado'
            ";
        }

        $sql .= "
            ORDER BY p.id_pedido DESC
        ";

        try {
            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute();

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudieron listar los pedidos: ' .
                $e->getMessage()
            );
        }
    }

    public function obtenerPedido(
        int $idPedido
    ): ?array {
        if ($idPedido <= 0) {
            throw new Exception(
                'El identificador del pedido no es válido.'
            );
        }

        try {
            $sqlPedido = "
                SELECT
                    p.id_pedido,
                    p.fecha_creacion,
                    p.fecha_entrega,
                    p.descripcion,
                    p.descuento_divisa,
                    p.estado_pedido,
                    p.monto_total,
                    p.id_cliente,
                    p.id_tipo_pedido,

                    tp.nombre_tipo_pedido,

                    c.nombre_razon_social,
                    c.apellido,
                    c.numero_de_documento,
                    c.correo,
                    c.telefono,
                    c.direccion

                FROM pedido p

                INNER JOIN cliente c
                    ON c.id_cliente =
                        p.id_cliente

                INNER JOIN tipo_de_pedido tp
                    ON tp.id_tipo_pedido =
                        p.id_tipo_pedido

                WHERE p.id_pedido = :id_pedido

                LIMIT 1
            ";

            $stmtPedido = $this->pdo->prepare(
                $sqlPedido
            );

            $stmtPedido->execute([
                ':id_pedido' => $idPedido
            ]);

            $pedido = $stmtPedido->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$pedido) {
                return null;
            }

            $sqlDetalle = "
                SELECT
                    dp.id_detalle_pedido,
                    dp.id_pedido,
                    dp.id_producto_caracteristica,
                    dp.id_servicio,
                    dp.cantidad,
                    dp.costo_mano_de_obra,
                    dp.costo_materiales,
                    dp.descuento_producto,
                    dp.metodo_servicio,

                    pc.id_producto,
                    pc.talla,

                    ca.detalle_material,
                    ca.color,
                    ca.tipo_de_prenda,

                    pr.nombre_producto,
                    pr.tipo_de_producto,

                    s.nombre_servicio

                FROM detalle_pedido dp

                INNER JOIN producto_caracteristica pc
                    ON pc.id_producto_caracteristica =
                        dp.id_producto_caracteristica

                INNER JOIN caracteristica ca
                    ON ca.id_caracteristica =
                        pc.id_caracteristica

                INNER JOIN producto pr
                    ON pr.id_producto =
                        pc.id_producto

                INNER JOIN servicio s
                    ON s.id_servicio =
                        dp.id_servicio

                WHERE dp.id_pedido = :id_pedido

                LIMIT 1
            ";

            $stmtDetalle = $this->pdo->prepare(
                $sqlDetalle
            );

            $stmtDetalle->execute([
                ':id_pedido' => $idPedido
            ]);

            $detalle = $stmtDetalle->fetch(
                PDO::FETCH_ASSOC
            );

            $pedido['detalle'] =
                $detalle ?: [];

            return $pedido;

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudo obtener el pedido: ' .
                $e->getMessage()
            );
        }
    }

    public function guardarPedido(
        array $datos
    ): int {
        $this->asignarDatosPedido(
            $datos
        );

        $this->validarDatosPedido();

        try {
            $this->pdo->beginTransaction();

            $this->montoTotal =
                $this->calcularMontoTotal();

            $sqlPedido = "
                INSERT INTO pedido
                (
                    fecha_creacion,
                    fecha_entrega,
                    id_cliente,
                    id_tipo_pedido,
                    descripcion,
                    descuento_divisa,
                    estado_pedido,
                    monto_total
                )
                VALUES
                (
                    :fecha_creacion,
                    :fecha_entrega,
                    :id_cliente,
                    :id_tipo_pedido,
                    :descripcion,
                    :descuento_divisa,
                    :estado_pedido,
                    :monto_total
                )
            ";

            $stmtPedido = $this->pdo->prepare(
                $sqlPedido
            );

            $stmtPedido->execute([
                ':fecha_creacion' =>
                    $this->fechaCreacion,

                ':fecha_entrega' =>
                    $this->fechaEntrega,

                ':id_cliente' =>
                    $this->idCliente,

                ':id_tipo_pedido' =>
                    $this->idTipoPedido,

                ':descripcion' =>
                    $this->descripcion,

                ':descuento_divisa' =>
                    $this->descuentoDivisa,

                ':estado_pedido' =>
                    $this->estadoPedido,

                ':monto_total' =>
                    $this->montoTotal
            ]);

            $this->idPedido = (int) (
                $this->pdo->lastInsertId()
            );

            if ($this->idPedido <= 0) {
                throw new Exception(
                    'No se pudo obtener el identificador del pedido.'
                );
            }

            $this->guardarDetallePedido();

            $this->pdo->commit();

            return $this->idPedido;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function editarPedido(
        array $datos
    ): void {
        $this->asignarDatosPedido(
            $datos
        );

        if ($this->idPedido <= 0) {
            throw new Exception(
                'El identificador del pedido no es válido.'
            );
        }

        $this->validarDatosPedido();

        try {
            $this->pdo->beginTransaction();

            $this->montoTotal =
                $this->calcularMontoTotal();

            $sqlPedido = "
                UPDATE pedido
                SET
                    fecha_creacion = :fecha_creacion,
                    fecha_entrega = :fecha_entrega,
                    id_cliente = :id_cliente,
                    id_tipo_pedido = :id_tipo_pedido,
                    descripcion = :descripcion,
                    descuento_divisa = :descuento_divisa,
                    estado_pedido = :estado_pedido,
                    monto_total = :monto_total
                WHERE id_pedido = :id_pedido
            ";

            $stmtPedido = $this->pdo->prepare(
                $sqlPedido
            );

            $stmtPedido->execute([
                ':fecha_creacion' =>
                    $this->fechaCreacion,

                ':fecha_entrega' =>
                    $this->fechaEntrega,

                ':id_cliente' =>
                    $this->idCliente,

                ':id_tipo_pedido' =>
                    $this->idTipoPedido,

                ':descripcion' =>
                    $this->descripcion,

                ':descuento_divisa' =>
                    $this->descuentoDivisa,

                ':estado_pedido' =>
                    $this->estadoPedido,

                ':monto_total' =>
                    $this->montoTotal,

                ':id_pedido' =>
                    $this->idPedido
            ]);

            $sqlEliminarDetalle = "
                DELETE FROM detalle_pedido
                WHERE id_pedido = :id_pedido
            ";

            $stmtEliminarDetalle =
                $this->pdo->prepare(
                    $sqlEliminarDetalle
                );

            $stmtEliminarDetalle->execute([
                ':id_pedido' =>
                    $this->idPedido
            ]);

            $this->guardarDetallePedido();

            $this->pdo->commit();

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function inhabilitarPedido(
        int $idPedido
    ): void {
        if ($idPedido <= 0) {
            throw new Exception(
                'El identificador del pedido no es válido.'
            );
        }

        try {
            $sql = "
                UPDATE pedido
                SET estado_pedido = 'inhabilitado'
                WHERE id_pedido = :id_pedido
            ";

            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute([
                ':id_pedido' => $idPedido
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception(
                    'No se encontró el pedido o ya está anulado.'
                );
            }

        } catch (Throwable $e) {
            throw new Exception(
                'No se pudo anular el pedido: ' .
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function obtenerClientes(): array
    {
        $sql = "
            SELECT
                id_cliente,
                nombre_razon_social,
                apellido,
                numero_de_documento
            FROM cliente
            WHERE LOWER(status_cliente) = 'activo'
            ORDER BY nombre_razon_social ASC
        ";

        try {
            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute();

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudieron obtener los clientes: ' .
                $e->getMessage()
            );
        }
    }

    public function obtenerTiposPedido(): array
    {
        $sql = "
            SELECT
                id_tipo_pedido,
                nombre_tipo_pedido
            FROM tipo_de_pedido
            WHERE LOWER(status_tipo_servicio) = 'activo'
            ORDER BY nombre_tipo_pedido ASC
        ";

        try {
            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute();

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudieron obtener los tipos de pedido: ' .
                $e->getMessage()
            );
        }
    }

    public function obtenerProductosActivos(): array
    {
        $sql = "
            SELECT
                id_producto,
                nombre_producto,
                tipo_de_producto
            FROM producto
            WHERE LOWER(status_producto) = 'activo'
            ORDER BY nombre_producto ASC
        ";

        try {
            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute();

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudieron obtener los productos: ' .
                $e->getMessage()
            );
        }
    }

    public function obtenerServicios(): array
    {
        $sql = "
            SELECT
                id_servicio,
                nombre_servicio
            FROM servicio
            WHERE LOWER(status_servicio) = 'activo'
            ORDER BY nombre_servicio ASC
        ";

        try {
            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute();

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudieron obtener los servicios: ' .
                $e->getMessage()
            );
        }
    }

    public function obtenerCaracteristicasPorProducto(
        int $idProducto
    ): array {
        if ($idProducto <= 0) {
            return [];
        }

        $sql = "
            SELECT
                pc.id_producto_caracteristica,
                pc.id_producto,
                pc.id_caracteristica,
                pc.talla,

                ca.detalle_material,
                ca.color,
                ca.tipo_de_prenda

            FROM producto_caracteristica pc

            INNER JOIN caracteristica ca
                ON ca.id_caracteristica =
                    pc.id_caracteristica

            WHERE pc.id_producto = :id_producto
            AND LOWER(pc.status_producto_caracteristica) = 'activo'
            AND LOWER(ca.status_caracteristica) = 'activo'

            ORDER BY pc.id_producto_caracteristica DESC
        ";

        try {
            $stmt = $this->pdo->prepare(
                $sql
            );

            $stmt->execute([
                ':id_producto' =>
                    $idProducto
            ]);

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            throw new Exception(
                'No se pudieron obtener las características: ' .
                $e->getMessage()
            );
        }
    }

    private function asignarDatosPedido(
        array $datos
    ): void {
        $pedido =
            $datos['pedido'] ?? [];

        $detalle =
            $datos['detalle'] ?? [];

        $this->idPedido = (int) (
            $datos['id_pedido'] ??
            0
        );

        $this->fechaCreacion =
            trim(
                (string) (
                    $pedido['fecha_creacion'] ??
                    ''
                )
            );

        $fechaEntrega =
            trim(
                (string) (
                    $pedido['fecha_entrega'] ??
                    ''
                )
            );

        $this->fechaEntrega =
            $fechaEntrega !== ''
                ? $fechaEntrega
                : null;

        $this->idCliente = (int) (
            $pedido['id_cliente'] ??
            0
        );

        $this->idTipoPedido = (int) (
            $pedido['id_tipo_pedido'] ??
            0
        );

        $descripcion =
            trim(
                (string) (
                    $pedido['descripcion'] ??
                    ''
                )
            );

        $this->descripcion =
            $descripcion !== ''
                ? $descripcion
                : null;

        $this->descuentoDivisa = (float) (
            $pedido['descuento_divisa'] ??
            0
        );

        $estado =
            strtolower(
                trim(
                    (string) (
                        $pedido['estado_pedido'] ??
                        'pendiente'
                    )
                )
            );

        $this->estadoPedido =
            $estado !== ''
                ? $estado
                : 'pendiente';

        $this->idProductoCaracteristica =
            (int) (
                $detalle['id_producto_caracteristica'] ??
                0
            );

        $this->idServicio = (int) (
            $detalle['id_servicio'] ??
            0
        );

        $this->cantidad = (int) (
            $detalle['cantidad'] ??
            0
        );

        $this->costoManoObra = (float) (
            $detalle['costo_mano_de_obra'] ??
            0
        );

        $this->costoMateriales = (float) (
            $detalle['costo_materiales'] ??
            0
        );

        $this->descuentoProducto = (float) (
            $detalle['descuento_producto'] ??
            0
        );

        $metodoServicio =
            trim(
                (string) (
                    $detalle['metodo_servicio'] ??
                    ''
                )
            );

        $this->metodoServicio =
            $metodoServicio !== ''
                ? $metodoServicio
                : null;

        $this->montoTotal =
            $this->calcularMontoTotal();
    }

    private function guardarDetallePedido(): void
    {
        $sql = "
            INSERT INTO detalle_pedido
            (
                id_pedido,
                id_producto_caracteristica,
                id_servicio,
                cantidad,
                costo_mano_de_obra,
                costo_materiales,
                descuento_producto,
                metodo_servicio
            )
            VALUES
            (
                :id_pedido,
                :id_producto_caracteristica,
                :id_servicio,
                :cantidad,
                :costo_mano_de_obra,
                :costo_materiales,
                :descuento_producto,
                :metodo_servicio
            )
        ";

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute([
            ':id_pedido' =>
                $this->idPedido,

            ':id_producto_caracteristica' =>
                $this->idProductoCaracteristica,

            ':id_servicio' =>
                $this->idServicio,

            ':cantidad' =>
                $this->cantidad,

            ':costo_mano_de_obra' =>
                $this->costoManoObra,

            ':costo_materiales' =>
                $this->costoMateriales,

            ':descuento_producto' =>
                $this->descuentoProducto,

            ':metodo_servicio' =>
                $this->metodoServicio
        ]);
    }

    private function calcularMontoTotal(): float
    {
        $total = (
            (
                $this->costoManoObra +
                $this->costoMateriales
            ) *
            $this->cantidad
        ) -
        $this->descuentoProducto -
        $this->descuentoDivisa;

        return max(
            0,
            round($total, 2)
        );
    }

    private function validarDatosPedido(): void
    {
        if ($this->fechaCreacion === '') {
            throw new Exception(
                'La fecha de creación es obligatoria.'
            );
        }

        if (
            $this->fechaEntrega !== null &&
            $this->fechaEntrega <
            $this->fechaCreacion
        ) {
            throw new Exception(
                'La fecha de entrega no puede ser anterior a la fecha de creación.'
            );
        }

        if ($this->idCliente <= 0) {
            throw new Exception(
                'Debe seleccionar un cliente válido.'
            );
        }

        if ($this->idTipoPedido <= 0) {
            throw new Exception(
                'Debe seleccionar un tipo de pedido válido.'
            );
        }

        if (
            $this->idProductoCaracteristica <=
            0
        ) {
            throw new Exception(
                'Debe seleccionar una característica del producto.'
            );
        }

        if ($this->idServicio <= 0) {
            throw new Exception(
                'Debe seleccionar un servicio válido.'
            );
        }

        if ($this->cantidad <= 0) {
            throw new Exception(
                'La cantidad debe ser mayor que cero.'
            );
        }

        if ($this->descuentoDivisa < 0) {
            throw new Exception(
                'El descuento general no puede ser negativo.'
            );
        }

        if ($this->costoManoObra < 0) {
            throw new Exception(
                'El costo de mano de obra no puede ser negativo.'
            );
        }

        if ($this->costoMateriales < 0) {
            throw new Exception(
                'El costo de materiales no puede ser negativo.'
            );
        }

        if ($this->descuentoProducto < 0) {
            throw new Exception(
                'El descuento del producto no puede ser negativo.'
            );
        }

        $estadosPermitidos = [
            'pendiente',
            'en proceso',
            'realizado',
            'entregado',
            'inhabilitado'
        ];

        if (
            !in_array(
                $this->estadoPedido,
                $estadosPermitidos,
                true
            )
        ) {
            throw new Exception(
                'El estado del pedido no es válido.'
            );
        }
    }
}
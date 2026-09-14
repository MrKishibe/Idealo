<?php

namespace Idealo\Models;

use PDO;
use Exception;

class Pedido
{
    /*
    |--------------------------------------------------------------------------
    | Atributos privados
    |--------------------------------------------------------------------------
    */

    private PDO $pdo;

    private int $idCliente;

    private int $idTipoPedido;

    private float $montoTotal;

    private array $detalles;

    private int $idPedido;


    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(PDO $db)
    {
        $this->pdo = $db;

        $this->idCliente = 0;

        $this->idTipoPedido = 0;

        $this->montoTotal = 0.0;

        $this->detalles = [];

        $this->idPedido = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Método público principal
    |--------------------------------------------------------------------------
    |
    | Es el único método que debe utilizar el controlador.
    |
    */

    public function guardarPedido(
        int $idCliente,
        int $tipoPedido,
        float $montoTotal,
        array $detalles
    ): bool {
        $this->asignarDatos(
            $idCliente,
            $tipoPedido,
            $montoTotal,
            $detalles
        );

        if (!$this->validarDatos()) {
            return false;
        }

        try {
            $this->iniciarTransaccion();

            $this->insertarPedido();

            $this->insertarDetalles();

            $this->confirmarTransaccion();

            return true;

        } catch (Exception $e) {
            $this->revertirTransaccion();

            return false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Asignar datos recibidos
    |--------------------------------------------------------------------------
    */

    private function asignarDatos(
        int $idCliente,
        int $tipoPedido,
        float $montoTotal,
        array $detalles
    ): void {
        $this->idCliente = $idCliente;

        $this->idTipoPedido = $tipoPedido;

        $this->montoTotal = $montoTotal;

        $this->detalles = $detalles;
    }


    /*
    |--------------------------------------------------------------------------
    | Validar información del pedido
    |--------------------------------------------------------------------------
    */

    private function validarDatos(): bool
    {
        if ($this->idCliente <= 0) {
            return false;
        }

        if ($this->idTipoPedido <= 0) {
            return false;
        }

        if ($this->montoTotal < 0) {
            return false;
        }

        if (empty($this->detalles)) {
            return false;
        }

        foreach ($this->detalles as $detalle) {
            if (
                !isset($detalle['cantidad']) ||
                !isset($detalle['id_producto_caracteristica']) ||
                !isset($detalle['id_servicio'])
            ) {
                return false;
            }

            if ((int) $detalle['cantidad'] <= 0) {
                return false;
            }

            if (
                (int) $detalle['id_producto_caracteristica'] <= 0
            ) {
                return false;
            }

            if ((int) $detalle['id_servicio'] <= 0) {
                return false;
            }
        }

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Iniciar transacción
    |--------------------------------------------------------------------------
    */

    private function iniciarTransaccion(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Insertar encabezado del pedido
    |--------------------------------------------------------------------------
    */

    private function insertarPedido(): void
    {
        $sql = "
            INSERT INTO pedido
            (
                fecha_creacion,
                id_tipo_pedido,
                estado_pedido,
                monto_total,
                id_cliente
            )
            VALUES
            (
                CURDATE(),
                :id_tipo,
                'pendiente',
                :monto,
                :id_cliente
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_tipo' => $this->idTipoPedido,
            ':monto' => $this->montoTotal,
            ':id_cliente' => $this->idCliente
        ]);

        $this->idPedido = (int) $this->pdo->lastInsertId();

        if ($this->idPedido <= 0) {
            throw new Exception(
                'No se pudo obtener el identificador del pedido.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Insertar detalles del pedido
    |--------------------------------------------------------------------------
    */

    private function insertarDetalles(): void
    {
        $sql = "
            INSERT INTO detalle_pedido
            (
                cantidad,
                id_producto_caracteristica,
                id_pedido,
                id_servicio
            )
            VALUES
            (
                :cantidad,
                :id_producto_caracteristica,
                :id_pedido,
                :id_servicio
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($this->detalles as $detalle) {
            $stmt->execute([
                ':cantidad' =>
                    (int) $detalle['cantidad'],

                ':id_producto_caracteristica' =>
                    (int) $detalle['id_producto_caracteristica'],

                ':id_pedido' =>
                    $this->idPedido,

                ':id_servicio' =>
                    (int) $detalle['id_servicio']
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Confirmar transacción
    |--------------------------------------------------------------------------
    */

    private function confirmarTransaccion(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Revertir transacción ante un error
    |--------------------------------------------------------------------------
    */

    private function revertirTransaccion(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
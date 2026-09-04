<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;

class TipoPedidoModel extends Database
{
    /*
    |--------------------------------------------------------------------------
    | Propiedades estáticas
    |--------------------------------------------------------------------------
    */
    private static $id_tipo_pedido;
    private static $nombre_tipo_pedido;
    private static $status_tipo_servicio;

    /*
    |--------------------------------------------------------------------------
    | Expresiones regulares
    |--------------------------------------------------------------------------
    */
    public static $expNombre =
        '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,50}$/';

    public static $expStatus =
        '/^(Activo|Inactivo)$/';

    /*
    |--------------------------------------------------------------------------
    | Consultar todos los tipos de pedido
    |--------------------------------------------------------------------------
    */
    public static function consultarPedidos()
    {
        return self::consultarDatosPedidos();
    }

    /*
    |--------------------------------------------------------------------------
    | Validar datos
    |--------------------------------------------------------------------------
    */
    public static function validarDatos(
        $nombre,
        $status,
        $id = null
    ) {
        $nombre = trim(strip_tags($nombre));
        $status = trim(strip_tags($status));

        if ($nombre === '') {
            return [
                'error' =>
                'El nombre del tipo de pedido es obligatorio.'
            ];
        }

        if (!preg_match(self::$expNombre, $nombre)) {
            return [
                'error' =>
                'El nombre debe contener únicamente letras, números ' .
                'y espacios, entre 3 y 50 caracteres.'
            ];
        }

        if (!preg_match(self::$expStatus, $status)) {
            return [
                'error' =>
                'El estado asignado no es válido.'
            ];
        }

        self::$id_tipo_pedido =
            $id !== null ? intval($id) : null;

        self::$nombre_tipo_pedido = $nombre;
        self::$status_tipo_servicio = $status;

        try {
            if (self::verificarDuplicado()) {
                return [
                    'error' =>
                    'El tipo de pedido ya se encuentra registrado.'
                ];
            }
        } catch (PDOException $error) {
            return [
                'error' =>
                'No se pudo verificar el tipo de pedido: ' .
                $error->getMessage()
            ];
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar
    |--------------------------------------------------------------------------
    */
    public static function getRegistrarDatos()
    {
        return self::registrarDatos();
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */
    public static function getActualizarDatos($id)
    {
        self::$id_tipo_pedido = intval($id);

        return self::actualizarDatos();
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar estado
    |--------------------------------------------------------------------------
    */
    public static function getCambiarEstado(
        $id,
        $nuevoEstado
    ) {
        $nuevoEstado = trim(strip_tags($nuevoEstado));

        if (!preg_match(self::$expStatus, $nuevoEstado)) {
            return [
                'error' => 'El estado enviado no es válido.'
            ];
        }

        self::$id_tipo_pedido = intval($id);
        self::$status_tipo_servicio = $nuevoEstado;

        return self::cambiarEstado();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultar registros
    |--------------------------------------------------------------------------
    */
    private static function consultarDatosPedidos()
    {
        try {
            $db = self::connect();

            $sql = "
                SELECT
                    id_tipo_pedido,
                    nombre_tipo_pedido,
                    status_tipo_servicio
                FROM tipo_de_pedido
                ORDER BY id_tipo_pedido DESC
            ";

            $consulta = $db->prepare($sql);
            $consulta->execute();

            return $consulta->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $error) {
            return [
                'error' => $error->getMessage()
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar duplicados
    |--------------------------------------------------------------------------
    */
    private static function verificarDuplicado()
    {
        $db = self::connect();

        if (self::$id_tipo_pedido !== null) {
            $sql = "
                SELECT COUNT(*)
                FROM tipo_de_pedido
                WHERE nombre_tipo_pedido = :nombre
                AND id_tipo_pedido != :id
            ";

            $consulta = $db->prepare($sql);

            $consulta->bindValue(
                ':nombre',
                self::$nombre_tipo_pedido,
                PDO::PARAM_STR
            );

            $consulta->bindValue(
                ':id',
                self::$id_tipo_pedido,
                PDO::PARAM_INT
            );

        } else {
            $sql = "
                SELECT COUNT(*)
                FROM tipo_de_pedido
                WHERE nombre_tipo_pedido = :nombre
            ";

            $consulta = $db->prepare($sql);

            $consulta->bindValue(
                ':nombre',
                self::$nombre_tipo_pedido,
                PDO::PARAM_STR
            );
        }

        $consulta->execute();

        return $consulta->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar registro
    |--------------------------------------------------------------------------
    */
    private static function registrarDatos()
    {
        try {
            $db = self::connect();

            $sql = "
                INSERT INTO tipo_de_pedido
                (
                    nombre_tipo_pedido,
                    status_tipo_servicio
                )
                VALUES
                (
                    :nombre,
                    :status
                )
            ";

            $registrar = $db->prepare($sql);

            $registrar->bindValue(
                ':nombre',
                self::$nombre_tipo_pedido,
                PDO::PARAM_STR
            );

            $registrar->bindValue(
                ':status',
                self::$status_tipo_servicio,
                PDO::PARAM_STR
            );

            $registrar->execute();

            return [
                'exitoso' => 'Registro realizado correctamente.',
                'id' => $db->lastInsertId()
            ];

        } catch (PDOException $error) {
            return [
                'error' => $error->getMessage()
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar registro completo
    |--------------------------------------------------------------------------
    */
    private static function actualizarDatos()
    {
        try {
            $db = self::connect();

            $sql = "
                UPDATE tipo_de_pedido
                SET
                    nombre_tipo_pedido = :nombre,
                    status_tipo_servicio = :status
                WHERE id_tipo_pedido = :id
            ";

            $actualizar = $db->prepare($sql);

            $actualizar->bindValue(
                ':nombre',
                self::$nombre_tipo_pedido,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':status',
                self::$status_tipo_servicio,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':id',
                self::$id_tipo_pedido,
                PDO::PARAM_INT
            );

            $actualizar->execute();

            if ($actualizar->rowCount() === 0) {
                return [
                    'error' =>
                    'No se encontró el registro o no hubo cambios.'
                ];
            }

            return [
                'exitoso' =>
                'Registro actualizado correctamente.'
            ];

        } catch (PDOException $error) {
            return [
                'error' => $error->getMessage()
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar estado
    |--------------------------------------------------------------------------
    */
    private static function cambiarEstado()
    {
        try {
            $db = self::connect();

            $sql = "
                UPDATE tipo_de_pedido
                SET status_tipo_servicio = :status
                WHERE id_tipo_pedido = :id
            ";

            $actualizar = $db->prepare($sql);

            $actualizar->bindValue(
                ':status',
                self::$status_tipo_servicio,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':id',
                self::$id_tipo_pedido,
                PDO::PARAM_INT
            );

            $actualizar->execute();

            if ($actualizar->rowCount() === 0) {
                return [
                    'error' =>
                    'No se encontró el registro o el estado ya estaba asignado.'
                ];
            }

            return [
                'exitoso' =>
                'Estado cambiado correctamente.'
            ];

        } catch (PDOException $error) {
            return [
                'error' => $error->getMessage()
            ];
        }
    }
}
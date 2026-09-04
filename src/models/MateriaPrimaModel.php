<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;

class MateriaPrimaModel extends Database
{
    /*
    |--------------------------------------------------------------------------
    | Propiedades estáticas
    |--------------------------------------------------------------------------
    */
    private static $id_materia_prima;
    private static $nombre_materia_prima;
    private static $id_tipo_materia_prima;
    private static $costo_unitario;
    private static $stock_actual;
    private static $stock_minimo;
    private static $status_materia_prima;
    private static $unidad_de_medida;

    /*
    |--------------------------------------------------------------------------
    | Expresiones regulares
    |--------------------------------------------------------------------------
    */
    public static $expNombre =
        '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\(\)\.\/]{3,100}$/';

    public static $expStatus =
        '/^(Activo|Inactivo)$/';

    public static $expUnidad =
        '/^(centímetro|metro|milímetro)$/';

    /*
    |--------------------------------------------------------------------------
    | Consultar todas las materias primas
    |--------------------------------------------------------------------------
    */
    public static function consultarMateriasPrimas()
    {
        return self::consultarDatosMateriasPrimas();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultar tipos de materia prima activos (para el select)
    |--------------------------------------------------------------------------
    */
    public static function consultarTiposActivos()
    {
        return self::consultarDatosTiposActivos();
    }

    /*
    |--------------------------------------------------------------------------
    | Validar datos
    |--------------------------------------------------------------------------
    */
    public static function validarDatos(
        $nombre,
        $idTipoMateria,
        $costoUnitario,
        $stockActual,
        $stockMinimo,
        $unidadMedida,
        $status,
        $id = null
    ) {
        $nombre = trim(strip_tags($nombre));
        $status = trim(strip_tags($status));
        $unidadMedida = trim(strip_tags($unidadMedida));

        /*
        |--------------------------------------------------------------------------
        | Validaciones básicas
        |--------------------------------------------------------------------------
        */
        if ($nombre === '') {
            return [
                'error' =>
                'El nombre de la materia prima es obligatorio.'
            ];
        }

        if (!preg_match(self::$expNombre, $nombre)) {
            return [
                'error' =>
                'El nombre debe contener únicamente letras, números, ' .
                'espacios y los caracteres - ( ) . / (entre 3 y 100 caracteres).'
            ];
        }

        if (!preg_match(self::$expStatus, $status)) {
            return [
                'error' =>
                'El estado asignado no es válido.'
            ];
        }

        if (!preg_match(self::$expUnidad, $unidadMedida)) {
            return [
                'error' =>
                'La unidad de medida debe ser: centímetro, metro o milímetro.'
            ];
        }

        if (
            $idTipoMateria === null ||
            $idTipoMateria <= 0 ||
            !is_numeric($idTipoMateria)
        ) {
            return [
                'error' =>
                'Debe seleccionar un tipo de materia prima válido.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validaciones numéricas
        |--------------------------------------------------------------------------
        */
        $costoUnitario = floatval($costoUnitario);
        $stockActual = floatval($stockActual);
        $stockMinimo = floatval($stockMinimo);

        if ($costoUnitario < 0) {
            return [
                'error' =>
                'El costo unitario no puede ser negativo.'
            ];
        }

        if ($stockActual < 0) {
            return [
                'error' =>
                'El stock actual no puede ser negativo.'
            ];
        }

        if ($stockMinimo < 0) {
            return [
                'error' =>
                'El stock mínimo no puede ser negativo.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Asignar propiedades
        |--------------------------------------------------------------------------
        */
        self::$id_materia_prima =
            $id !== null ? intval($id) : null;

        self::$nombre_materia_prima = $nombre;
        self::$id_tipo_materia_prima = intval($idTipoMateria);
        self::$costo_unitario = $costoUnitario;
        self::$stock_actual = $stockActual;
        self::$stock_minimo = $stockMinimo;
        self::$status_materia_prima = $status;
        self::$unidad_de_medida = $unidadMedida;

        /*
        |--------------------------------------------------------------------------
        | Verificar duplicado
        |--------------------------------------------------------------------------
        */
        try {
            if (self::verificarDuplicado()) {
                return [
                    'error' =>
                    'La materia prima ya se encuentra registrada.'
                ];
            }
        } catch (PDOException $error) {
            return [
                'error' =>
                'No se pudo verificar la materia prima: ' .
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
        self::$id_materia_prima = intval($id);

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

        self::$id_materia_prima = intval($id);
        self::$status_materia_prima = $nuevoEstado;

        return self::cambiarEstado();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultar registros
    |--------------------------------------------------------------------------
    */
    private static function consultarDatosMateriasPrimas()
    {
        try {
            $db = self::connect();

            $sql = "
                SELECT
                    mp.id_materia_prima,
                    mp.nombre_materia_prima,
                    mp.id_tipo_materia_prima,
                    tmp.nombre_de_material,
                    mp.costo_unitario,
                    mp.stock_actual,
                    mp.stock_minimo,
                    mp.status_materia_prima,
                    mp.unidad_de_medida
                FROM materia_prima mp
                INNER JOIN tipo_de_materia_prima tmp
                    ON mp.id_tipo_materia_prima = tmp.id_tipo_materia_prima
                ORDER BY mp.id_materia_prima DESC
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
    | Consultar tipos activos
    |--------------------------------------------------------------------------
    */
    private static function consultarDatosTiposActivos()
    {
        try {
            $db = self::connect();

            $sql = "
                SELECT
                    id_tipo_materia_prima,
                    nombre_de_material
                FROM tipo_de_materia_prima
                WHERE status_tipo_materia = 'Activo'
                ORDER BY nombre_de_material ASC
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

        if (self::$id_materia_prima !== null) {
            $sql = "
                SELECT COUNT(*)
                FROM materia_prima
                WHERE nombre_materia_prima = :nombre
                AND id_materia_prima != :id
            ";

            $consulta = $db->prepare($sql);

            $consulta->bindValue(
                ':nombre',
                self::$nombre_materia_prima,
                PDO::PARAM_STR
            );

            $consulta->bindValue(
                ':id',
                self::$id_materia_prima,
                PDO::PARAM_INT
            );

        } else {
            $sql = "
                SELECT COUNT(*)
                FROM materia_prima
                WHERE nombre_materia_prima = :nombre
            ";

            $consulta = $db->prepare($sql);

            $consulta->bindValue(
                ':nombre',
                self::$nombre_materia_prima,
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
                INSERT INTO materia_prima
                (
                    nombre_materia_prima,
                    id_tipo_materia_prima,
                    costo_unitario,
                    stock_actual,
                    stock_minimo,
                    status_materia_prima,
                    unidad_de_medida
                )
                VALUES
                (
                    :nombre,
                    :id_tipo,
                    :costo,
                    :stock_actual,
                    :stock_minimo,
                    :status,
                    :unidad
                )
            ";

            $registrar = $db->prepare($sql);

            $registrar->bindValue(
                ':nombre',
                self::$nombre_materia_prima,
                PDO::PARAM_STR
            );

            $registrar->bindValue(
                ':id_tipo',
                self::$id_tipo_materia_prima,
                PDO::PARAM_INT
            );

            $registrar->bindValue(
                ':costo',
                self::$costo_unitario,
                PDO::PARAM_STR
            );

            $registrar->bindValue(
                ':stock_actual',
                self::$stock_actual,
                PDO::PARAM_STR
            );

            $registrar->bindValue(
                ':stock_minimo',
                self::$stock_minimo,
                PDO::PARAM_STR
            );

            $registrar->bindValue(
                ':status',
                self::$status_materia_prima,
                PDO::PARAM_STR
            );

            $registrar->bindValue(
                ':unidad',
                self::$unidad_de_medida,
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
                UPDATE materia_prima
                SET
                    nombre_materia_prima = :nombre,
                    id_tipo_materia_prima = :id_tipo,
                    costo_unitario = :costo,
                    stock_actual = :stock_actual,
                    stock_minimo = :stock_minimo,
                    status_materia_prima = :status,
                    unidad_de_medida = :unidad
                WHERE id_materia_prima = :id
            ";

            $actualizar = $db->prepare($sql);

            $actualizar->bindValue(
                ':nombre',
                self::$nombre_materia_prima,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':id_tipo',
                self::$id_tipo_materia_prima,
                PDO::PARAM_INT
            );

            $actualizar->bindValue(
                ':costo',
                self::$costo_unitario,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':stock_actual',
                self::$stock_actual,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':stock_minimo',
                self::$stock_minimo,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':status',
                self::$status_materia_prima,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':unidad',
                self::$unidad_de_medida,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':id',
                self::$id_materia_prima,
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
                UPDATE materia_prima
                SET status_materia_prima = :status
                WHERE id_materia_prima = :id
            ";

            $actualizar = $db->prepare($sql);

            $actualizar->bindValue(
                ':status',
                self::$status_materia_prima,
                PDO::PARAM_STR
            );

            $actualizar->bindValue(
                ':id',
                self::$id_materia_prima,
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
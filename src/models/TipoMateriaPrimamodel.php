<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;

class TipoMateriaPrimaModel extends Database
{
    // Propiedades estáticas de datos
    private static $id_tipo_materia_prima;
    private static $nombre_de_material;
    private static $descripcion;
    private static $status_tipo_materia;

    // =========================================================================
    // CAPA 1: EXPRESIONES REGULARES STRICTS (Solo letras, números y espacios)
    // =========================================================================
    // Alfanumérico con soporte para acentos y eñes (De 3 a 50 caracteres)
    public static $expNombre = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,50}$/'; 
    
    // Alfanumérico opcional con soporte para acentos y eñes (Hasta 250 caracteres)
    public static $expDescripcion = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{0,250}$/';
    
    public static $expStatus = '/^(Activo|Inactivo)$/';

    /**
     * Consulta general de todos los tipos de materiales
     */
    public static function consultarMateriales()
    {
        try {
            $db = self::connect(); 
            $consulta = $db->prepare("SELECT id_tipo_materia_prima, nombre_de_material, descripcion, status_tipo_materia FROM tipo_de_materia_prima ORDER BY id_tipo_materia_prima DESC");
            $consulta->execute();
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    /**
     * Verifica la existencia de duplicados de manera inteligente
     */
    private static function verificarDuplicado($nombre, $id = null)
    {
        $db = self::connect();
        if ($id !== null) {
            $query = $db->prepare("SELECT COUNT(*) FROM tipo_de_materia_prima WHERE nombre_de_material = ? AND id_tipo_materia_prima != ?");
            $query->execute([$nombre, $id]);
        } else {
            $query = $db->prepare("SELECT COUNT(*) FROM tipo_de_materia_prima WHERE nombre_de_material = ?");
            $query->execute([$nombre]);
        }
        return $query->fetchColumn() > 0;
    }

    /**
     * VALIDACIÓN DE DOBLE CAPA: Estructura, Formato Strict y Sanitización
     */
    public static function validarDatos($nombre, $descripcion, $status, $id = null)
    {
        // -----------------------------------------------------------------
        // CAPA 2A: Sanitización y Limpieza de espacios o etiquetas HTML
        // -----------------------------------------------------------------
        $nombre = trim(strip_tags($nombre));
        $descripcion = trim(strip_tags($descripcion));
        $status = trim($status);

        // -----------------------------------------------------------------
        // CAPA 2B: Validación estricta contra expresiones alfanuméricas
        // -----------------------------------------------------------------
        if (empty($nombre)) {
            return array("error" => 'El nombre del material es obligatorio.');
        }

        if (!preg_match(self::$expNombre, $nombre)) {
            return array("error" => 'El nombre solo debe contener letras, números y espacios (entre 3 y 50 caracteres).');
        }

        if (self::verificarDuplicado($nombre, $id)) {
            return array("error" => 'El tipo de material ya se encuentra registrado en el sistema.');
        }

        if (!empty($descripcion) && !preg_match(self::$expDescripcion, $descripcion)) {
            return array("error" => 'La descripción solo debe contener letras, números y espacios (máximo 250 caracteres).');
        }

        if (!preg_match(self::$expStatus, $status)) {
            return array("error" => 'El estado asignado no es válido.');
        }

        // Si supera de forma exitosa ambas capas, se transfieren los valores limpios
        self::$nombre_de_material = $nombre;
        self::$descripcion = $descripcion;
        self::$status_tipo_materia = $status;

        return true;
    }

    /**
     * Pasarela para registrar nuevos tipos de materiales
     */
    public static function getRegistrarDatos()
    {
        return self::registrarDatos();
    }

    private static function registrarDatos()
    {
        try {
            $db = self::connect();
            $registrar = $db->prepare("INSERT INTO `tipo_de_materia_prima`(`nombre_de_material`, `descripcion`, `status_tipo_materia`) VALUES (:nombre, :descripcion, :status)");
            $registrar->bindParam(":nombre", self::$nombre_de_material);
            $registrar->bindParam(":descripcion", self::$descripcion);
            $registrar->bindParam(":status", self::$status_tipo_materia);
            $registrar->execute();

            return array(
                "existoso" => 'Registro realizado.',
                "id" => $db->lastInsertId()
            );
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    /**
     * Pasarela para la actualización de datos completos
     */
    public static function getActualizarDatos($id)
    {
        self::$id_tipo_materia_prima = intval($id);
        return self::actualizarDatos();
    }

    private static function actualizarDatos()
    {
        try {
            $db = self::connect();
            $actualizar = $db->prepare("UPDATE `tipo_de_materia_prima` SET `nombre_de_material` = ?, `descripcion` = ?, `status_tipo_materia` = ? WHERE `id_tipo_materia_prima` = ?");
            $actualizar->bindValue(1, self::$nombre_de_material);
            $actualizar->bindValue(2, self::$descripcion);
            $actualizar->bindValue(3, self::$status_tipo_materia);
            $actualizar->bindValue(4, self::$id_tipo_materia_prima);
            $actualizar->execute();

            return array("existoso" => 'Registro actualizado.');
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    /**
     * Pasarela para cambios rápidos de estado
     */
    public static function getCambiarEstado($id, $nuevoEstado)
    {
        // Capa de seguridad básica para mutación directa
        if (!preg_match(self::$expStatus, $nuevoEstado)) {
            return array("error" => 'Estado inválido.');
        }

        self::$id_tipo_materia_prima = intval($id);
        self::$status_tipo_materia = $nuevoEstado;
        return self::cambiarEstado();
    }

    private static function cambiarEstado()
    {
        try {
            $db = self::connect();
            $actualizar = $db->prepare("UPDATE `tipo_de_materia_prima` SET `status_tipo_materia` = ? WHERE `id_tipo_materia_prima` = ?");
            $actualizar->bindValue(1, self::$status_tipo_materia);
            $actualizar->bindValue(2, self::$id_tipo_materia_prima);
            $actualizar->execute();

            return array("existoso" => 'Estado cambiado.');
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }
}
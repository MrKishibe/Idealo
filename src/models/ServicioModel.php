<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;

class ServicioModel extends Database
{
    private static $id_servicio;
    private static $nombre_servicio;
    private static $status_servicio;

    // Validación estricta de doble capa: solo letras, números y espacios
    public static $expNombre = '/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s]{3,50}$/';
    public static $expStatus = '/^(activo|inactivo)$/';

    /**
     * Consulta servicios filtrados por su estado
     */
    public static function listarPorEstado(string $estado): array
    {
        try {
            $db = self::connect();
            $sql = "SELECT id_servicio, nombre_servicio, status_servicio 
                    FROM servicio WHERE status_servicio = ? ORDER BY id_servicio DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$estado]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            return [];
        }
    }

    /**
     * Verifica duplicados ignorando mayúsculas y minúsculas
     */
    private static function verificarDuplicado($nombre, $id = null)
    {
        $db = self::connect();
        if ($id !== null) {
            $sql = "SELECT COUNT(*) FROM servicio WHERE LOWER(nombre_servicio) = LOWER(?) AND id_servicio != ?";
            $query = $db->prepare($sql);
            $query->execute([$nombre, $id]);
        } else {
            $sql = "SELECT COUNT(*) FROM servicio WHERE LOWER(nombre_servicio) = LOWER(?)";
            $query = $db->prepare($sql);
            $query->execute([$nombre]);
        }
        return $query->fetchColumn() > 0;
    }

    /**
     * Sanitización y validación estricta
     */
    public static function validarDatos($nombre, $status, $id = null)
    {
        $nombre = trim(strip_tags($nombre));
        $status = trim(strtolower($status));

        if (!preg_match(self::$expNombre, $nombre)) {
            return array("error" => 'El nombre del servicio debe tener entre 3 y 50 caracteres, conteniendo solo letras y números.');
        }

        if (self::verificarDuplicado($nombre, $id)) {
            return array("error" => 'Ya existe un servicio registrado con ese nombre.');
        }

        if (!preg_match(self::$expStatus, $status)) {
            return array("error" => 'El estado asignado no es válido.');
        }

        return true;
    }

    /**
     * Pasarela para registro
     */
    public static function getRegistrarDatos($nombre, $status)
    {
        self::$nombre_servicio = trim(strip_tags($nombre));
        self::$status_servicio = trim(strtolower($status));
        return self::registrarDatos();
    }

    private static function registrarDatos()
    {
        try {
            $db = self::connect();
            $registrar = $db->prepare("INSERT INTO `servicio`(`nombre_servicio`, `status_servicio`) VALUES (?, ?)");
            $registrar->bindValue(1, self::$nombre_servicio);
            $registrar->bindValue(2, self::$status_servicio);
            $registrar->execute();

            return array("exitoso" => 'Registro realizado.');
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    /**
     * Pasarela para actualización completa
     */
    public static function getActualizarDatos($id, $nombre, $status)
    {
        self::$id_servicio = intval($id);
        self::$nombre_servicio = trim(strip_tags($nombre));
        self::$status_servicio = trim(strtolower($status));
        return self::actualizarDatos();
    }

    private static function actualizarDatos()
    {
        try {
            $db = self::connect();
            $actualizar = $db->prepare("UPDATE `servicio` SET `nombre_servicio` = ?, `status_servicio` = ? WHERE `id_servicio` = ?");
            $actualizar->bindValue(1, self::$nombre_servicio);
            $actualizar->bindValue(2, self::$status_servicio);
            $actualizar->bindValue(3, self::$id_servicio);
            $actualizar->execute();

            return array("exitoso" => 'Registro actualizado.');
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    /**
     * Pasarela para mutación de estado rápido
     */
    public static function getCambiarEstado($id, $nuevoEstado)
    {
        self::$id_servicio = intval($id);
        self::$status_servicio = trim(strtolower($nuevoEstado));
        return self::cambiarEstado();
    }

    private static function cambiarEstado()
    {
        try {
            $db = self::connect();
            $actualizar = $db->prepare("UPDATE `servicio` SET `status_servicio` = ? WHERE `id_servicio` = ?");
            $actualizar->bindValue(1, self::$status_servicio);
            $actualizar->bindValue(2, self::$id_servicio);
            $actualizar->execute();

            return array("exitoso" => 'Estado cambiado.');
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }
}
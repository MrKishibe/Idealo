<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOException;

class EmpleadoModel extends Database
{

    private static $id_empleado;
    private static $nombres;
    private static $apellidos;
    private static $cedula;
    private static $telefono;
    private static $direccion;
    private static $cargo;
    private static $salario;
    private static $status_empleado;


    public static $expNombres   = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,100}$/';
    public static $expApellidos = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,100}$/';
    public static $expCedula    = '/^[a-zA-Z0-9\-]{3,20}$/';
    public static $expTelefono  = '/^[0-9\+\-\s]{0,20}$/';
    public static $expDireccion = '/^[\s\S]{0,500}$/';
    public static $expCargo     = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,100}$/';
    public static $expSalario   = '/^\d+(\.\d{1,2})?$/';
    public static $expStatus    = '/^(Activo|Inactivo)$/';


    public static function consultarEmpleados()
    {
        return self::consultarDatosEmpleados();
    }


    public static function validarDatos($nombres, $apellidos, $cedula, $telefono, $direccion, $cargo, $salario, $status, $id = null)
    {

        $nombres   = trim(strip_tags($nombres));
        $apellidos = trim(strip_tags($apellidos));
        $cedula    = trim(strip_tags($cedula));
        $telefono  = trim(strip_tags($telefono));
        $direccion = trim(strip_tags($direccion));
        $cargo     = trim(strip_tags($cargo));
        $salario   = trim($salario);
        $status    = trim($status);

        if (empty($nombres)) {
            return array("error" => "El nombre del empleado es obligatorio.");
        }
        if (!preg_match(self::$expNombres, $nombres)) {
            return array("error" => "Los nombres solo deben contener letras y espacios (entre 3 y 100 caracteres).");
        }


        if (empty($apellidos)) {
            return array("error" => "Los apellidos del empleado son obligatorios.");
        }
        if (!preg_match(self::$expApellidos, $apellidos)) {
            return array("error" => "Los apellidos solo deben contener letras y espacios (entre 3 y 100 caracteres).");
        }

        if (empty($cedula)) {
            return array("error" => "La cédula del empleado es obligatoria.");
        }
        if (!preg_match(self::$expCedula, $cedula)) {
            return array("error" => "La cédula solo debe contener letras, números y guiones (entre 3 y 20 caracteres).");
        }


        self::$id_empleado = $id !== null ? intval($id) : null;
        self::$cedula = $cedula;

        if (self::verificarDuplicado()) {
            return array("error" => "La cédula ya se encuentra registrada en el sistema.");
        }

        if (!empty($telefono) && !preg_match(self::$expTelefono, $telefono)) {
            return array("error" => "El teléfono solo debe contener números, +, - y espacios (máximo 20 caracteres).");
        }

        if (!empty($direccion) && !preg_match(self::$expDireccion, $direccion)) {
            return array("error" => "La dirección supera el límite de 500 caracteres.");
        }

        if (empty($cargo)) {
            return array("error" => "El cargo del empleado es obligatorio.");
        }
        if (!preg_match(self::$expCargo, $cargo)) {
            return array("error" => "El cargo solo debe contener letras y espacios (entre 3 y 100 caracteres).");
        }

        if ($salario === '' || $salario === null) {
            return array("error" => "El salario del empleado es obligatorio.");
        }
        if (!preg_match(self::$expSalario, $salario)) {
            return array("error" => "El salario debe ser un número válido con hasta 2 decimales.");
        }
        if (floatval($salario) < 0) {
            return array("error" => "El salario no puede ser negativo.");
        }

        if (!preg_match(self::$expStatus, $status)) {
            return array("error" => "El estado asignado no es válido.");
        }


        self::$nombres        = $nombres;
        self::$apellidos      = $apellidos;
        self::$telefono       = $telefono;
        self::$direccion      = $direccion;
        self::$cargo          = $cargo;
        self::$salario        = floatval($salario);
        self::$status_empleado = $status;

        return true;
    }


    public static function getRegistrarDatos()
    {
        return self::registrarDatos();
    }


    public static function getActualizarDatos($id)
    {
        self::$id_empleado = intval($id);
        return self::actualizarDatos();
    }


    public static function getCambiarEstado($id, $nuevoEstado)
    {
        if (!preg_match(self::$expStatus, $nuevoEstado)) {
            return array("error" => "Estado inválido.");
        }

        self::$id_empleado = intval($id);
        self::$status_empleado = $nuevoEstado;
        return self::cambiarEstado();
    }


    private static function consultarDatosEmpleados()
    {
        try {
            $db = self::connect();
            $consulta = $db->prepare("SELECT id_empleado, nombres, apellidos, cedula, telefono, direccion, cargo, salario, status_empleado FROM empleado ORDER BY id_empleado DESC");
            $consulta->execute();
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    private static function verificarDuplicado()
    {
        $db = self::connect();
        if (self::$id_empleado !== null) {
            $query = $db->prepare("SELECT COUNT(*) FROM empleado WHERE cedula = ? AND id_empleado != ?");
            $query->execute([self::$cedula, self::$id_empleado]);
        } else {
            $query = $db->prepare("SELECT COUNT(*) FROM empleado WHERE cedula = ?");
            $query->execute([self::$cedula]);
        }
        return $query->fetchColumn() > 0;
    }

    private static function registrarDatos()
    {
        try {
            $db = self::connect();
            $registrar = $db->prepare("INSERT INTO `empleado`(`nombres`, `apellidos`, `cedula`, `telefono`, `direccion`, `cargo`, `salario`, `status_empleado`) VALUES (:nombres, :apellidos, :cedula, :telefono, :direccion, :cargo, :salario, :status)");
            $registrar->bindParam(":nombres", self::$nombres);
            $registrar->bindParam(":apellidos", self::$apellidos);
            $registrar->bindParam(":cedula", self::$cedula);
            $registrar->bindParam(":telefono", self::$telefono);
            $registrar->bindParam(":direccion", self::$direccion);
            $registrar->bindParam(":cargo", self::$cargo);
            $registrar->bindParam(":salario", self::$salario);
            $registrar->bindParam(":status", self::$status_empleado);
            $registrar->execute();

            return array(
                "existoso" => "Registro realizado.",
                "id" => $db->lastInsertId()
            );
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    private static function actualizarDatos()
    {
        try {
            $db = self::connect();
            $actualizar = $db->prepare("UPDATE `empleado` SET `nombres` = ?, `apellidos` = ?, `telefono` = ?, `direccion` = ?, `cargo` = ?, `salario` = ?, `status_empleado` = ? WHERE `id_empleado` = ?");
            $actualizar->bindValue(1, self::$nombres);
            $actualizar->bindValue(2, self::$apellidos);
            $actualizar->bindValue(3, self::$telefono);
            $actualizar->bindValue(4, self::$direccion);
            $actualizar->bindValue(5, self::$cargo);
            $actualizar->bindValue(6, self::$salario);
            $actualizar->bindValue(7, self::$status_empleado);
            $actualizar->bindValue(8, self::$id_empleado);
            $actualizar->execute();

            return array("existoso" => "Registro actualizado.");
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    private static function cambiarEstado()
    {
        try {
            $db = self::connect();
            $actualizar = $db->prepare("UPDATE `empleado` SET `status_empleado` = ? WHERE `id_empleado` = ?");
            $actualizar->bindValue(1, self::$status_empleado);
            $actualizar->bindValue(2, self::$id_empleado);
            $actualizar->execute();

            return array("existoso" => "Estado cambiado.");
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }
}

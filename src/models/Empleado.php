<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;

class Empleado extends Usuariomodel
{

    public function autenticar($db)
    {
        $query = "SELECT u.id_usuario, u.contrasena, e.nombres, e.cargo, u.id_rol 
                  FROM usuario u 
                  JOIN empleado e ON u.id_usuario = e.id_usuario 
                  WHERE u.cedula_usuario = :cedula AND u.status_usuario = 'activo' LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->execute([':cedula' => $this->cedula_usuario]);

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($this->verificarHash($this->contrasena, $row['contrasena'])) {
                return $row;
            }
        }
        return false;
    }

    public function registrarEmpleadoCompleto($db, $cedula, $passwordPlana, $idRol, $nombres, $apellidos, $cargo)
    {
        try {
            $db->beginTransaction();

            $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);

            $queryUsuario = "INSERT INTO usuario (cedula_usuario, contrasena, status_usuario, id_rol) 
                             VALUES (:cedula, :contrasena, 'activo', :id_rol)";
            $stmtUsuario = $db->prepare($queryUsuario);
            $stmtUsuario->execute([
                ':cedula' => $cedula,
                ':contrasena' => $passwordHash,
                ':id_rol' => $idRol
            ]);

            $id_usuario_generado = $db->lastInsertId();

            $queryEmpleado = "INSERT INTO empleado (nombres, apellidos, cedula, cargo, id_usuario, estado) 
                              VALUES (:nombres, :apellidos, :cedula, :cargo, :id_usuario, 'activo')";
            $stmtEmpleado = $db->prepare($queryEmpleado);
            $stmtEmpleado->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':cedula' => $cedula,
                ':cargo' => $cargo,
                ':id_usuario' => $id_usuario_generado
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function obtenerTodos($db)
    {
        $query = "SELECT u.id_usuario, u.cedula_usuario, u.id_rol, u.status_usuario, 
                         e.nombres, e.apellidos, e.cargo 
                  FROM usuario u
                  INNER JOIN empleado e ON u.id_usuario = e.id_usuario
                  WHERE u.status_usuario = 'activo'
                  ORDER BY u.id_usuario DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEmpleadoCompleto($db, $idUsuario, $cedula, $idRol, $nombres, $apellidos, $cargo)
    {
        try {
            $db->beginTransaction();

            $queryU = "UPDATE usuario SET cedula_usuario = :cedula, id_rol = :id_rol WHERE id_usuario = :id_usuario";
            $stmtU = $db->prepare($queryU);
            $stmtU->execute([
                ':cedula' => $cedula,
                ':id_rol' => $idRol,
                ':id_usuario' => $idUsuario
            ]);

            $queryE = "UPDATE empleado SET nombres = :nombres, apellidos = :apellidos, cedula = :cedula, cargo = :cargo WHERE id_usuario = :id_usuario";
            $stmtE = $db->prepare($queryE);
            $stmtE->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':cedula' => $cedula,
                ':cargo' => $cargo,
                ':id_usuario' => $idUsuario
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function eliminarLogico($db, $idUsuario)
    {
        try {
            $db->beginTransaction();

            $queryU = "UPDATE usuario SET status_usuario = 'inactivo' WHERE id_usuario = :id_usuario";
            $stmtU = $db->prepare($queryU);
            $stmtU->execute([':id_usuario' => $idUsuario]);

            $queryE = "UPDATE empleado SET estado = 'inactivo' WHERE id_usuario = :id_usuario";
            $stmtE = $db->prepare($queryE);
            $stmtE->execute([':id_usuario' => $idUsuario]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}

<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;

class UsuarioModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listarActivos()
    {
        $sql = "SELECT u.id_usuario, u.cedula_usuario, u.id_rol, 
                       e.id_empleado, e.nombres, e.apellidos, e.telefono, e.direccion, e.salario, e.cargo
                FROM usuario u
                INNER JOIN empleado e ON u.id_usuario = e.id_usuario
                WHERE u.status_usuario = 'activo' AND e.estado = 'activo'
                ORDER BY u.id_usuario DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar($datos)
    {
        try {
            $this->db->beginTransaction();

            $sqlUsuario = "INSERT INTO usuario (cedula_usuario, contrasena, status_usuario, id_rol) 
                           VALUES (:cedula_usuario, :contrasena, 'activo', :id_rol)";
            $stmtU = $this->db->prepare($sqlUsuario);
            $stmtU->execute([
                ':cedula_usuario' => $datos['cedula_usuario'],
                ':contrasena' => password_hash($datos['contrasena'], PASSWORD_BCRYPT),
                ':id_rol' => $datos['id_rol']
            ]);

            $idUsuario = $this->db->lastInsertId();

            $sqlEmpleado = "INSERT INTO empleado (nombres, apellidos, cedula, telefono, direccion, salario, cargo, estado, id_usuario) 
                            VALUES (:nombres, :apellidos, :cedula, :telefono, :direccion, :salario, :cargo, 'activo', :id_usuario)";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([
                ':nombres' => $datos['nombres'],
                ':apellidos' => $datos['apellidos'],
                ':cedula' => $datos['cedula_usuario'],
                ':telefono' => $datos['telefono'],
                ':direccion' => $datos['direccion'],
                ':salario' => $datos['salario'],
                ':cargo' => $datos['cargo'],
                ':id_usuario' => $idUsuario
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function editar($datos)
    {
        try {
            $this->db->beginTransaction();

            $sqlUsuario = "UPDATE usuario SET cedula_usuario = :cedula_usuario, id_rol = :id_rol WHERE id_usuario = :id_usuario";
            $stmtU = $this->db->prepare($sqlUsuario);
            $stmtU->execute([
                ':cedula_usuario' => $datos['cedula_usuario'],
                ':id_rol' => $datos['id_rol'],
                ':id_usuario' => $datos['id_usuario']
            ]);

            $sqlEmpleado = "UPDATE empleado SET 
                                nombres = :nombres, 
                                apellidos = :apellidos, 
                                cedula = :cedula, 
                                telefono = :telefono, 
                                direccion = :direccion, 
                                cargo = :cargo, 
                                salario = :salario 
                            WHERE id_usuario = :id_usuario";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([
                ':nombres' => $datos['nombres'],
                ':apellidos' => $datos['apellidos'],
                ':cedula' => $datos['cedula_usuario'],
                ':telefono' => $datos['telefono'],
                ':direccion' => $datos['direccion'],
                ':cargo' => $datos['cargo'],
                ':salario' => $datos['salario'],
                ':id_usuario' => $datos['id_usuario']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function inactivar($id)
    {
        try {
            $this->db->beginTransaction();

            $sqlUsuario = "UPDATE usuario SET status_usuario = 'inactivo' WHERE id_usuario = :id_usuario";
            $stmtU = $this->db->prepare($sqlUsuario);
            $stmtU->execute([':id_usuario' => $id]);

            $sqlEmpleado = "UPDATE empleado SET estado = 'inactivo' WHERE id_usuario = :id_usuario";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([':id_usuario' => $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

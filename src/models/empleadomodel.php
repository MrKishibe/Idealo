<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use Exception;

class EmpleadoModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listarTodos($todos = false)
    {
        $sql = "SELECT id_empleado, nombres, apellidos, cedula, telefono, direccion, salario, cargo, status_empleado, id_usuario
                FROM empleado";

        if (!$todos) {
            $sql .= " WHERE status_empleado = 'activo'";
        }

        $sql .= " ORDER BY id_empleado DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($idEmpleado)
    {
        $sql = "SELECT id_empleado, nombres, apellidos, cedula, telefono, direccion, salario, cargo, status_empleado, id_usuario
                FROM empleado
                WHERE id_empleado = :id_empleado";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_empleado' => $idEmpleado]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardar($datos)
    {
        try {
            $this->db->beginTransaction();

            // 1. Insertamos silenciosamente en la tabla usuario para satisfacer la clave foránea
            $sqlUsuario = "INSERT INTO usuario (cedula_usuario, contrasena, status_usuario, id_rol) 
                           VALUES (:cedula_usuario, :contrasena, 'activo', 2)";
            $stmtU = $this->db->prepare($sqlUsuario);
            // Cédula como contraseña temporal cifrada por defecto
            $contrasenaHash = password_hash($datos['cedula'], PASSWORD_BCRYPT);
            $stmtU->execute([
                ':cedula_usuario' => $datos['cedula'],
                ':contrasena'     => $contrasenaHash
            ]);

            $idUsuario = $this->db->lastInsertId();

            // 2. Insertamos los datos estrictos en la tabla empleado
            $sqlEmpleado = "INSERT INTO empleado (nombres, apellidos, cedula, telefono, direccion, salario, cargo, status_empleado, id_usuario) 
                            VALUES (:nombres, :apellidos, :cedula, :telefono, :direccion, :salario, :cargo, 'activo', :id_usuario)";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([
                ':nombres'    => $datos['nombres'],
                ':apellidos'  => $datos['apellidos'],
                ':cedula'     => $datos['cedula'],
                ':telefono'   => $datos['telefono'] ?? '',
                ':direccion'  => $datos['direccion'] ?? '',
                ':salario'    => $datos['salario'] ?? 0.00,
                ':cargo'      => $datos['cargo'] ?? 'Costurero',
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
            $sqlEmpleado = "UPDATE empleado SET 
                                nombres = :nombres, 
                                apellidos = :apellidos, 
                                telefono = :telefono, 
                                direccion = :direccion, 
                                cargo = :cargo, 
                                salario = :salario 
                            WHERE id_empleado = :id_empleado";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([
                ':nombres'     => $datos['nombres'],
                ':apellidos'   => $datos['apellidos'],
                ':telefono'    => $datos['telefono'],
                ':direccion'   => $datos['direccion'],
                ':cargo'       => $datos['cargo'],
                ':salario'     => $datos['salario'],
                ':id_empleado' => $datos['id_empleado']
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function cambiarEstado($idEmpleado, $estado)
    {
        try {
            $this->db->beginTransaction();

            // 1. Cambiar estado en la tabla empleado (status_empleado)
            $sqlEmpleado = "UPDATE empleado SET status_empleado = :estado WHERE id_empleado = :id_empleado";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([
                ':estado'      => $estado,
                ':id_empleado' => $idEmpleado
            ]);

            // 2. Sincronizar el estado en la tabla usuario asociada (para que no intente iniciar sesión si está inactivo)
            $sqlGet = "SELECT id_usuario FROM empleado WHERE id_empleado = :id_empleado";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([':id_empleado' => $idEmpleado]);
            $emp = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($emp && isset($emp['id_usuario'])) {
                $sqlUsuario = "UPDATE usuario SET status_usuario = :estado WHERE id_usuario = :id_usuario";
                $stmtU = $this->db->prepare($sqlUsuario);
                $stmtU->execute([
                    ':estado'     => $estado,
                    ':id_usuario' => $emp['id_usuario']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function inactivar($idEmpleado)
    {
        return $this->cambiarEstado($idEmpleado, 'inactivo');
    }
}

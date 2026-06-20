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

    /**
     * Lista todos los empleados activos junto a su cuenta de usuario vinculada
     */
    public function listarTodos()
    {
        // Usar la columna `status_empleado` (esquema) y exponer `status_usuario` para la vista
        $sql = "SELECT u.id_usuario, u.cedula_usuario, u.id_rol,
                   COALESCE(u.status_usuario, e.status_empleado) AS status_usuario,
                   e.id_empleado, e.nombres, e.apellidos, e.telefono, e.direccion, e.salario, e.cargo, 
                   e.status_empleado
            FROM empleado e
            INNER JOIN usuario u ON e.id_usuario = u.id_usuario
            WHERE e.status_empleado = 'activo' AND u.status_usuario = 'activo'
            ORDER BY e.id_empleado DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo empleado creando simultáneamente su usuario de acceso
     */
    public function guardar($datos)
    {
        try {
            $this->db->beginTransaction();

            $sqlUsuario = "INSERT INTO usuario (cedula_usuario, contrasena, status_usuario, id_rol) 
                           VALUES (:cedula_usuario, :contrasena, 'activo', :id_rol)";
            $stmtU = $this->db->prepare($sqlUsuario);
            $stmtU->execute([
                ':cedula_usuario' => $datos['cedula_usuario'],
                ':contrasena'     => password_hash($datos['contrasena'] ?? $datos['cedula_usuario'], PASSWORD_BCRYPT),
                ':id_rol'         => $datos['id_rol']
            ]);

            $idUsuario = $this->db->lastInsertId();

            $sqlEmpleado = "INSERT INTO empleado (nombres, apellidos, cedula, telefono, direccion, salario, cargo, status_empleado, id_usuario) 
                            VALUES (:nombres, :apellidos, :cedula, :telefono, :direccion, :salario, :cargo, 'activo', :id_usuario)";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([
                ':nombres'    => $datos['nombres'],
                ':apellidos'  => $datos['apellidos'],
                ':cedula'     => $datos['cedula_usuario'],
                ':telefono'   => $datos['telefono'] ?? '',
                ':direccion'  => $datos['direccion'] ?? '',
                ':salario'    => $datos['salario'] ?? 0.00,
                ':cargo'      => $datos['cargo'] ?? 'Operador',
                ':id_usuario' => $idUsuario
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Modifica los datos del empleado y actualiza el rol/cédula en su usuario de acceso
     */
    public function editar($datos)
    {
        try {
            $this->db->beginTransaction();

            $sqlUsuario = "UPDATE usuario SET cedula_usuario = :cedula_usuario, id_rol = :id_rol WHERE id_usuario = :id_usuario";
            $stmtU = $this->db->prepare($sqlUsuario);
            $stmtU->execute([
                ':cedula_usuario' => $datos['cedula_usuario'],
                ':id_rol'         => $datos['id_rol'],
                ':id_usuario'     => $datos['id_usuario']
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
                ':nombres'    => $datos['nombres'],
                ':apellidos'  => $datos['apellidos'],
                ':cedula'     => $datos['cedula_usuario'],
                ':telefono'   => $datos['telefono'],
                ':direccion'  => $datos['direccion'],
                ':cargo'      => $datos['cargo'],
                ':salario'    => $datos['salario'],
                ':id_usuario' => $datos['id_usuario']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Inactiva tanto la ficha del empleado como su cuenta de usuario del sistema
     */
    public function inactivar($idUsuario)
    {
        try {
            $this->db->beginTransaction();

            $sqlUsuario = "UPDATE usuario SET status_usuario = 'inactivo' WHERE id_usuario = :id_usuario";
            $stmtU = $this->db->prepare($sqlUsuario);
            $stmtU->execute([':id_usuario' => $idUsuario]);

            $sqlEmpleado = "UPDATE empleado SET status_empleado = 'inactivo' WHERE id_usuario = :id_usuario";
            $stmtE = $this->db->prepare($sqlEmpleado);
            $stmtE->execute([':id_usuario' => $idUsuario]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
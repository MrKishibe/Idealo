<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';

use Idealo\Config\Database;
use PDO;
use PDOException;

class ProductoModel
{
    private $id_producto;
    private $nombre_producto;
    private $tipo_de_producto;
    private $status_producto;
    private $conex;

    public $expNombre = '/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s-]{3,150}$/';

    public function __construct()
    {
        $this->conex = Database::connect();
    }

    /**
     * CORREGIDO: Retorna todos los productos para el filtro visual del DataTables
     */
    public function listarTodos(): array
    {
        try {
            $sql = "SELECT id_producto, nombre_producto, tipo_de_producto, status_producto 
                    FROM producto 
                    ORDER BY id_producto DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            return [];
        }
    }

    public function listarActivos(): array
    {
        try {
            $sql = "SELECT id_producto, nombre_producto, tipo_de_producto, status_producto 
                    FROM producto 
                    WHERE status_producto = 'activo' 
                    ORDER BY id_producto DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            return [];
        }
    }

    public function guardar(array $datos = []): array
    {
        try {
            if (!empty($datos)) {
                $id     = $datos['id_producto'] ?? null;
                $nombre = trim($datos['nombre_producto'] ?? '');
                $tipo   = trim($datos['tipo_de_producto'] ?? '');
                $status = trim($datos['status_producto'] ?? 'activo');

                if (!preg_match($this->expNombre, $nombre)) {
                    return array("error" => 'El nombre del producto debe tener entre 3 y 150 caracteres.');
                }
                if (empty($tipo)) {
                    return array("error" => 'Debe seleccionar un tipo de producto válido.');
                }
                if ($this->verificarDuplicado($nombre, $id)) {
                    return array("error" => 'Ya existe un producto registrado con ese nombre.');
                }
                
                $this->nombre_producto  = $nombre;
                $this->tipo_de_producto = $tipo;
                $this->status_producto  = $status;

                if (!empty($id)) {
                    $this->id_producto = $id;
                }
            }

            if (empty($this->id_producto)) {
                return $this->registrarDatos();
            } else {
                return $this->actualizarDatos();
            }
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    private function verificarDuplicado($nombre, $id = null)
    {
        if ($id !== null) {
            $sql = "SELECT COUNT(*) FROM producto WHERE LOWER(nombre_producto) = LOWER(?) AND id_producto != ?";
            $query = $this->conex->prepare($sql);
            $query->execute([$nombre, $id]);
        } else {
            $sql = "SELECT COUNT(*) FROM producto WHERE LOWER(nombre_producto) = LOWER(?)";
            $query = $this->conex->prepare($sql);
            $query->execute([$nombre]);
        }
        return $query->fetchColumn() > 0;
    }

    private function registrarDatos()
    {
        try {
            $sql = "INSERT INTO `producto`(`nombre_producto`, `tipo_de_producto`, `status_producto`) 
                    VALUES (:nombre, :tipo, :status)";
            $registrar = $this->conex->prepare($sql);
            $registrar->bindParam(":nombre", $this->nombre_producto);
            $registrar->bindParam(":tipo", $this->tipo_de_producto);
            $registrar->bindParam(":status", $this->status_producto);
            $registrar->execute();
            return array("success" => true);
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    private function actualizarDatos()
    {
        try {
            $sql = "UPDATE `producto` 
                    SET `nombre_producto` = ?, `tipo_de_producto` = ?, `status_producto` = ? 
                    WHERE `id_producto` = ?";
            $actualizar = $this->conex->prepare($sql);
            $actualizar->bindValue(1, $this->nombre_producto);
            $actualizar->bindValue(2, $this->tipo_de_producto);
            $actualizar->bindValue(3, $this->status_producto);
            $actualizar->bindValue(4, $this->id_producto);
            $actualizar->execute();
            return array("success" => true);
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }

    public function getCambiarEstado($id, $nuevoEstado)
    {
        try {
            $sql = "UPDATE `producto` SET `status_producto` = ? WHERE `id_producto` = ?";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(1, $nuevoEstado);
            $stmt->bindValue(2, $id);
            $stmt->execute();
            return array("success" => true);
        } catch (PDOException $error) {
            return array("error" => $error->getMessage());
        }
    }
}
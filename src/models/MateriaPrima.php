<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;

class MateriaPrima
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    public function obtenerTodos()
    {
        $query = "SELECT mp.id_materia_prima, 
                         mp.nombre_materia_prima, 
                         mp.costo_unitario, 
                         mp.stock_actual, 
                         mp.stock_minimo, 
                         mp.status_materia_prima,
                         tmp.nombre_de_material AS categoria,
                         um.id_unidad_de_medida,
                         um.abreviatura
                  FROM materia_prima mp
                  INNER JOIN tipo_de_materia_prima tmp ON mp.id_tipo_materia_prima = tmp.id_tipo_materia_prima
                  INNER JOIN unidad_de_medida um ON mp.id_unidad_de_medida = um.id_unidad_de_medida
                  WHERE mp.status_materia_prima = 'disponible' 
                    AND (tmp.nombre_de_material = 'Telas' OR tmp.nombre_de_material = 'Tintas')
                  ORDER BY mp.id_materia_prima DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertar($nombre, $costo, $stock_actual, $stock_minimo, $id_tipo, $id_unidad)
    {
        try {
            $query = "INSERT INTO materia_prima (nombre_materia_prima, costo_unitario, stock_actual, stock_minimo, id_tipo_materia_prima, id_unidad_de_medida, status_materia_prima) 
                      VALUES (:nombre, :costo, :stock_actual, :stock_minimo, :id_tipo, :id_unidad, 'disponible')";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':nombre' => $nombre,
                ':costo' => $costo,
                ':stock_actual' => $stock_actual,
                ':stock_minimo' => $stock_minimo,
                ':id_tipo' => $id_tipo,
                ':id_unidad' => $id_unidad
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id, $nombre, $costo, $stock_actual, $stock_minimo, $id_tipo, $id_unidad)
    {
        try {
            $query = "UPDATE materia_prima 
                      SET nombre_materia_prima = :nombre, \r
                          id_tipo_materia_prima = :id_tipo, \r
                          costo_unitario = :costo, \r
                          stock_actual = :stock_actual, \r
                          stock_minimo = :stock_minimo,
                          id_unidad_de_medida = :id_unidad
                      WHERE id_materia_prima = :id";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':nombre'       => $nombre,
                ':id_tipo'      => $id_tipo,
                ':costo'        => $costo,
                ':stock_actual' => $stock_actual,
                ':stock_minimo' => $stock_minimo,
                ':id_unidad'    => $id_unidad,
                ':id'           => $id
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id)
    {
        try {
            $query = "UPDATE materia_prima SET status_materia_prima = 'no disponible' WHERE id_materia_prima = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

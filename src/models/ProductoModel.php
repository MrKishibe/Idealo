<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';

use Idealo\Config\Database;
use PDO;
use PDOException;
use Exception;

class ProductoModel extends Database
{
    private $id_producto;
    private $nombre_producto;
    private $tipo_de_producto;
    private $status_producto;

    // Campos de 'caracteristica'
    private $detalle_material;
    private $color;
    private $tipo_de_prenda;

    // Tallas múltiples para 'producto_caracteristica'
    private $tallas = [];

    private $conex;

    public $expNombre = '/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s\.\,\#\-]{3,150}$/u';

    public function __construct()
    {
        $this->conex = parent::connect();
    }

    /**
     * Retorna los productos agrupando todas sus tallas activas con GROUP_CONCAT
     */
    public function listarTodos(): array
    {
        try {
            $sql = "SELECT 
                        p.id_producto, 
                        p.nombre_producto, 
                        p.tipo_de_producto, 
                        p.status_producto,
                        c.id_caracteristica,
                        c.detalle_material,
                        c.color,
                        c.tipo_de_prenda,
                        GROUP_CONCAT(DISTINCT pc.talla ORDER BY pc.id_producto_caracteristica SEPARATOR ', ') AS tallas_str
                    FROM producto p
                    LEFT JOIN producto_caracteristica pc 
                        ON p.id_producto = pc.id_producto 
                        AND pc.status_producto_caracteristica = 'activo'
                    LEFT JOIN caracteristica c 
                        ON pc.id_caracteristica = c.id_caracteristica
                    GROUP BY 
                        p.id_producto, 
                        p.nombre_producto, 
                        p.tipo_de_producto, 
                        p.status_producto,
                        c.id_caracteristica,
                        c.detalle_material,
                        c.color,
                        c.tipo_de_prenda
                    ORDER BY p.id_producto DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lista as &$item) {
                $item['tallas_array'] = !empty($item['tallas_str'])
                    ? array_map('trim', explode(',', $item['tallas_str']))
                    : [];
            }

            return $lista;
        } catch (PDOException $error) {
            return [];
        }
    }

    public function listarActivos(): array
    {
        try {
            $sql = "SELECT 
                        p.id_producto, 
                        p.nombre_producto, 
                        p.tipo_de_producto, 
                        p.status_producto,
                        c.id_caracteristica,
                        c.detalle_material,
                        c.color,
                        c.tipo_de_prenda,
                        GROUP_CONCAT(DISTINCT pc.talla ORDER BY pc.id_producto_caracteristica SEPARATOR ', ') AS tallas_str
                    FROM producto p
                    LEFT JOIN producto_caracteristica pc 
                        ON p.id_producto = pc.id_producto 
                        AND pc.status_producto_caracteristica = 'activo'
                    LEFT JOIN caracteristica c 
                        ON pc.id_caracteristica = c.id_caracteristica
                    WHERE p.status_producto = 'activo'
                    GROUP BY 
                        p.id_producto, 
                        p.nombre_producto, 
                        p.tipo_de_producto, 
                        p.status_producto,
                        c.id_caracteristica,
                        c.detalle_material,
                        c.color,
                        c.tipo_de_prenda
                    ORDER BY p.id_producto DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lista as &$item) {
                $item['tallas_array'] = !empty($item['tallas_str'])
                    ? array_map('trim', explode(',', $item['tallas_str']))
                    : [];
            }

            return $lista;
        } catch (PDOException $error) {
            return [];
        }
    }

    public function guardar(array $datos = []): array
    {
        try {
            if (empty($datos)) {
                return ["error" => 'No se recibieron datos para procesar.'];
            }

            $id       = !empty($datos['id_producto']) ? intval($datos['id_producto']) : null;
            $nombre   = trim($datos['nombre_producto'] ?? '');
            $tipo     = trim($datos['tipo_de_producto'] ?? '');
            $status   = trim($datos['status_producto'] ?? 'activo');
            $material = trim($datos['detalle_material'] ?? 'General');
            $prenda   = trim($datos['tipo_de_prenda'] ?? $tipo);
            $color    = trim($datos['color'] ?? 'No especificado');
            $tallas   = $datos['tallas'] ?? [];

            if (is_string($tallas)) {
                $tallas = array_map('trim', explode(',', $tallas));
            }
            $tallas = array_values(array_filter($tallas, fn($t) => trim($t) !== ''));

            if (!preg_match($this->expNombre, $nombre)) {
                return ["error" => 'El nombre del producto debe tener entre 3 y 150 caracteres.'];
            }
            if (empty($tipo)) {
                return ["error" => 'Debe seleccionar un tipo de producto válido.'];
            }
            if (empty($tallas)) {
                return ["error" => 'Debe seleccionar al menos una talla o medida.'];
            }
            if ($this->verificarDuplicado($nombre, $id)) {
                return ["error" => 'Ya existe un producto registrado con ese nombre.'];
            }

            $this->id_producto      = $id;
            $this->nombre_producto  = $nombre;
            $this->tipo_de_producto = $tipo;
            $this->status_producto  = $status;
            $this->detalle_material = $material;
            $this->tipo_de_prenda   = $prenda;
            $this->color            = $color;
            $this->tallas           = $tallas;

            if (empty($this->id_producto)) {
                return $this->registrarTransaccional();
            } else {
                return $this->actualizarTransaccional();
            }
        } catch (Exception $error) {
            return ["error" => $error->getMessage()];
        }
    }

    private function verificarDuplicado($nombre, $id = null): bool
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

    /**
     * REGISTRO TRANSACCIONAL
     */
    private function registrarTransaccional(): array
    {
        try {
            $this->conex->beginTransaction();

            // 1. Guardar en 'producto'
            $sqlP = "INSERT INTO `producto` (`nombre_producto`, `tipo_de_producto`, `status_producto`) 
                     VALUES (:nombre, :tipo, :status)";
            $stmtP = $this->conex->prepare($sqlP);
            $stmtP->bindParam(":nombre", $this->nombre_producto);
            $stmtP->bindParam(":tipo", $this->tipo_de_producto);
            $stmtP->bindParam(":status", $this->status_producto);
            $stmtP->execute();
            $idProducto = (int) $this->conex->lastInsertId();

            // 2. Guardar en 'caracteristica' con material, color y prenda
            $sqlC = "INSERT INTO `caracteristica` (`detalle_material`, `color`, `tipo_de_prenda`, `status_caracteristica`) 
                     VALUES (:material, :color, :prenda, 'activo')";
            $stmtC = $this->conex->prepare($sqlC);
            $stmtC->bindParam(":material", $this->detalle_material);
            $stmtC->bindParam(":color", $this->color);
            $stmtC->bindParam(":prenda", $this->tipo_de_prenda);
            $stmtC->execute();
            $idCaracteristica = (int) $this->conex->lastInsertId();

            // 3. Guardar una fila en 'producto_caracteristica' por cada talla seleccionada
            $sqlPC = "INSERT INTO `producto_caracteristica` 
                      (`id_producto`, `id_caracteristica`, `talla`, `status_producto_caracteristica`) 
                      VALUES (?, ?, ?, ?)";
            $stmtPC = $this->conex->prepare($sqlPC);

            foreach ($this->tallas as $talla) {
                $tallaLimpia = trim($talla);
                if ($tallaLimpia !== '') {
                    $stmtPC->execute([$idProducto, $idCaracteristica, $tallaLimpia, $this->status_producto]);
                }
            }

            $this->conex->commit();
            return ["success" => true];
        } catch (Exception $error) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return ["error" => "Error en el registro transaccional: " . $error->getMessage()];
        }
    }

    /**
     * ACTUALIZACIÓN TRANSACCIONAL
     */
    private function actualizarTransaccional(): array
    {
        try {
            $this->conex->beginTransaction();

            // 1. Actualizar 'producto'
            $sqlP = "UPDATE `producto` 
                     SET `nombre_producto` = ?, `tipo_de_producto` = ?, `status_producto` = ? 
                     WHERE `id_producto` = ?";
            $stmtP = $this->conex->prepare($sqlP);
            $stmtP->execute([
                $this->nombre_producto,
                $this->tipo_de_producto,
                $this->status_producto,
                $this->id_producto
            ]);

            // 2. Obtener la característica actual o crearla
            $sqlGetC = "SELECT id_caracteristica FROM `producto_caracteristica` WHERE `id_producto` = ? LIMIT 1";
            $stmtGetC = $this->conex->prepare($sqlGetC);
            $stmtGetC->execute([$this->id_producto]);
            $idCaracteristica = $stmtGetC->fetchColumn();

            if ($idCaracteristica) {
                $sqlUpdC = "UPDATE `caracteristica` 
                            SET `detalle_material` = ?, `color` = ?, `tipo_de_prenda` = ? 
                            WHERE `id_caracteristica` = ?";
                $stmtUpdC = $this->conex->prepare($sqlUpdC);
                $stmtUpdC->execute([$this->detalle_material, $this->color, $this->tipo_de_prenda, $idCaracteristica]);
            } else {
                $sqlInsC = "INSERT INTO `caracteristica` (`detalle_material`, `color`, `tipo_de_prenda`, `status_caracteristica`) 
                            VALUES (?, ?, ?, 'activo')";
                $stmtInsC = $this->conex->prepare($sqlInsC);
                $stmtInsC->execute([$this->detalle_material, $this->color, $this->tipo_de_prenda]);
                $idCaracteristica = (int) $this->conex->lastInsertId();
            }

            // 3. Sincronizar tallas múltiples en 'producto_caracteristica'
            $sqlCur = "SELECT id_producto_caracteristica, talla FROM `producto_caracteristica` WHERE `id_producto` = ?";
            $stmtCur = $this->conex->prepare($sqlCur);
            $stmtCur->execute([$this->id_producto]);
            $existentes = $stmtCur->fetchAll(PDO::FETCH_ASSOC);

            $mapExistentes = [];
            foreach ($existentes as $ex) {
                $mapExistentes[strtolower(trim($ex['talla']))] = $ex['id_producto_caracteristica'];
            }

            $tallasNuevasMap = [];
            $stmtInsPC = $this->conex->prepare("INSERT INTO `producto_caracteristica` (`id_producto`, `id_caracteristica`, `talla`, `status_producto_caracteristica`) VALUES (?, ?, ?, ?)");
            $stmtUpdPC = $this->conex->prepare("UPDATE `producto_caracteristica` SET `id_caracteristica` = ?, `status_producto_caracteristica` = ? WHERE `id_producto_caracteristica` = ?");

            foreach ($this->tallas as $talla) {
                $tallaLimpia = trim($talla);
                if ($tallaLimpia === '') continue;
                $key = strtolower($tallaLimpia);
                $tallasNuevasMap[$key] = true;

                if (isset($mapExistentes[$key])) {
                    $stmtUpdPC->execute([$idCaracteristica, $this->status_producto, $mapExistentes[$key]]);
                } else {
                    $stmtInsPC->execute([$this->id_producto, $idCaracteristica, $tallaLimpia, $this->status_producto]);
                }
            }

            // Inactivar o eliminar tallas desmarcadas
            $stmtCheckDetalle = $this->conex->prepare("SELECT COUNT(*) FROM `detalle_pedido` WHERE `id_producto_caracteristica` = ?");
            $stmtInactivar = $this->conex->prepare("UPDATE `producto_caracteristica` SET `status_producto_caracteristica` = 'inactivo' WHERE `id_producto_caracteristica` = ?");
            $stmtDelete = $this->conex->prepare("DELETE FROM `producto_caracteristica` WHERE `id_producto_caracteristica` = ?");

            foreach ($existentes as $ex) {
                $key = strtolower(trim($ex['talla']));
                if (!isset($tallasNuevasMap[$key])) {
                    $stmtCheckDetalle->execute([$ex['id_producto_caracteristica']]);
                    if ($stmtCheckDetalle->fetchColumn() > 0) {
                        $stmtInactivar->execute([$ex['id_producto_caracteristica']]);
                    } else {
                        $stmtDelete->execute([$ex['id_producto_caracteristica']]);
                    }
                }
            }

            $this->conex->commit();
            return ["success" => true];
        } catch (Exception $error) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return ["error" => "Error al actualizar: " . $error->getMessage()];
        }
    }

    public function getCambiarEstado($id, $nuevoEstado): array
    {
        try {
            $this->conex->beginTransaction();

            $sqlP = "UPDATE `producto` SET `status_producto` = ? WHERE `id_producto` = ?";
            $stmtP = $this->conex->prepare($sqlP);
            $stmtP->execute([$nuevoEstado, $id]);

            $sqlPC = "UPDATE `producto_caracteristica` SET `status_producto_caracteristica` = ? WHERE `id_producto` = ?";
            $stmtPC = $this->conex->prepare($sqlPC);
            $stmtPC->execute([$nuevoEstado, $id]);

            $this->conex->commit();
            return ["success" => true];
        } catch (Exception $error) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return ["error" => $error->getMessage()];
        }
    }
}

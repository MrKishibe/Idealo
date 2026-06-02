<?php

namespace Idealo\Controllers;

use Idealo\Models\UsuarioModel;

use Idealo\Config\Database;
use PDO;
use PDOException;

class InventarioController
{

    public function materiaPrima()
    {
        $materiales = [];

        try {
            $pdo = Database::connect();

            $stmt = $pdo->query("SELECT id_materia_prima, nombre_materia_prima, stock_actual, stock_minimo, id_tipo_materia_prima FROM materia_prima");
            $materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo "Ha ocurrido un error. Por favor contacte al administrador.";
            exit();
        }

        require_once __DIR__ . '/../view/inventario/materia_prima.php';
    }

    public function guardarMateria()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo = Database::connect();

                $stmt = $pdo->prepare("INSERT INTO materia_prima (nombre_materia_prima, id_tipo_materia_prima, stock_actual, stock_minimo, id_unidad_de_medida) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nombre_materia_prima'],
                    $_POST['id_tipo_materia_prima'],
                    $_POST['stock_actual'],
                    $_POST['stock_minimo'],
                    $_POST['id_unidad_de_medida']
                ]);

                header("Location: index.php?controller=inventario&action=materiaPrima");
                exit();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo "Ha ocurrido un error. Por favor contacte al administrador.";
                exit();
            }
        }
    }

    public function editarMateria()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo = Database::connect();

                $stmt = $pdo->prepare("UPDATE materia_prima SET nombre_materia_prima = ?, id_tipo_materia_prima = ?, stock_actual = ?, stock_minimo = ?, id_unidad_de_medida = ? WHERE id_materia_prima = ?");
                $stmt->execute([
                    $_POST['nombre_materia_prima'],
                    $_POST['id_tipo_materia_prima'],
                    $_POST['stock_actual'],
                    $_POST['stock_minimo'],
                    $_POST['id_unidad_de_medida'],
                    $_POST['id_materia_prima']
                ]);

                header("Location: index.php?controller=inventario&action=materiaPrima");
                exit();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo "Ha ocurrido un error. Por favor contacte al administrador.";
                exit();
            }
        }
    }

    public function eliminarMateria()
    {
        if (isset($_GET['id'])) {
            try {
                $pdo = Database::connect();

                $stmt = $pdo->prepare("DELETE FROM materia_prima WHERE id_materia_prima = ?");
                $stmt->execute([$_GET['id']]);

                header("Location: index.php?controller=inventario&action=materiaPrima");
                exit();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo "Ha ocurrido un error. Por favor contacte al administrador.";
                exit();
            }
        }
    }
}

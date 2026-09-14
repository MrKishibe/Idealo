<?php

namespace Idealo\Models;

use Idealo\Config\Database;
use PDO;
use PDOStatement;
use Exception;

require_once __DIR__ .
    '/../../config/Database.php';

abstract class ModeloBase
{
    private Database $database;
    private PDO $pdo;

    public function __construct()
    {
        $this->database = new Database();

        $this->pdo = $this->database->connect();
    }

    protected function conexion(): PDO
    {
        return $this->pdo;
    }

    protected function ejecutar(
        string $sql,
        array $parametros = []
    ): PDOStatement {
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($parametros);

        return $stmt;
    }

    protected function verificarExistencia(
        string $tabla,
        string $campo,
        int $id
    ): bool {
        $tablasPermitidas = [
            'cliente',
            'tipo_de_pedido',
            'servicio',
            'producto',
            'producto_caracteristica',
            'caracteristica',
            'pedido'
        ];

        if (!in_array($tabla, $tablasPermitidas, true)) {
            throw new Exception(
                'La tabla solicitada no está permitida.'
            );
        }

        $sql = "
            SELECT COUNT(*)
            FROM {$tabla}
            WHERE {$campo} = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
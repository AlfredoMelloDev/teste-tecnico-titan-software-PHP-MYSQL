<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    // Reaproveita a mesma conexão durante toda a requisição.
    private static ?PDO $connection = null;

    // Impede a criação direta de objetos desta classe.
    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // As credenciais ficam separadas para não misturar configuração com a conexão.
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                // Faz o PDO lançar exceções quando uma consulta falhar.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // Retorna os registros usando o nome das colunas.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Usa consultas preparadas pelo próprio MySQL.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$connection;
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Base dos modelos: conexão compartilhada e paginação no servidor (RNF06).
 */
abstract class Model
{
    protected PDO $connection;

    public function __construct()
    {
        $this->connection = Database::connection();
    }

    /**
     * Executa a consulta de uma página e a contagem do resultado completo.
     * Os dois SQLs recebem os mesmos parâmetros (sempre preparados).
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, totalPages: int}
     */
    protected function paginateQuery(
        string $selectSql,
        string $countSql,
        array $parameters,
        int $page,
        int $perPage
    ): array {
        $count = $this->connection->prepare($countSql);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);

        $statement = $this->connection->prepare($selectSql . ' LIMIT :limit OFFSET :offset');

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }
}

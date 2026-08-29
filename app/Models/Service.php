<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Service
{
    private PDO $connection;

    public function __construct()
    {
        // Reutiliza a conexão PDO criada pela classe Database.
        $this->connection = Database::connection();
    }

    public function create(
        string $description,
        string $price,
        int $userId
    ): int {
        $sql = '
            INSERT INTO `service` (
                description,
                price,
                user_id_user
            ) VALUES (
                :description,
                :price,
                :user_id
            )
        ';

        /*
         * finished_at e commission_user começam vazios.
         * Por isso, todo serviço novo é considerado pendente.
         */
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'description' => $description,
            'price' => $price,
            'user_id' => $userId,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $serviceId): ?array
    {
        $sql = '
            SELECT
                id_service,
                description,
                price,
                created_at,
                update_at,
                finished_at,
                commission_user,
                user_id_user
            FROM `service`
            WHERE id_service = :service_id
            LIMIT 1
        ';

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'service_id' => $serviceId,
        ]);

        $service = $statement->fetch();

        // Retorna null quando o serviço solicitado não existir.
        return $service !== false ? $service : null;
    }


    // O método retorna todos os serviços cadastrados, com a possibilidade de aplicar filtros.
    /**
     * @param array{
     *     description?: string,
     *     status?: string,
     *     user?: string,
     *     start_date?: string,
     *     end_date?: string
     * } $filters
     */

    public function findAllWithUser(array $filters = []): array
    {
        $sql = '
        SELECT
            service.id_service,
            service.description,
            service.price,
            service.created_at,
            service.finished_at,
            service.commission_user,
            user.name AS user_name,
            CASE
                WHEN service.finished_at IS NULL
                    THEN "Pendente"
                ELSE "Finalizado"
            END AS status
        FROM `service`
        INNER JOIN `user`
            ON user.id_user = service.user_id_user
        WHERE 1 = 1
    ';

        $parameters = [];

        // A descrição pode ser localizada mesmo quando digitada parcialmente.
        if (($filters['description'] ?? '') !== '') {
            $sql .= '
            AND service.description LIKE :description
        ';

            $parameters['description'] =
                '%' . $filters['description'] . '%';
        }

        // O nome também utiliza busca parcial para facilitar o filtro.
        if (($filters['user'] ?? '') !== '') {
            $sql .= '
            AND user.name LIKE :user_name
        ';

            $parameters['user_name'] =
                '%' . $filters['user'] . '%';
        }

        /*
     * O status não existe como coluna no banco.
     * Ele é determinado pela presença da data de finalização.
     */
        if (($filters['status'] ?? '') === 'Pendente') {
            $sql .= '
            AND service.finished_at IS NULL
        ';
        }

        if (($filters['status'] ?? '') === 'Finalizado') {
            $sql .= '
            AND service.finished_at IS NOT NULL
        ';
        }

        if (($filters['start_date'] ?? '') !== '') {
            $sql .= '
            AND service.created_at >= :start_date
        ';

            $parameters['start_date'] =
                $filters['start_date'] . ' 00:00:00';
        }

        if (($filters['end_date'] ?? '') !== '') {
            $sql .= '
            AND service.created_at <= :end_date
        ';

            $parameters['end_date'] =
                $filters['end_date'] . ' 23:59:59';
        }

        $sql .= '
        ORDER BY
            service.created_at DESC,
            service.id_service DESC
    ';

        // Todos os valores recebidos pelo filtro continuam parametrizados.
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function totalValueByUser(int $userId): string
    {
        $sql = '
            SELECT COALESCE(SUM(price), 0.00)
            FROM `service`
            WHERE user_id_user = :user_id
        ';

        // O indicador considera somente os serviços do usuário logado.
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
        ]);

        $total = $statement->fetchColumn();

        return is_string($total) ? $total : '0.00';
    }

    public function findLatestPendingByUser(int $userId): array
    {
        $sql = '
            SELECT
                id_service,
                description,
                price,
                created_at
            FROM `service`
            WHERE user_id_user = :user_id
              AND finished_at IS NULL
            ORDER BY
                created_at DESC,
                id_service DESC
            LIMIT 5
        ';

        /*
         * A ausência da data de finalização define o serviço como pendente.
         * A lista fica limitada aos cinco registros mais recentes.
         */
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
        ]);

        return $statement->fetchAll();
    }

    public function update(
        int $serviceId,
        string $description,
        string $price
    ): void {
        $sql = '
        UPDATE `service`
        SET
            description = :description,
            price = :price
        WHERE id_service = :service_id
          AND finished_at IS NULL
    ';

        // Serviços finalizados não podem ter seus valores alterados.
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'service_id' => $serviceId,
            'description' => $description,
            'price' => $price,
        ]);
    }

    /**
     * Exclui um serviço pelo seu identificador.
     *
     * O retorno informa se algum registro realmente foi removido.
     */
    public function delete(int $serviceId): bool
    {
        $sql = '
        DELETE FROM `service`
        WHERE id_service = :service_id
    ';

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'service_id' => $serviceId,
        ]);

        return $statement->rowCount() > 0;
    }

    // Finaliza um serviço pendente e registra o valor da comissão.
    public function finish(int $serviceId, float $commission): bool
    {
        $sql = '
        UPDATE `service`
        SET
            finished_at = CURRENT_TIMESTAMP,
            commission_user = :commission
        WHERE id_service = :service_id
          AND finished_at IS NULL
    ';

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            // O formato com ponto decimal é o esperado pelo campo DECIMAL do MySQL.
            'commission' => number_format($commission, 2, '.', ''),
            'service_id' => $serviceId,
        ]);

        // Nenhuma linha alterada também indica que o serviço já estava finalizado.
        return $statement->rowCount() > 0;
    }
}

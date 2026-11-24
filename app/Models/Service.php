<?php

declare(strict_types=1);

namespace App\Models;

final class Service extends Model
{
    // Colunas comuns às consultas, já com o responsável e o cliente.
    private const SELECT = '
        SELECT
            service.id_service,
            service.description,
            service.price,
            service.created_at,
            service.finished_at,
            service.commission_user,
            service.commission_rate,
            service.user_id,
            service.client_id,
            service.finished_by,
            user.name AS user_name,
            user.email AS user_email,
            client.name AS client_name,
            client.active AS client_active
        FROM `service`
        INNER JOIN `user` ON user.id_user = service.user_id
        INNER JOIN `client` ON client.id_client = service.client_id
    ';

    // Todo serviço novo começa pendente: finished_at e comissão vazios (RN08).
    public function create(string $description, string $price, int $userId, int $clientId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `service` (description, price, user_id, client_id)
             VALUES (:description, :price, :user_id, :client_id)'
        );
        $statement->execute([
            'description' => $description,
            'price' => $price,
            'user_id' => $userId,
            'client_id' => $clientId,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $serviceId): ?array
    {
        $statement = $this->connection->prepare(self::SELECT . ' WHERE service.id_service = :id LIMIT 1');
        $statement->execute(['id' => $serviceId]);

        return $statement->fetch() ?: null;
    }

    /**
     * Lê o serviço e bloqueia a linha até o fim da transação (SELECT ... FOR UPDATE).
     * Deve ser chamado dentro de uma transação aberta.
     */
    public function findByIdForUpdate(int $serviceId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_service, description, price, finished_at, user_id, client_id
             FROM `service` WHERE id_service = :id FOR UPDATE'
        );
        $statement->execute(['id' => $serviceId]);

        return $statement->fetch() ?: null;
    }

    // A condição finished_at IS NULL impede alterar um serviço finalizado (RN09).
    public function updatePending(
        int $serviceId,
        string $description,
        string $price,
        int $clientId,
        int $userId
    ): void {
        $statement = $this->connection->prepare(
            'UPDATE `service`
             SET description = :description, price = :price, client_id = :client_id, user_id = :user_id
             WHERE id_service = :id AND finished_at IS NULL'
        );
        $statement->execute([
            'id' => $serviceId,
            'description' => $description,
            'price' => $price,
            'client_id' => $clientId,
            'user_id' => $userId,
        ]);
    }

    // Exclui somente se o serviço ainda estiver pendente (RN10).
    public function deletePending(int $serviceId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM `service` WHERE id_service = :id AND finished_at IS NULL'
        );
        $statement->execute(['id' => $serviceId]);

        return $statement->rowCount() > 0;
    }

    // Grava data, comissão, percentual e autor da finalização em um único UPDATE (RN11, RN13).
    public function markFinished(int $serviceId, string $commission, string $rate, int $finishedBy): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE `service`
             SET finished_at = NOW(), commission_user = :commission,
                 commission_rate = :rate, finished_by = :finished_by
             WHERE id_service = :id AND finished_at IS NULL'
        );
        $statement->execute([
            'id' => $serviceId,
            'commission' => $commission,
            'rate' => $rate,
            'finished_by' => $finishedBy,
        ]);

        return $statement->rowCount() > 0;
    }

    /**
     * Listagem com filtros combináveis (RF12, RF13). Quando $scopeUserId é
     * informado (funcionário), o resultado fica restrito aos serviços dele (RN18).
     *
     * @param array{description: string, client: string, user_id: int|null,
     *              status: string, start_date: string, end_date: string} $filters
     */
    public function search(array $filters, ?int $scopeUserId, int $page, int $perPage): array
    {
        [$where, $parameters] = $this->listConditions($filters, $scopeUserId);

        return $this->paginateQuery(
            self::SELECT . $where . ' ORDER BY service.created_at DESC, service.id_service DESC',
            'SELECT COUNT(*) FROM `service`
             INNER JOIN `user` ON user.id_user = service.user_id
             INNER JOIN `client` ON client.id_client = service.client_id' . $where,
            $parameters,
            $page,
            $perPage
        );
    }

    private function listConditions(array $filters, ?int $scopeUserId): array
    {
        $conditions = [];
        $parameters = [];

        if ($scopeUserId !== null) {
            $conditions[] = 'service.user_id = :scope_user';
            $parameters['scope_user'] = $scopeUserId;
        }

        if ($filters['description'] !== '') {
            $conditions[] = 'service.description LIKE :description';
            $parameters['description'] = '%' . $filters['description'] . '%';
        }

        if ($filters['client'] !== '') {
            $conditions[] = 'client.name LIKE :client';
            $parameters['client'] = '%' . $filters['client'] . '%';
        }

        if ($filters['user_id'] !== null) {
            $conditions[] = 'service.user_id = :user_id';
            $parameters['user_id'] = $filters['user_id'];
        }

        // A situação não é coluna: é derivada de finished_at.
        if ($filters['status'] === 'pendente') {
            $conditions[] = 'service.finished_at IS NULL';
        } elseif ($filters['status'] === 'finalizado') {
            $conditions[] = 'service.finished_at IS NOT NULL';
        }

        // O período usa a data de cadastro, com os dias inicial e final completos (RN14).
        if ($filters['start_date'] !== '') {
            $conditions[] = 'service.created_at >= :start_date';
            $parameters['start_date'] = $filters['start_date'] . ' 00:00:00';
        }

        if ($filters['end_date'] !== '') {
            $conditions[] = 'service.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)';
            $parameters['end_date'] = $filters['end_date'];
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$where, $parameters];
    }

    /**
     * Indicadores do dashboard (RF14). Não recebem os filtros da listagem (RN16).
     */
    public function summary(?int $scopeUserId): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total_services,
                    COALESCE(SUM(finished_at IS NULL), 0) AS pending_services,
                    COALESCE(SUM(finished_at IS NOT NULL), 0) AS finished_services,
                    COALESCE(SUM(price), 0) AS total_value,
                    COALESCE(SUM(commission_user), 0) AS total_commission
                FROM `service`';
        $parameters = [];

        if ($scopeUserId !== null) {
            $sql .= ' WHERE user_id = :user_id';
            $parameters['user_id'] = $scopeUserId;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch() ?: [];

        return [
            'total_services' => (int) ($row['total_services'] ?? 0),
            'pending_services' => (int) ($row['pending_services'] ?? 0),
            'finished_services' => (int) ($row['finished_services'] ?? 0),
            'total_value' => (string) ($row['total_value'] ?? '0'),
            'total_commission' => (string) ($row['total_commission'] ?? '0'),
        ];
    }

    // Até cinco pendências mais recentes (RF14).
    public function latestPending(?int $scopeUserId): array
    {
        $sql = self::SELECT . ' WHERE service.finished_at IS NULL';
        $parameters = [];

        if ($scopeUserId !== null) {
            $sql .= ' AND service.user_id = :user_id';
            $parameters['user_id'] = $scopeUserId;
        }

        $statement = $this->connection->prepare(
            $sql . ' ORDER BY service.created_at DESC, service.id_service DESC LIMIT 5'
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * Relatório de serviços finalizados (RF15), filtrado por responsável e
     * período de conclusão (RN15). Os totais valem para todo o resultado (RN17).
     *
     * @param array{user_id: int|null, start_date: string, end_date: string} $filters
     */
    public function commissionReport(array $filters, ?int $scopeUserId, int $page, int $perPage): array
    {
        $conditions = ['service.finished_at IS NOT NULL'];
        $parameters = [];

        $userId = $scopeUserId ?? $filters['user_id'];

        if ($userId !== null) {
            $conditions[] = 'service.user_id = :user_id';
            $parameters['user_id'] = $userId;
        }

        if ($filters['start_date'] !== '') {
            $conditions[] = 'service.finished_at >= :start_date';
            $parameters['start_date'] = $filters['start_date'] . ' 00:00:00';
        }

        if ($filters['end_date'] !== '') {
            $conditions[] = 'service.finished_at < DATE_ADD(:end_date, INTERVAL 1 DAY)';
            $parameters['end_date'] = $filters['end_date'];
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);

        $result = $this->paginateQuery(
            self::SELECT . $where . ' ORDER BY service.finished_at DESC, service.id_service DESC',
            'SELECT COUNT(*) FROM `service`' . $where,
            $parameters,
            $page,
            $perPage
        );

        $totals = $this->connection->prepare(
            'SELECT COUNT(*) AS services, COALESCE(SUM(price), 0) AS value,
                    COALESCE(SUM(commission_user), 0) AS commission
             FROM `service`' . $where
        );
        $totals->execute($parameters);
        $result['totals'] = $totals->fetch();

        return $result;
    }
}

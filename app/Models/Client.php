<?php

declare(strict_types=1);

namespace App\Models;

final class Client extends Model
{
    private const COLUMNS = 'id_client, name, phone, email, active, created_at';

    public function findById(int $clientId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::COLUMNS . ' FROM `client` WHERE id_client = :id LIMIT 1'
        );
        $statement->execute(['id' => $clientId]);

        return $statement->fetch() ?: null;
    }

    // Telefone e e-mail são opcionais: chegam como null quando vazios (RN05).
    public function create(string $name, ?string $phone, ?string $email): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `client` (name, phone, email) VALUES (:name, :phone, :email)'
        );
        $statement->execute(['name' => $name, 'phone' => $phone, 'email' => $email]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $clientId, string $name, ?string $phone, ?string $email): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `client` SET name = :name, phone = :phone, email = :email WHERE id_client = :id'
        );
        $statement->execute([
            'id' => $clientId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    // Inativa sem apagar: os serviços existentes mantêm o vínculo (RN04).
    public function deactivate(int $clientId): void
    {
        $statement = $this->connection->prepare('UPDATE `client` SET active = 0 WHERE id_client = :id');
        $statement->execute(['id' => $clientId]);
    }

    /**
     * Gestor: ativos e inativos (UC05). Funcionário: somente ativos (UC06).
     *
     * @return array{rows: array, total: int, page: int, totalPages: int}
     */
    public function paginate(string $search, bool $onlyActive, int $page, int $perPage): array
    {
        $conditions = [];
        $parameters = [];

        if ($onlyActive) {
            $conditions[] = 'active = 1';
        }

        if ($search !== '') {
            $conditions[] = 'name LIKE :search';
            $parameters['search'] = '%' . $search . '%';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return $this->paginateQuery(
            'SELECT ' . self::COLUMNS . " FROM `client` {$where} ORDER BY active DESC, name ASC",
            "SELECT COUNT(*) FROM `client` {$where}",
            $parameters,
            $page,
            $perPage
        );
    }

    // Clientes que podem ser vinculados a novos serviços (RN05).
    public function listActive(): array
    {
        return $this->connection
            ->query('SELECT id_client, name FROM `client` WHERE active = 1 ORDER BY name')
            ->fetchAll();
    }

    // O funcionário pode consultar um cliente inativo se ele estiver em um serviço seu (UC06 FA1).
    public function isLinkedToUser(int $clientId, int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM `service` WHERE client_id = :client_id AND user_id = :user_id'
        );
        $statement->execute(['client_id' => $clientId, 'user_id' => $userId]);

        return (int) $statement->fetchColumn() > 0;
    }
}

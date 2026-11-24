<?php

declare(strict_types=1);

namespace App\Models;

final class User extends Model
{
    private const COLUMNS = 'id_user, name, email, role, active, created_at';

    // Usado no login: inclui o hash da senha e ignora usuários inativos (RN01).
    public function findActiveByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::COLUMNS . ', password
             FROM `user`
             WHERE email = :email AND active = 1
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        return $statement->fetch() ?: null;
    }

    // Usado a cada requisição para confirmar que o usuário logado continua ativo (RC1).
    public function findActiveById(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::COLUMNS . ' FROM `user` WHERE id_user = :id AND active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        return $statement->fetch() ?: null;
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::COLUMNS . ' FROM `user` WHERE id_user = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        return $statement->fetch() ?: null;
    }

    // O e-mail é único (RN02); na edição o próprio usuário é desconsiderado.
    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM `user` WHERE email = :email AND id_user <> :except_id'
        );
        $statement->execute([
            'email' => $email,
            'except_id' => $exceptUserId ?? 0,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        // A senha chega pronta (hash); o texto da senha nunca é gravado (RNF02).
        $statement = $this->connection->prepare(
            'INSERT INTO `user` (name, email, password, role) VALUES (:name, :email, :password, :role)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $userId, string $name, string $email, string $role): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `user` SET name = :name, email = :email, role = :role WHERE id_user = :id'
        );
        $statement->execute([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]);
    }

    // Inativa sem apagar: os serviços vinculados continuam preservados (RN04).
    public function deactivate(int $userId): void
    {
        $statement = $this->connection->prepare('UPDATE `user` SET active = 0 WHERE id_user = :id');
        $statement->execute(['id' => $userId]);
    }

    /**
     * Conta os gestores ativos, bloqueando essas linhas até o fim da transação.
     * Assim duas alterações simultâneas não deixam o sistema sem gestor (RN03).
     */
    public function lockActiveManagers(): array
    {
        $statement = $this->connection->query(
            "SELECT id_user FROM `user` WHERE role = 'gestor' AND active = 1 FOR UPDATE"
        );

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @return array{rows: array, total: int, page: int, totalPages: int}
     */
    public function paginate(string $search, int $page, int $perPage): array
    {
        $where = '';
        $parameters = [];

        if ($search !== '') {
            $where = 'WHERE name LIKE :search_name OR email LIKE :search_email';
            $parameters['search_name'] = '%' . $search . '%';
            $parameters['search_email'] = '%' . $search . '%';
        }

        return $this->paginateQuery(
            'SELECT ' . self::COLUMNS . " FROM `user` {$where} ORDER BY active DESC, name ASC",
            "SELECT COUNT(*) FROM `user` {$where}",
            $parameters,
            $page,
            $perPage
        );
    }

    // Usuários ativos que podem ser responsáveis por serviços (RN07).
    public function listActive(): array
    {
        return $this->connection
            ->query('SELECT id_user, name, role FROM `user` WHERE active = 1 ORDER BY name')
            ->fetchAll();
    }

    // Todos os usuários (inclusive inativos), para os filtros por responsável.
    public function listAll(): array
    {
        return $this->connection
            ->query('SELECT id_user, name, active FROM `user` ORDER BY name')
            ->fetchAll();
    }
}

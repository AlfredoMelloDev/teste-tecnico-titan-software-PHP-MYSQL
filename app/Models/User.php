<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    private PDO $connection;

    public function __construct()
    {
        // Utiliza a conexão central para não abrir um novo acesso a cada consulta.
        $this->connection = Database::connection();
    }

    public function findActiveByEmail(string $email): ?array
    {
        // Usuários desativados não podem acessar o sistema.
        $sql = '
            SELECT
                id_user,
                name,
                email,
                password
            FROM `user`
            WHERE email = :email
              AND ativo = 1
            LIMIT 1
        ';

        // O parâmetro nomeado evita inserir o e-mail diretamente no SQL.
        $statement = $this->connection->prepare($sql);
        $statement->execute(['email' => $email]);

        $user = $statement->fetch();

        // Retorna null quando nenhum usuário ativo for encontrado.
        return $user !== false ? $user : null;
    }

    public function emailExists(string $email): bool
    {
        // Basta contar os registros, pois não precisamos carregar o usuário inteiro.
        $sql = '
            SELECT COUNT(*)
            FROM `user`
            WHERE email = :email
        ';

        $statement = $this->connection->prepare($sql);
        $statement->execute(['email' => $email]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash
    ): int {
        $sql = '
            INSERT INTO `user` (
                name,
                email,
                password
            ) VALUES (
                :name,
                :email,
                :password
            )
        ';

        // A senha chega pronta porque a proteção pertence à regra de cadastro.
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
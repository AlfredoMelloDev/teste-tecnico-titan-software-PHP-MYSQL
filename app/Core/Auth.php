<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    /**
     * @param array{
     *     id_user: int|string,
     *     name: string,
     *     email: string
     * } $user
     */
    public static function login(array $user): void
    {
        // Evita manter o mesmo identificador usado antes do login.
        session_regenerate_id(true);

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id_user'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth_user']['id']);
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public static function user(): ?array
    {
        return self::check()
            ? $_SESSION['auth_user']
            : null;
    }

    public static function id(): ?int
    {
        return self::check()
            ? (int) $_SESSION['auth_user']['id']
            : null;
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        header('Location: /login');
        exit;
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);

        // Também renova a sessão após a saída do usuário.
        session_regenerate_id(true);
    }
}
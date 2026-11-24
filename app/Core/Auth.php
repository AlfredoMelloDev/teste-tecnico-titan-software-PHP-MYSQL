<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public const ROLE_MANAGER = 'gestor';
    public const ROLE_EMPLOYEE = 'funcionario';

    /**
     * @param array{id_user: int|string, name: string, email: string, role: string} $user
     */
    public static function login(array $user): void
    {
        // Evita manter o mesmo ID de sessão usado antes do login (RNF02).
        session_regenerate_id(true);

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id_user'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    // Verifica se existe um usuário na sessão.
    public static function check(): bool
    {
        return isset($_SESSION['auth_user']['id']);
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}|null
     */
    public static function user(): ?array
    {
        return self::check() ? $_SESSION['auth_user'] : null;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['auth_user']['id'] : null;
    }

    public static function isManager(): bool
    {
        return (self::user()['role'] ?? null) === self::ROLE_MANAGER;
    }

    /**
     * Exige sessão autenticada de um usuário que continua ativo (RC1).
     * A cada requisição o cadastro é lido de novo: se o usuário foi inativado,
     * a sessão é encerrada; se o perfil mudou, a sessão passa a usar o novo perfil.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::error('Faça login para continuar.');
            header('Location: /login');
            exit;
        }

        $current = (new User())->findActiveById((int) self::id());

        if ($current === null) {
            self::logout();
            Flash::error('Sua sessão foi encerrada. Entre novamente.');
            header('Location: /login');
            exit;
        }

        $_SESSION['auth_user']['name'] = $current['name'];
        $_SESSION['auth_user']['email'] = $current['email'];
        $_SESSION['auth_user']['role'] = $current['role'];
    }

    // Exige o perfil de gestor; a verificação é feita no servidor, não só no menu (RC2).
    public static function requireManager(): void
    {
        self::requireLogin();

        if (!self::isManager()) {
            Flash::error('Você não tem permissão para acessar esta página.');
            header('Location: /dashboard');
            exit;
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);

        // Também renova a sessão após a saída do usuário (RNF02).
        session_regenerate_id(true);
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    private function __construct() {}

    
    // Retorna o token da sessão ou cria um quando necessário.
    public static function token(): string
    {
        $token = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($token) || $token === '') {

            // Criação do TOKEN CSRF
            // Cada sessão recebe um valor imprevisível para proteger os formulários.
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    // Compara o token recebido sem expor diferenças de tempo na comparação.
    public static function validate(mixed $submittedToken): bool
    {
        if (!is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        // O token da sessão pode não existir quando a sessão expira ou o usuário abre uma nova aba.
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }
}

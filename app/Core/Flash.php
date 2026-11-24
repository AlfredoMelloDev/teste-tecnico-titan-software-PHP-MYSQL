<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mensagens e dados de formulário que sobrevivem a um único redirecionamento.
 */
final class Flash
{
    private const MESSAGES = 'flash_messages';
    private const OLD = 'flash_old';

    private function __construct()
    {
    }

    public static function success(string $message): void
    {
        $_SESSION[self::MESSAGES]['success'] = $message;
    }

    public static function error(string $message): void
    {
        $_SESSION[self::MESSAGES]['error'] = $message;
    }

    /**
     * Retorna as mensagens e as remove da sessão, para aparecerem uma única vez.
     *
     * @return array{success?: string, error?: string}
     */
    public static function pullMessages(): array
    {
        $messages = $_SESSION[self::MESSAGES] ?? [];
        unset($_SESSION[self::MESSAGES]);

        return is_array($messages) ? $messages : [];
    }

    // Guarda o que foi digitado para preencher o formulário novamente após um erro (RC4).
    public static function setOld(array $data): void
    {
        $_SESSION[self::OLD] = $data;
    }

    public static function pullOld(): array
    {
        $old = $_SESSION[self::OLD] ?? [];
        unset($_SESSION[self::OLD]);

        return is_array($old) ? $old : [];
    }
}

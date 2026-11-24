<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Registra detalhes técnicos em storage/logs, fora da pasta pública (RNF08).
 * O usuário vê apenas mensagens simples, sem SQL, credenciais ou caminhos.
 */
final class Logger
{
    private function __construct()
    {
    }

    public static function error(string $context, ?Throwable $exception = null): void
    {
        $line = sprintf(
            '[%s] %s%s%s',
            date('Y-m-d H:i:s'),
            $context,
            $exception !== null
                ? ' | ' . get_class($exception) . ': ' . $exception->getMessage()
                    . ' em ' . $exception->getFile() . ':' . $exception->getLine()
                : '',
            PHP_EOL
        );

        error_log($line, 3, dirname(__DIR__, 2) . '/storage/logs/app.log');
    }
}

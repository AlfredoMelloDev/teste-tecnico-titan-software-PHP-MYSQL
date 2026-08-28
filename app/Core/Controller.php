<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__)
            . '/Views/'
            . $view
            . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException(
                "A view '{$view}' não foi encontrada."
            );
        }

        // Transforma os dados recebidos em variáveis disponíveis na view.
        extract($data, EXTR_SKIP);

        require $viewFile;
    }
}
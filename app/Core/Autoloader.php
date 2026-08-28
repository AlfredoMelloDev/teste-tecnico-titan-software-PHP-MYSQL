<?php

declare(strict_types=1);

// Carrega automaticamente as classes do projeto sem depender do Composer.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    // Ignora classes que não pertencem ao namespace da aplicação.
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);

    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR
        . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
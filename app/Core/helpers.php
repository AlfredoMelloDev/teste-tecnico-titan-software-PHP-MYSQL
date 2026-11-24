<?php

declare(strict_types=1);

/*
 * Funções de apoio às views. Ficam fora das classes porque são usadas
 * em praticamente todas as telas apenas para formatar e escapar dados.
 */

// Escapa o conteúdo digitado pelos usuários antes de exibi-lo (RNF03).
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Exibe valores monetários em reais: R$ 1.234,56 (RNF05).
function money(mixed $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

// Exibe datas no formato dia/mês/ano (RNF05).
function date_br(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

// Exibe data e hora no formato dia/mês/ano hora:minuto.
function datetime_br(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '—' : date('d/m/Y H:i', $timestamp);
}

// Campo oculto com o token CSRF, usado em todos os formulários POST.
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="'
        . e(\App\Core\Csrf::token()) . '">';
}

// Monta uma URL mantendo os filtros atuais e trocando apenas alguns parâmetros.
function url_with(string $path, array $query, array $changes = []): string
{
    $parameters = array_filter(
        array_merge($query, $changes),
        static fn ($value): bool => $value !== null
    );

    return $parameters === []
        ? $path
        : $path . '?' . http_build_query($parameters);
}

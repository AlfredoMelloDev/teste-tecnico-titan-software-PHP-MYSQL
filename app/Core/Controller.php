<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use RuntimeException;

abstract class Controller
{
    // Quantidade de registros por página nas listagens e no relatório (RNF06).
    protected const PER_PAGE = 25;

    // Renderiza uma view e envia a resposta para o navegador.
    protected function view(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("A view '{$view}' não foi encontrada.");
        }

        // As mensagens temporárias ficam disponíveis para o layout.
        $data['flash'] = $data['flash'] ?? Flash::pullMessages();

        // Transforma os dados recebidos em variáveis disponíveis na view.
        extract($data, EXTR_SKIP);

        require $viewFile;
    }

    protected function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }

    // Toda operação que altera dados valida o token CSRF antes de qualquer coisa (RC3).
    protected function requireCsrf(string $redirectTo): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Flash::error('A página expirou. Atualize e tente novamente.');
            $this->redirect($redirectTo);
        }
    }

    // Lê um identificador inteiro positivo da requisição.
    protected function intParam(array $source, string $key): ?int
    {
        $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT);

        return is_int($value) && $value > 0 ? $value : null;
    }

    // Página atual da listagem (1 quando ausente ou inválida).
    protected function currentPage(): int
    {
        return $this->intParam($_GET, 'page') ?? 1;
    }

    /**
     * Valida uma data no formato do campo date (AAAA-MM-DD).
     * Retorna null para data vazia e false para data inválida (ex.: 31/02).
     */
    protected function parseDate(string $date): string|null|false
    {
        if ($date === '') {
            return null;
        }

        $object = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($object === false || $object->format('Y-m-d') !== $date) {
            return false;
        }

        return $date;
    }

    /**
     * Valida um período (RN14/RN15): aceita um só limite, rejeita datas
     * inválidas e início posterior ao fim.
     *
     * @return array{0: string, 1: string, 2: string|null} início, fim e mensagem de erro
     */
    protected function parsePeriod(string $start, string $end): array
    {
        $startDate = $this->parseDate($start);
        $endDate = $this->parseDate($end);

        if ($startDate === false || $endDate === false) {
            return ['', '', 'Informe uma data válida no período (dia/mês/ano).'];
        }

        if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
            return ['', '', 'A data inicial não pode ser posterior à data final.'];
        }

        return [$startDate ?? '', $endDate ?? '', null];
    }
}

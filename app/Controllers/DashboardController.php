<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Service;
use DateTimeImmutable;

final class DashboardController extends Controller
{
    private Service $services;

    public function __construct()
    {
        // O Model concentra as consultas relacionadas aos serviços.
        $this->services = new Service();
    }

    public function index(): void
    {
        // Impede o acesso ao dashboard sem uma sessão autenticada.
        Auth::requireLogin();

        $userId = (int) Auth::id();

        // Recupera mensagens geradas por cadastros e outras operações.
        $success = $_SESSION['dashboard_success'] ?? null;
        $error = $_SESSION['dashboard_error'] ?? null;

        // As mensagens devem aparecer somente uma vez.
        unset(
            $_SESSION['dashboard_success'],
            $_SESSION['dashboard_error']
        );

        $status = trim($_GET['status'] ?? '');

        // Somente os dois estados conhecidos podem chegar à consulta.
        if (!in_array($status, ['Pendente', 'Finalizado'], true)) {
            $status = '';
        }

        /*
         * Os filtros são obtidos pela URL porque não modificam registros.
         * Dessa forma, a busca pode ser atualizada ou compartilhada.
         */
        $filters = [
            'description' => trim($_GET['description'] ?? ''),
            'status' => $status,
            'user' => trim($_GET['user'] ?? ''),
            'start_date' => $this->validDate(
                trim($_GET['start_date'] ?? '')
            ),
            'end_date' => $this->validDate(
                trim($_GET['end_date'] ?? '')
            ),
        ];

        if (
            $filters['start_date'] !== ''
            && $filters['end_date'] !== ''
            && $filters['start_date'] > $filters['end_date']
        ) {
            $error = 'A data inicial não pode ser maior que a data final.';

            // Evita executar uma consulta com um período invertido.
            $filters['start_date'] = '';
            $filters['end_date'] = '';
        }

        // A listagem geral considera os filtros informados pelo usuário.
        $services = $this->services->findAllWithUser($filters);

        // Os indicadores continuam relacionados ao usuário autenticado.
        $totalValue = $this->services->totalValueByUser($userId);
        $pendingServices = $this->services
            ->findLatestPendingByUser($userId);

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'currentDate' => date('d/m/Y'),
            'success' => $success,
            'error' => $error,
            'services' => $services,
            'totalValue' => $totalValue,
            'pendingServices' => $pendingServices,
            'filters' => $filters,
        ]);
    }

    private function validDate(string $date): string
    {
        if ($date === '') {
            return '';
        }

        $dateObject = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        /*
         * A comparação evita aceitar datas que o PHP tenha
         * corrigido automaticamente, como 31 de fevereiro.
         */
        if (
            $dateObject === false
            || $dateObject->format('Y-m-d') !== $date
        ) {
            return '';
        }

        return $date;
    }
}
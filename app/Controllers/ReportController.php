<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Service;
use App\Models\User;

/**
 * UC13 - Relatório de serviços finalizados e comissões.
 */
final class ReportController extends Controller
{
    public function commissions(): void
    {
        Auth::requireLogin();

        $isManager = Auth::isManager();

        // Sem período informado, o relatório abre com o mês atual.
        $hasPeriod = isset($_GET['start_date']) || isset($_GET['end_date']);
        $startInput = trim($_GET['start_date'] ?? ($hasPeriod ? '' : date('Y-m-01')));
        $endInput = trim($_GET['end_date'] ?? ($hasPeriod ? '' : date('Y-m-d')));

        [$startDate, $endDate, $periodError] = $this->parsePeriod($startInput, $endInput);

        $filters = [
            // O funcionário vê somente os próprios registros (RN18).
            'user_id' => $isManager ? $this->intParam($_GET, 'user_id') : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $flash = Flash::pullMessages();
        $result = null;

        if ($periodError !== null) {
            // Período inválido: o relatório não é gerado (FA1).
            $flash['error'] = $periodError;
        } else {
            $result = (new Service())->commissionReport(
                $filters,
                $isManager ? null : Auth::id(),
                $this->currentPage(),
                self::PER_PAGE
            );
        }

        $this->view('reports/commissions', [
            'title' => 'Relatório de comissões',
            'activeMenu' => 'reports',
            'subtitle' => 'Serviços finalizados no período de conclusão informado.',
            'flash' => $flash,
            'filters' => ['user_id' => $filters['user_id'], 'start_date' => $startInput, 'end_date' => $endInput],
            'query' => array_filter([
                'user_id' => $filters['user_id'],
                'start_date' => $startInput,
                'end_date' => $endInput,
            ]),
            'result' => $result,
            'responsibles' => $isManager ? (new User())->listAll() : [],
            'isManager' => $isManager,
        ]);
    }
}

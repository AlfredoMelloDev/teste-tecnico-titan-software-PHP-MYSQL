<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Service;

/**
 * UC12 - Visualizar dashboard. O gestor vê a operação geral; o funcionário,
 * somente os próprios serviços. Os filtros da listagem não afetam estes números (RN16).
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $services = new Service();
        $scope = Auth::isManager() ? null : Auth::id();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'activeMenu' => 'dashboard',
            'subtitle' => 'Situação em ' . date('d/m/Y') . '.',
            'summary' => $services->summary($scope),
            'pendingServices' => $services->latestPending($scope),
            'isManager' => Auth::isManager(),
        ]);
    }
}

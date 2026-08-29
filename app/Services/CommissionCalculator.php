<?php

declare(strict_types=1);

namespace App\Services;

final class CommissionCalculator
{
    /**
     * Calcula a comissão conforme o valor final do serviço.
     */
    public function calculate(float $serviceValue): float
    {
        $percentage = $this->percentageFor($serviceValue);

        // O resultado é arredondado para os dois centavos armazenados no banco.
        return round($serviceValue * $percentage, 2);
    }

    /**
     * Mantém as faixas de comissão concentradas em um único lugar.
     */
    private function percentageFor(float $serviceValue): float
    {
        if ($serviceValue > 10000.00) {
            return 0.20;
        }

        if ($serviceValue > 1000.00) {
            return 0.10;
        }

        return 0.05;
    }
}
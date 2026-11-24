<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Regra de comissão (RF10 / RN12):
 *  - até R$ 1.000,00 ............................. 5%
 *  - acima de R$ 1.000,00 até R$ 10.000,00 ....... 10%
 *  - acima de R$ 10.000,00 ....................... 20%
 * O percentual incide sobre o valor integral (sem cálculo progressivo),
 * com arredondamento para duas casas decimais.
 *
 * O cálculo usa centavos inteiros em vez de float, para não haver erro de
 * arredondamento binário (ex.: 0,1 + 0,2 ≠ 0,3 em ponto flutuante).
 */
final class CommissionCalculator
{
    /**
     * @return array{rate: string, commission: string} valores no formato do DECIMAL do MySQL
     */
    public function calculate(string $price): array
    {
        $cents = $this->toCents($price);
        $percent = $this->percentFor($cents);

        // Arredondamento "meio para cima" feito com inteiros: (centavos × % + 50) ÷ 100.
        $commissionCents = intdiv($cents * $percent + 50, 100);

        return [
            'rate' => $percent . '.00',
            'commission' => $this->fromCents($commissionCents),
        ];
    }

    // As faixas ficam concentradas em um único lugar.
    private function percentFor(int $cents): int
    {
        if ($cents > 1_000_000) {      // acima de R$ 10.000,00
            return 20;
        }

        if ($cents > 100_000) {        // acima de R$ 1.000,00
            return 10;
        }

        return 5;
    }

    // Converte "1234.5" ou "1234.56" em 123456 centavos.
    private function toCents(string $price): int
    {
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $price, $parts)) {
            throw new InvalidArgumentException('Valor de serviço em formato inválido.');
        }

        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

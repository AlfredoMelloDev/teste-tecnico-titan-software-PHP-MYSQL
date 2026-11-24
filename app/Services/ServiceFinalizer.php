<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Service;
use DomainException;
use Throwable;

/**
 * Finalização de serviço (UC10), em uma única transação:
 *  1. bloqueia a linha do serviço (SELECT ... FOR UPDATE);
 *  2. confere permissão e situação pendente;
 *  3. calcula a comissão sobre o valor lido dentro da transação;
 *  4. grava data, comissão, percentual e quem finalizou.
 * Uma edição simultânea espera o bloqueio terminar, então a comissão
 * sempre corresponde ao valor final do serviço (RN13).
 */
final class ServiceFinalizer
{
    public function __construct(
        private Service $services = new Service(),
        private CommissionCalculator $calculator = new CommissionCalculator()
    ) {
    }

    /**
     * @param array{id: int, role: string} $actor usuário autenticado
     * @return array{service: array, commission: string, rate: string}
     * @throws DomainException com mensagem para o usuário quando a operação é recusada
     */
    public function finalize(int $serviceId, array $actor): array
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $service = $this->services->findByIdForUpdate($serviceId);

            if ($service === null) {
                throw new DomainException('O serviço informado não foi encontrado.');
            }

            // Somente o gestor ou o responsável pelo serviço podem finalizar (RN11, RN18).
            $isManager = $actor['role'] === 'gestor';

            if (!$isManager && (int) $service['user_id'] !== $actor['id']) {
                throw new DomainException('Você não tem permissão para finalizar este serviço.');
            }

            // Uma segunda tentativa é recusada e a comissão original é mantida (FA3).
            if ($service['finished_at'] !== null) {
                throw new DomainException('Este serviço já foi finalizado.');
            }

            $result = $this->calculator->calculate((string) $service['price']);

            if (!$this->services->markFinished($serviceId, $result['commission'], $result['rate'], $actor['id'])) {
                throw new DomainException('Este serviço já foi finalizado.');
            }

            $connection->commit();
        } catch (Throwable $exception) {
            // Qualquer recusa ou falha desfaz a transação: o serviço continua pendente (FA4).
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }

        return [
            'service' => $service,
            'commission' => $result['commission'],
            'rate' => $result['rate'],
        ];
    }
}

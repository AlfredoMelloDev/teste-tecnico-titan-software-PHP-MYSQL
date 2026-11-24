<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Logger;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Services\MailService;
use App\Services\ServiceFinalizer;
use DomainException;
use Throwable;

final class ServiceController extends Controller
{
    private Service $services;
    private Client $clients;
    private User $users;

    public function __construct()
    {
        $this->services = new Service();
        $this->clients = new Client();
        $this->users = new User();
    }

    /**
     * UC11 - Pesquisar serviços: filtros combináveis e paginação de 25 registros.
     */
    public function index(): void
    {
        Auth::requireLogin();

        $isManager = Auth::isManager();
        $error = null;

        $status = trim($_GET['status'] ?? '');
        [$startDate, $endDate, $periodError] = $this->parsePeriod(
            trim($_GET['start_date'] ?? ''),
            trim($_GET['end_date'] ?? '')
        );

        if ($periodError !== null) {
            // O filtro de período não é aplicado e o usuário recebe orientação (FA1).
            $error = $periodError;
        }

        $filters = [
            'description' => trim($_GET['description'] ?? ''),
            'client' => trim($_GET['client'] ?? ''),
            // O filtro por responsável só é oferecido ao gestor.
            'user_id' => $isManager ? $this->intParam($_GET, 'user_id') : null,
            'status' => in_array($status, ['pendente', 'finalizado'], true) ? $status : '',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        // O funcionário vê somente os serviços sob sua responsabilidade (RN18).
        $result = $this->services->search(
            $filters,
            $isManager ? null : Auth::id(),
            $this->currentPage(),
            self::PER_PAGE
        );

        $flash = Flash::pullMessages();

        if ($error !== null) {
            $flash['error'] = $error;
        }

        $this->view('services/index', [
            'title' => 'Serviços',
            'activeMenu' => 'services',
            'flash' => $flash,
            'filters' => $filters,
            'query' => array_filter([
                'description' => $filters['description'],
                'client' => $filters['client'],
                'user_id' => $filters['user_id'],
                'status' => $filters['status'],
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
            ]),
            'result' => $result,
            'responsibles' => $isManager ? $this->users->listAll() : [],
            'isManager' => $isManager,
            'currentUserId' => (int) Auth::id(),
        ]);
    }

    // UC07 - formulário de registro de serviço.
    public function showCreate(): void
    {
        Auth::requireLogin();

        $this->view('services/form', [
            'title' => 'Novo serviço',
            'activeMenu' => 'services-create',
            'service' => null,
            'old' => Flash::pullOld(),
            'clients' => $this->clients->listActive(),
            'responsibles' => Auth::isManager() ? $this->users->listActive() : [],
            'isManager' => Auth::isManager(),
        ]);
    }

    // UC07 - registra o serviço como pendente.
    public function store(): void
    {
        Auth::requireLogin();
        $this->requireCsrf('/services/create');

        $data = $this->readForm();
        $errors = $this->validate($data);

        $client = $data['client_id'] !== null ? $this->clients->findById($data['client_id']) : null;

        if ($client === null || (int) $client['active'] !== 1) {
            $errors[] = 'Selecione um cliente ativo.';
        }

        // O funcionário é sempre o responsável; só o gestor escolhe outro (RN07).
        $responsibleId = Auth::isManager() ? $data['user_id'] : (int) Auth::id();
        $responsible = $responsibleId !== null ? $this->users->findActiveById($responsibleId) : null;

        if ($responsible === null) {
            $errors[] = 'Selecione um responsável ativo.';
        }

        if ($errors !== []) {
            $this->backWithErrors('/services/create', $errors, $data);
        }

        try {
            $serviceId = $this->services->create(
                $data['description'],
                $data['price'],
                $responsibleId,
                $data['client_id']
            );

            Flash::success("Serviço #{$serviceId} cadastrado como pendente.");
        } catch (Throwable $exception) {
            Logger::error('Falha ao cadastrar serviço', $exception);
            Flash::error('Não foi possível cadastrar o serviço. Tente novamente.');
            Flash::setOld($data['raw']);
            $this->redirect('/services/create');
        }

        $this->redirect('/services');
    }

    // UC08 - formulário de edição de serviço pendente.
    public function showEdit(): void
    {
        Auth::requireLogin();

        $serviceId = $this->intParam($_GET, 'id');
        $service = $serviceId !== null ? $this->services->findById($serviceId) : null;

        $this->ensureCanChange($service);

        // O cliente atual continua na lista mesmo que tenha sido inativado (RN05).
        $clients = $this->clients->listActive();

        if ((int) $service['client_active'] !== 1) {
            array_unshift($clients, [
                'id_client' => $service['client_id'],
                'name' => $service['client_name'] . ' (inativo - cliente atual)',
            ]);
        }

        // Se o responsável atual foi inativado, ele continua visível como opção atual.
        $responsibles = Auth::isManager() ? $this->users->listActive() : [];

        if (Auth::isManager() && !in_array((int) $service['user_id'], array_map('intval', array_column($responsibles, 'id_user')), true)) {
            array_unshift($responsibles, [
                'id_user' => $service['user_id'],
                'name' => $service['user_name'] . ' (inativo - responsável atual)',
                'role' => 'funcionario',
            ]);
        }

        $this->view('services/form', [
            'title' => 'Editar serviço #' . $service['id_service'],
            'activeMenu' => 'services',
            'service' => $service,
            'old' => Flash::pullOld(),
            'clients' => $clients,
            'responsibles' => $responsibles,
            'isManager' => Auth::isManager(),
        ]);
    }

    // UC08 - grava a edição somente se o serviço continuar pendente.
    public function update(): void
    {
        Auth::requireLogin();

        $serviceId = $this->intParam($_POST, 'service_id');
        $back = '/services/edit?id=' . (int) $serviceId;
        $this->requireCsrf($back);

        $data = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($back, $errors, $data);
        }

        $connection = Database::connection();

        try {
            $connection->beginTransaction();

            // Bloqueia a linha: uma finalização simultânea espera esta edição terminar.
            $service = $serviceId !== null ? $this->services->findByIdForUpdate($serviceId) : null;
            $this->ensureCanChange($service, inTransaction: true);

            // Trocar o cliente exige um cliente ativo; manter o atual é sempre permitido (RN05).
            $clientId = $data['client_id'] ?? (int) $service['client_id'];

            if ($clientId !== (int) $service['client_id']) {
                $client = $this->clients->findById($clientId);

                if ($client === null || (int) $client['active'] !== 1) {
                    $connection->rollBack();
                    $this->backWithErrors($back, ['Escolha um cliente ativo. O cliente atual foi mantido.'], $data);
                }
            }

            // Somente o gestor altera o responsável, e apenas para um usuário ativo (RN07).
            $userId = (int) $service['user_id'];

            if (Auth::isManager() && $data['user_id'] !== null && $data['user_id'] !== $userId) {
                if ($this->users->findActiveById($data['user_id']) === null) {
                    $connection->rollBack();
                    $this->backWithErrors($back, ['Selecione um responsável ativo.'], $data);
                }

                $userId = $data['user_id'];
            }

            $this->services->updatePending($serviceId, $data['description'], $data['price'], $clientId, $userId);
            $connection->commit();

            Flash::success("Serviço #{$serviceId} atualizado.");
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            Logger::error("Falha ao atualizar o serviço {$serviceId}", $exception);
            Flash::error('Não foi possível atualizar o serviço. Tente novamente.');
        }

        $this->redirect('/services');
    }

    // UC09 - somente o gestor exclui, e somente serviço pendente.
    public function delete(): void
    {
        Auth::requireLogin();
        $this->requireCsrf('/services');

        if (!Auth::isManager()) {
            Flash::error('Somente o gestor pode excluir serviços.');
            $this->redirect('/services');
        }

        $serviceId = $this->intParam($_POST, 'service_id');

        try {
            if ($serviceId !== null && $this->services->deletePending($serviceId)) {
                Flash::success("Serviço #{$serviceId} excluído.");
            } else {
                Flash::error('O serviço não foi encontrado ou já está finalizado. Serviços finalizados não podem ser excluídos.');
            }
        } catch (Throwable $exception) {
            Logger::error("Falha ao excluir o serviço {$serviceId}", $exception);
            Flash::error('Não foi possível excluir o serviço. Tente novamente.');
        }

        $this->redirect('/services');
    }

    // UC10 - finaliza o serviço e calcula a comissão em uma transação.
    public function finish(): void
    {
        Auth::requireLogin();
        $this->requireCsrf('/services');

        $serviceId = $this->intParam($_POST, 'service_id');

        if ($serviceId === null) {
            Flash::error('Serviço inválido.');
            $this->redirect('/services');
        }

        try {
            $result = (new ServiceFinalizer())->finalize($serviceId, [
                'id' => (int) Auth::id(),
                'role' => Auth::user()['role'],
            ]);

            Flash::success(sprintf(
                'Serviço #%d finalizado. Comissão de %s%% sobre o valor: %s.',
                $serviceId,
                number_format((float) $result['rate'], 0),
                money($result['commission'])
            ));

            $this->notifyResponsible($serviceId);
        } catch (DomainException $exception) {
            // Recusas previstas (sem permissão, já finalizado): mensagem direta ao usuário.
            Flash::error($exception->getMessage());
        } catch (Throwable $exception) {
            Logger::error("Falha ao finalizar o serviço {$serviceId}", $exception);
            Flash::error('Não foi possível finalizar o serviço. Ele continua pendente.');
        }

        $this->redirect('/services');
    }

    // Avisa o responsável por e-mail. Uma falha no envio não desfaz a finalização.
    private function notifyResponsible(int $serviceId): void
    {
        try {
            $service = $this->services->findById($serviceId);

            if ($service === null) {
                return;
            }

            $message = sprintf(
                "Olá, %s.\n\nO serviço #%d foi finalizado.\nCliente: %s\nDescrição: %s\nValor: %s\nComissão: %s\nData da finalização: %s",
                $service['user_name'],
                $serviceId,
                $service['client_name'],
                $service['description'],
                money($service['price']),
                money($service['commission_user']),
                datetime_br($service['finished_at'])
            );

            (new MailService())->send($service['user_email'], 'Serviço finalizado', $message);
        } catch (Throwable $exception) {
            Logger::error("Falha ao notificar a finalização do serviço {$serviceId}", $exception);
        }
    }

    /**
     * Confere existência, permissão (gestor ou responsável) e situação pendente.
     * Usado tanto ao abrir a edição quanto ao gravar (inclusive por URL direta).
     */
    private function ensureCanChange(?array $service, bool $inTransaction = false): void
    {
        $message = null;

        if ($service === null) {
            $message = 'O serviço informado não foi encontrado.';
        } elseif (!Auth::isManager() && (int) $service['user_id'] !== Auth::id()) {
            $message = 'Você não tem permissão para alterar este serviço.';
        } elseif ($service['finished_at'] !== null) {
            $message = 'Serviços finalizados não podem ser alterados.';
        }

        if ($message === null) {
            return;
        }

        if ($inTransaction && Database::connection()->inTransaction()) {
            Database::connection()->rollBack();
        }

        Flash::error($message);
        $this->redirect('/services');
    }

    private function readForm(): array
    {
        $raw = [
            'description' => trim($_POST['description'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
            'client_id' => trim((string) ($_POST['client_id'] ?? '')),
            'user_id' => trim((string) ($_POST['user_id'] ?? '')),
        ];

        return [
            'raw' => $raw,
            'description' => $raw['description'],
            'price' => $this->normalizePrice($raw['price']),
            'client_id' => $this->intParam($_POST, 'client_id'),
            'user_id' => $this->intParam($_POST, 'user_id'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $length = mb_strlen($data['description']);

        if ($length < 1 || $length > 255) {
            $errors[] = 'Informe a descrição (até 255 caracteres).';
        }

        if ($data['price'] === null) {
            $errors[] = 'Informe um valor maior que zero, com até duas casas decimais (máximo R$ 999.999.999,99).';
        }

        return $errors;
    }

    private function backWithErrors(string $location, array $errors, array $data): never
    {
        Flash::error(implode(' ', $errors));
        Flash::setOld($data['raw']);
        $this->redirect($location);
    }

    /**
     * Converte o valor digitado (vírgula ou ponto) para o formato do DECIMAL(11,2).
     * Aceita até nove dígitos inteiros e duas casas decimais; rejeita zero (RN06).
     */
    private function normalizePrice(string $price): ?string
    {
        // Aceita "1.234,56" removendo o separador de milhar quando há vírgula decimal.
        if (str_contains($price, ',')) {
            $price = str_replace(['.', ','], ['', '.'], $price);
        }

        if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $price)) {
            return null;
        }

        [$integer, $decimal] = array_pad(explode('.', $price, 2), 2, '');

        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $decimal = str_pad($decimal, 2, '0');

        if ($integer === '0' && $decimal === '00') {
            return null;
        }

        return $integer . '.' . $decimal;
    }
}

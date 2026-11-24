<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;
use App\Models\Client;
use Throwable;

final class ClientController extends Controller
{
    private Client $clients;

    public function __construct()
    {
        $this->clients = new Client();
    }

    /**
     * Gestor: consulta clientes ativos e inativos (UC05).
     * Funcionário: localiza clientes ativos por nome (UC06).
     */
    public function index(): void
    {
        Auth::requireLogin();

        $search = trim($_GET['search'] ?? '');
        $isManager = Auth::isManager();

        $this->view('clients/index', [
            'title' => 'Clientes',
            'activeMenu' => 'clients',
            'search' => $search,
            'query' => array_filter(['search' => $search]),
            'result' => $this->clients->paginate($search, !$isManager, $this->currentPage(), self::PER_PAGE),
            'isManager' => $isManager,
        ]);
    }

    // UC04 - gestor e funcionário cadastram clientes.
    public function showCreate(): void
    {
        Auth::requireLogin();

        $this->view('clients/form', [
            'title' => 'Novo cliente',
            'activeMenu' => 'clients',
            'client' => null,
            'old' => Flash::pullOld(),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->requireCsrf('/clients/create');

        $data = $this->readForm();
        $this->validateOrBack($data, '/clients/create');

        try {
            $clientId = $this->clients->create($data['name'], $data['phone'], $data['email']);
            Flash::success("Cliente \"{$data['name']}\" cadastrado. Ele já pode ser vinculado a serviços.");
        } catch (Throwable $exception) {
            Logger::error('Falha ao cadastrar cliente', $exception);
            Flash::error('Não foi possível cadastrar o cliente. Tente novamente.');
            Flash::setOld($data);
            $this->redirect('/clients/create');
        }

        $this->redirect('/clients/show?id=' . $clientId);
    }

    /**
     * Detalhes do cliente. O funcionário vê clientes ativos e também os inativos
     * vinculados aos seus serviços (UC06 FA1), sem acesso a serviços de outros.
     */
    public function show(): void
    {
        Auth::requireLogin();

        $clientId = $this->intParam($_GET, 'id');
        $client = $clientId !== null ? $this->clients->findById($clientId) : null;

        $allowed = $client !== null && (
            Auth::isManager()
            || (int) $client['active'] === 1
            || $this->clients->isLinkedToUser($clientId, (int) Auth::id())
        );

        if (!$allowed) {
            Flash::error('Cliente não encontrado.');
            $this->redirect('/clients');
        }

        $this->view('clients/show', [
            'title' => $client['name'],
            'activeMenu' => 'clients',
            'client' => $client,
            'isManager' => Auth::isManager(),
        ]);
    }

    // UC05 FA1 - edição pelo gestor.
    public function showEdit(): void
    {
        Auth::requireManager();

        $clientId = $this->intParam($_GET, 'id');
        $client = $clientId !== null ? $this->clients->findById($clientId) : null;

        if ($client === null) {
            Flash::error('Cliente não encontrado.');
            $this->redirect('/clients');
        }

        $this->view('clients/form', [
            'title' => 'Editar cliente',
            'activeMenu' => 'clients',
            'client' => $client,
            'old' => Flash::pullOld(),
        ]);
    }

    public function update(): void
    {
        Auth::requireManager();

        $clientId = $this->intParam($_POST, 'client_id');
        $back = '/clients/edit?id=' . (int) $clientId;
        $this->requireCsrf($back);

        if ($clientId === null || $this->clients->findById($clientId) === null) {
            Flash::error('Cliente não encontrado.');
            $this->redirect('/clients');
        }

        $data = $this->readForm();
        $this->validateOrBack($data, $back);

        try {
            $this->clients->update($clientId, $data['name'], $data['phone'], $data['email']);
            Flash::success('Cliente atualizado.');
        } catch (Throwable $exception) {
            Logger::error("Falha ao atualizar o cliente {$clientId}", $exception);
            Flash::error('Não foi possível atualizar o cliente. Tente novamente.');
        }

        $this->redirect('/clients/show?id=' . $clientId);
    }

    // UC05 FA2 - inativação: preserva os serviços e impede novos vínculos (RN04, RN05).
    public function deactivate(): void
    {
        Auth::requireManager();
        $this->requireCsrf('/clients');

        $clientId = $this->intParam($_POST, 'client_id');

        if ($clientId === null || $this->clients->findById($clientId) === null) {
            Flash::error('Cliente não encontrado.');
            $this->redirect('/clients');
        }

        try {
            $this->clients->deactivate($clientId);
            Flash::success('Cliente inativado. Os serviços anteriores foram preservados.');
        } catch (Throwable $exception) {
            Logger::error("Falha ao inativar o cliente {$clientId}", $exception);
            Flash::error('Não foi possível inativar o cliente. Tente novamente.');
        }

        $this->redirect('/clients/show?id=' . $clientId);
    }

    private function readForm(): array
    {
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        return [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => $phone === '' ? null : $phone,
            'email' => $email === '' ? null : $email,
        ];
    }

    // Nome obrigatório; telefone e e-mail opcionais, mas válidos quando informados (RN05).
    private function validateOrBack(array $data, string $back): void
    {
        $errors = [];

        if ($data['name'] === '' || mb_strlen($data['name']) > 150) {
            $errors[] = 'Informe o nome do cliente (até 150 caracteres).';
        }

        if ($data['phone'] !== null && !preg_match('/^[0-9()+\-\s]{8,20}$/', $data['phone'])) {
            $errors[] = 'Telefone inválido: use somente números, espaços, parênteses e hífen.';
        }

        if ($data['email'] !== null && (mb_strlen($data['email']) > 100 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
            $errors[] = 'Informe um e-mail válido ou deixe o campo vazio.';
        }

        if ($errors !== []) {
            Flash::error(implode(' ', $errors));
            Flash::setOld($data);
            $this->redirect($back);
        }
    }
}

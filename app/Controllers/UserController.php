<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Logger;
use App\Models\User;
use DomainException;
use Throwable;

/**
 * UC03 - Gerenciar usuários. Todas as ações são exclusivas do gestor.
 * O cadastro público de usuários da base anterior foi removido.
 */
final class UserController extends Controller
{
    private const ROLES = [Auth::ROLE_MANAGER, Auth::ROLE_EMPLOYEE];

    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        Auth::requireManager();

        $search = trim($_GET['search'] ?? '');

        $this->view('users/index', [
            'title' => 'Usuários',
            'activeMenu' => 'users',
            'search' => $search,
            'query' => array_filter(['search' => $search]),
            'result' => $this->users->paginate($search, $this->currentPage(), self::PER_PAGE),
            'currentUserId' => (int) Auth::id(),
        ]);
    }

    public function showCreate(): void
    {
        Auth::requireManager();

        $this->view('users/form', [
            'title' => 'Novo usuário',
            'activeMenu' => 'users',
            'user' => null,
            'old' => Flash::pullOld(),
        ]);
    }

    public function store(): void
    {
        Auth::requireManager();
        $this->requireCsrf('/users/create');

        $data = $this->readForm();
        $password = $_POST['password'] ?? '';
        $errors = $this->validate($data, null);

        // Senha inicial com pelo menos 8 caracteres (RN02).
        if (mb_strlen($password) < 8) {
            $errors[] = 'A senha inicial deve ter pelo menos 8 caracteres.';
        }

        if ($errors !== []) {
            $this->backWithErrors('/users/create', $errors, $data);
        }

        try {
            // A senha é gravada somente como hash (RNF02).
            $this->users->create(
                $data['name'],
                $data['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $data['role']
            );

            Flash::success("Usuário {$data['name']} cadastrado.");
        } catch (Throwable $exception) {
            Logger::error('Falha ao cadastrar usuário', $exception);
            Flash::error('Não foi possível cadastrar o usuário. Tente novamente.');
            Flash::setOld($data);
            $this->redirect('/users/create');
        }

        $this->redirect('/users');
    }

    public function showEdit(): void
    {
        Auth::requireManager();

        $userId = $this->intParam($_GET, 'id');
        $user = $userId !== null ? $this->users->findById($userId) : null;

        if ($user === null) {
            Flash::error('Usuário não encontrado.');
            $this->redirect('/users');
        }

        $this->view('users/form', [
            'title' => 'Editar usuário',
            'activeMenu' => 'users',
            'user' => $user,
            'old' => Flash::pullOld(),
        ]);
    }

    public function update(): void
    {
        Auth::requireManager();

        $userId = $this->intParam($_POST, 'user_id');
        $back = '/users/edit?id=' . (int) $userId;
        $this->requireCsrf($back);

        $user = $userId !== null ? $this->users->findById($userId) : null;

        if ($user === null) {
            Flash::error('Usuário não encontrado.');
            $this->redirect('/users');
        }

        $data = $this->readForm();
        $errors = $this->validate($data, $userId);

        if ($errors !== []) {
            $this->backWithErrors($back, $errors, $data);
        }

        $connection = Database::connection();

        try {
            $connection->beginTransaction();

            // O último gestor ativo não pode perder o perfil de gestor (RN03).
            if ($user['role'] === Auth::ROLE_MANAGER && $data['role'] !== Auth::ROLE_MANAGER) {
                $this->ensureNotLastManager($userId);
            }

            $this->users->update($userId, $data['name'], $data['email'], $data['role']);
            $connection->commit();

            Flash::success('Usuário atualizado.');
        } catch (DomainException $exception) {
            $connection->rollBack();
            $this->backWithErrors($back, [$exception->getMessage()], $data);
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            Logger::error("Falha ao atualizar o usuário {$userId}", $exception);
            Flash::error('Não foi possível atualizar o usuário. Tente novamente.');
        }

        $this->redirect('/users');
    }

    // FA3 - inativa sem apagar: os serviços do usuário são preservados (RN04).
    public function deactivate(): void
    {
        Auth::requireManager();
        $this->requireCsrf('/users');

        $userId = $this->intParam($_POST, 'user_id');
        $user = $userId !== null ? $this->users->findById($userId) : null;

        if ($user === null) {
            Flash::error('Usuário não encontrado.');
            $this->redirect('/users');
        }

        $connection = Database::connection();

        try {
            $connection->beginTransaction();

            if ($user['role'] === Auth::ROLE_MANAGER && (int) $user['active'] === 1) {
                $this->ensureNotLastManager($userId);
            }

            $this->users->deactivate($userId);
            $connection->commit();

            // Se o gestor inativou a si mesmo, a próxima requisição encerra a sessão (RC1).
            Flash::success("Usuário {$user['name']} inativado. Os serviços dele foram preservados.");
        } catch (DomainException $exception) {
            $connection->rollBack();
            Flash::error($exception->getMessage());
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            Logger::error("Falha ao inativar o usuário {$userId}", $exception);
            Flash::error('Não foi possível inativar o usuário. Tente novamente.');
        }

        $this->redirect('/users');
    }

    // Bloqueia os gestores ativos e recusa a operação se este for o único (RN03).
    private function ensureNotLastManager(int $userId): void
    {
        $managers = $this->users->lockActiveManagers();

        if ($managers === [$userId]) {
            throw new DomainException('O último gestor ativo não pode ser inativado nem perder o perfil de gestor.');
        }
    }

    private function readForm(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'email' => mb_strtolower(trim($_POST['email'] ?? '')),
            'role' => trim($_POST['role'] ?? ''),
        ];
    }

    private function validate(array $data, ?int $exceptUserId): array
    {
        $errors = [];

        if ($data['name'] === '' || mb_strlen($data['name']) > 150) {
            $errors[] = 'Informe o nome (até 150 caracteres).';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 100) {
            $errors[] = 'Informe um e-mail válido.';
        } elseif ($this->users->emailExists($data['email'], $exceptUserId)) {
            $errors[] = 'Já existe um usuário com este e-mail.';
        }

        if (!in_array($data['role'], self::ROLES, true)) {
            $errors[] = 'Escolha o perfil: gestor ou funcionário.';
        }

        return $errors;
    }

    private function backWithErrors(string $location, array $errors, array $data): never
    {
        Flash::error(implode(' ', $errors));
        Flash::setOld($data);
        $this->redirect($location);
    }
}

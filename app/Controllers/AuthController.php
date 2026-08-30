<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

final class AuthController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }


    public function showLogin(): void
    {

        $error = $_SESSION['login_error'] ?? null;
        $success = $_SESSION['login_success'] ?? null;
        $oldEmail = $_SESSION['login_email'] ?? '';

        // Mensagens temporárias devem aparecer somente uma vez.
        unset(
            $_SESSION['login_error'],
            $_SESSION['login_success'],
            $_SESSION['login_email']
        );

        $this->view('auth/login', [
            'title' => 'Entrar no sistema',
            'error' => $error,
            'success' => $success,
            'oldEmail' => $oldEmail,
        ]);
    }

    public function login(): void
    {
        // Validação do token CSRF
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['login_error'] =
                'A solicitação expirou. Atualize a página e tente novamente.';

            header('Location: /login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->users->findActiveByEmail($email)
            : null;

        if (
            $user === null
            || !password_verify($password, $user['password'])
        ) {
            // A mensagem genérica não revela se foi o e-mail ou a senha que falhou.
            $_SESSION['login_error'] =
                'Email ou Senha incorretos. Por favor, tente novamente.';

            $_SESSION['login_email'] = $email;

            header('Location: /login');
            exit;
        }

        // Registra o usuário e renova o identificador da sessão.
        Auth::login($user);

        header('Location: /dashboard');
        exit;
    }

    public function logout(): void
    {
        // Validação do token CSRF
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['dashboard_error'] =
                'A solicitação expirou. Atualize a página e tente novamente.';

            header('Location: /dashboard');
            exit;
        }

        Auth::logout();

        header('Location: /login');
        exit;
    }
}

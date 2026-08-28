<?php

declare(strict_types=1);

namespace App\Controllers;

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
                'Ops, Email ou Senha inválido';

            $_SESSION['login_email'] = $email;

            header('Location: /login');
            exit;
        }

        // Troca o identificador da sessão depois da autenticação.
        session_regenerate_id(true);

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id_user'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        header('Location: /dashboard');
        exit;
    }
}
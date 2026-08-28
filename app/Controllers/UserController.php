<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

final class UserController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function showCreate(): void
    {
        // Recupera os dados da tentativa anterior para não limpar todo o formulário.
        $error = $_SESSION['user_form_error'] ?? null;
        $old = $_SESSION['user_form_old'] ?? [];

        unset(
            $_SESSION['user_form_error'],
            $_SESSION['user_form_old']
        );

        $this->view('users/create', [
            'title' => 'Cadastrar usuário',
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function store(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // A validação no servidor continua obrigatória, mesmo com campos required.
        if (
            $name === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 8
        ) {
            $this->redirectWithError(
                'Informe nome, e-mail válido e uma senha com pelo menos 8 caracteres.',
                $name,
                $email
            );
        }

        if ($this->users->emailExists($email)) {
            $this->redirectWithError(
                'Já existe um usuário cadastrado com este e-mail.',
                $name,
                $email
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $this->users->create(
            $name,
            $email,
            $passwordHash
        );

        $_SESSION['login_success'] =
            'Usuário cadastrado. Agora você já pode entrar.';

        header('Location: /login');
        exit;
    }

    private function redirectWithError(
        string $message,
        string $name,
        string $email
    ): never {
        $_SESSION['user_form_error'] = $message;
        $_SESSION['user_form_old'] = [
            'name' => $name,
            'email' => $email,
        ];

        header('Location: /users/create');
        exit;
    }
}
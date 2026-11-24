<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\User;

final class AuthController extends Controller
{
    // UC01 - tela de login.
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        // Mensagens de sessão encerrada ou de erro de login chegam pela Flash.
        $messages = Flash::pullMessages();
        $old = Flash::pullOld();

        $this->view('auth/login', [
            'title' => 'Entrar no sistema',
            'error' => $messages['error'] ?? null,
            'success' => $messages['success'] ?? null,
            'oldEmail' => (string) ($old['email'] ?? ''),
            'flash' => [],
        ]);
    }

    // UC01 - valida as credenciais e cria a sessão com o perfil do usuário.
    public function login(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Flash::error('A página expirou. Tente entrar novamente.');
            $this->redirect('/login');
        }

        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? (new User())->findActiveByEmail($email)
            : null;

        // Mensagem genérica: não revela se o e-mail existe ou se a senha está errada (FA1).
        if ($user === null || !password_verify($password, $user['password'])) {
            Flash::error('E-mail ou senha incorretos, ou usuário inativo.');
            Flash::setOld(['email' => $email]);
            $this->redirect('/login');
        }

        Auth::login($user);

        $this->redirect('/dashboard');
    }

    // UC02 - encerra a sessão.
    public function logout(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Flash::error('A página expirou. Atualize e tente novamente.');
            $this->redirect('/dashboard');
        }

        Auth::logout();
        Flash::success('Sessão encerrada.');

        $this->redirect('/login');
    }
}

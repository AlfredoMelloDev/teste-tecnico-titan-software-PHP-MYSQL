<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\ClientController;
use App\Controllers\ReportController;
use App\Controllers\ServiceController;
use App\Controllers\DashboardController;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
require_once dirname(__DIR__) . '/app/Core/helpers.php';

date_default_timezone_set('America/Sao_Paulo');

// Erros nunca são exibidos ao usuário; o detalhe vai para storage/logs (RNF08).
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Converte avisos do PHP em exceções, para que passem pelo mesmo tratamento.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Qualquer falha não tratada termina com uma mensagem simples e o registro no log.
set_exception_handler(static function (Throwable $exception): void {
    Logger::error('Falha não tratada em ' . ($_SERVER['REQUEST_URI'] ?? ''), $exception);

    if (!headers_sent()) {
        http_response_code(500);
    }

    require dirname(__DIR__) . '/app/Views/errors/500.php';
});

// Cookie de sessão inacessível ao JavaScript e enviado só por HTTPS quando disponível (RNF02).
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Permite que o servidor interno do PHP entregue CSS, JavaScript e imagens diretamente.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($requestPath !== '/' && is_file(__DIR__ . $requestPath)) {
        return false;
    }
}

// Cria o controlador somente quando a rota é chamada.
$action = static fn (string $controller, string $method): Closure =>
    static function () use ($controller, $method): void {
        (new $controller())->{$method}();
    };

$router = new Router();

$router->get('/', static function (): void {
    header('Location: ' . (Auth::check() ? '/dashboard' : '/login'));
    exit;
});

// Autenticação (UC01, UC02).
$router->get('/login', $action(AuthController::class, 'showLogin'));
$router->post('/login', $action(AuthController::class, 'login'));
$router->post('/logout', $action(AuthController::class, 'logout'));

// Dashboard (UC12).
$router->get('/dashboard', $action(DashboardController::class, 'index'));

// Serviços (UC07 a UC11).
$router->get('/services', $action(ServiceController::class, 'index'));
$router->get('/services/create', $action(ServiceController::class, 'showCreate'));
$router->post('/services', $action(ServiceController::class, 'store'));
$router->get('/services/edit', $action(ServiceController::class, 'showEdit'));
$router->post('/services/update', $action(ServiceController::class, 'update'));
$router->post('/services/delete', $action(ServiceController::class, 'delete'));
$router->post('/services/finish', $action(ServiceController::class, 'finish'));

// Clientes (UC04 a UC06).
$router->get('/clients', $action(ClientController::class, 'index'));
$router->get('/clients/create', $action(ClientController::class, 'showCreate'));
$router->post('/clients', $action(ClientController::class, 'store'));
$router->get('/clients/show', $action(ClientController::class, 'show'));
$router->get('/clients/edit', $action(ClientController::class, 'showEdit'));
$router->post('/clients/update', $action(ClientController::class, 'update'));
$router->post('/clients/deactivate', $action(ClientController::class, 'deactivate'));

// Usuários (UC03), somente gestor.
$router->get('/users', $action(UserController::class, 'index'));
$router->get('/users/create', $action(UserController::class, 'showCreate'));
$router->post('/users', $action(UserController::class, 'store'));
$router->get('/users/edit', $action(UserController::class, 'showEdit'));
$router->post('/users/update', $action(UserController::class, 'update'));
$router->post('/users/deactivate', $action(UserController::class, 'deactivate'));

// Relatório de serviços e comissões (UC13).
$router->get('/reports/commissions', $action(ReportController::class, 'commissions'));

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);

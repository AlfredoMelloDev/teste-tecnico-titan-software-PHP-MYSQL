<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\ServiceController;
use App\Controllers\DashboardController;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

date_default_timezone_set('America/Sao_Paulo');
session_start();

// Permite que o servidor interno entregue CSS, JavaScript e imagens diretamente.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url(
        $_SERVER['REQUEST_URI'],
        PHP_URL_PATH
    );

    $requestedFile = __DIR__ . $requestPath;

    if ($requestPath !== '/' && is_file($requestedFile)) {
        return false;
    }
}

// Cria o roteador e registra as rotas da aplicação.
$router = new Router();
$authController = new AuthController();
$userController = new UserController();
$serviceController = new ServiceController();
$dashboardController = new DashboardController();


$router->get('/', function (): void {
    header('Location: /login');
    exit;
});

$router->get('/login', [$authController, 'showLogin']);

$router->post('/login', [$authController, 'login']);

$router->get(
    '/users/create',
    [$userController, 'showCreate']
);

$router->post(
    '/users',
    [$userController, 'store']
);

$router->get(
    '/dashboard',
    [$dashboardController, 'index']
);

$router->post(
    '/logout',
    [$authController, 'logout']
);

$router->get(
    '/services/create',
    [$serviceController, 'showCreate']
);

$router->post(
    '/services',
    [$serviceController, 'store']
);

// Localiza e executa a rota correspondente à requisição atual.
$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);
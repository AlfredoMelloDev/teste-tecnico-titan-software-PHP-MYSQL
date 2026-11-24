<?php

use App\Core\Auth;

/**
 * Início comum das telas internas: menu lateral por perfil, cabeçalho e mensagens.
 *
 * @var string $title
 * @var string $activeMenu
 * @var array{success?: string, error?: string} $flash
 * @var string|null $subtitle
 */

$authUser = Auth::user() ?? ['name' => 'Usuário', 'email' => '', 'role' => ''];
$isManager = Auth::isManager();
$activeMenu = $activeMenu ?? '';

$menu = [
    ['dashboard', '/dashboard', '▦', 'Dashboard'],
    ['services', '/services', '☰', 'Serviços'],
    ['services-create', '/services/create', '＋', 'Novo serviço'],
    ['clients', '/clients', '☺', 'Clientes'],
    ['reports', '/reports/commissions', '％', 'Relatório de comissões'],
];

if ($isManager) {
    $menu[] = ['users', '/users', '⚙', 'Usuários'];
}

$nameParts = preg_split('/\s+/u', trim($authUser['name']), -1, PREG_SPLIT_NO_EMPTY) ?: ['Usuário'];
$initial = mb_strtoupper(mb_substr($nameParts[0], 0, 1));

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | Gestão de Serviços e Comissões</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>

<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <p class="sidebar__label">Gestão de Serviços</p>

            <nav class="sidebar__nav" aria-label="Navegação principal">
                <?php foreach ($menu as [$key, $href, $icon, $label]): ?>
                    <a
                        class="sidebar__link<?= $activeMenu === $key ? ' sidebar__link--active' : '' ?>"
                        href="<?= e($href) ?>"
                        <?= $activeMenu === $key ? 'aria-current="page"' : '' ?>>
                        <span class="sidebar__icon" aria-hidden="true"><?= e($icon) ?></span>
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar__footer">
                <div class="sidebar__profile">
                    <span class="sidebar__avatar"><?= e($initial) ?></span>

                    <div class="sidebar__profile-text">
                        <strong title="<?= e($authUser['name']) ?>"><?= e($authUser['name']) ?></strong>
                        <span><?= $isManager ? 'Gestor' : 'Funcionário' ?> · <?= e($authUser['email']) ?></span>
                    </div>
                </div>

                <form class="sidebar__logout" action="/logout" method="post">
                    <?= csrf_field() ?>
                    <button class="button" type="submit">Sair do sistema</button>
                </form>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <p class="dashboard-header__eyebrow"><?= $isManager ? 'Visão do gestor' : 'Visão do funcionário' ?></p>
                    <h1><?= e($title) ?></h1>
                    <?php if (!empty($subtitle)): ?>
                        <p class="dashboard-header__description"><?= e($subtitle) ?></p>
                    <?php endif; ?>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($flash['success'])): ?>
                    <p class="alert alert--success" role="status"><?= e($flash['success']) ?></p>
                <?php endif; ?>

                <?php if (isset($flash['error'])): ?>
                    <p class="alert alert--error" role="alert"><?= e($flash['error']) ?></p>
                <?php endif; ?>

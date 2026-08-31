<?php

use App\Core\Csrf;

/**
 * Dados preparados pelo DashboardController.
 *
 * @var string $title
 * @var array{id: int, name: string, email: string} $user
 * @var string $currentDate
 * @var string|null $success
 * @var string|null $error
 * @var array<int, array<string, mixed>> $services
 * @var array<int, array<string, mixed>> $pendingServices
 * @var array{
 *     description: string,
 *     status: string,
 *     user: string,
 *     start_date: string,
 *     end_date: string
 * } $filters
 * @var array{
 *     total_services: int,
 *     pending_services: int,
 *     finished_services: int,
 *     total_value: float
 * } $summary
 */

$csrfToken = Csrf::token();

/**
 * Encurta textos somente para exibição.
 * O conteúdo original permanece armazenado no banco.
 */
$shortenText = static function (string $text, int $limit): string {
    $characters = preg_split('//u', trim($text), -1, PREG_SPLIT_NO_EMPTY);

    if ($characters === false || count($characters) <= $limit) {
        return trim($text);
    }

    return implode('', array_slice($characters, 0, $limit)) . '...';
};

/**
 * Exibe o primeiro nome e a inicial do segundo.
 * Nomes sem espaço também recebem um limite visual.
 */
$formatUserName = static function (
    string $fullName
) use ($shortenText): string {
    $parts = preg_split(
        '/\s+/u',
        trim($fullName),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if ($parts === false || $parts === []) {
        return 'Usuário';
    }

    $formattedName = $shortenText($parts[0], 18);

    if (isset($parts[1])) {
        $secondNameCharacters = preg_split(
            '//u',
            $parts[1],
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $secondInitial = $secondNameCharacters[0] ?? '';

        if ($secondInitial !== '') {
            $formattedName .= ' ' . strtoupper($secondInitial) . '.';
        }
    }

    return $formattedName;
};

$displayName = $formatUserName($user['name']);

$displayNameCharacters = preg_split(
    '//u',
    $displayName,
    -1,
    PREG_SPLIT_NO_EMPTY
);

$userInitial = strtoupper($displayNameCharacters[0] ?? 'U');

$currentHour = (int) date('H');

if ($currentHour < 12) {
    $greeting = 'Bom dia';
} elseif ($currentHour < 18) {
    $greeting = 'Boa tarde';
} else {
    $greeting = 'Boa noite';
}

$hasActiveFilters = array_filter($filters) !== [];

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>

<body>
    <div class="dashboard-layout">
        <aside class="sidebar">

            <p class="sidebar__label">Principal</p>

            <nav class="sidebar__nav" aria-label="Navegação principal">
                <a
                    class="sidebar__link sidebar__link--active"
                    href="/dashboard"
                    aria-current="page">
                    <span class="sidebar__icon" aria-hidden="true">▦</span>
                    Dashboard
                </a>

                <a
                    class="sidebar__link"
                    href="/services/create">
                    <span class="sidebar__icon" aria-hidden="true">＋</span>
                    Novo serviço
                </a>
            </nav>

            <div class="sidebar__footer">
                <div class="sidebar__profile">
                    <span class="sidebar__avatar">
                        <?= htmlspecialchars(
                            $userInitial,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <div class="sidebar__profile-text">
                        <strong>
                            <?= htmlspecialchars(
                                $displayName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(
                                $user['email'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>
                </div>

                <form
                    class="sidebar__logout"
                    action="/logout"
                    method="post">
                    <input
                        type="hidden"
                        name="_token"
                        value="<?= htmlspecialchars(
                                    $csrfToken,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                    <button
                        class="button"
                        type="submit">
                        Sair do sistema
                    </button>
                </form>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <p class="dashboard-header__eyebrow">
                        Visão geral
                    </p>

                    <h1>
                        <?= htmlspecialchars(
                            $greeting,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>,

                        <span
                            title="<?= htmlspecialchars(
                                        $user['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                            <?= htmlspecialchars(
                                $displayName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>!
                    </h1>

                    <p class="dashboard-header__description">
                        Acompanhe o desempenho dos serviços em
                        <?= htmlspecialchars(
                            $currentDate,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>.
                    </p>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if ($success !== null): ?>
                    <p class="alert alert--success" role="status">
                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($error !== null): ?>
                    <p class="alert alert--error" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <section
                    class="metrics-grid"
                    aria-label="Indicadores dos serviços">
                    <article class="metric-card metric-card--value">
                        <div class="metric-card__top">
                            <p class="metric-card__label">
                                Valor dos seus serviços
                            </p>

                            <span
                                class="metric-card__icon"
                                aria-hidden="true">
                                R$
                            </span>
                        </div>

                        <p class="metric-card__value">
                            R$ <?= number_format(
                                    $summary['total_value'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                        </p>
                    </article>

                    <article class="metric-card metric-card--total">
                        <div class="metric-card__top">
                            <p class="metric-card__label">
                                Seus serviços
                            </p>

                            <span
                                class="metric-card__icon"
                                aria-hidden="true">
                                #
                            </span>
                        </div>

                        <p class="metric-card__value">
                            <?= $summary['total_services'] ?>
                        </p>
                    </article>

                    <article class="metric-card metric-card--pending">
                        <div class="metric-card__top">
                            <p class="metric-card__label">
                                Seus Serviços pendentes
                            </p>

                            <span
                                class="metric-card__icon"
                                aria-hidden="true">
                                !
                            </span>
                        </div>

                        <p class="metric-card__value">
                            <?= $summary['pending_services'] ?>
                        </p>
                    </article>

                    <article class="metric-card metric-card--finished">
                        <div class="metric-card__top">
                            <p class="metric-card__label">
                                Seus Serviços finalizados
                            </p>

                            <span
                                class="metric-card__icon"
                                aria-hidden="true">
                                ✓
                            </span>
                        </div>

                        <p class="metric-card__value">
                            <?= $summary['finished_services'] ?>
                        </p>
                    </article>
                </section>

                <section class="dashboard-panels">
                    <article class="dashboard-panel">
                        <div class="dashboard-panel__header">
                            <h2>Seus últimos serviços pendentes</h2>

                            <span class="status status--pending">
                                <?= $summary['pending_services'] ?>
                                pendente<?= $summary['pending_services'] === 1
                                            ? ''
                                            : 's' ?>
                            </span>
                        </div>

                        <?php if ($pendingServices === []): ?>
                            <div class="empty-state">
                                Nenhum serviço pendente no momento.
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingServices as $pending): ?>
                                <div class="pending-service">
                                    <span class="pending-service__id">
                                        #<?= (int) $pending['id_service'] ?>
                                    </span>

                                    <div class="pending-service__description">
                                        <strong
                                            title="<?= htmlspecialchars(
                                                        $pending['description'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                            <?= htmlspecialchars(
                                                $shortenText($pending['description'], 38),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                        <span>Aguardando finalização</span>
                                    </div>

                                    <strong class="pending-service__value">
                                        R$ <?= number_format(
                                                (float) $pending['price'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                    </strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </article>

                    <article class="quick-action">
                        <h2>Agilidade no atendimento</h2>

                        <p>
                            Registre um novo serviço e mantenha a
                            operação sempre atualizada.
                        </p>

                        <a
                            class="button button--accent"
                            href="/services/create">
                            Novo serviço
                        </a>
                    </article>
                </section>

                <section
                    class="services-panel"
                    aria-labelledby="services-title">
                    <div class="services-panel__header">
                        <div>
                            <h2 id="services-title">
                                Serviços cadastrados
                            </h2>

                            <p>
                                <?= count($services) ?>
                                resultado<?= count($services) === 1
                                                ? ''
                                                : 's' ?>
                                encontrado<?= count($services) === 1
                                                ? ''
                                                : 's' ?>.
                            </p>
                        </div>
                    </div>

                    <details
                        class="filter-panel"
                        <?= $hasActiveFilters ? 'open' : '' ?>>
                        <summary>
                            Filtros de pesquisa
                            <?= $hasActiveFilters ? '• ativos' : '' ?>
                        </summary>

                        <div class="filter-panel__content">
                            <form action="/dashboard" method="get">
                                <div class="form-grid">
                                    <div class="field">
                                        <label for="description">
                                            Nome do serviço
                                        </label>

                                        <input
                                            id="description"
                                            name="description"
                                            type="text"
                                            value="<?= htmlspecialchars(
                                                        $filters['description'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                    </div>

                                    <div class="field">
                                        <label for="user">
                                            Nome do usuário
                                        </label>

                                        <input
                                            id="user"
                                            name="user"
                                            type="text"
                                            value="<?= htmlspecialchars(
                                                        $filters['user'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                    </div>

                                    <div class="field">
                                        <label for="status">Status</label>

                                        <select id="status" name="status">
                                            <option value="">Todos</option>

                                            <option
                                                value="Pendente"
                                                <?= $filters['status']
                                                    === 'Pendente'
                                                    ? 'selected'
                                                    : '' ?>>
                                                Pendente
                                            </option>

                                            <option
                                                value="Finalizado"
                                                <?= $filters['status']
                                                    === 'Finalizado'
                                                    ? 'selected'
                                                    : '' ?>>
                                                Finalizado
                                            </option>
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label for="start_date">
                                            Data inicial
                                        </label>

                                        <input
                                            id="start_date"
                                            name="start_date"
                                            type="date"
                                            value="<?= htmlspecialchars(
                                                        $filters['start_date'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                    </div>

                                    <div class="field">
                                        <label for="end_date">
                                            Data final
                                        </label>

                                        <input
                                            id="end_date"
                                            name="end_date"
                                            type="date"
                                            value="<?= htmlspecialchars(
                                                        $filters['end_date'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button
                                        class="button button--primary"
                                        type="submit">
                                        Aplicar filtros
                                    </button>

                                    <a
                                        class="button button--secondary"
                                        href="/dashboard">
                                        Limpar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </details>

                    <?php if ($services === []): ?>
                        <div class="empty-state">
                            Nenhum serviço foi encontrado.
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Descrição</th>
                                        <th>Status</th>
                                        <th>Valor</th>
                                        <th>Usuário</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td data-label="ID">
                                                #<?= (int) $service['id_service'] ?>
                                            </td>

                                            <td
                                                class="service-description"
                                                data-label="Descrição"
                                                title="<?= htmlspecialchars(
                                                            $service['description'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">
                                                <?= htmlspecialchars(
                                                    $shortenText($service['description'], 48),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td data-label="Status">
                                                <?php
                                                $statusClass =
                                                    $service['finished_at']
                                                    === null
                                                    ? 'status--pending'
                                                    : 'status--finished';
                                                ?>

                                                <span
                                                    class="status <?= $statusClass ?>">
                                                    <?= htmlspecialchars(
                                                        $service['status'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>
                                            </td>

                                            <td data-label="Valor">
                                                R$ <?= number_format(
                                                        (float) $service['price'],
                                                        2,
                                                        ',',
                                                        '.'
                                                    ) ?>
                                            </td>

                                            <td
                                                class="service-user"
                                                data-label="Usuário"
                                                title="<?= htmlspecialchars(
                                                            $service['user_name'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">
                                                <span class="service-user__name">
                                                    <?= htmlspecialchars(
                                                        $formatUserName($service['user_name']),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>
                                            </td>

                                            <td data-label="Ações">
                                                <div class="table-actions">
                                                    <?php if (
                                                        $service['finished_at']
                                                        === null
                                                    ): ?>
                                                        <a
                                                            class="button button--secondary button--small"
                                                            href="/services/edit?id=<?= (int) $service['id_service'] ?>"
                                                            aria-label="Editar serviço <?= (int) $service['id_service'] ?>"
                                                            title="Alterar descrição ou valor">
                                                            <svg
                                                                class="action-icon"
                                                                viewBox="0 0 24 24"
                                                                aria-hidden="true">
                                                                <path d="M12 20h9"></path>
                                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"></path>
                                                            </svg>

                                                            Editar
                                                        </a>

                                                        <form
                                                            action="/services/finish"
                                                            method="post"
                                                            data-confirm-finish>
                                                            <input
                                                                type="hidden"
                                                                name="_token"
                                                                value="<?= htmlspecialchars(
                                                                            $csrfToken,
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ) ?>">

                                                            <input
                                                                type="hidden"
                                                                name="service_id"
                                                                value="<?= (int) $service['id_service'] ?>">

                                                            <button
                                                                class="button button--primary button--small"
                                                                type="submit"
                                                                aria-label="Finalizar serviço <?= (int) $service['id_service'] ?>"
                                                                title="Registrar a conclusão e calcular a comissão">
                                                                <svg
                                                                    class="action-icon"
                                                                    viewBox="0 0 24 24"
                                                                    aria-hidden="true">
                                                                    <circle cx="12" cy="12" r="9"></circle>
                                                                    <path d="m8 12 2.5 2.5L16 9"></path>
                                                                </svg>

                                                                Finalizar
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span
                                                            class="status status--finished table-action-status"
                                                            title="Este serviço não pode mais ser editado">

                                                            Concluído
                                                        </span>
                                                    <?php endif; ?>

                                                    <form
                                                        class="table-action-delete"
                                                        action="/services/delete"
                                                        method="post"
                                                        data-confirm-delete>
                                                        <input
                                                            type="hidden"
                                                            name="_token"
                                                            value="<?= htmlspecialchars(
                                                                        $csrfToken,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>">

                                                        <input
                                                            type="hidden"
                                                            name="service_id"
                                                            value="<?= (int) $service['id_service'] ?>">

                                                        <button
                                                            class="button button--danger button--small"
                                                            type="submit"
                                                            aria-label="Excluir serviço <?= (int) $service['id_service'] ?>"
                                                            title="Excluir permanentemente">
                                                            <svg
                                                                class="action-icon"
                                                                viewBox="0 0 24 24"
                                                                aria-hidden="true">
                                                                <path d="M4 7h16"></path>
                                                                <path d="M9 7V4h6v3"></path>
                                                                <path d="m6 7 1 13h10l1-13"></path>
                                                                <path d="M10 11v5"></path>
                                                                <path d="M14 11v5"></path>
                                                            </svg>

                                                            Excluir
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
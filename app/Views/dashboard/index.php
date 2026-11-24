<?php

/**
 * UC12 - Dashboard.
 *
 * @var array{total_services: int, pending_services: int, finished_services: int,
 *            total_value: string, total_commission: string} $summary
 * @var array $pendingServices
 * @var bool $isManager
 */

require dirname(__DIR__) . '/layout/top.php';



?>
<section class="metrics-grid" aria-label="Indicadores dos serviços">
    <article class="metric-card metric-card--value">
        <div class="metric-card__top">
            <p class="metric-card__label">Valor dos serviços <?= $isManager ? 'da operação' : '(seus)' ?></p>
            <span class="metric-card__icon" aria-hidden="true">R$</span>
        </div>
        <p class="metric-card__value"><?= money($summary['total_value']) ?></p>
        <p class="metric-card__note">Soma dos valores cadastrados, não representa pagamentos recebidos.</p>
    </article>

    <article class="metric-card metric-card--total">
        <div class="metric-card__top">
            <p class="metric-card__label">Total de serviços</p>
            <span class="metric-card__icon" aria-hidden="true">#</span>
        </div>
        <p class="metric-card__value"><?= $summary['total_services'] ?></p>
    </article>

    <article class="metric-card metric-card--pending">
        <div class="metric-card__top">
            <p class="metric-card__label">Pendentes</p>
            <span class="metric-card__icon" aria-hidden="true">!</span>
        </div>
        <p class="metric-card__value"><?= $summary['pending_services'] ?></p>
    </article>

    <article class="metric-card metric-card--finished">
        <div class="metric-card__top">
            <p class="metric-card__label">Finalizados</p>
            <span class="metric-card__icon" aria-hidden="true">✓</span>
        </div>
        <p class="metric-card__value"><?= $summary['finished_services'] ?></p>
        <p class="metric-card__note">Comissões: <?= money($summary['total_commission']) ?></p>
    </article>
</section>

<section class="dashboard-panels">
    <article class="dashboard-panel">
        <div class="dashboard-panel__header">
            <h2>Pendências mais recentes</h2>
            <a class="button button--secondary button--small" href="/services?status=pendente">Ver todas</a>
        </div>

        <?php if ($pendingServices === []): ?>
            <div class="empty-state">
                <?= $summary['total_services'] === 0
                    ? 'Nenhum serviço cadastrado ainda.'
                    : 'Nenhum serviço pendente no momento.' ?>
            </div>
        <?php else: ?>
            <?php foreach ($pendingServices as $pending): ?>
                <div class="pending-service">
                    <span class="pending-service__id">#<?= (int) $pending['id_service'] ?></span>

                    <div class="pending-service__description">
                        <strong title="<?= e($pending['description']) ?>"><?= e($pending['description']) ?></strong>
                        <span><?= e($pending['client_name']) ?><?= $isManager ? ' · ' . e($pending['user_name']) : '' ?> · <?= date_br($pending['created_at']) ?></span>
                    </div>

                    <strong class="pending-service__value"><?= money($pending['price']) ?></strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </article>

    <article class="quick-action">
        <h2>Agilidade no atendimento</h2>
        <p>Registre um novo serviço e mantenha a operação sempre atualizada.</p>
        <a class="button button--accent" href="/services/create">Novo serviço</a>
    </article>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

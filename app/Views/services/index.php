<?php

/**
 * UC11 - Pesquisar serviços.
 *
 * @var array $filters
 * @var array $query
 * @var array{rows: array, total: int, page: int, totalPages: int} $result
 * @var array $responsibles
 * @var bool $isManager
 * @var int $currentUserId
 */

$subtitle = $isManager
    ? 'Todos os serviços da operação.'
    : 'Somente os serviços sob a sua responsabilidade.';

require dirname(__DIR__) . '/layout/top.php';

$hasFilters = $query !== [];

?>
<section class="services-panel" aria-labelledby="services-title">
    <div class="services-panel__header">
        <div>
            <h2 id="services-title">Serviços cadastrados</h2>
            <p><?= $result['total'] ?> resultado<?= $result['total'] === 1 ? '' : 's' ?> encontrado<?= $result['total'] === 1 ? '' : 's' ?>.</p>
        </div>

        <a class="button button--primary" href="/services/create">＋ Novo serviço</a>
    </div>

    <details class="filter-panel" <?= $hasFilters ? 'open' : '' ?>>
        <summary>Filtros de pesquisa<?= $hasFilters ? ' • ativos' : '' ?></summary>

        <div class="filter-panel__content">
            <form action="/services" method="get">
                <div class="form-grid">
                    <div class="field">
                        <label for="description">Descrição</label>
                        <input id="description" name="description" type="text" value="<?= e($filters['description']) ?>">
                    </div>

                    <div class="field">
                        <label for="client">Cliente</label>
                        <input id="client" name="client" type="text" value="<?= e($filters['client']) ?>">
                    </div>

                    <?php if ($isManager): ?>
                        <div class="field">
                            <label for="user_id">Responsável</label>
                            <select id="user_id" name="user_id">
                                <option value="">Todos</option>
                                <?php foreach ($responsibles as $responsible): ?>
                                    <option value="<?= (int) $responsible['id_user'] ?>" <?= $filters['user_id'] === (int) $responsible['id_user'] ? 'selected' : '' ?>>
                                        <?= e($responsible['name']) ?><?= (int) $responsible['active'] === 1 ? '' : ' (inativo)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="status">Situação</label>
                        <select id="status" name="status">
                            <option value="">Todas</option>
                            <option value="pendente" <?= $filters['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="finalizado" <?= $filters['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="start_date">Cadastrado a partir de</label>
                        <input id="start_date" name="start_date" type="date" value="<?= e($filters['start_date']) ?>">
                    </div>

                    <div class="field">
                        <label for="end_date">Cadastrado até</label>
                        <input id="end_date" name="end_date" type="date" value="<?= e($filters['end_date']) ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button--primary" type="submit">Aplicar filtros</button>
                    <a class="button button--secondary" href="/services">Limpar</a>
                </div>
            </form>
        </div>
    </details>

    <?php if ($result['rows'] === []): ?>
        <div class="empty-state">Nenhum serviço foi encontrado.</div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Cliente</th>
                        <th>Responsável</th>
                        <th>Valor</th>
                        <th>Situação</th>
                        <th>Cadastro</th>
                        <th>Conclusão</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($result['rows'] as $service): ?>
                        <?php
                        $isPending = $service['finished_at'] === null;
                        $canChange = $isPending && ($isManager || (int) $service['user_id'] === $currentUserId);
                        ?>
                        <tr>
                            <td data-label="ID">#<?= (int) $service['id_service'] ?></td>
                            <td class="service-description" data-label="Descrição" title="<?= e($service['description']) ?>"><?= e($service['description']) ?></td>
                            <td data-label="Cliente">
                                <a href="/clients/show?id=<?= (int) $service['client_id'] ?>"><?= e($service['client_name']) ?></a>
                                <?= (int) $service['client_active'] === 1 ? '' : '<small>(inativo)</small>' ?>
                            </td>
                            <td data-label="Responsável"><?= e($service['user_name']) ?></td>
                            <td data-label="Valor"><?= money($service['price']) ?></td>
                            <td data-label="Situação">
                                <span class="status <?= $isPending ? 'status--pending' : 'status--finished' ?>">
                                    <?= $isPending ? 'Pendente' : 'Finalizado' ?>
                                </span>
                            </td>
                            <td data-label="Cadastro"><?= date_br($service['created_at']) ?></td>
                            <td data-label="Conclusão">
                                <?php if ($isPending): ?>
                                    —
                                <?php else: ?>
                                    <?= datetime_br($service['finished_at']) ?><br>
                                    <small>Comissão <?= money($service['commission_user']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Ações">
                                <div class="table-actions">
                                    <?php if ($canChange): ?>
                                        <a class="button button--secondary button--small" href="/services/edit?id=<?= (int) $service['id_service'] ?>">Editar</a>

                                        <form action="/services/finish" method="post"
                                            data-confirm="Finalizar serviço?"
                                            data-confirm-message="<?= e('#' . $service['id_service'] . ' - ' . $service['description'] . ' (' . money($service['price']) . ')') ?>"
                                            data-confirm-warning="A data de conclusão e a comissão serão registradas. Depois disso o serviço não poderá ser editado nem excluído."
                                            data-confirm-button="Sim, finalizar">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="service_id" value="<?= (int) $service['id_service'] ?>">
                                            <button class="button button--primary button--small" type="submit">Finalizar</button>
                                        </form>
                                    <?php elseif (!$isPending): ?>
                                        <span class="status status--finished table-action-status" title="Serviço finalizado: não pode ser alterado">Concluído</span>
                                    <?php endif; ?>

                                    <?php if ($isManager && $isPending): ?>
                                        <form class="table-action-delete" action="/services/delete" method="post"
                                            data-confirm="Excluir serviço?"
                                            data-confirm-message="<?= e('#' . $service['id_service'] . ' - ' . $service['description']) ?>"
                                            data-confirm-warning="A exclusão é permanente."
                                            data-confirm-button="Sim, excluir"
                                            data-confirm-variant="danger">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="service_id" value="<?= (int) $service['id_service'] ?>">
                                            <button class="button button--danger button--small" type="submit">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        $page = $result['page'];
        $totalPages = $result['totalPages'];
        $totalRows = $result['total'];
        $basePath = '/services';
        require dirname(__DIR__) . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

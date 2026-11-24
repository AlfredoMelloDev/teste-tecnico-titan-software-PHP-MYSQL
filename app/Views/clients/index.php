<?php

/**
 * UC05 (gestor) e UC06 (funcionário) - consulta de clientes.
 *
 * @var string $search
 * @var array $query
 * @var array{rows: array, total: int, page: int, totalPages: int} $result
 * @var bool $isManager
 */

$subtitle = $isManager
    ? 'Clientes ativos e inativos.'
    : 'Localize clientes ativos pelo nome.';

require dirname(__DIR__) . '/layout/top.php';

?>
<section class="services-panel">
    <div class="services-panel__header">
        <div>
            <h2>Clientes</h2>
            <p><?= $result['total'] ?> encontrado<?= $result['total'] === 1 ? '' : 's' ?>.</p>
        </div>

        <a class="button button--primary" href="/clients/create">＋ Novo cliente</a>
    </div>

    <form class="search-bar" action="/clients" method="get" role="search">
        <label for="search">Buscar por nome</label>
        <input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Parte do nome">
        <button class="button button--secondary" type="submit">Buscar</button>
    </form>

    <?php if ($result['rows'] === []): ?>
        <div class="empty-state">Nenhum cliente encontrado.</div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <?php if ($isManager): ?><th>Situação</th><?php endif; ?>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $client): ?>
                        <tr>
                            <td data-label="Nome"><?= e($client['name']) ?></td>
                            <td data-label="Telefone"><?= e($client['phone'] ?? '—') ?></td>
                            <td data-label="E-mail"><?= e($client['email'] ?? '—') ?></td>
                            <?php if ($isManager): ?>
                                <td data-label="Situação">
                                    <span class="status <?= (int) $client['active'] === 1 ? 'status--finished' : 'status--pending' ?>">
                                        <?= (int) $client['active'] === 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <td data-label="Ações">
                                <div class="table-actions">
                                    <a class="button button--secondary button--small" href="/clients/show?id=<?= (int) $client['id_client'] ?>">Ver</a>
                                    <?php if ($isManager): ?>
                                        <a class="button button--secondary button--small" href="/clients/edit?id=<?= (int) $client['id_client'] ?>">Editar</a>
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
        $basePath = '/clients';
        require dirname(__DIR__) . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

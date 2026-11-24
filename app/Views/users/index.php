<?php

/**
 * UC03 - lista de usuários (gestor).
 *
 * @var string $search
 * @var array $query
 * @var array{rows: array, total: int, page: int, totalPages: int} $result
 * @var int $currentUserId
 */

$subtitle = 'Cadastro, edição e inativação de quem acessa o sistema.';

require dirname(__DIR__) . '/layout/top.php';

?>
<section class="services-panel">
    <div class="services-panel__header">
        <div>
            <h2>Usuários</h2>
            <p><?= $result['total'] ?> encontrado<?= $result['total'] === 1 ? '' : 's' ?>.</p>
        </div>

        <a class="button button--primary" href="/users/create">＋ Novo usuário</a>
    </div>

    <form class="search-bar" action="/users" method="get" role="search">
        <label for="search">Buscar por nome ou e-mail</label>
        <input id="search" name="search" type="search" value="<?= e($search) ?>">
        <button class="button button--secondary" type="submit">Buscar</button>
    </form>

    <?php if ($result['rows'] === []): ?>
        <div class="empty-state">Nenhum usuário encontrado.</div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $user): ?>
                        <?php $isActive = (int) $user['active'] === 1; ?>
                        <tr>
                            <td data-label="Nome"><?= e($user['name']) ?><?= (int) $user['id_user'] === $currentUserId ? ' <small>(você)</small>' : '' ?></td>
                            <td data-label="E-mail"><?= e($user['email']) ?></td>
                            <td data-label="Perfil"><?= $user['role'] === 'gestor' ? 'Gestor' : 'Funcionário' ?></td>
                            <td data-label="Situação">
                                <span class="status <?= $isActive ? 'status--finished' : 'status--pending' ?>"><?= $isActive ? 'Ativo' : 'Inativo' ?></span>
                            </td>
                            <td data-label="Ações">
                                <div class="table-actions">
                                    <a class="button button--secondary button--small" href="/users/edit?id=<?= (int) $user['id_user'] ?>">Editar</a>

                                    <?php if ($isActive): ?>
                                        <form action="/users/deactivate" method="post"
                                            data-confirm="Inativar usuário?"
                                            data-confirm-message="<?= e($user['name'] . ' (' . $user['email'] . ')') ?>"
                                            data-confirm-warning="O usuário deixará de acessar o sistema. Os serviços dele serão preservados."
                                            data-confirm-button="Sim, inativar"
                                            data-confirm-variant="danger">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id_user'] ?>">
                                            <button class="button button--danger button--small" type="submit">Inativar</button>
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
        $basePath = '/users';
        require dirname(__DIR__) . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

<?php

/**
 * Dados de um cliente (UC05 / UC06).
 *
 * @var array $client
 * @var bool $isManager
 */

$isActive = (int) $client['active'] === 1;
$subtitle = $isActive ? 'Cliente ativo.' : 'Cliente inativo: não pode ser vinculado a novos serviços.';

require dirname(__DIR__) . '/layout/top.php';

?>
<section class="form-card form-card--wide">
    <dl class="details">
        <dt>Nome</dt>
        <dd><?= e($client['name']) ?></dd>

        <dt>Telefone</dt>
        <dd><?= e($client['phone'] ?? '—') ?></dd>

        <dt>E-mail</dt>
        <dd><?= e($client['email'] ?? '—') ?></dd>

        <dt>Situação</dt>
        <dd><?= $isActive ? 'Ativo' : 'Inativo' ?></dd>

        <dt>Cadastrado em</dt>
        <dd><?= date_br($client['created_at']) ?></dd>
    </dl>

    <div class="form-actions">
        <a class="button button--secondary" href="/clients">Voltar</a>

        <?php if ($isActive): ?>
            <a class="button button--primary" href="/services/create">Registrar serviço</a>
        <?php endif; ?>

        <?php if ($isManager): ?>
            <a class="button button--secondary" href="/clients/edit?id=<?= (int) $client['id_client'] ?>">Editar</a>

            <?php if ($isActive): ?>
                <form action="/clients/deactivate" method="post"
                    data-confirm="Inativar cliente?"
                    data-confirm-message="<?= e($client['name']) ?>"
                    data-confirm-warning="Os serviços anteriores serão preservados, mas o cliente não poderá ser vinculado a novos serviços."
                    data-confirm-button="Sim, inativar"
                    data-confirm-variant="danger">
                    <?= csrf_field() ?>
                    <input type="hidden" name="client_id" value="<?= (int) $client['id_client'] ?>">
                    <button class="button button--danger" type="submit">Inativar</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

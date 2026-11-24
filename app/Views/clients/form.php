<?php

/**
 * UC04 (cadastrar) e UC05 FA1 (editar cliente).
 *
 * @var array|null $client
 * @var array $old
 */

$isEdit = $client !== null;
$field = static fn (string $name): string => (string) ($old[$name] ?? ($isEdit ? ($client[$name] ?? '') : ''));

$subtitle = 'Nome obrigatório; telefone e e-mail opcionais.';

require dirname(__DIR__) . '/layout/top.php';

?>
<section class="form-card form-card--wide">
    <form action="<?= $isEdit ? '/clients/update' : '/clients' ?>" method="post">
        <?= csrf_field() ?>

        <?php if ($isEdit): ?>
            <input type="hidden" name="client_id" value="<?= (int) $client['id_client'] ?>">
        <?php endif; ?>

        <div class="field">
            <label for="name">Nome</label>
            <input id="name" name="name" type="text" maxlength="150" value="<?= e($field('name')) ?>" required>
        </div>

        <div class="field">
            <label for="phone">Telefone (opcional)</label>
            <input id="phone" name="phone" type="tel" maxlength="20" placeholder="(51) 99999-9999" value="<?= e($field('phone')) ?>">
        </div>

        <div class="field">
            <label for="email">E-mail (opcional)</label>
            <input id="email" name="email" type="email" maxlength="100" value="<?= e($field('email')) ?>">
        </div>

        <div class="form-actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar cliente' ?></button>
            <a class="button button--secondary" href="/clients">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

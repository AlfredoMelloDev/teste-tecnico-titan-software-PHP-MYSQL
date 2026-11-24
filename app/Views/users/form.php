<?php

/**
 * UC03 - cadastrar ou editar usuário (gestor).
 *
 * @var array|null $user
 * @var array $old
 */

$isEdit = $user !== null;
$field = static fn (string $name, string $default = ''): string =>
    (string) ($old[$name] ?? ($isEdit ? ($user[$name] ?? $default) : $default));

$role = $field('role', 'funcionario');
$subtitle = $isEdit
    ? 'A senha não é alterada por esta tela.'
    : 'O usuário entra com o e-mail e a senha inicial informados aqui.';

require dirname(__DIR__) . '/layout/top.php';

?>
<section class="form-card form-card--wide">
    <form action="<?= $isEdit ? '/users/update' : '/users' ?>" method="post">
        <?= csrf_field() ?>

        <?php if ($isEdit): ?>
            <input type="hidden" name="user_id" value="<?= (int) $user['id_user'] ?>">
        <?php endif; ?>

        <div class="field">
            <label for="name">Nome</label>
            <input id="name" name="name" type="text" maxlength="150" value="<?= e($field('name')) ?>" required>
        </div>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" maxlength="100" value="<?= e($field('email')) ?>" required>
        </div>

        <div class="field">
            <label for="role">Perfil</label>
            <select id="role" name="role" required>
                <option value="funcionario" <?= $role === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                <option value="gestor" <?= $role === 'gestor' ? 'selected' : '' ?>>Gestor</option>
            </select>
        </div>

        <?php if (!$isEdit): ?>
            <div class="field">
                <label for="password">Senha inicial</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                <p class="help-text">Pelo menos 8 caracteres.</p>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar usuário' ?></button>
            <a class="button button--secondary" href="/users">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

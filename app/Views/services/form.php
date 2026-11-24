<?php

/**
 * UC07 (registrar) e UC08 (editar serviço pendente).
 *
 * @var array|null $service null no cadastro
 * @var array $old dados digitados na tentativa anterior
 * @var array $clients clientes que podem ser escolhidos
 * @var array $responsibles usuários ativos (somente para o gestor)
 * @var bool $isManager
 */

$isEdit = $service !== null;

$value = static fn (string $field, string $default = ''): string =>
    (string) ($old[$field] ?? $default);

$description = $value('description', $isEdit ? $service['description'] : '');
$price = $value('price', $isEdit ? number_format((float) $service['price'], 2, ',', '') : '');
$clientId = (int) $value('client_id', $isEdit ? (string) $service['client_id'] : '0');
$userId = (int) $value('user_id', $isEdit ? (string) $service['user_id'] : '0');

$subtitle = $isEdit
    ? 'Somente serviços pendentes podem ser alterados.'
    : 'O serviço será cadastrado como pendente, com a data de hoje.';

require dirname(__DIR__) . '/layout/top.php';

?>
<section class="form-card form-card--wide">
    <?php if ($clients === []): ?>
        <p class="alert alert--error">
            Não há clientes ativos. <a href="/clients/create">Cadastre um cliente</a> antes de registrar o serviço.
        </p>
    <?php endif; ?>

    <form action="<?= $isEdit ? '/services/update' : '/services' ?>" method="post">
        <?= csrf_field() ?>

        <?php if ($isEdit): ?>
            <input type="hidden" name="service_id" value="<?= (int) $service['id_service'] ?>">
        <?php endif; ?>

        <div class="field">
            <label for="client_search">Cliente</label>
            <input id="client_search" type="search" placeholder="Digite parte do nome para localizar" data-filter-select="client_id" autocomplete="off">
            <select id="client_id" name="client_id" required>
                <option value="">Selecione o cliente</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id_client'] ?>" <?= $clientId === (int) $client['id_client'] ? 'selected' : '' ?>>
                        <?= e($client['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="help-text">Não encontrou? <a href="/clients/create">Cadastre um novo cliente</a>.</p>
        </div>

        <div class="field">
            <label for="description">Descrição do serviço</label>
            <input id="description" name="description" type="text" maxlength="255" value="<?= e($description) ?>" required>
            <p class="help-text">Até 255 caracteres.</p>
        </div>

        <div class="field">
            <label for="price">Valor (R$)</label>
            <input id="price" name="price" type="text" maxlength="15" inputmode="decimal" placeholder="Ex.: 250,00" value="<?= e($price) ?>" required>
            <p class="help-text">Maior que zero, com até duas casas decimais.</p>
        </div>

        <?php if ($isManager): ?>
            <div class="field">
                <label for="user_id">Responsável</label>
                <select id="user_id" name="user_id" required>
                    <option value="">Selecione o responsável</option>
                    <?php foreach ($responsibles as $responsible): ?>
                        <option value="<?= (int) $responsible['id_user'] ?>" <?= $userId === (int) $responsible['id_user'] ? 'selected' : '' ?>>
                            <?= e($responsible['name']) ?> (<?= $responsible['role'] === 'gestor' ? 'gestor' : 'funcionário' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <p class="help-text">Você será o responsável por este serviço.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar serviço' ?></button>
            <a class="button button--secondary" href="/services">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

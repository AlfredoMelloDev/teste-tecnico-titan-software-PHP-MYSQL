<?php

/**
 * Variáveis fornecidas pelo ServiceController.
 *
 * @var string $title
 * @var array<string, mixed> $service
 * @var string|null $error
 * @var array<string, string> $old
 */

$description = $old['description']
    ?? $service['description'];

$price = $old['price']
    ?? number_format(
        (float) $service['price'],
        2,
        ',',
        ''
    );

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <main>
        <h1>Editar Serviço</h1>

        <p>
            Serviço:
            <strong>#<?= (int) $service['id_service'] ?></strong>
        </p>

        <?php if ($error !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="/services/update" method="post">
            <!-- O identificador não deve ser alterado pelo formulário. -->
            <input
                type="hidden"
                name="service_id"
                value="<?= (int) $service['id_service'] ?>"
            >

            <div>
                <label for="description">Descrição</label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    maxlength="255"
                    value="<?= htmlspecialchars(
                        $description,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >
            </div>

            <div>
                <label for="price">Valor</label>
                <input
                    type="text"
                    id="price"
                    name="price"
                    maxlength="12"
                    inputmode="decimal"
                    value="<?= htmlspecialchars(
                        $price,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >
            </div>

            <button type="submit">Salvar alterações</button>
        </form>

        <a href="/dashboard">Cancelar e voltar</a>
    </main>
</body>
</html>
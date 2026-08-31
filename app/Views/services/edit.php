<?php

use App\Core\Csrf;

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

// O token acompanha o formulário para validar a origem da alteração.
$csrfToken = Csrf::token();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar__content">
            <a class="brand" href="/dashboard">
                <span>Gerenciamento de Serviços</span>
            </a>
        </div>
    </header>

    <main class="form-page">
        <a class="back-link" href="/dashboard">
            ← Voltar para o dashboard
        </a>

        <section class="form-card">
            <div class="form-card__header">
                <h1>Editar serviço</h1>

                <p>
                    Serviço
                    <strong>
                        #<?= (int) $service['id_service'] ?>
                    </strong>
                </p>
            </div>

            <?php if ($error !== null): ?>
                <p class="alert alert--error" role="alert">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            <?php endif; ?>

            <form action="/services/update" method="post">
                <input
                    type="hidden"
                    name="_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <!-- O identificador não pode ser alterado pelo formulário. -->
                <input
                    type="hidden"
                    name="service_id"
                    value="<?= (int) $service['id_service'] ?>"
                >

                <div class="field">
                    <label for="description">
                        Descrição do serviço
                    </label>

                    <input
                        id="description"
                        name="description"
                        type="text"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $description,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                        autofocus
                    >

                    <p class="help-text">
                        Atualize a descrição do serviço quando necessário.
                    </p>
                </div>

                <div class="field">
                    <label for="price">Valor</label>

                    <input
                        id="price"
                        name="price"
                        type="text"
                        maxlength="12"
                        inputmode="decimal"
                        placeholder="Ex.: 250,00"
                        value="<?= htmlspecialchars(
                            $price,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button
                        class="button button--primary"
                        type="submit"
                    >
                        Salvar alterações
                    </button>

                    <a
                        class="button button--secondary"
                        href="/dashboard"
                    >
                        Cancelar
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>

</html>
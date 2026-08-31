<?php

use App\Core\Csrf;

/**
 * Variáveis fornecidas pelo ServiceController.
 *
 * @var string $title
 * @var string|null $error
 * @var array<string, string> $old
 */

// O token acompanha o formulário para validar a origem do cadastro.
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
                <span>Controle de Serviços</span>
            </a>
        </div>
    </header>

    <main class="form-page">
        <a class="back-link" href="/dashboard">
            ← Voltar para o dashboard
        </a>

        <section class="form-card">
            <div class="form-card__header">
                <h1>Cadastrar novo serviço</h1>

                <p>
                    Informe a descrição e o valor do serviço prestado.
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

            <form action="/services" method="post">
                <input
                    type="hidden"
                    name="_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
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
                            $old['description'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        autocomplete="off"
                        required
                        autofocus
                    >

                    <p class="help-text">
                        Descreva de forma objetiva o serviço realizado.
                    </p>
                </div>

                <div class="field">
                    <label for="price">Valor</label>

                    <!-- O campo aceita vírgula ou ponto como separador decimal. -->
                    <input
                        id="price"
                        name="price"
                        type="text"
                        maxlength="12"
                        inputmode="decimal"
                        placeholder="Ex.: 250,00"
                        value="<?= htmlspecialchars(
                            $old['price'] ?? '',
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
                        Cadastrar serviço
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
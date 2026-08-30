<?php

use App\Core\Csrf;

/**
 * Variáveis fornecidas pelo ServiceController.
 *
 * @var string $title
 * @var string|null $error
 * @var array<string, string> $old
 */

// O token CSRF é incluído em todos os formulários que alteram dados.
$csrfToken = Csrf::token();
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
        <h1>Cadastrar Novo Serviço</h1>

        <?php if ($error !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="/services" method="post">

            // Token CSRF
            <input
                type="hidden"
                name="_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div>
                
                <label for="description">Descrição</label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    maxlength="255"
                    value="<?= htmlspecialchars(
                                $old['description'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    required>
            </div>

            <div>
                <label for="price">Valor</label>

                <!-- O campo aceita vírgula ou ponto como separador decimal. -->
                <input
                    type="text"
                    id="price"
                    name="price"
                    maxlength="12"
                    inputmode="decimal"
                    placeholder="Ex.: 250,00"
                    value="<?= htmlspecialchars(
                                $old['price'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    required>
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <a href="/dashboard">Voltar para o dashboard</a>
    </main>
</body>

</html>
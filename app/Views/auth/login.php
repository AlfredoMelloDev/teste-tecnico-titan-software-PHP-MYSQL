<?php

use App\Core\Csrf;

/**
 * Variáveis fornecidas pelo AuthController.
 *
 * @var string $title
 * @var string|null $error
 * @var string|null $success
 * @var string $oldEmail
 */

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
        <h1>Sistema de Controle de Serviços</h1>

        <?php if ($success !== null): ?>
            <p role="status">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <p role="alert">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>

        <form action="/login" method="post">

            // Token CSRF
            <input
                type="hidden"
                name="_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div>

                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    autocomplete="email"
                    value="<?= htmlspecialchars(
                                $oldEmail,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    required>
            </div>

            <div>
                <label for="password">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required>
            </div>

            <button type="submit">Entrar</button>
        </form>

        <a href="/users/create">Cadastrar usuário</a>
    </main>
</body>

</html>
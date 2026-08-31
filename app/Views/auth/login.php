<?php

use App\Core\Csrf;

/**
 * Dados preparados pelo AuthController.
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
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>
    <main class="auth-page">
        <section class="auth-presentation">
            <div class="auth-presentation__content">

                <h1>Mais controle para o seu negócio em um só lugar.</h1>

                <p>
                    Organize serviços, acompanhe resultados e transforme
                    a rotina da sua operação em decisões mais eficientes.
                </p>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-card__header">
                    <h2>Bem-vindo</h2>
                    <p>Informe seus dados para acessar o sistema.</p>
                </div>

                <?php if ($success !== null): ?>
                    <p class="alert alert--success" role="status">
                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($error !== null): ?>
                    <p class="alert alert--error" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <form
                    class="form-stack"
                    action="/login"
                    method="post">
                    <input
                        type="hidden"
                        name="_token"
                        value="<?= htmlspecialchars(
                                    $csrfToken,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                    <div class="field">
                        <label for="email">E-mail</label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?= htmlspecialchars(
                                        $oldEmail,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            autocomplete="email"
                            required
                            autofocus>
                    </div>

                    <div class="field">
                        <label for="password">Senha</label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required>
                    </div>

                    <button
                        class="button button--primary"
                        type="submit">
                        Entrar
                    </button>
                </form>

                <p class="form-footer">
                    Ainda não possui uma conta?
                    <a href="/users/create">Cadastrar usuário</a>
                </p>
            </div>
        </section>
    </main>
</body>

</html>
<?php

use App\Core\Csrf;

/**
 * Variáveis fornecidas pelo UserController.
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
                    <h2>Criar uma conta</h2>

                    <p>
                        Preencha os campos para criar um usuário .
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

                <form
                    class="form-stack"
                    action="/users"
                    method="post">

                    <!-- Token CSRF -->
                    <input
                        type="hidden"
                        name="_token"
                        value="<?= htmlspecialchars(
                                    $csrfToken,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                    <div class="field">
                        <label for="name">Nome</label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                        $old['name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            autocomplete="name"
                            required
                            autofocus>
                    </div>

                    <div class="field">
                        <label for="email">E-mail</label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            maxlength="100"
                            value="<?= htmlspecialchars(
                                        $old['email'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            autocomplete="email"
                            required>
                    </div>

                    <div class="field">
                        <label for="password">Senha</label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            autocomplete="new-password"
                            required>

                        <p class="help-text">
                            Utilize pelo menos oito caracteres.
                        </p>
                    </div>

                    <button
                        class="button button--primary"
                        type="submit">
                        Cadastrar
                    </button>
                </form>

                <p class="form-footer">
                    Já possui uma conta?
                    <a href="/login">Voltar para o login</a>
                </p>
            </div>
        </section>
    </main>
</body>

</html>
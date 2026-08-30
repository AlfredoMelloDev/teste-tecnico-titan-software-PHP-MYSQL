<?php

use App\Core\Csrf;

/**
 * Variáveis fornecidas pelo UserController.
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
        <h1>Cadastrar Novo Usuário</h1>

        <?php if ($error !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="/users" method="post">
            
            // Token CSRF
            <input
                type="hidden"
                name="_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="name">Nome</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                                $old['name'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    required>
            </div>

            <div>
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                                $old['email'] ?? '',
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
                    minlength="8"
                    autocomplete="new-password"
                    required>
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <a href="/login">Voltar para o login</a>
    </main>
</body>

</html>
<?php

/**
 * Dados preparados pelo DashboardController.
 *
 * @var string $title
 * @var array{id: int, name: string, email: string} $user
 * @var string $currentDate
 * @var string|null $success
 * @var string|null $error
 * @var array<int, array<string, mixed>> $services
 * @var string $totalValue
 * @var array<int, array<string, mixed>> $pendingServices
 * @var array{
 *     description: string,
 *     status: string,
 *     user: string,
 *     start_date: string,
 *     end_date: string
 * } $filters
 */

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body>
    <header>
        <h1>Dashboard</h1>

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

        <p>
            Logado como:
            <strong>
                <?= htmlspecialchars(
                    $user['name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </p>

        <p>
            E-mail:
            <?= htmlspecialchars(
                $user['email'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <p>
            Data atual:
            <?= htmlspecialchars(
                $currentDate,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <form action="/logout" method="post">
            <button type="submit">Sair</button>
        </form>
    </header>

    <main>
        <!-- Indicadores relacionados ao usuário que está logado. -->
        <section>
            <h2>Resumo</h2>

            <article>
                <h3>Valor total dos seus serviços</h3>

                <strong>
                    R$ <?= number_format(
                            (float) $totalValue,
                            2,
                            ',',
                            '.'
                        ) ?>
                </strong>
            </article>
        </section>

        <section>
            <h2>Seus últimos serviços pendentes</h2>

            <?php if ($pendingServices === []): ?>
                <p>Você não possui serviços pendentes.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($pendingServices as $pendingService): ?>
                        <li>
                            <strong>
                                #<?= (int) $pendingService['id_service'] ?>
                            </strong>

                            <?= htmlspecialchars(
                                $pendingService['description'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                            —

                            R$ <?= number_format(
                                    (float) $pendingService['price'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- A tabela geral apresenta serviços de todos os funcionários. -->
        <section>
            <header>
                <h2>Serviços</h2>

                <a href="/services/create">
                    Cadastrar serviço
                </a>
            </header>

            <!-- Os filtros usam GET porque apenas consultam informações. -->
            <form action="/dashboard" method="get">
                <fieldset>
                    <legend>Filtrar serviços</legend>

                    <div>
                        <label for="description">Nome do serviço</label>
                        <input
                            type="search"
                            id="description"
                            name="description"
                            maxlength="255"
                            value="<?= htmlspecialchars(
                                        $filters['description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                    </div>

                    <div>
                        <label for="user">Nome do usuário</label>
                        <input
                            type="search"
                            id="user"
                            name="user"
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                        $filters['user'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                    </div>

                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>

                            <option
                                value="Pendente"
                                <?= $filters['status'] === 'Pendente'
                                    ? 'selected'
                                    : '' ?>>
                                Pendente
                            </option>

                            <option
                                value="Finalizado"
                                <?= $filters['status'] === 'Finalizado'
                                    ? 'selected'
                                    : '' ?>>
                                Finalizado
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="start_date">Data inicial</label>
                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            value="<?= htmlspecialchars(
                                        $filters['start_date'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                    </div>

                    <div>
                        <label for="end_date">Data final</label>
                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            value="<?= htmlspecialchars(
                                        $filters['end_date'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                    </div>

                    <button type="submit">Filtrar</button>

                    <a href="/dashboard">Limpar filtros</a>
                </fieldset>
            </form>

            <?php if ($services === []): ?>
                <p>Nenhum serviço foi cadastrado até o momento.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <?= (int) $service['id_service'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $service['description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $service['status'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    R$ <?= number_format(
                                            (float) $service['price'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $service['user_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
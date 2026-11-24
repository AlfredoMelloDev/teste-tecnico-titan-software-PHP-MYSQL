<?php

/**
 * UC13 - Relatório de serviços e comissões.
 *
 * @var array{user_id: int|null, start_date: string, end_date: string} $filters
 * @var array $query
 * @var array|null $result null quando o período é inválido
 * @var array $responsibles
 * @var bool $isManager
 */

require dirname(__DIR__) . '/layout/top.php';

// Mantém start_date/end_date na URL mesmo vazios, para não voltar ao mês atual ao paginar.
$pageQuery = $query + ['start_date' => '', 'end_date' => ''];

?>
<section class="services-panel">
    <form class="report-filters" action="/reports/commissions" method="get">
        <div class="form-grid">
            <?php if ($isManager): ?>
                <div class="field">
                    <label for="user_id">Responsável</label>
                    <select id="user_id" name="user_id">
                        <option value="">Todos</option>
                        <?php foreach ($responsibles as $responsible): ?>
                            <option value="<?= (int) $responsible['id_user'] ?>" <?= $filters['user_id'] === (int) $responsible['id_user'] ? 'selected' : '' ?>>
                                <?= e($responsible['name']) ?><?= (int) $responsible['active'] === 1 ? '' : ' (inativo)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="start_date">Concluídos a partir de</label>
                <input id="start_date" name="start_date" type="date" value="<?= e($filters['start_date']) ?>">
            </div>

            <div class="field">
                <label for="end_date">Concluídos até</label>
                <input id="end_date" name="end_date" type="date" value="<?= e($filters['end_date']) ?>">
            </div>
        </div>

        <div class="form-actions">
            <button class="button button--primary" type="submit">Gerar relatório</button>
            <a class="button button--secondary" href="/reports/commissions">Mês atual</a>
        </div>
    </form>

    <?php if ($result !== null): ?>
        <div class="report-totals" aria-label="Totais do resultado filtrado">
            <div><span>Serviços</span><strong><?= (int) $result['totals']['services'] ?></strong></div>
            <div><span>Valor dos serviços</span><strong><?= money($result['totals']['value']) ?></strong></div>
            <div><span>Comissões</span><strong><?= money($result['totals']['commission']) ?></strong></div>
        </div>

        <?php if ($result['rows'] === []): ?>
            <div class="empty-state">Nenhum serviço finalizado no período.</div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Serviço</th>
                            <th>Cliente</th>
                            <th>Responsável</th>
                            <th>Valor</th>
                            <th>Conclusão</th>
                            <th>%</th>
                            <th>Comissão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['rows'] as $row): ?>
                            <tr>
                                <td data-label="Serviço">#<?= (int) $row['id_service'] ?> - <?= e($row['description']) ?></td>
                                <td data-label="Cliente"><?= e($row['client_name']) ?><?= (int) $row['client_active'] === 1 ? '' : ' <small>(inativo)</small>' ?></td>
                                <td data-label="Responsável"><?= e($row['user_name']) ?></td>
                                <td data-label="Valor"><?= money($row['price']) ?></td>
                                <td data-label="Conclusão"><?= datetime_br($row['finished_at']) ?></td>
                                <td data-label="%"><?= number_format((float) $row['commission_rate'], 0) ?>%</td>
                                <td data-label="Comissão"><strong><?= money($row['commission_user']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $page = $result['page'];
            $totalPages = $result['totalPages'];
            $totalRows = $result['total'];
            $basePath = '/reports/commissions';
            $query = $pageQuery;
            require dirname(__DIR__) . '/partials/pagination.php';
            ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/bottom.php'; ?>

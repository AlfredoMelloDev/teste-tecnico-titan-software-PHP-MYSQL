<?php

/**
 * Paginação no servidor (RNF06).
 *
 * @var int $page
 * @var int $totalPages
 * @var int $totalRows
 * @var string $basePath
 * @var array $query parâmetros atuais (filtros)
 */

if ($totalPages <= 1) {
    return;
}

?>
<nav class="pagination" aria-label="Paginação">
    <?php if ($page > 1): ?>
        <a class="button button--secondary button--small" href="<?= e(url_with($basePath, $query, ['page' => $page - 1])) ?>">← Anterior</a>
    <?php endif; ?>

    <span class="pagination__info">Página <?= $page ?> de <?= $totalPages ?> (<?= $totalRows ?> registros)</span>

    <?php if ($page < $totalPages): ?>
        <a class="button button--secondary button--small" href="<?= e(url_with($basePath, $query, ['page' => $page + 1])) ?>">Próxima →</a>
    <?php endif; ?>
</nav>

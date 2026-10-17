<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Catalog\Paginated<mixed> $page
 * @var callable(int): string $link
 */
if ($page->lastPage() <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page->page > 1): ?>
        <a class="button button--ghost" href="<?= $this->e($link($page->page - 1)) ?>" rel="prev"><?= $this->icon('chevron-left') ?> Previous</a>
    <?php endif ?>
    <ol>
        <?php for ($i = 1; $i <= $page->lastPage(); ++$i): ?>
            <li><a href="<?= $this->e($link($i)) ?>"<?= $i === $page->page ? ' aria-current="page"' : '' ?>><?= $i ?></a></li>
        <?php endfor ?>
    </ol>
    <?php if ($page->page < $page->lastPage()): ?>
        <a class="button button--ghost" href="<?= $this->e($link($page->page + 1)) ?>" rel="next">Next <?= $this->icon('chevron-right') ?></a>
    <?php endif ?>
</nav>

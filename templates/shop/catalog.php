<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Catalog\CatalogQuery $query
 * @var ?\TechNest\Catalog\Category $category
 * @var list<\TechNest\Catalog\Category> $categories
 * @var \TechNest\Catalog\Paginated<\TechNest\Catalog\Product> $results
 */
$heading = match (true) {
    $query->search !== '' => 'Results for “' . $query->search . '”',
    $category !== null => $category->name,
    default => 'All products',
};
$this->layout('layout', ['title' => $heading]);
$link = fn (int $page): string => $this->url('/catalog', $query->params(['page' => $page]));
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/">Home</a> <?= $this->icon('chevron-right') ?>
    <?php if ($category !== null): ?>
        <a href="/catalog">Shop</a> <?= $this->icon('chevron-right') ?> <span aria-current="page"><?= $this->e($category->name) ?></span>
    <?php else: ?>
        <span aria-current="page">Shop</span>
    <?php endif ?>
</nav>

<div class="catalog">
    <aside class="catalog__filters" aria-label="Filters">
        <h2 class="filter-heading">Categories</h2>
        <ul class="filter-list">
            <li><a href="<?= $this->e($this->url('/catalog', $query->params(['category' => '', 'page' => 1]))) ?>"<?= $query->category === '' ? ' aria-current="true"' : '' ?>>All products</a></li>
            <?php foreach ($categories as $c): ?>
                <li>
                    <a href="<?= $this->e($this->url('/catalog', $query->params(['category' => $c->slug, 'page' => 1]))) ?>"<?= $query->category === $c->slug ? ' aria-current="true"' : '' ?>>
                        <?= $this->e($c->name) ?> <span class="muted"><?= $c->productCount ?></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </aside>

    <section class="catalog__results" aria-labelledby="catalog-title">
        <div class="catalog__head">
            <div>
                <h1 id="catalog-title"><?= $this->e($heading) ?></h1>
                <p class="muted">
                    <?php if ($results->total === 0): ?>
                        No products match.
                    <?php else: ?>
                        Showing <?= $results->firstItem() ?>–<?= $results->lastItem() ?> of <?= $this->e($this->plural($results->total, 'product')) ?>
                    <?php endif ?>
                </p>
            </div>
            <form class="catalog__controls" method="get" action="/catalog">
                <?php if ($query->search !== ''): ?><input type="hidden" name="q" value="<?= $this->e($query->search) ?>"><?php endif ?>
                <?php if ($query->category !== ''): ?><input type="hidden" name="category" value="<?= $this->e($query->category) ?>"><?php endif ?>
                <label class="checkbox">
                    <input type="checkbox" name="in_stock" value="1"<?= $query->inStockOnly ? ' checked' : '' ?> data-autosubmit>
                    In stock only
                </label>
                <label class="select-label">
                    <span>Sort</span>
                    <select name="sort">
                        <?php foreach (\TechNest\Catalog\CatalogQuery::SORTS as $value => $label): ?>
                            <option value="<?= $this->e($value) ?>"<?= $query->sort === $value ? ' selected' : '' ?>><?= $this->e($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <?php /* Sorting waits for Apply: a select that submits on change reloads the page on every arrow key. */ ?>
                <button type="submit" class="button button--ghost">Apply</button>
            </form>
        </div>

        <?php if ($results->items === []): ?>
            <div class="empty">
                <?= $this->icon('search', 'empty__icon') ?>
                <h2>Nothing here yet</h2>
                <p class="muted">Try a different search, or clear the filters.</p>
                <a class="button button--primary" href="/catalog">Show all products</a>
            </div>
        <?php else: ?>
            <div class="product-grid product-grid--3">
                <?php foreach ($results->items as $product): ?>
                    <?= $this->insert('partials/product-card', ['product' => $product]) ?>
                <?php endforeach ?>
            </div>
            <?= $this->insert('partials/pagination', ['page' => $results, 'link' => $link]) ?>
        <?php endif ?>
    </section>
</div>

<?php
/**
 * @var \TechNest\View\View $this
 * @var list<\TechNest\Catalog\Product> $products
 * @var string $search
 */
$this->layout('admin/shell', ['title' => 'Products']);
?>
<div class="page-head">
    <h1 class="page-title">Products</h1>
    <div class="page-head__actions">
        <form method="get" action="/admin/products" class="inline-search" role="search">
            <label class="visually-hidden" for="product-search">Search products</label>
            <input id="product-search" type="search" name="q" value="<?= $this->e($search) ?>" placeholder="Search by name">
        </form>
        <a class="button button--primary" href="/admin/products/new"><?= $this->icon('plus') ?> New product</a>
    </div>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th scope="col">Product</th><th scope="col">Category</th><th scope="col" class="num">Price</th><th scope="col" class="num">Stock</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr<?= $product->isActive ? '' : ' class="is-muted"' ?>>
                    <td>
                        <div class="product-cell">
                            <img src="<?= $this->e($product->imageUrl()) ?>" alt="" width="40" height="40">
                            <div><a href="/admin/products/<?= $product->id ?>/edit"><?= $this->e($product->name) ?></a><br><span class="muted small">/products/<?= $this->e($product->slug) ?></span></div>
                        </div>
                    </td>
                    <td><?= $this->e($product->categoryName) ?></td>
                    <td class="num"><?= $this->money($product->priceCents) ?></td>
                    <td class="num">
                        <?php if ($product->stock === 0): ?><span class="tag tag--danger">0</span>
                        <?php elseif ($product->isLowStock()): ?><span class="tag tag--warning"><?= $product->stock ?></span>
                        <?php else: ?><?= $product->stock ?><?php endif ?>
                    </td>
                    <td><?= $product->isActive ? '<span class="tag tag--success">On sale</span>' : '<span class="tag tag--muted">Archived</span>' ?><?= $product->isFeatured ? ' <span class="tag tag--info">Featured</span>' : '' ?></td>
                    <td class="actions">
                        <a class="icon-button" href="/admin/products/<?= $product->id ?>/edit" aria-label="Edit <?= $this->e($product->name) ?>"><?= $this->icon('edit') ?></a>
                        <form method="post" action="/admin/products/<?= $product->id ?>/archive"<?= $product->isActive ? ' data-confirm="Archive ' . $this->e($product->name) . '? It will be hidden from the store but kept in order history."' : '' ?>>
                            <?= $this->csrfField() ?>
                            <input type="hidden" name="archived" value="<?= $product->isActive ? '1' : '0' ?>">
                            <button type="submit" class="icon-button" aria-label="<?= $product->isActive ? 'Archive' : 'Restore' ?> <?= $this->e($product->name) ?>" title="<?= $product->isActive ? 'Archive' : 'Restore' ?>"><?= $this->icon('archive') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

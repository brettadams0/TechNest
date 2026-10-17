<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Catalog\Product $product
 */
?>
<article class="product-card">
    <a class="product-card__media" href="<?= $this->e($product->url()) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= $this->e($product->imageUrl()) ?>" alt="" width="600" height="600" loading="lazy">
        <?php if (!$product->isInStock()): ?>
            <span class="tag tag--muted product-card__tag">Sold out</span>
        <?php elseif ($product->isLowStock()): ?>
            <span class="tag tag--warning product-card__tag">Only <?= $product->stock ?> left</span>
        <?php endif ?>
    </a>
    <div class="product-card__body">
        <p class="eyebrow"><?= $this->e($product->categoryName) ?></p>
        <h3 class="product-card__title"><a href="<?= $this->e($product->url()) ?>"><?= $this->e($product->name) ?></a></h3>
        <p class="product-card__price">
            <?php if ($product->hasPriceRange()): ?><span class="muted">From</span> <?php endif ?>
            <?= $this->money($product->priceCents) ?>
        </p>
    </div>
</article>

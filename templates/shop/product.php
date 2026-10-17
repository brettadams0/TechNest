<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Catalog\Product $product
 * @var list<\TechNest\Catalog\Product> $related
 * @var \TechNest\Checkout\PriceCalculator $pricing
 */
$this->layout('layout', ['title' => $product->name, 'description' => \TechNest\Support\Str::limit($product->description, 155)]);
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/">Home</a> <?= $this->icon('chevron-right') ?>
    <a href="<?= $this->e($this->url('/catalog', ['category' => $product->categorySlug])) ?>"><?= $this->e($product->categoryName) ?></a> <?= $this->icon('chevron-right') ?>
    <span aria-current="page"><?= $this->e($product->name) ?></span>
</nav>

<article class="product">
    <div class="product__media">
        <img src="<?= $this->e($product->imageUrl()) ?>" alt="<?= $this->e($product->name) ?>" width="600" height="600">
    </div>

    <div class="product__info">
        <p class="eyebrow"><?= $this->e($product->categoryName) ?></p>
        <h1><?= $this->e($product->name) ?></h1>

        <form class="buy-box" method="post" action="/cart/add" data-price-form data-base-price="<?= $product->priceCents ?>">
            <?= $this->csrfField() ?>
            <input type="hidden" name="product_id" value="<?= $product->id ?>">

            <p class="buy-box__price"><span data-price-output><?= $this->money($product->priceCents) ?></span></p>
            <p class="stock <?= $product->isInStock() ? ($product->isLowStock() ? 'stock--low' : 'stock--in') : 'stock--out' ?>">
                <?php if (!$product->isInStock()): ?>
                    Sold out
                <?php elseif ($product->isLowStock()): ?>
                    Only <?= $product->stock ?> left in stock
                <?php else: ?>
                    In stock — ships in 1–2 business days
                <?php endif ?>
            </p>

            <p class="product__description"><?= $this->e($product->description) ?></p>

            <?php foreach ($product->optionGroups() as $group => $options): ?>
                <fieldset class="choices">
                    <legend><?= $this->e($group) ?></legend>
                    <div class="choices__list">
                        <?php foreach ($options as $i => $option): ?>
                            <label class="choice">
                                <input type="radio" name="options[<?= $this->e($group) ?>]" value="<?= $option->id ?>" data-extra="<?= $option->extraCents ?>"<?= $i === 0 ? ' checked' : '' ?>>
                                <span>
                                    <?= $this->e($option->value) ?>
                                    <?php if ($option->extraCents > 0): ?><small>+<?= $this->moneyShort($option->extraCents) ?></small><?php endif ?>
                                </span>
                            </label>
                        <?php endforeach ?>
                    </div>
                </fieldset>
            <?php endforeach ?>

            <div class="buy-box__actions">
                <label class="quantity">
                    <span class="visually-hidden">Quantity</span>
                    <input type="number" name="quantity" value="1" min="1" max="<?= max(1, min(\TechNest\Cart\Cart::MAX_QUANTITY, $product->stock)) ?>" inputmode="numeric">
                </label>
                <button type="submit" class="button button--primary button--lg button--block"<?= $product->isInStock() ? '' : ' disabled' ?>>
                    <?= $this->icon('cart') ?> <?= $product->isInStock() ? 'Add to cart' : 'Sold out' ?>
                </button>
            </div>
        </form>

        <ul class="product__perks">
            <li><?= $this->icon('truck') ?> Free shipping on orders over <?= $this->moneyShort($pricing->freeShippingThresholdCents) ?></li>
            <li><?= $this->icon('shield') ?> Cancel any time before your order is processed</li>
            <li><?= $this->icon('tag') ?> Prices in Canadian dollars; HST added at checkout</li>
        </ul>
    </div>
</article>

<?php if ($related !== []): ?>
    <section class="section" aria-labelledby="related-title">
        <div class="section__head"><h2 id="related-title">More in <?= $this->e($product->categoryName) ?></h2></div>
        <div class="product-grid">
            <?php foreach ($related as $item): ?>
                <?= $this->insert('partials/product-card', ['product' => $item]) ?>
            <?php endforeach ?>
        </div>
    </section>
<?php endif ?>

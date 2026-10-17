<?php
/**
 * @var \TechNest\View\View $this
 * @var list<\TechNest\Catalog\Product> $featured
 * @var list<\TechNest\Catalog\Category> $categories
 * @var \TechNest\Checkout\PriceCalculator $pricing
 */
$this->layout('layout', ['mainClass' => 'page page--home']);
?>
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <p class="eyebrow eyebrow--accent">New season · Prices in CAD</p>
            <h1>Tech that earns its place on your desk.</h1>
            <p class="lead">Phones, laptops, audio and the accessories that hold it all together — with live stock levels, honest totals before you check out, and every order tracked from placed to delivered.</p>
            <div class="hero__actions">
                <a class="button button--primary button--lg" href="/catalog">Shop the catalog <?= $this->icon('chevron-right') ?></a>
                <a class="button button--ghost button--lg" href="#categories">Browse categories</a>
            </div>
        </div>
        <div class="hero__art" aria-hidden="true">
            <img class="hero__img hero__img--back" src="/assets/img/products/macbook-air-m2.svg" alt="" width="600" height="600">
            <img class="hero__img hero__img--main" src="/assets/img/products/iphone-15-pro.svg" alt="" width="600" height="600">
            <img class="hero__img hero__img--side" src="/assets/img/products/sony-wh-1000xm5.svg" alt="" width="600" height="600">
        </div>
    </div>
</section>

<div class="container">
    <ul class="perks" aria-label="Why shop here">
        <li><?= $this->icon('truck') ?><div><strong>Free shipping over <?= $this->moneyShort($pricing->freeShippingThresholdCents) ?></strong><span>Flat <?= $this->moneyShort($pricing->shippingCents) ?> otherwise</span></div></li>
        <li><?= $this->icon('package') ?><div><strong>Live stock levels</strong><span>No overselling, ever</span></div></li>
        <li><?= $this->icon('clock') ?><div><strong>Track every order</strong><span>From placed to delivered</span></div></li>
        <li><?= $this->icon('shield') ?><div><strong>Cancel while pending</strong><span>Stock returns instantly</span></div></li>
    </ul>

    <section id="categories" class="section" aria-labelledby="categories-title">
        <div class="section__head">
            <h2 id="categories-title">Shop by category</h2>
            <a class="link-arrow" href="/catalog">All products <?= $this->icon('chevron-right') ?></a>
        </div>
        <ul class="category-grid">
            <?php foreach ($categories as $category): ?>
                <li>
                    <a class="category-tile" href="<?= $this->e($this->url('/catalog', ['category' => $category->slug])) ?>">
                        <span class="category-tile__name"><?= $this->e($category->name) ?></span>
                        <span class="category-tile__count"><?= $this->e($this->plural($category->productCount, 'product')) ?></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </section>

    <section class="section" aria-labelledby="featured-title">
        <div class="section__head">
            <h2 id="featured-title">Featured</h2>
            <a class="link-arrow" href="/catalog">See everything <?= $this->icon('chevron-right') ?></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?= $this->insert('partials/product-card', ['product' => $product]) ?>
            <?php endforeach ?>
        </div>
    </section>
</div>

<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Cart\PricedCart $cart
 * @var \TechNest\Checkout\PriceCalculator $calculator
 */
$this->layout('layout', ['title' => 'Your cart']);
$totals = $cart->totals;
$toFreeShipping = $calculator->freeShippingThresholdCents - $totals->subtotalCents;
?>
<h1 class="page-title">Your cart</h1>

<?php foreach ($cart->notices as $notice): ?>
    <div class="alert alert--info"><?= $this->icon('info') ?><p><?= $this->e($notice) ?></p></div>
<?php endforeach ?>

<?php if ($cart->isEmpty()): ?>
    <div class="empty">
        <?= $this->icon('cart', 'empty__icon') ?>
        <h2>Your cart is empty</h2>
        <p class="muted">Find something you like and it will wait for you here.</p>
        <a class="button button--primary" href="/catalog">Start shopping</a>
    </div>
<?php else: ?>
    <div class="cart">
        <ul class="cart__lines" aria-label="Items in your cart">
            <?php foreach ($cart->lines as $line): ?>
                <li class="cart-line">
                    <a class="cart-line__media" href="<?= $this->e($line->product->url()) ?>" tabindex="-1" aria-hidden="true">
                        <img src="<?= $this->e($line->product->imageUrl()) ?>" alt="" width="96" height="96">
                    </a>
                    <div class="cart-line__info">
                        <h2 class="cart-line__name"><a href="<?= $this->e($line->product->url()) ?>"><?= $this->e($line->product->name) ?></a></h2>
                        <?php if ($line->optionsLabel() !== ''): ?><p class="muted small"><?= $this->e($line->optionsLabel()) ?></p><?php endif ?>
                        <p class="small"><?= $this->money($line->unitCents()) ?> each</p>
                    </div>
                    <form class="cart-line__qty" method="post" action="/cart/update">
                        <?= $this->csrfField() ?>
                        <input type="hidden" name="key" value="<?= $this->e($line->key) ?>">
                        <label>
                            <span class="visually-hidden">Quantity for <?= $this->e($line->product->name) ?></span>
                            <input type="number" name="quantity" value="<?= $line->quantity ?>" min="0" max="<?= \TechNest\Cart\Cart::MAX_QUANTITY ?>" inputmode="numeric" data-autosubmit>
                        </label>
                        <button type="submit" class="button button--ghost button--sm no-js-only">Update</button>
                    </form>
                    <p class="cart-line__total"><?= $this->money($line->totalCents()) ?></p>
                    <form method="post" action="/cart/remove">
                        <?= $this->csrfField() ?>
                        <input type="hidden" name="key" value="<?= $this->e($line->key) ?>">
                        <button type="submit" class="icon-button" aria-label="Remove <?= $this->e($line->product->name) ?>"><?= $this->icon('trash') ?></button>
                    </form>
                </li>
            <?php endforeach ?>
        </ul>

        <aside class="summary" aria-labelledby="summary-title">
            <h2 id="summary-title">Order summary</h2>
            <?= $this->insert('partials/totals', ['totals' => $totals, 'calculator' => $calculator]) ?>
            <?php if ($totals->shippingCents > 0 && $toFreeShipping > 0): ?>
                <p class="summary__hint"><?= $this->icon('truck') ?> Add <?= $this->money($toFreeShipping) ?> more for free shipping.</p>
            <?php endif ?>
            <a class="button button--primary button--lg button--block" href="/checkout">Check out</a>
            <a class="button button--ghost button--block" href="/catalog">Continue shopping</a>
        </aside>
    </div>
<?php endif ?>

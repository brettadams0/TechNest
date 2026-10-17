<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Cart\PricedCart $cart
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 * @var array<string, string> $provinces
 */
$this->layout('layout', ['title' => 'Checkout']);
?>
<h1 class="page-title">Checkout</h1>

<div class="checkout">
    <form class="card checkout__form" method="post" action="/checkout" novalidate>
        <?= $this->csrfField() ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert"><?= $this->icon('alert') ?><p>Please fix the highlighted fields.</p></div>
        <?php endif ?>

        <h2>Shipping address</h2>
        <div class="form-grid">
            <div class="field field--full">
                <label for="name">Full name</label>
                <input id="name" name="name" autocomplete="name" required maxlength="80" value="<?= $this->e($this->old($old, 'name')) ?>"<?= $this->fieldState($errors, 'name') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'name']) ?>
            </div>
            <div class="field field--full">
                <label for="street">Street address</label>
                <input id="street" name="street" autocomplete="street-address" required maxlength="120" value="<?= $this->e($this->old($old, 'street')) ?>"<?= $this->fieldState($errors, 'street') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'street']) ?>
            </div>
            <div class="field">
                <label for="city">City</label>
                <input id="city" name="city" autocomplete="address-level2" required maxlength="60" value="<?= $this->e($this->old($old, 'city')) ?>"<?= $this->fieldState($errors, 'city') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'city']) ?>
            </div>
            <div class="field">
                <label for="province">Province</label>
                <select id="province" name="province" autocomplete="address-level1" required<?= $this->fieldState($errors, 'province') ?>>
                    <option value="">Choose…</option>
                    <?php foreach ($provinces as $code => $name): ?>
                        <option value="<?= $this->e($code) ?>"<?= $this->old($old, 'province', 'ON') === $code ? ' selected' : '' ?>><?= $this->e($name) ?></option>
                    <?php endforeach ?>
                </select>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'province']) ?>
            </div>
            <div class="field">
                <label for="postal_code">Postal code</label>
                <input id="postal_code" name="postal_code" autocomplete="postal-code" required maxlength="7" placeholder="N9B 3P4" value="<?= $this->e($this->old($old, 'postal_code')) ?>"<?= $this->fieldState($errors, 'postal_code') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'postal_code']) ?>
            </div>
        </div>

        <h2>Payment</h2>
        <div class="notice">
            <?= $this->icon('info') ?>
            <p>This is a portfolio demo: there is no payment step and nothing is charged. Placing an order reserves stock and creates a real order record you can track.</p>
        </div>

        <button type="submit" class="button button--primary button--lg button--block">Place order · <?= $this->money($cart->totals->totalCents) ?></button>
    </form>

    <aside class="summary" aria-labelledby="summary-title">
        <h2 id="summary-title">Your order</h2>
        <ul class="mini-lines">
            <?php foreach ($cart->lines as $line): ?>
                <li>
                    <img src="<?= $this->e($line->product->imageUrl()) ?>" alt="" width="56" height="56">
                    <div>
                        <p><strong><?= $this->e($line->product->name) ?></strong> × <?= $line->quantity ?></p>
                        <?php if ($line->optionsLabel() !== ''): ?><p class="muted small"><?= $this->e($line->optionsLabel()) ?></p><?php endif ?>
                    </div>
                    <p><?= $this->money($line->totalCents()) ?></p>
                </li>
            <?php endforeach ?>
        </ul>
        <?= $this->insert('partials/totals', ['totals' => $cart->totals]) ?>
        <a class="link-arrow" href="/cart"><?= $this->icon('chevron-left') ?> Edit cart</a>
    </aside>
</div>

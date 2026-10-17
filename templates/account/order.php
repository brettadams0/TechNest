<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Order\Order $order
 */
$this->layout('layout', ['title' => 'Order ' . $order->number()]);
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/account">Your account</a> <?= $this->icon('chevron-right') ?> <span aria-current="page">Order <?= $this->e($order->number()) ?></span>
</nav>

<div class="page-head">
    <div>
        <h1 class="page-title">Order <?= $this->e($order->number()) ?></h1>
        <p class="muted">Placed <?= $this->e($this->date($order->createdAt, 'F j, Y \a\t g:i a')) ?></p>
    </div>
    <?= $this->insert('partials/status', ['status' => $order->status]) ?>
</div>

<?= $this->insert('partials/order-progress', ['order' => $order]) ?>

<div class="order-detail">
    <section class="card" aria-labelledby="items-title">
        <h2 id="items-title">Items</h2>
        <?= $this->insert('partials/order-items', ['order' => $order]) ?>
    </section>

    <aside class="stack">
        <section class="card" aria-labelledby="ship-title">
            <h2 id="ship-title">Shipping to</h2>
            <address>
                <?= $this->e($order->shipTo->name) ?><br>
                <?= $this->e($order->shipTo->street) ?><br>
                <?= $this->e($order->shipTo->city) ?>, <?= $this->e($order->shipTo->province) ?> <?= $this->e($order->shipTo->postalCode) ?>
            </address>
        </section>

        <?php if ($order->status->isCustomerCancellable()): ?>
            <section class="card" aria-labelledby="cancel-title">
                <h2 id="cancel-title">Changed your mind?</h2>
                <p class="muted small">You can cancel until we start preparing your order.</p>
                <form method="post" action="/account/orders/<?= $order->id ?>/cancel" data-confirm="Cancel order <?= $this->e($order->number()) ?>? This cannot be undone.">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="button button--danger button--block">Cancel order</button>
                </form>
            </section>
        <?php endif ?>
    </aside>
</div>

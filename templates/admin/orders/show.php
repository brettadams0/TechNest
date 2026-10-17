<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Order\Order $order
 */
$this->layout('admin/shell', ['title' => 'Order ' . $order->number()]);
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/admin/orders">Orders</a> <?= $this->icon('chevron-right') ?> <span aria-current="page"><?= $this->e($order->number()) ?></span>
</nav>

<div class="page-head">
    <div>
        <h1 class="page-title">Order <?= $this->e($order->number()) ?></h1>
        <p class="muted">Placed by <strong><?= $this->e($order->username) ?></strong> on <?= $this->e($this->date($order->createdAt, 'F j, Y \a\t g:i a')) ?></p>
    </div>
    <?= $this->insert('partials/status', ['status' => $order->status]) ?>
</div>

<div class="order-detail">
    <div class="stack">
        <section class="card" aria-labelledby="items-title">
            <h2 id="items-title">Items</h2>
            <?= $this->insert('partials/order-items', ['order' => $order]) ?>
        </section>

        <section class="card" aria-labelledby="history-title">
            <h2 id="history-title">History</h2>
            <ol class="timeline">
                <?php foreach ($order->history as $change): ?>
                    <li>
                        <?= $this->insert('partials/status', ['status' => $change->to]) ?>
                        <span class="muted small"><?= $this->e($this->date($change->changedAt, 'M j, Y g:i a')) ?><?= $change->changedBy !== null ? ' · by ' . $this->e($change->changedBy) : ' · system' ?></span>
                    </li>
                <?php endforeach ?>
            </ol>
        </section>
    </div>

    <aside class="stack">
        <section class="card" aria-labelledby="update-title">
            <h2 id="update-title">Update status</h2>
            <?php if ($order->status->isFinal()): ?>
                <p class="muted">This order is <?= $this->e($order->status->value) ?>; there is nothing further to do.</p>
            <?php else: ?>
                <form method="post" action="/admin/orders/<?= $order->id ?>/status" class="stack stack--sm">
                    <?= $this->csrfField() ?>
                    <label for="status" class="visually-hidden">New status</label>
                    <select id="status" name="status">
                        <?php foreach ($order->status->nextStates() as $next): ?>
                            <option value="<?= $this->e($next->value) ?>">Mark as <?= $this->e($next->value) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="submit" class="button button--primary button--block">Update</button>
                    <p class="muted small">Only valid next steps are offered. Cancelling returns the items to stock.</p>
                </form>
            <?php endif ?>
        </section>

        <section class="card" aria-labelledby="ship-title">
            <h2 id="ship-title">Ship to</h2>
            <address>
                <?= $this->e($order->shipTo->name) ?><br>
                <?= $this->e($order->shipTo->street) ?><br>
                <?= $this->e($order->shipTo->city) ?>, <?= $this->e($order->shipTo->province) ?> <?= $this->e($order->shipTo->postalCode) ?>
            </address>
        </section>
    </aside>
</div>

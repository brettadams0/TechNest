<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Order\Order $order
 */
?>
<ul class="order-items">
    <?php foreach ($order->items as $item): ?>
        <li>
            <img src="/assets/img/products/<?= $this->e($item->productImage ?? 'placeholder.svg') ?>" alt="" width="64" height="64">
            <div>
                <p><strong><?= $this->e($item->productName) ?></strong></p>
                <?php if ($item->optionsLabel !== ''): ?><p class="muted small"><?= $this->e($item->optionsLabel) ?></p><?php endif ?>
                <p class="small"><?= $item->quantity ?> × <?= $this->money($item->unitPriceCents) ?></p>
            </div>
            <p class="order-items__total"><?= $this->money($item->lineTotalCents) ?></p>
        </li>
    <?php endforeach ?>
</ul>
<?= $this->insert('partials/totals', ['totals' => new \TechNest\Checkout\Totals($order->subtotalCents, $order->shippingCents, $order->taxCents, $order->totalCents)]) ?>

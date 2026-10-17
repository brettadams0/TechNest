<?php
/**
 * @var \TechNest\View\View $this
 * @var list<\TechNest\Order\Order> $orders
 */
?>
<div class="table-wrap">
    <table class="table">
        <thead>
        <tr><th scope="col">Order</th><th scope="col">Placed</th><th scope="col">Customer</th><th scope="col" class="num">Items</th><th scope="col" class="num">Total</th><th scope="col">Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><a href="/admin/orders/<?= $order->id ?>"><?= $this->e($order->number()) ?></a></td>
                <td><?= $this->e($this->date($order->createdAt, 'M j, g:i a')) ?></td>
                <td><?= $this->e($order->username) ?></td>
                <td class="num"><?= $order->itemCount ?></td>
                <td class="num"><?= $this->money($order->totalCents) ?></td>
                <td><?= $this->insert('partials/status', ['status' => $order->status]) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>

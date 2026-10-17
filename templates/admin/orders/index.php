<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Catalog\Paginated<\TechNest\Order\Order> $orders
 * @var ?\TechNest\Order\OrderStatus $status
 * @var array<string, int> $counts
 */
use TechNest\Order\OrderStatus;

$this->layout('admin/shell', ['title' => 'Orders']);
$link = fn (int $page): string => $this->url('/admin/orders', array_filter(['status' => $status?->value ?? '', 'page' => $page > 1 ? $page : '']));
?>
<div class="page-head">
    <h1 class="page-title">Orders</h1>
</div>

<nav class="tabs" aria-label="Filter by status">
    <a href="/admin/orders"<?= $status === null ? ' aria-current="page"' : '' ?>>All <span><?= array_sum($counts) ?></span></a>
    <?php foreach (OrderStatus::cases() as $s): ?>
        <a href="<?= $this->e($this->url('/admin/orders', ['status' => $s->value])) ?>"<?= $status === $s ? ' aria-current="page"' : '' ?>><?= $this->e($s->label()) ?> <span><?= $counts[$s->value] ?? 0 ?></span></a>
    <?php endforeach ?>
</nav>

<section class="card">
    <?php if ($orders->total === 0): ?>
        <p class="muted">No orders <?= $status !== null ? 'with this status' : 'yet' ?>.</p>
    <?php else: ?>
        <?= $this->insert('admin/orders/table', ['orders' => $orders->items]) ?>
        <?= $this->insert('partials/pagination', ['page' => $orders, 'link' => $link]) ?>
    <?php endif ?>
</section>

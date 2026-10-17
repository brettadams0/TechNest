<?php
/**
 * @var \TechNest\View\View $this
 * @var int $days
 * @var array{current: array<string, int>, previous: array<string, int>} $summary
 * @var string $revenueChart
 * @var string $categoryChart
 * @var list<array{name: string, units: int, revenue: int}> $topProducts
 * @var array<string, int> $statusCounts
 * @var list<\TechNest\Order\Order> $recentOrders
 * @var list<\TechNest\Catalog\Product> $lowStock
 */
use TechNest\Order\OrderStatus;

$this->layout('admin/shell', ['title' => 'Dashboard']);

$kpis = [
    ['revenue', 'Revenue', true],
    ['orders', 'Orders', false],
    ['aov', 'Average order', true],
    ['units', 'Units sold', false],
    ['customers', 'Customers', false],
];
$delta = static function (int $now, int $before): ?float {
    return $before === 0 ? null : ($now - $before) / $before * 100;
};
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="muted">Last <?= $days ?> days, compared with the same length of time before. Revenue is goods sold, before shipping and HST; cancelled orders are excluded.</p>
    </div>
    <a class="button button--secondary" href="/admin/products/new"><?= $this->icon('plus') ?> New product</a>
</div>

<ul class="kpis">
    <?php foreach ($kpis as [$key, $label, $isMoney]): ?>
        <?php $change = $delta($summary['current'][$key], $summary['previous'][$key]); ?>
        <li class="kpi">
            <p class="kpi__label"><?= $this->e($label) ?></p>
            <p class="kpi__value"><?= $isMoney ? $this->money($summary['current'][$key]) : number_format($summary['current'][$key]) ?></p>
            <?php if ($change !== null): ?>
                <p class="kpi__delta <?= $change >= 0 ? 'is-up' : 'is-down' ?>">
                    <?= $change >= 0 ? '▲' : '▼' ?> <?= $this->e(number_format(abs($change), 1)) ?>%
                    <span class="visually-hidden"><?= $change >= 0 ? 'up' : 'down' ?> from the previous period</span>
                </p>
            <?php else: ?>
                <p class="kpi__delta muted">No prior data</p>
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ul>

<div class="dash-grid">
    <section class="card dash-grid__wide" aria-labelledby="revenue-title">
        <div class="card__head"><h2 id="revenue-title">Revenue per day</h2><span class="muted small">Hover a bar for the day’s figures</span></div>
        <div class="chart-frame"><?= $revenueChart ?></div>
    </section>

    <section class="card" aria-labelledby="category-title">
        <div class="card__head"><h2 id="category-title">Revenue by category</h2></div>
        <?php if ($categoryChart === ''): ?>
            <p class="muted">No sales in this period.</p>
        <?php else: ?>
            <div class="chart-frame"><?= $categoryChart ?></div>
        <?php endif ?>
    </section>

    <section class="card" aria-labelledby="top-title">
        <div class="card__head"><h2 id="top-title">Best sellers</h2></div>
        <ol class="rank-list">
            <?php foreach ($topProducts as $row): ?>
                <li><span><?= $this->e($row['name']) ?></span><span class="muted"><?= $this->e($this->plural($row['units'], 'unit')) ?></span><strong><?= $this->money($row['revenue']) ?></strong></li>
            <?php endforeach ?>
        </ol>
    </section>

    <section class="card" aria-labelledby="status-title">
        <div class="card__head"><h2 id="status-title">Orders by status</h2><a class="link-arrow small" href="/admin/orders">All orders <?= $this->icon('chevron-right') ?></a></div>
        <ul class="status-list">
            <?php foreach (OrderStatus::cases() as $status): ?>
                <li>
                    <a href="<?= $this->e($this->url('/admin/orders', ['status' => $status->value])) ?>"><?= $this->insert('partials/status', ['status' => $status]) ?></a>
                    <strong><?= $statusCounts[$status->value] ?? 0 ?></strong>
                </li>
            <?php endforeach ?>
        </ul>
    </section>

    <section class="card" aria-labelledby="stock-title">
        <div class="card__head"><h2 id="stock-title">Low stock</h2><a class="link-arrow small" href="/admin/products">Products <?= $this->icon('chevron-right') ?></a></div>
        <?php if ($lowStock === []): ?>
            <p class="muted">Everything is well stocked.</p>
        <?php else: ?>
            <ul class="stock-list">
                <?php foreach ($lowStock as $product): ?>
                    <li>
                        <a href="/admin/products/<?= $product->id ?>/edit"><?= $this->e($product->name) ?></a>
                        <span class="tag <?= $product->stock === 0 ? 'tag--danger' : 'tag--warning' ?>"><?= $product->stock === 0 ? 'Sold out' : $product->stock . ' left' ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <section class="card dash-grid__wide" aria-labelledby="recent-title">
        <div class="card__head"><h2 id="recent-title">Recent orders</h2></div>
        <?= $this->insert('admin/orders/table', ['orders' => $recentOrders]) ?>
    </section>
</div>

<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Order\Order $order
 */
use TechNest\Order\OrderStatus;

$steps = [OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
$reached = array_search($order->status, $steps, true);
?>
<?php if ($order->status === OrderStatus::Cancelled): ?>
    <div class="alert alert--neutral"><?= $this->icon('x') ?><p>This order was cancelled and its items returned to stock.</p></div>
<?php else: ?>
    <ol class="progress" aria-label="Order progress">
        <?php foreach ($steps as $i => $step): ?>
            <li class="progress__step<?= $i <= $reached ? ' is-done' : '' ?><?= $i === $reached ? ' is-current' : '' ?>"<?= $i === $reached ? ' aria-current="step"' : '' ?>>
                <span class="progress__dot"><?= $i < $reached ? $this->icon('check') : '' ?></span>
                <span class="progress__label"><?= $this->e($step->label()) ?></span>
            </li>
        <?php endforeach ?>
    </ol>
<?php endif ?>

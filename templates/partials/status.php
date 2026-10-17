<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Order\OrderStatus $status
 */
?>
<span class="status status--<?= $this->e($status->value) ?>"><?= $this->e($status->label()) ?></span>

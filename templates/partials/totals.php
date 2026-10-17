<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Checkout\Totals $totals
 */
?>
<dl class="totals">
    <div><dt>Subtotal</dt><dd><?= $this->money($totals->subtotalCents) ?></dd></div>
    <div><dt>Shipping</dt><dd><?= $totals->shippingCents === 0 ? 'Free' : $this->money($totals->shippingCents) ?></dd></div>
    <div><dt>HST<?= $totals->taxBasisPoints > 0 ? ' (' . $this->e($totals->taxRate()) . ')' : '' ?></dt><dd><?= $this->money($totals->taxCents) ?></dd></div>
    <div class="totals__grand"><dt>Total</dt><dd><?= $this->money($totals->totalCents) ?></dd></div>
</dl>

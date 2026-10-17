<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\Checkout\PriceCalculator $calculator
 */
$this->layout('layout', ['title' => 'Help & FAQ']);
$example = $calculator->totals(9000);
?>
<div class="prose-page">
    <h1 class="page-title">Help &amp; FAQ</h1>
    <nav class="toc" aria-label="On this page">
        <a href="#shopping">Shopping</a><a href="#orders">Orders</a><a href="#shipping">Shipping &amp; tax</a><a href="#accounts">Accounts</a><a href="#about">About this project</a>
    </nav>

    <section id="shopping" class="card prose">
        <h2>Shopping</h2>
        <h3>How do I find something?</h3>
        <p>Search from the bar at the top of any page, or <a href="/catalog">browse the catalog</a> by category. You can sort by price or newest, and hide anything that is sold out.</p>
        <h3>What do the stock labels mean?</h3>
        <p>Stock is live. “Only 3 left” means exactly that, and when something sells out it cannot be added to a cart. If an item in your cart sells out or changes before you check out, the cart tells you what changed instead of charging you for something different.</p>
    </section>

    <section id="orders" class="card prose">
        <h2>Orders</h2>
        <p>Every order moves through the same steps, and you can follow it from <a href="/account">your account</a>:</p>
        <dl class="definition-list">
            <div><dt><?= $this->insert('partials/status', ['status' => \TechNest\Order\OrderStatus::Pending]) ?></dt><dd>Received. You can still cancel it yourself.</dd></div>
            <div><dt><?= $this->insert('partials/status', ['status' => \TechNest\Order\OrderStatus::Processing]) ?></dt><dd>Being picked and packed.</dd></div>
            <div><dt><?= $this->insert('partials/status', ['status' => \TechNest\Order\OrderStatus::Shipped]) ?></dt><dd>On its way.</dd></div>
            <div><dt><?= $this->insert('partials/status', ['status' => \TechNest\Order\OrderStatus::Delivered]) ?></dt><dd>Arrived.</dd></div>
            <div><dt><?= $this->insert('partials/status', ['status' => \TechNest\Order\OrderStatus::Cancelled]) ?></dt><dd>Stopped before shipping; its stock went straight back on sale.</dd></div>
        </dl>
    </section>

    <section id="shipping" class="card prose">
        <h2>Shipping &amp; tax</h2>
        <p>Shipping is free on orders of <?= $this->moneyShort($calculator->freeShippingThresholdCents) ?> or more, and a flat <?= $this->moneyShort($calculator->shippingCents) ?> below that. Prices are in Canadian dollars; <?= $this->e($example->taxRate()) ?> HST is added at checkout on the items and the shipping.</p>
        <p>For example, a <?= $this->money($example->subtotalCents) ?> order pays <?= $this->money($example->shippingCents) ?> shipping and <?= $this->money($example->taxCents) ?> HST, for <?= $this->money($example->totalCents) ?> in total. You always see that breakdown before you place an order.</p>
    </section>

    <section id="accounts" class="card prose">
        <h2>Accounts &amp; security</h2>
        <p>You need an account to check out. Passwords are stored as bcrypt hashes, sign-in is rate-limited after repeated failures, and every form is protected against cross-site request forgery. You can change your password from your account page.</p>
    </section>

    <section id="about" class="card prose">
        <h2>About this project</h2>
        <p>TechNest is a portfolio project by <a href="https://brettadams0.github.io/">Brett Adams</a>: a complete store written in PHP without a framework, so the routing, sessions, SQL and security are all visible in the code rather than hidden behind one. No payment is taken and nothing ships.</p>
        <p>It started as university coursework and was later rebuilt to a professional standard — transactional checkout, an order state machine, an analytics dashboard, a strict Content-Security-Policy, and a test suite that runs against both SQLite and MySQL.</p>
        <p><a class="button button--secondary" href="https://github.com/brettadams0/TechNest" rel="noopener"><?= $this->icon('github') ?> Read the source</a></p>
    </section>
</div>

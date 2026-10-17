<?php
/**
 * @var \TechNest\View\View $this
 * @var string $content
 * @var string $theme
 * @var ?\TechNest\User\User $currentUser
 * @var bool $isAdmin
 * @var int $cartCount
 * @var list<array{type: string, message: string}> $flashes
 * @var string $searchQuery
 */
$pageTitle = isset($title) && $title !== '' ? $title . ' · TechNest' : 'TechNest — Electronics, delivered';
?>
<!doctype html>
<html lang="en" data-theme="<?= $this->e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($pageTitle) ?></title>
    <meta name="description" content="<?= $this->e($description ?? 'TechNest sells phones, laptops, audio, wearables and more — with live stock levels and order tracking.') ?>">
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $this->e($this->asset('/assets/css/app.css')) ?>">
    <script src="<?= $this->e($this->asset('/assets/js/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<div class="demo-banner">
    <div class="container">Portfolio demo store — no payment is taken and nothing ships. <a href="/help#about">About this project</a></div>
</div>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="/" aria-label="TechNest home"><?= \TechNest\View\Icon::logo('brand__mark') ?><span>TechNest</span></a>

        <form class="search" action="/catalog" method="get" role="search">
            <label class="visually-hidden" for="site-search">Search products</label>
            <input id="site-search" type="search" name="q" placeholder="Search phones, laptops, audio…" value="<?= $this->e($searchQuery) ?>" maxlength="100">
            <button type="submit" aria-label="Search"><?= $this->icon('search') ?></button>
        </form>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><?= $this->icon('menu') ?><span class="visually-hidden">Menu</span></button>

        <nav id="site-nav" class="site-nav" aria-label="Main">
            <a href="/catalog"<?= $this->current('/catalog') ?>>Shop</a>
            <a href="/help"<?= $this->current('/help') ?>>Help</a>
            <?php if ($isAdmin): ?>
                <a href="/admin"<?= $this->current('/admin', true) ?>>Admin</a>
            <?php endif ?>
            <?php if ($currentUser !== null): ?>
                <a href="/account"<?= $this->current('/account', true) ?>><?= $this->icon('user') ?><span><?= $this->e($currentUser->username) ?></span></a>
                <form action="/logout" method="post" class="site-nav__logout">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="link-button">Sign out</button>
                </form>
            <?php else: ?>
                <a href="/login"<?= $this->current('/login') ?>>Sign in</a>
            <?php endif ?>
            <a class="cart-link" href="/cart"<?= $this->current('/cart') ?>>
                <?= $this->icon('cart') ?><span>Cart</span>
                <?php if ($cartCount > 0): ?><span class="cart-link__count" aria-label="<?= $this->e($this->plural($cartCount, 'item')) ?>"><?= $cartCount ?></span><?php endif ?>
            </a>
        </nav>
    </div>
</header>

<main id="main" class="<?= $this->e($mainClass ?? 'container page') ?>">
    <?php if ($flashes !== []): ?>
        <div class="flashes" role="status">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert--<?= $this->e($flash['type']) ?>">
                    <?= $this->icon($flash['type'] === 'error' ? 'alert' : ($flash['type'] === 'success' ? 'check' : 'info')) ?>
                    <p><?= $this->e($flash['message']) ?></p>
                    <button type="button" class="alert__close" data-dismiss aria-label="Dismiss"><?= $this->icon('x') ?></button>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>
            <a class="brand brand--footer" href="/"><?= \TechNest\View\Icon::logo('brand__mark') ?><span>TechNest</span></a>
            <p class="muted">A full-stack store in framework-free PHP. Prices in CAD.</p>
        </div>
        <nav aria-label="Footer">
            <h2 class="footer-heading">Shop</h2>
            <a href="/catalog">All products</a>
            <a href="/catalog?sort=newest">New arrivals</a>
            <a href="/cart">Cart</a>
        </nav>
        <nav aria-label="Support">
            <h2 class="footer-heading">Support</h2>
            <a href="/help">Help &amp; FAQ</a>
            <a href="/help#shipping">Shipping &amp; tax</a>
            <a href="/account">Your orders</a>
        </nav>
        <nav aria-label="Project">
            <h2 class="footer-heading">Project</h2>
            <a href="/help#about">About this demo</a>
            <a href="https://github.com/brettadams0/TechNest" rel="noopener">Source on GitHub</a>
            <a href="https://brettadams0.github.io/" rel="noopener">Brett Adams</a>
        </nav>
    </div>
</footer>
</body>
</html>

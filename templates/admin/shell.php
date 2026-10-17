<?php
/**
 * Wraps every admin page in the admin navigation, then the site layout.
 *
 * @var \TechNest\View\View $this
 * @var string $content
 */
$this->layout('layout', ['title' => ($title ?? 'Dashboard') . ' · Admin', 'mainClass' => 'page page--admin']);
$links = [
    ['/admin', 'Dashboard', 'chart', false],
    ['/admin/orders', 'Orders', 'package', true],
    ['/admin/products', 'Products', 'tag', true],
    ['/admin/users', 'Customers', 'users', true],
    ['/admin/themes', 'Themes', 'palette', true],
];
?>
<div class="container admin">
    <nav class="admin-nav" aria-label="Admin">
        <p class="admin-nav__title">Store admin</p>
        <?php foreach ($links as [$href, $label, $icon, $prefix]): ?>
            <a href="<?= $this->e($href) ?>"<?= $this->current($href, $prefix) ?>><?= $this->icon($icon) ?><span><?= $this->e($label) ?></span></a>
        <?php endforeach ?>
        <a href="/" class="admin-nav__store"><?= $this->icon('store') ?><span>View store</span></a>
    </nav>
    <div class="admin-content">
        <?= $content ?>
    </div>
</div>

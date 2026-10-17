<?php
/**
 * @var \TechNest\View\View $this
 * @var int $status
 * @var string $message
 */
$this->layout('layout', ['title' => (string) $status]);
?>
<div class="error-page">
    <p class="error-page__code"><?= $status ?></p>
    <h1><?= $this->e($message) ?></h1>
    <div class="error-page__actions">
        <a class="button button--primary" href="/">Go to the homepage</a>
        <a class="button button--ghost" href="/catalog">Browse products</a>
    </div>
</div>

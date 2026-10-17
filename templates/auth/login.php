<?php
/**
 * @var \TechNest\View\View $this
 * @var string $login
 * @var string $error
 * @var bool $isDemo
 */
$this->layout('layout', ['title' => 'Sign in']);
?>
<div class="auth">
    <form class="card auth__card" method="post" action="/login">
        <?= $this->csrfField() ?>
        <h1>Sign in</h1>
        <p class="muted">Welcome back. Sign in to check out and track your orders.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert--error" role="alert"><?= $this->icon('alert') ?><p><?= $this->e($error) ?></p></div>
        <?php endif ?>

        <div class="field">
            <label for="login">Username or email</label>
            <input id="login" name="login" autocomplete="username" required value="<?= $this->e($login) ?>" autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="button button--primary button--lg button--block">Sign in</button>
        <p class="auth__switch">New here? <a href="/register">Create an account</a></p>

        <?php if ($isDemo): ?>
            <div class="demo-accounts">
                <p><strong>Exploring the demo?</strong></p>
                <p>Customer: <code>demo</code> / <code>technest-demo</code></p>
                <p>Admin: <code>admin</code> / <code>technest-admin</code></p>
            </div>
        <?php endif ?>
    </form>
</div>

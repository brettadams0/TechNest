<?php
/**
 * @var \TechNest\View\View $this
 * @var array<string, string> $errors
 * @var array<string, string> $old
 */
$this->layout('layout', ['title' => 'Create an account']);
?>
<div class="auth">
    <form class="card auth__card" method="post" action="/register" novalidate>
        <?= $this->csrfField() ?>
        <h1>Create an account</h1>
        <p class="muted">You will need one to check out and follow your orders.</p>

        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" required minlength="3" maxlength="30" value="<?= $this->e($this->old($old, 'username')) ?>"<?= $this->fieldState($errors, 'username') ?>>
            <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'username']) ?>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required value="<?= $this->e($this->old($old, 'email')) ?>"<?= $this->fieldState($errors, 'email') ?>>
            <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'email']) ?>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="10" aria-describedby="password-hint"<?= $this->fieldState($errors, 'password') ?>>
            <p class="field__hint" id="password-hint">At least 10 characters.</p>
            <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'password']) ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required<?= $this->fieldState($errors, 'password_confirmation') ?>>
            <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'password_confirmation']) ?>
        </div>
        <button type="submit" class="button button--primary button--lg button--block">Create account</button>
        <p class="auth__switch">Already have one? <a href="/login">Sign in</a></p>
    </form>
</div>

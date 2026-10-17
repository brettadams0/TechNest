<?php
/**
 * @var \TechNest\View\View $this
 * @var \TechNest\User\User $user
 * @var list<\TechNest\Order\Order> $orders
 * @var array<string, string> $errors
 */
$this->layout('layout', ['title' => 'Your account']);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Hi, <?= $this->e($user->username) ?></h1>
        <p class="muted">Member since <?= $this->e($this->date($user->createdAt, 'F Y')) ?> · <?= $this->e($user->email) ?></p>
    </div>
</div>

<div class="account">
    <section class="card" aria-labelledby="orders-title">
        <h2 id="orders-title">Your orders</h2>
        <?php if ($orders === []): ?>
            <div class="empty empty--compact">
                <p class="muted">You have not placed an order yet.</p>
                <a class="button button--primary" href="/catalog">Browse the catalog</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col">Order</th><th scope="col">Placed</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><a href="/account/orders/<?= $order->id ?>"><?= $this->e($order->number()) ?></a></td>
                            <td><?= $this->e($this->date($order->createdAt)) ?></td>
                            <td><?= $order->itemCount ?></td>
                            <td><?= $this->money($order->totalCents) ?></td>
                            <td><?= $this->insert('partials/status', ['status' => $order->status]) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <section class="card" id="security" aria-labelledby="security-title">
        <h2 id="security-title">Change password</h2>
        <form method="post" action="/account/password" novalidate>
            <?= $this->csrfField() ?>
            <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required<?= $this->fieldState($errors, 'current_password') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'current_password']) ?>
            </div>
            <div class="field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="10"<?= $this->fieldState($errors, 'password') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'password']) ?>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required<?= $this->fieldState($errors, 'password_confirmation') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'password_confirmation']) ?>
            </div>
            <button type="submit" class="button button--secondary">Update password</button>
        </form>
    </section>
</div>

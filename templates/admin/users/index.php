<?php
/**
 * @var \TechNest\View\View $this
 * @var list<array{user: \TechNest\User\User, is_admin: bool, orders: int, spent_cents: int}> $users
 * @var ?int $selfId
 */
$this->layout('admin/shell', ['title' => 'Customers']);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Customers</h1>
        <p class="muted"><?= $this->e($this->plural(count($users), 'account')) ?>. Disabling an account signs it out on its next request.</p>
    </div>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th scope="col">Account</th><th scope="col">Joined</th><th scope="col" class="num">Orders</th><th scope="col" class="num">Lifetime spend</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>
                <?php $u = $row['user']; $self = $u->id === $selfId; ?>
                <tr<?= $u->isActive ? '' : ' class="is-muted"' ?>>
                    <td><strong><?= $this->e($u->username) ?></strong><?= $self ? ' <span class="muted small">(you)</span>' : '' ?><br><span class="muted small"><?= $this->e($u->email) ?></span></td>
                    <td><?= $this->e($this->date($u->createdAt)) ?></td>
                    <td class="num"><?= $row['orders'] ?></td>
                    <td class="num"><?= $this->money($row['spent_cents']) ?></td>
                    <td><?= $row['is_admin'] ? '<span class="tag tag--info">Admin</span>' : '<span class="muted">Customer</span>' ?></td>
                    <td><?= $u->isActive ? '<span class="tag tag--success">Active</span>' : '<span class="tag tag--muted">Disabled</span>' ?></td>
                    <td class="actions">
                        <?php if (!$self): ?>
                            <form method="post" action="/admin/users/<?= $u->id ?>/admin" data-confirm="<?= $row['is_admin'] ? 'Remove admin access from ' : 'Make ' ?><?= $this->e($u->username) ?><?= $row['is_admin'] ? '?' : ' an administrator?' ?>">
                                <?= $this->csrfField() ?>
                                <button type="submit" class="button button--ghost button--sm"><?= $row['is_admin'] ? 'Revoke admin' : 'Make admin' ?></button>
                            </form>
                            <form method="post" action="/admin/users/<?= $u->id ?>/active"<?= $u->isActive ? ' data-confirm="Disable ' . $this->e($u->username) . '? They will be signed out."' : '' ?>>
                                <?= $this->csrfField() ?>
                                <button type="submit" class="button button--ghost button--sm<?= $u->isActive ? ' button--danger-text' : '' ?>"><?= $u->isActive ? 'Disable' : 'Enable' ?></button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

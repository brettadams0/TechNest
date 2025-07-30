<?php
// includes/admin/index.php
// Admin dashboard for TechNest

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
}

// Check if user is admin
$db = get_db();
$stmt = $db->prepare('SELECT * FROM admin WHERE user_id = ?');
$stmt->execute([current_user_id()]);
if (!$stmt->fetch()) {
    echo '<main><p>Access denied. Admins only.</p></main>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
?>
<main>
    <h1>Admin Dashboard</h1>
    <ul>
        <li><a href="/admin/manage_products.php">Manage Products</a></li>
        <li><a href="/admin/manage_users.php">Manage Users</a></li>
        <li><a href="/admin/manage_orders.php">Manage Orders</a></li>
        <li><a href="/admin/manage_themes.php">Manage Themes</a></li>
    </ul>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

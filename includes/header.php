<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Fetch active theme from DB
$db = get_db();
$theme = $db->query("SELECT css_file FROM themes WHERE is_active = 1 LIMIT 1")->fetchColumn();
if (!$theme) {
    $theme = 'theme-regular.css'; // fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TechNest</title>

    <!-- Dynamically include active theme CSS (root-relative path) -->
    <link rel="stylesheet" href="/assets/<?php echo htmlspecialchars($theme); ?>" />

    <!-- Site-wide JS (root-relative path) -->
    <script src="/assets/main.js" defer></script>

    <meta name="description" content="TechNest - Modern online electronics store for gadgets, laptops, and accessories." />
    <meta name="keywords" content="electronics, gadgets, laptops, store, technest, online shop" />
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico" />
</head>
<body>
<header>
    <nav>
        <button id="menu-toggle" aria-label="Open navigation" aria-controls="main-nav" aria-expanded="false" style="display:none;">
            &#9776;
        </button>
        <div id="main-nav">
            <a href="/index.php">Home</a>
            <a href="/catalog.php">Catalog</a>
            <a href="/about.php">About</a>

            <?php if (is_logged_in()): ?>
                <?php if (is_admin()): ?>
                    <a href="/admin/">Admin Dashboard</a>
                    <a href="/admin/manage_products.php">Manage Products</a>
                    <a href="/admin/manage_users.php">Manage Users</a>
                    <a href="/admin/manage_orders.php">Manage Orders</a>
                    <a href="/admin/manage_themes.php">Manage Themes</a>
                <?php endif; ?>
                <a href="/profile.php">Profile</a>
                <a href="/logout.php">Logout</a>
            <?php else: ?>
                <a href="/login.php">Login</a>
                <a href="/register.php">Register</a>
            <?php endif; ?>

            <a href="/help.php">Help</a>
        </div>
    </nav>
</header>

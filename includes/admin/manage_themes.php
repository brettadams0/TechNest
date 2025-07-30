<?php
// admin/manage_themes.php
// Admin theme switching for TechNest
require_once '../../includes/header.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// Check if user is logged in and admin
if (!is_logged_in()) {
    header('Location: ../../public_html/login.php');
    exit;
}
$db = get_db();
$stmt = $db->prepare('SELECT * FROM admin WHERE user_id = ?');
$stmt->execute([current_user_id()]);
if (!$stmt->fetch()) {
    echo '<main><p>Access denied. Admins only.</p></main>';
    require_once '../../includes/footer.php';
    exit;
}
// Handle theme switch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme_id'])) {
    $theme_id = (int)$_POST['theme_id'];
    $db->exec('UPDATE themes SET is_active = 0');
    $stmt = $db->prepare('UPDATE themes SET is_active = 1 WHERE theme_id = ?');
    $stmt->execute([$theme_id]);
    echo '<main><p>Theme updated!</p></main>';
}
// Fetch all themes
$themes = $db->query('SELECT * FROM themes')->fetchAll();
?>
<main>
    <h1>Manage Themes</h1>
    <form method="post">
        <ul>
            <?php foreach ($themes as $theme): ?>
                <li>
                    <label>
                        <input type="radio" name="theme_id" value="<?php echo $theme['theme_id']; ?>" <?php if ($theme['is_active']) echo 'checked'; ?>>
                        <?php echo htmlspecialchars($theme['name']); ?>
                        (<?php echo htmlspecialchars($theme['css_file']); ?>)
                        <?php if ($theme['is_active']): ?> <strong>(Active)</strong><?php endif; ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="submit">Switch Theme</button>
    </form>
    <p><a href="index.php">Back to Admin Dashboard</a></p>
</main>
<?php require_once '../../includes/footer.php'; ?> 
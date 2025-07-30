<?php
// admin/manage_users.php
// Admin user management for TechNest
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
// Handle enable/disable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $action = $_POST['action'] === 'disable' ? 0 : 1;
    $stmt = $db->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
    $stmt->execute([$action, $uid]);
    echo '<main><p>User status updated.</p></main>';
}
// Fetch all users
$users = $db->query('SELECT * FROM users')->fetchAll();
?>
<main>
    <h1>Manage Users</h1>
    <table>
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo $u['user_id']; ?></td>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['is_active'] ? 'Active' : 'Disabled'; ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                    <input type="hidden" name="action" value="<?php echo $u['is_active'] ? 'disable' : 'enable'; ?>">
                    <button type="submit"><?php echo $u['is_active'] ? 'Disable' : 'Enable'; ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">Back to Admin Dashboard</a></p>
</main>
<?php require_once '../../includes/footer.php'; ?> 
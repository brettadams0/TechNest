<?php
require_once __DIR__ . '/../includes/header.php';
if (!is_logged_in()) {
    echo '<main><p>You must be logged in to view your profile. <a href="login.php">Login here</a>.</p></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$db = get_db();
$user_id = current_user_id();
$stmt = $db->prepare('SELECT username, email FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<main>
    <h1>Your Profile</h1>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><a href="logout.php">Logout</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

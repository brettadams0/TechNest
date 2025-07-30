<?php
// User registration page for TechNest
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!$username || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = get_db();
        // Check for existing username/email
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, $hash]);
            $success = true;
        }
    }
}
?>
<main>
    <h1>Register</h1>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?>
        <p>Registration successful! <a href="login.php">Login here</a>.</p>
    <?php else: ?>
    <form method="post">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <label>Confirm Password: <input type="password" name="confirm" required></label><br>
        <button type="submit">Register</button>
    </form>
    <?php endif; ?>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
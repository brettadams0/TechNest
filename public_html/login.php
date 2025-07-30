<?php
// login.php - User login page for TechNest
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<main>
    <h1>Login</h1>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
    <form method="post">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    <p><a href="help_login.php">Need help? See login instructions.</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
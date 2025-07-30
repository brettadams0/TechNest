<?php
// Contact page for TechNest
require_once __DIR__ . '/../includes/header.php';
?>
<main>
    <h1>Contact Us</h1>
    <form method="post">
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Message:<br><textarea name="message" rows="5" required></textarea></label><br>
        <button type="submit">Send</button>
    </form>
    <p>Email: support@technest.com</p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
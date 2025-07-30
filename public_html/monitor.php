<?php
// monitor.php
// Backend monitoring page for TechNest
require_once 'header.php';
require_once 'db.php';

// Check DB connection
$db_status = false;
try {
    $db = get_db();
    $db_status = true;
} catch (Exception $e) {
    $db_status = false;
}
?>
<main>
    <h1>Site Monitoring</h1>
    <ul>
        <li>Database: <?php echo $db_status ? '<span style="color:green">Online</span>' : '<span style="color:red">Offline</span>'; ?></li>
        <li>PHP Version: <?php echo phpversion(); ?></li>
        <li>Server Time: <?php echo date('Y-m-d H:i:s'); ?></li>
    </ul>
</main>
<?php require_once 'footer.php'; ?> 
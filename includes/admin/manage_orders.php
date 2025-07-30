<?php
// admin/manage_orders.php
// Admin order management for TechNest
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
// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $stmt = $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    echo '<main><p>Order status updated.</p></main>';
}
// Order detail view
if (isset($_GET['view'])) {
    $oid = (int)$_GET['view'];
    $stmt = $db->prepare('SELECT * FROM orders WHERE order_id = ?');
    $stmt->execute([$oid]);
    $order = $stmt->fetch();
    if (!$order) {
        echo '<main><p>Order not found.</p></main>';
        require_once '../../includes/footer.php';
        exit;
    }
    $items = $db->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
    $items->execute([$oid]);
    $items = $items->fetchAll();
    ?>
    <main>
        <h1>Order #<?php echo $order['order_id']; ?></h1>
        <p><strong>User ID:</strong> <?php echo $order['user_id']; ?></p>
        <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
        <p><strong>Total:</strong> $<?php echo number_format($order['total'],2); ?></p>
        <h2>Items</h2>
        <ul>
            <?php foreach ($items as $item): ?>
                <li><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?> ($<?php echo number_format($item['price'],2); ?>)</li>
            <?php endforeach; ?>
        </ul>
        <h2>Update Status</h2>
        <form method="post">
            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
            <select name="status">
                <?php foreach (["pending","processing","shipped","delivered","cancelled"] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php if ($order['status'] === $status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Update Status</button>
        </form>
        <p><a href="manage_orders.php">Back to Orders</a></p>
    </main>
    <?php
    require_once '../../includes/footer.php';
    exit;
}
// Fetch all orders
$orders = $db->query('SELECT * FROM orders ORDER BY order_date DESC')->fetchAll();
?>
<main>
    <h1>Manage Orders</h1>
    <table>
        <tr><th>Order ID</th><th>User ID</th><th>Date</th><th>Status</th><th>Total</th><th>Action</th></tr>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><?php echo $o['order_id']; ?></td>
            <td><?php echo $o['user_id']; ?></td>
            <td><?php echo $o['order_date']; ?></td>
            <td><?php echo htmlspecialchars($o['status']); ?></td>
            <td>$<?php echo number_format($o['total'],2); ?></td>
            <td><a href="manage_orders.php?view=<?php echo $o['order_id']; ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">Back to Admin Dashboard</a></p>
</main>
<?php require_once '../../includes/footer.php'; ?> 
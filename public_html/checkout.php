<?php
// checkout.php - Checkout page for TechNest
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Only allow logged-in users
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
// Fetch cart from session
$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    echo '<main><p>Your cart is empty.</p></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
// Fetch product/order details
$db = get_db();
$items = [];
$total = 0;
foreach ($cart as $item) {
    $product = $db->prepare('SELECT * FROM products WHERE product_id = ?');
    $product->execute([$item['product_id']]);
    $product = $product->fetch();
    if (!$product) {
        continue;
    }
    $extra = 0;
    foreach ($item['options'] as $opt_id) {
        $opt = $db->prepare('SELECT * FROM product_options WHERE option_id = ?');
        $opt->execute([$opt_id]);
        $opt = $opt->fetch();
        if ($opt) {
            $extra += $opt['extra_price'];
        }
    }
    $price = $product['price'] + $extra;
    $subtotal = $price * $item['quantity'];
    $total += $subtotal;
    $items[] = [
        'name' => $product['name'],
        'quantity' => $item['quantity'],
        'price' => $price,
        'subtotal' => $subtotal,
        'product_id' => $item['product_id'],
        'options' => $item['options'],
    ];
}
$success = false;
$error = '';
// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    if (!$address) {
        $error = 'Shipping address required.';
    } else {
        $stmt = $db->prepare('INSERT INTO orders (user_id, total, status) VALUES (?, ?, ?)');
        $stmt->execute([current_user_id(), $total, 'pending']);
        $order_id = $db->lastInsertId();
        foreach ($items as $item) {
            foreach ($item['options'] as $opt_id) {
                $db->prepare('INSERT INTO order_items (order_id, product_id, option_id, quantity, price) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$order_id, $item['product_id'], $opt_id, $item['quantity'], $item['price']]);
            }
        }
        $_SESSION['cart'] = [];
        $success = true;
    }
}
?>
<main>
    <h1>Checkout</h1>
    <?php if ($success): ?>
        <p>Order placed! Thank you for shopping at TechNest.</p>
        <p><a href="index.php">Return to Home</a></p>
    <?php else: ?>
        <h2>Order Summary</h2>
        <ul>
            <?php foreach ($items as $item): ?>
                <li><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?> ($<?php echo number_format($item['subtotal'], 2); ?>)</li>
            <?php endforeach; ?>
        </ul>
        <h3>Total: $<?php echo number_format($total, 2); ?></h3>
        <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
        <form method="post">
            <label>Shipping Address:<br><textarea name="address" required></textarea></label><br>
            <button type="submit">Place Order</button>
        </form>
        <p><a href="help_checkout.php">Need help? See checkout instructions.</a></p>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
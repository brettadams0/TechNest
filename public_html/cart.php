<?php
// Shopping cart page for TechNest
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_id'], $_POST['options'], $_POST['quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $options = $_POST['options'];
        $quantity = max(1, (int)$_POST['quantity']);
        $key = $product_id . '-' . implode('-', $options);
        $_SESSION['cart'][$key] = [
            'product_id' => $product_id,
            'options' => $options,
            'quantity' => $quantity,
        ];
    } elseif (isset($_POST['remove'])) {
        unset($_SESSION['cart'][$_POST['remove']]);
    } elseif (isset($_POST['update_qty'], $_POST['key'])) {
        $qty = max(1, (int)$_POST['update_qty']);
        $_SESSION['cart'][$_POST['key']]['quantity'] = $qty;
    }
}

$db = get_db();
$items = [];
$total = 0;
foreach ($_SESSION['cart'] as $key => $item) {
    $product = $db->prepare('SELECT * FROM products WHERE product_id = ?');
    $product->execute([$item['product_id']]);
    $product = $product->fetch();
    if (!$product) {
        continue;
    }
    $options = [];
    $extra = 0;
    foreach ($item['options'] as $opt_id) {
        $opt = $db->prepare('SELECT * FROM product_options WHERE option_id = ?');
        $opt->execute([$opt_id]);
        $opt = $opt->fetch();
        if ($opt) {
            $options[] = $opt['option_name'] . ': ' . $opt['option_value'];
            $extra += $opt['extra_price'];
        }
    }
    $price = $product['price'] + $extra;
    $subtotal = $price * $item['quantity'];
    $total += $subtotal;
    $items[] = [
        'key' => $key,
        'name' => $product['name'],
        'options' => $options,
        'quantity' => $item['quantity'],
        'price' => $price,
        'subtotal' => $subtotal,
    ];
}
?>
<main>
    <h1>Your Cart</h1>
    <?php if (!$items): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
    <form method="post">
        <table>
            <tr><th>Product</th><th>Options</th><th>Quantity</th><th>Price</th><th>Subtotal</th><th>Remove</th></tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo implode(', ', $item['options']); ?></td>
                <td>
                    <input type="number" name="update_qty" value="<?php echo $item['quantity']; ?>" min="1" style="width:3em;">
                    <input type="hidden" name="key" value="<?php echo $item['key']; ?>">
                    <button type="submit">Update</button>
                </td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                <td><button type="submit" name="remove" value="<?php echo $item['key']; ?>">Remove</button></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </form>
    <h3>Total: $<?php echo number_format($total, 2); ?></h3>
    <a href="checkout.php" class="button">Proceed to Checkout</a>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
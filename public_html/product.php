<?php
// Product details page for TechNest
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Get product ID from query string
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$product_id) {
    echo '<main><p>Product not found.</p></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$db = get_db();
// Fetch product
$stmt = $db->prepare('SELECT * FROM products WHERE product_id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    echo '<main><p>Product not found.</p></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
// Fetch options
$options = $db->prepare('SELECT * FROM product_options WHERE product_id = ?');
$options->execute([$product_id]);
$options = $options->fetchAll();
// Group options by option_name
$grouped = [];
foreach ($options as $opt) {
    $grouped[$opt['option_name']][] = $opt;
}
?>
<main>
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="300">
    <p><?php echo htmlspecialchars($product['description']); ?></p>
    <form method="post" action="cart.php">
        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
        <?php foreach ($grouped as $option_name => $opts): ?>
            <label><?php echo htmlspecialchars($option_name); ?>:
                <select name="options[<?php echo htmlspecialchars($option_name); ?>]">
                    <?php foreach ($opts as $opt): ?>
                        <option value="<?php echo $opt['option_id']; ?>">
                            <?php echo htmlspecialchars($opt['option_value']); ?>
                            <?php if ($opt['extra_price'] > 0): ?> (+$<?php echo number_format($opt['extra_price'],2); ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br>
        <?php endforeach; ?>
        <label>Quantity: <input type="number" name="quantity" value="1" min="1"></label><br>
        <button type="submit">Add to Cart ($<?php echo number_format($product['price'],2); ?>+)</button>
    </form>
    <p><a href="catalog.php">Back to Catalog</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
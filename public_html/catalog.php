<?php
// Product catalog for TechNest
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Fetch all products
$db = get_db();
$products = $db->query('SELECT * FROM products ORDER BY name')->fetchAll();
?>
<main>
    <h1>Product Catalog</h1>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="200">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>$<?php echo number_format($product['price'], 2); ?></p>
                <a href="product.php?id=<?php echo $product['product_id']; ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
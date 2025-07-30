<?php
// index.php - Homepage for TechNest
// Shows featured products and links to catalog
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Fetch 6 featured products from the database
$db = get_db();
$products = $db->query('SELECT * FROM products ORDER BY product_id LIMIT 6')->fetchAll();
?>
<main>
    <h1>Welcome to TechNest</h1>
    <p>Your one-stop shop for the latest electronics and gadgets.</p>
    <h2>Featured Products</h2>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <!-- Product image, name, price, and link -->
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="200">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>$<?php echo number_format($product['price'], 2); ?></p>
                <a href="product.php?id=<?php echo $product['product_id']; ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    </div>
    <p><a href="catalog.php">Browse Full Catalog</a></p>
    <p><a href="help_browsing.php">Need help browsing? See our guide.</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
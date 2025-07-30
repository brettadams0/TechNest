<?php
// admin/manage_products.php
// Admin product management for TechNest

require_once '../../includes/header.php'; // Site header
require_once '../../includes/auth.php';   // Auth helpers
require_once '../../includes/db.php';     // DB connection

// --- Admin Access Control ---
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

// --- Add Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Insert new product
    $stmt = $db->prepare('INSERT INTO products (name, description, image_url, price) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['image_url'],
        $_POST['price']
    ]);
    echo '<main><p>Product added.</p></main>';
}

// --- Delete Product ---
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $stmt = $db->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->execute([$pid]);
    echo '<main><p>Product deleted.</p></main>';
}

// --- Edit Product (show form) ---
$edit_product = null;
if (isset($_GET['edit'])) {
    $pid = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->execute([$pid]);
    $edit_product = $stmt->fetch();
}
// --- Update Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $stmt = $db->prepare('UPDATE products SET name=?, description=?, image_url=?, price=? WHERE product_id=?');
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['image_url'],
        $_POST['price'],
        $_POST['product_id']
    ]);
    echo '<main><p>Product updated.</p></main>';
}
// --- Fetch All Products ---
$products = $db->query('SELECT * FROM products ORDER BY product_id DESC')->fetchAll();
?>
<main>
    <h1>Manage Products</h1>
    <?php if ($edit_product): ?>
        <!-- Edit Product Form -->
        <h2>Edit Product</h2>
        <form method="post">
            <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
            <label>Name: <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required></label><br>
            <label>Description:<br><textarea name="description"><?php echo htmlspecialchars($edit_product['description']); ?></textarea></label><br>
            <label>Image URL: <input type="text" name="image_url" value="<?php echo htmlspecialchars($edit_product['image_url']); ?>"></label><br>
            <label>Price: <input type="number" name="price" step="0.01" value="<?php echo $edit_product['price']; ?>" required></label><br>
            <button type="submit" name="update_product">Update Product</button>
            <a href="manage_products.php">Cancel</a>
        </form>
    <?php else: ?>
        <!-- Add Product Form -->
        <h2>Add Product</h2>
        <form method="post">
            <input type="hidden" name="add_product" value="1">
            <label>Name: <input type="text" name="name" required></label><br>
            <label>Description:<br><textarea name="description"></textarea></label><br>
            <label>Image URL: <input type="text" name="image_url"></label><br>
            <label>Price: <input type="number" name="price" step="0.01" required></label><br>
            <button type="submit">Add Product</button>
        </form>
    <?php endif; ?>
    <h2>All Products</h2>
    <table>
        <tr><th>ID</th><th>Name</th><th>Price</th><th>Actions</th></tr>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?php echo $p['product_id']; ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>$<?php echo number_format($p['price'],2); ?></td>
            <td>
                <a href="manage_products.php?edit=<?php echo $p['product_id']; ?>">Edit</a> |
                <a href="manage_products.php?delete=<?php echo $p['product_id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">Back to Admin Dashboard</a></p>
</main>
<?php require_once '../../includes/footer.php'; ?> 
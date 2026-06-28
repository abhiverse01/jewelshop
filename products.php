<?php
/**
 * Products — Displays all products in a responsive grid
 *
 * This page was COMPLETELY EMPTY in the original project.
 * It now fetches products from the database and renders them in cards.
 *
 * NOTE: header.php MUST be required first — it calls session_start() and
 * loads config/db.php which creates the $conn object used below.
 */
$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

// Fetch all products, newest first (safe now: $conn is initialized, session is started)
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<div class="page-header">
    <h1>Our Collection</h1>
    <?php if (isset($_SESSION['user'])): ?>
        <a href="add_product.php" class="btn-add">+ Add Product</a>
    <?php endif; ?>
</div>

<?php if (empty($products)): ?>
    <div class="no-products">
        <div class="empty-icon">&#128269;</div>
        <h3>No products yet</h3>
        <p>Start by adding your first piece of jewellery to the collection.</p>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="add_product.php" class="btn-add mt-2">Add Your First Product</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if (!empty($product['image']) && file_exists(__DIR__ . '/assets/images/' . basename($product['image']))): ?>
                        <img src="assets/images/<?php echo htmlspecialchars(basename($product['image'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        &#128142;
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-price">$<?php echo number_format((float)$product['price'], 2); ?></p>
                    <?php if (!empty($product['description'])): ?>
                        <p class="product-desc"><?php echo htmlspecialchars($product['description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
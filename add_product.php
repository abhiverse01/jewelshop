<?php
/**
 * Add Product — Form to add a new product with image upload
 *
 * Improvements from original:
 *   - Uses header/footer includes for consistent layout
 *   - Better file upload handling with unique filenames
 *   - Prepared statements for SQL insertion
 *   - Session check — only logged-in users can add products
 *
 * IMPORTANT: auth check + redirect MUST happen BEFORE any HTML output
 * (i.e. before header.php is required), otherwise header("Location: ...")
 * fails with "headers already sent". We bootstrap the session + DB inline
 * here, then load header.php only once we know the user is allowed in.
 */
$pageTitle = 'Add Product';

// --- Bootstrap: start session + load $conn BEFORE any output ---
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

// Require login — must run BEFORE header.php outputs any HTML
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['add_product'])) {

    $name        = trim($_POST['name'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate inputs
    if ($name === '' || $price === '') {
        $error = 'Product name and price are required.';
    } elseif (!is_numeric($price) || floatval($price) < 0) {
        $error = 'Please enter a valid price.';
    } else {
        // Handle image upload
        $imageName = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            // Detect MIME type. Try finfo (preferred), fall back to $_FILES['type']
            // (browser-reported, less trustworthy but always available).
            $mimeType = '';
            if (function_exists('finfo_open')) {
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
                finfo_close($fileInfo);
            } elseif (class_exists('finfo')) {
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES['image']['tmp_name']);
            } else {
                // Last resort: trust the browser's MIME hint (less secure, but
                // we still validate the extension below).
                $mimeType = $_FILES['image']['type'] ?? '';
            }

            // Also validate the file extension as a second line of defense
            $ext            = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExtMap  = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $extValid = isset($allowedExtMap[$ext]);

            if (!$extValid) {
                $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP.';
            } elseif (!in_array($mimeType, $allowedTypes)) {
                $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'File too large. Maximum size is 5MB.';
            } else {
                $uniqueName  = uniqid('jewel_', true) . '.' . $ext;
                $targetDir   = __DIR__ . '/assets/images';

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $targetPath = $targetDir . '/' . $uniqueName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imageName = $uniqueName;
                } else {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        }

        if (empty($error)) {
            // Insert with prepared statement
            $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
            // 'd' expects a float; cast the string so bind_param doesn't choke on locales
            $priceFloat = (float) $price;
            $stmt->bind_param("sdss", $name, $priceFloat, $description, $imageName);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: products.php");
                exit;
            } else {
                $error = 'Failed to add product. Please try again.';
                $stmt->close();
            }
        }
    }
}

// All auth + POST handling is done. Now safe to render HTML.
require_once __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <h2>Add New Product</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" data-validate>
        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" name="name" id="name" placeholder="e.g. Gold Diamond Ring" required>
        </div>

        <div class="form-group">
            <label for="price">Price ($) *</label>
            <input type="text" name="price" id="price" placeholder="e.g. 299.99" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" placeholder="Describe the product — materials, dimensions, style..." rows="4"></textarea>
        </div>

        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <small class="mt-1" style="color: var(--color-text-light); display:block;">Allowed: JPG, PNG, GIF, WebP — Max 5MB</small>
        </div>

        <div class="form-actions">
            <a href="products.php" class="btn-secondary">Cancel</a>
            <button type="submit" name="add_product" class="btn-primary">Add Product</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php

/**
 * Header — included at the top of every page.
 * Starts the session (idempotent), loads DB, and renders the <head> + navigation bar.
 *
 * Pages that need to do auth checks or redirects BEFORE any HTML output should
 * call session_start() and require config/db.php themselves at the very top,
 * THEN require this header. The session_start() below is guarded so it is safe
 * to call when a session is already active.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Helper: base URL for assets (works in both / and /pages/)
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}

// Determine which page is active
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Jewellery Shop' : 'Jewellery Shop'; ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
</head>

<body>

    <header class="site-header">
        <div class="header-inner">
            <a href="<?php echo $baseUrl; ?>/index.php" class="logo">
                <span class="logo-icon">&#128142;</span>
                <span>Jewellery Shop</span>
            </a>

            <button class="menu-toggle" aria-label="Toggle navigation">&#9776;</button>

            <nav>
                <ul class="nav-links">
                    <li><a href="<?php echo $baseUrl; ?>/index.php" <?php if ($currentPage === 'index') echo 'class="active"'; ?>>Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/products.php" <?php if ($currentPage === 'products') echo 'class="active"'; ?>>Product</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <!-- <li><span class="nav-user">Hi, <?php echo htmlspecialchars($_SESSION['user']); ?></span></li> -->
                        <li><a href="<?php echo $baseUrl; ?>/add_product.php" <?php if ($currentPage === 'add_product') echo 'class="active"'; ?>>Add Product</a></li>
                        <li><a href="<?php echo $baseUrl; ?>/logout.php" class="btn-logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $baseUrl; ?>/login.php" <?php if ($currentPage === 'login') echo 'class="active"'; ?>>Login</a></li>
                        <li><a href="<?php echo $baseUrl; ?>/register.php" <?php if ($currentPage === 'register') echo 'class="active"'; ?>>Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
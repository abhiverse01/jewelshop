<?php

/**
 * Homepage — Landing page for the Jewellery Shop
 */
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <h1>Welcome to Jewellery Shop</h1>
    <p>Discover our exquisite collection of handcrafted jewellery. From elegant rings to stunning necklaces, find the perfect piece for every occasion.</p>
    <a href="products.php" class="hero-btn">Browse Collection</a>
</section>

<!-- Feature Cards -->
<section class="features">
    <div class="feature-card">
        <div class="icon">&#128141;</div>
        <h3>Premium Rings</h3>
        <p>Explore our curated selection of gold, silver, and diamond rings crafted by expert artisans.</p>
    </div>
    <div class="feature-card">
        <div class="icon">&#128141;</div>
        <h3>Bracelets</h3>
        <p>Explore our curated selection of gold, silver, and diamond bracelets crafted by expert artisans.</p>
    </div>
    <div class="feature-card">
        <div class="icon">&#128142;</div>
        <h3>Necklaces & Pendants</h3>
        <p>Find statement necklaces and delicate pendants that complement every style and outfit.</p>
    </div>
    <div class="feature-card">
        <div class="icon">&#128144;</div>
        <h3>Earrings</h3>
        <p>From classic studs to elaborate chandeliers, our earrings add sparkle to any look.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
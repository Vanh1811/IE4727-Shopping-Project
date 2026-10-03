<?php
$pageTitle = 'Shopping bag';
$pageDescription = 'Review your F2B SHOP shopping bag.';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
    <div class="container page-hero__content">
        <p class="eyebrow">Your selection</p>
        <h1 class="page-heading">Shopping bag</h1>
    </div>
</section>

<section class="section">
    <div class="container empty-state">
        <div class="empty-state__icon" aria-hidden="true">+</div>
        <h2 class="section-heading">Your bag is ready for something good.</h2>
        <p class="body-text">Cart items, quantities, and totals will appear here when shopping-cart functionality is added.</p>
        <a class="button button--dark" href="shop.php">Continue shopping <span aria-hidden="true">→</span></a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

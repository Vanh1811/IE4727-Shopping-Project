<?php
$pageTitle = 'Order confirmation';
$pageDescription = 'Order confirmation placeholder for F2B SHOP.';
include __DIR__ . '/includes/header.php';
?>

<section class="section section--confirmation">
    <div class="container confirmation-card">
        <div class="confirmation-card__mark" aria-hidden="true">✓</div>
        <p class="eyebrow">Thank you for choosing F2B SHOP</p>
        <h1 class="page-heading">Order received.</h1>
        <p class="body-text">This is a placeholder confirmation screen. A real order number, summary, and status will be generated after checkout is connected to the backend.</p>
        <div class="button-row button-row--center">
            <a class="button button--dark" href="orders.php">View order status <span aria-hidden="true">→</span></a>
            <a class="text-link" href="shop.php">Keep shopping <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

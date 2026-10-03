<?php
$pageTitle = 'Order history';
$pageDescription = 'View F2B SHOP order history and status.';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
    <div class="container page-hero__content">
        <p class="eyebrow">Your F2B SHOP account</p>
        <h1 class="page-heading">Order status</h1>
        <p class="page-intro">Track past orders and review progress in one place.</p>
    </div>
</section>

<section class="section">
    <div class="container orders-placeholder">
        <div class="orders-placeholder__header"><h2 class="section-heading">Recent orders</h2><span class="helper-text">Placeholder data</span></div>
        <div class="order-row">
            <div><span class="helper-text">Order number</span><strong>F2B-0000</strong></div>
            <div><span class="helper-text">Placed</span><strong>To be connected</strong></div>
            <div><span class="helper-text">Status</span><span class="status-pill">Awaiting backend</span></div>
        </div>
        <p class="body-text">Authentication, order history, and live status updates are intentionally outside the Week 1 scope.</p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

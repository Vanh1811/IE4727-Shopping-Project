<?php
$pageTitle = 'Checkout';
$pageDescription = 'Checkout placeholder for F2B SHOP.';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
    <div class="container page-hero__content">
        <p class="eyebrow">Almost yours</p>
        <h1 class="page-heading">Checkout</h1>
        <p class="page-intro">A future checkout flow will collect delivery and payment details securely.</p>
    </div>
</section>

<section class="section">
    <div class="container checkout-placeholder">
        <div class="placeholder-panel">
            <span class="placeholder-panel__number">01</span>
            <div><h2>Delivery details</h2><p>Form fields will be added after the order model is agreed.</p></div>
        </div>
        <div class="placeholder-panel" id="payment">
            <span class="placeholder-panel__number">02</span>
            <div><h2>Payment</h2><p>Payment method and validation are intentionally not implemented yet.</p></div>
        </div>
        <div class="placeholder-panel">
            <span class="placeholder-panel__number">03</span>
            <div><h2>Review order</h2><p>Order summary and confirmation will connect to the cart in a later week.</p></div>
        </div>
        <a class="button button--dark" href="confirmation.php">Preview confirmation <span aria-hidden="true">→</span></a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

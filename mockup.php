<?php
$pageTitle = 'Mock-up review';
$pageDescription = 'Review a F2B SHOP design mock-up.';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero__content">
        <p class="eyebrow">Make it yours</p>
        <h1 class="page-heading">Mock-up review</h1>
        <p class="page-intro">A future review space for checking your design before final production.</p>
    </div>
</section>

<section class="section">
    <div class="container mockup-layout">
        <div class="mockup-preview" role="img" aria-label="Design mock-up placeholder">
            <span class="mockup-preview__label">YOUR DESIGN<br>PREVIEW</span>
            <span class="mockup-preview__garment" aria-hidden="true"></span>
        </div>
        <div class="mockup-copy">
            <p class="eyebrow">Review space</p>
            <h2 class="section-heading">Your mock-up will live here.</h2>
            <p class="body-text">After an order is placed, customers will be able to inspect a design mock-up, approve it, or request final changes.</p>
            <div class="mockup-actions">
                <button class="button button--dark" type="button" disabled>Approve mock-up</button>
                <button class="button button--outline" type="button" disabled>Request changes</button>
            </div>
            <p class="helper-text">Review actions will be connected to the order workflow later.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

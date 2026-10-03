<?php
$pageTitle = 'Product details';
$pageDescription = 'View a F2B SHOP product.';
include __DIR__ . '/includes/header.php';
?>

<section class="section section--product-detail">
    <div class="container product-detail">
        <div class="product-detail__visual product-card__image--sand" role="img" aria-label="Studio Overshirt product image placeholder">
            <span class="image-placeholder" aria-hidden="true">F2B SHOP / PRODUCT</span>
        </div>
        <div class="product-detail__copy">
            <p class="eyebrow">New arrival / Layering essential</p>
            <h1 class="page-heading">Studio Overshirt</h1>
            <div class="product-detail__rating"><span class="rating" aria-label="Rated 4.8 out of 5">★★★★★</span> <span>4.8 (24 reviews)</span></div>
            <p class="product-detail__price">$68</p>
            <p class="body-text">A relaxed overshirt with a clean line and enough room for the day’s changing plans. Product details are placeholders for the Week 1 foundation.</p>
            <div class="option-group">
                <p class="option-label">Colour: <strong>Sand</strong></p>
                <div class="swatches" aria-label="Colour options">
                    <button class="swatch swatch--sand" type="button" aria-label="Sand" aria-pressed="true"></button>
                    <button class="swatch swatch--ink" type="button" aria-label="Ink" aria-pressed="false"></button>
                    <button class="swatch swatch--olive" type="button" aria-label="Olive" aria-pressed="false"></button>
                </div>
            </div>
            <div class="option-group">
                <div class="option-label-row"><p class="option-label">Size</p><a href="#size-guide">Size guide</a></div>
                <div class="size-options" aria-label="Size options">
                    <button type="button">XS</button><button type="button">S</button><button type="button">M</button><button type="button">L</button><button type="button">XL</button>
                </div>
            </div>
            <button class="button button--dark button--wide" type="button" disabled>Add to bag <span aria-hidden="true">→</span></button>
            <p class="helper-text">Product interactions will be connected in a later week.</p>
        </div>
    </div>
</section>

<section class="section section--muted" id="size-guide">
    <div class="container narrow-content">
        <p class="eyebrow">Product notes</p>
        <h2 class="section-heading">Designed for repeat wear.</h2>
        <p class="body-text">This area is reserved for material, fit, care, and delivery information once the product model is available.</p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

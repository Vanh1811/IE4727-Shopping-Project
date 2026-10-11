<?php
$pageTitle = 'Studio Overshirt';
$pageDescription = 'View the Studio Overshirt from F2B SHOP.';
$pageStylesheet = 'css/product.css';

// This static shape can be replaced by a database result in a later phase.
$product = [
    'name' => 'Studio Overshirt',
    'category' => 'New arrival / Layering essential',
    'currentPrice' => '$68',
    'originalPrice' => '$82',
    'discount' => '-17%',
    'rating' => '4.8',
    'reviewCount' => '24 reviews',
    'colour' => 'Sand',
    'description' => 'A relaxed overshirt with a clean line and enough room for the day’s changing plans. Cut from a soft structured cotton blend, it is an easy layer for everyday dressing.',
    'images' => [
        ['src' => 'images/products/studio-overshirt.svg', 'alt' => 'Studio Overshirt front view in sand'],
        ['src' => 'images/products/studio-overshirt-detail.svg', 'alt' => 'Studio Overshirt collar and pocket detail'],
        ['src' => 'images/products/studio-overshirt-back.svg', 'alt' => 'Studio Overshirt back view in sand'],
        ['src' => 'images/products/studio-overshirt-fit.svg', 'alt' => 'Studio Overshirt styled as an everyday layer'],
    ],
];

include __DIR__ . '/includes/header.php';
?>

<div class="product-page">
    <div class="container">
        <nav class="product-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">/</span>
            <a href="shop.php">Shop</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Product</span>
        </nav>

        <section class="product-layout" aria-labelledby="product-title">
            <div class="product-gallery">
                <div class="product-gallery__main">
                    <span class="product-gallery__badge"><?= htmlspecialchars($product['discount'], ENT_QUOTES, 'UTF-8') ?></span>
                    <img id="product-main-image" src="<?= htmlspecialchars($product['images'][0]['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['images'][0]['alt'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="product-gallery__thumbs" aria-label="Product image thumbnails">
                    <?php foreach ($product['images'] as $index => $image): ?>
                        <a class="product-thumbnail<?= $index === 0 ? ' is-active' : '' ?>" href="#product-main-image" aria-label="View <?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>">
                            <img src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="product-info">
                <p class="eyebrow"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></p>
                <h1 class="product-info__title" id="product-title"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="product-info__rating">
                    <span class="product-stars" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5">★★★★★</span>
                    <a href="#reviews"><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($product['reviewCount'], ENT_QUOTES, 'UTF-8') ?>)</a>
                </div>
                <div class="product-info__price">
                    <strong><?= htmlspecialchars($product['currentPrice'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <del><?= htmlspecialchars($product['originalPrice'], ENT_QUOTES, 'UTF-8') ?></del>
                    <span><?= htmlspecialchars($product['discount'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="product-info__description"><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>

                <fieldset class="product-option">
                    <legend>Colour: <strong><?= htmlspecialchars($product['colour'], ENT_QUOTES, 'UTF-8') ?></strong></legend>
                    <div class="product-swatches" aria-label="Colour options">
                        <button class="product-swatch product-swatch--sand" type="button" aria-label="Sand" aria-pressed="true"></button>
                        <button class="product-swatch product-swatch--ink" type="button" aria-label="Ink" aria-pressed="false"></button>
                        <button class="product-swatch product-swatch--olive" type="button" aria-label="Olive" aria-pressed="false"></button>
                    </div>
                </fieldset>

                <fieldset class="product-option">
                    <legend class="sr-only">Size</legend>
                    <div class="product-option__heading">
                        <span aria-hidden="true">Size</span>
                        <a href="#size-guide">Size guide</a>
                    </div>
                    <div class="product-size-grid" aria-label="Size options">
                        <button type="button">XS</button>
                        <button type="button">S</button>
                        <button type="button">M</button>
                        <button type="button">L</button>
                        <button type="button">XL</button>
                    </div>
                </fieldset>

                <button class="button button--dark product-info__add" type="button">Add to bag <span aria-hidden="true">→</span></button>
                <p class="product-info__note">Select a colour and size to continue.</p>

                <dl class="product-highlights">
                    <div><dt>Fit</dt><dd>Relaxed</dd></div>
                    <div><dt>Fabric</dt><dd>Cotton blend</dd></div>
                    <div><dt>Care</dt><dd>Cold wash</dd></div>
                </dl>
            </div>
        </section>

        <section class="product-notes" id="size-guide" aria-labelledby="product-notes-title">
            <div>
                <p class="eyebrow">Product notes</p>
                <h2 id="product-notes-title">Designed for repeat wear.</h2>
            </div>
            <p>Roomy through the body with a structured shoulder and a clean hem. Wear it open over a tee or buttoned as a lightweight shirt.</p>
        </section>

        <section class="product-review-anchor" id="reviews" aria-label="Product reviews">
            <span class="product-stars" aria-hidden="true">★★★★★</span>
            <span>Community rating <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> from <?= htmlspecialchars($product['reviewCount'], ENT_QUOTES, 'UTF-8') ?></span>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

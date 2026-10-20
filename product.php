<?php
$pageStylesheet = 'css/product.css';

include __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order_access.php';
require_once __DIR__ . '/includes/cart.php';

$product = null;
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($productId !== false && $productId !== null && $productId > 0 && $db !== null) {
    $productQuery = <<<'SQL'
        SELECT
            p.name,
            p.description,
            p.price,
            p.original_price,
            p.rating,
            p.review_count,
            p.image,
            p.image_2,
            p.image_3,
            p.image_4,
            p.colour,
            c.name AS category_name
        FROM products AS p
        INNER JOIN categories AS c ON c.id = p.category_id
        WHERE p.id = ?
        LIMIT 1
    SQL;

    $productStatement = $db->prepare($productQuery);

    if ($productStatement !== false) {
        $productStatement->bind_param('i', $productId);

        if ($productStatement->execute()) {
            $productStatement->bind_result(
                $productName,
                $productDescription,
                $productPrice,
                $productOriginalPrice,
                $productRating,
                $productReviewCount,
                $productImage,
                $productImageTwo,
                $productImageThree,
                $productImageFour,
                $productColour,
                $productCategory
            );

            if ($productStatement->fetch()) {
                $price = (float) $productPrice;
                $originalPrice = $productOriginalPrice !== null ? (float) $productOriginalPrice : null;
                $hasDiscount = $originalPrice !== null && $originalPrice > $price;
                $discountPercent = $hasDiscount
                    ? (int) round((1 - ($price / $originalPrice)) * 100)
                    : 0;
                $productImages = [];

                foreach (
                    [
                        [$productImage, $productName . ' front view'],
                        [$productImageTwo, $productName . ' detail view'],
                        [$productImageThree, $productName . ' back view'],
                        [$productImageFour, $productName . ' styled view'],
                    ] as [$imagePath, $imageAlt]
                ) {
                    if (is_string($imagePath) && trim($imagePath) !== '') {
                        $productImages[] = [
                            'src' => trim($imagePath),
                            'alt' => $imageAlt,
                        ];
                    }
                }

                $product = [
                    'name' => $productName,
                    'category' => $productCategory,
                    'currentPrice' => '$' . number_format($price, 2),
                    'originalPrice' => $hasDiscount ? '$' . number_format($originalPrice, 2) : '',
                    'discount' => $discountPercent > 0 ? '-' . $discountPercent . '%' : '',
                    'rating' => number_format((float) $productRating, 1),
                    'reviewCount' => (string) $productReviewCount . ' reviews',
                    'colour' => $productColour,
                    'description' => $productDescription,
                    'images' => $productImages,
                ];

                $pageTitle = $product['name'];
                $pageDescription = 'View ' . $product['name'] . ' from F2B SHOP.';
            }
        } else {
            error_log('F2B SHOP product lookup failed.');
        }

        $productStatement->close();
    } else {
        error_log('F2B SHOP could not prepare the product lookup query.');
    }
}

if ($product !== null && $product['images'] === []) {
    error_log('F2B SHOP product has no usable main image.');
    $product = null;
}

if ($product === null) {
    $pageTitle = 'Product Not Found';
    $pageDescription = 'The requested product could not be found.';
}

$colourPalette = [
    'Black' => '#242424',
    'White' => '#f7f6f2',
    'Navy' => '#25364e',
    'Green' => '#65765a',
    'Coral' => '#d88372',
    'Cream' => '#e7ddc9',
    'Blue' => '#6688a8',
    'Olive' => '#7b8060',
    'Ink' => '#2e3642',
    'Mist' => '#bec4c3',
    'Sand' => '#c5a87d',
    'Slate' => '#64717d',
];

$colourOptions = [];
if ($product !== null) {
    $colourOptions[] = $product['colour'];
}

$cartCsrfToken = f2bCsrfToken('cart');

include __DIR__ . '/includes/header.php';
?>

<div class="product-page">
    <div class="container">
        <nav class="product-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">/</span>
            <a href="shop.php">Shop</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= $product === null ? 'Product Not Found' : 'Product' ?></span>
        </nav>

        <?php if ($product === null): ?>
            <section class="product-not-found" aria-labelledby="product-not-found-title">
                <p class="eyebrow">Product not found</p>
                <h1 id="product-not-found-title">PRODUCT NOT FOUND</h1>
                <p>The product you're looking for does not exist.</p>
                <a class="button button--dark" href="shop.php">Back to Shop</a>
            </section>
        <?php else: ?>
            <section class="product-layout" aria-labelledby="product-title">
                <div class="product-gallery">
                    <div class="product-gallery__main">
                        <?php if ($product['discount'] !== ''): ?>
                            <span class="product-gallery__badge"><?= htmlspecialchars($product['discount'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <img id="product-main-image" src="<?= htmlspecialchars($product['images'][0]['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['images'][0]['alt'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="product-gallery__thumbs" aria-label="Product image thumbnails">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <button class="product-thumbnail<?= $index === 0 ? ' is-active' : '' ?>" type="button" data-image-src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>" data-image-alt="<?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>" aria-label="View <?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                                <img src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="product-info">
                    <p class="eyebrow"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></p>
                    <h1 class="product-info__title" id="product-title"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="product-info__rating">
                        <span class="product-stars" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        <a href="#reviews"><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($product['reviewCount'], ENT_QUOTES, 'UTF-8') ?>)</a>
                    </div>
                    <div class="product-info__price">
                        <strong><?= htmlspecialchars($product['currentPrice'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($product['originalPrice'] !== ''): ?>
                            <del><?= htmlspecialchars($product['originalPrice'], ENT_QUOTES, 'UTF-8') ?></del>
                        <?php endif; ?>
                        <?php if ($product['discount'] !== ''): ?>
                            <span><?= htmlspecialchars($product['discount'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="product-info__description"><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>

                    <form class="product-info__form" id="product-cart-form" action="cart-action.php" method="post" data-product-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" data-max-quantity="<?= (int) F2B_CART_MAX_QUANTITY ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $cartCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
                        <input type="hidden" name="colour" id="product-colour" value="<?= htmlspecialchars($product['colour'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="size" id="product-size" value="">
                        <input type="hidden" name="quantity" id="product-quantity" value="1">

                        <fieldset class="product-option">
                            <legend>Colour: <strong id="selected-colour"><?= htmlspecialchars($product['colour'], ENT_QUOTES, 'UTF-8') ?></strong></legend>
                            <div class="product-swatches" aria-label="Colour options">
                                <?php foreach ($colourOptions as $colourOption): ?>
                                    <button class="product-swatch" type="button" data-colour="<?= htmlspecialchars($colourOption, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($colourOption, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $product['colour'] === $colourOption ? 'true' : 'false' ?>" style="background-color: <?= htmlspecialchars($colourPalette[$colourOption] ?? '#b8a78e', ENT_QUOTES, 'UTF-8') ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <fieldset class="product-option">
                            <legend class="sr-only">Size</legend>
                            <div class="product-option__heading">
                                <span aria-hidden="true">Size</span>
                                <a href="#size-guide">Size guide</a>
                            </div>
                            <div class="product-size-grid" aria-label="Size options">
                                <button type="button" data-size="S" aria-pressed="false">S</button>
                                <button type="button" data-size="M" aria-pressed="false">M</button>
                                <button type="button" data-size="L" aria-pressed="false">L</button>
                                <button type="button" data-size="XL" aria-pressed="false">XL</button>
                            </div>
                        </fieldset>

                        <div class="product-quantity" aria-label="Quantity selector">
                            <span class="product-quantity__label">Quantity</span>
                            <div class="product-quantity__controls">
                                <button type="button" data-quantity-action="decrease" aria-label="Decrease quantity">&minus;</button>
                                <output id="product-quantity-value" class="product-quantity__value" aria-live="polite">1</output>
                                <button type="button" data-quantity-action="increase" aria-label="Increase quantity">+</button>
                            </div>
                        </div>

                        <button class="button button--dark product-info__add" id="add-to-cart" type="submit">Add to Cart <span aria-hidden="true">&rarr;</span></button>
                        <p class="product-info__note" id="product-feedback" role="status" aria-live="polite"></p>
                    </form>

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
                <span class="product-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                <span>Community rating <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> from <?= htmlspecialchars($product['reviewCount'], ENT_QUOTES, 'UTF-8') ?></span>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

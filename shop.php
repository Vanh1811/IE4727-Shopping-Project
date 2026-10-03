<?php
$pageTitle = 'Shop';
$pageDescription = 'Browse the F2B SHOP collection.';
$pageStylesheet = 'css/shop.css';
include __DIR__ . '/includes/header.php';

$shopProducts = [
    [
        'name' => 'Classic Black T-Shirt',
        'price' => '$29.90',
        'rating' => '4.8',
        'reviews' => '124',
        'image' => 'images/products/form-tee.svg',
        'alt' => 'Classic black T-shirt',
    ],
    [
        'name' => 'Oversized Graphic Tee',
        'price' => '$34.90',
        'rating' => '4.7',
        'reviews' => '86',
        'image' => 'images/products/contour-rib-tee.svg',
        'alt' => 'Oversized graphic tee',
        'badge' => 'New',
    ],
    [
        'name' => 'Essential Hoodie',
        'price' => '$59.90',
        'rating' => '4.9',
        'reviews' => '212',
        'image' => 'images/products/studio-overshirt.svg',
        'alt' => 'Essential hoodie',
    ],
    [
        'name' => 'Relaxed Fit Shirt',
        'price' => '$49.90',
        'rating' => '4.6',
        'reviews' => '73',
        'image' => 'images/products/open-line-shirt.svg',
        'alt' => 'Relaxed fit shirt',
    ],
    [
        'name' => 'Straight-Leg Jeans',
        'price' => '$79.90',
        'originalPrice' => '$94.90',
        'rating' => '4.8',
        'reviews' => '158',
        'image' => 'images/products/arc-denim.svg',
        'alt' => 'Straight-leg jeans',
        'badge' => 'Sale',
        'discount' => '-15%',
    ],
    [
        'name' => 'Lightweight Jacket',
        'price' => '$84.90',
        'rating' => '4.7',
        'reviews' => '64',
        'image' => 'images/products/studio-overshirt.svg',
        'alt' => 'Lightweight jacket',
    ],
    [
        'name' => 'Casual Polo Shirt',
        'price' => '$39.90',
        'rating' => '4.5',
        'reviews' => '49',
        'image' => 'images/products/form-tee.svg',
        'alt' => 'Casual polo shirt',
    ],
    [
        'name' => 'Everyday Sweatshirt',
        'price' => '$54.90',
        'rating' => '4.8',
        'reviews' => '91',
        'image' => 'images/products/contour-rib-tee.svg',
        'alt' => 'Everyday sweatshirt',
    ],
    [
        'name' => 'Utility Cargo Pants',
        'price' => '$69.90',
        'rating' => '4.7',
        'reviews' => '67',
        'image' => 'images/products/everyday-pleat-trouser.svg',
        'alt' => 'Utility cargo pants',
    ],
    [
        'name' => 'Minimalist Overshirt',
        'price' => '$68.90',
        'rating' => '4.9',
        'reviews' => '118',
        'image' => 'images/products/open-line-shirt.svg',
        'alt' => 'Minimalist overshirt',
        'badge' => 'New',
    ],
    [
        'name' => 'Knit Pullover',
        'price' => '$64.90',
        'originalPrice' => '$74.90',
        'rating' => '4.6',
        'reviews' => '58',
        'image' => 'images/products/studio-overshirt.svg',
        'alt' => 'Knit pullover',
        'badge' => 'Sale',
        'discount' => '-13%',
    ],
    [
        'name' => 'Canvas Jacket',
        'price' => '$89.90',
        'rating' => '4.8',
        'reviews' => '42',
        'image' => 'images/products/soft-frame-tote.svg',
        'alt' => 'Canvas jacket',
    ],
];
?>

<section class="shop-page">
    <div class="container">
        <nav class="shop-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">&gt;</span>
            <span aria-current="page">Shop</span>
        </nav>

        <header class="shop-heading">
            <p class="eyebrow">The collection</p>
            <h1 class="page-heading">Shop</h1>
            <p class="page-intro">Discover considered essentials and expressive pieces made for everyday style.</p>
        </header>

        <div class="shop-layout">
            <aside class="shop-sidebar" aria-labelledby="filters-heading">
                <div class="shop-sidebar__heading">
                    <h2 id="filters-heading">Filters</h2>
                    <span aria-hidden="true">+</span>
                </div>

                <section class="shop-filter-section" aria-labelledby="category-heading">
                    <h3 id="category-heading">Categories</h3>
                    <p>Browse by category</p>
                    <p>Filter options coming soon.</p>
                </section>

                <section class="shop-filter-section" aria-labelledby="price-heading">
                    <h3 id="price-heading">Price</h3>
                    <p>Choose a price range</p>
                    <p>Filter options coming soon.</p>
                </section>
            </aside>

            <section class="shop-catalogue" aria-labelledby="catalogue-heading">
                <div class="shop-toolbar">
                    <p id="catalogue-heading" class="shop-result-count">Showing 1&ndash;12 of 24 Products</p>
                    <div class="shop-sort-placeholder">
                        <label for="sort-products">Sort by</label>
                        <select id="sort-products" disabled>
                            <option>Featured</option>
                        </select>
                    </div>
                </div>

                <div class="shop-product-grid">
                    <?php foreach ($shopProducts as $index => $product): ?>
                        <article class="product-card shop-product-card">
                            <a class="product-card__image-link" href="product.php?id=<?= $index + 1 ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="product-card__image">
                                    <?php if (!empty($product['badge'])): ?>
                                        <span class="product-card__badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['alt'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </a>
                            <div class="product-card__content">
                                <h2 class="product-card__name"><a href="product.php?id=<?= $index + 1 ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                                <div class="product-card__rating" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5 from <?= htmlspecialchars($product['reviews'], ENT_QUOTES, 'UTF-8') ?> reviews">
                                    <span class="product-card__stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                                    <span><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="product-card__review-count">(<?= htmlspecialchars($product['reviews'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                </div>
                                <div class="product-card__price">
                                    <span class="product-card__current-price"><?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($product['originalPrice'])): ?>
                                        <span class="product-card__original-price"><?= htmlspecialchars($product['originalPrice'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['discount'])): ?>
                                        <span class="product-card__discount"><?= htmlspecialchars($product['discount'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

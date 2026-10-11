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
                </div>

                <form class="shop-filter-form" action="shop.php" method="get">
                    <section class="shop-filter-section" aria-labelledby="category-heading">
                        <h3 id="category-heading">Categories</h3>
                        <div class="shop-filter-options">
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="t-shirts">
                                <span>T-Shirts</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="shirts">
                                <span>Shirts</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="hoodies">
                                <span>Hoodies</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="jackets">
                                <span>Jackets</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="pants">
                                <span>Pants</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="accessories">
                                <span>Accessories</span>
                            </label>
                        </div>
                    </section>

                    <section class="shop-filter-section" aria-labelledby="price-heading">
                        <h3 id="price-heading">Price</h3>
                        <div class="shop-price-fields">
                            <div class="shop-price-field">
                                <label class="shop-form-label" for="min-price">Min Price</label>
                                <div class="shop-price-input">
                                    <span aria-hidden="true">$</span>
                                    <input id="min-price" name="min_price" type="number" min="0" step="1" placeholder="0">
                                </div>
                            </div>
                            <div class="shop-price-field">
                                <label class="shop-form-label" for="max-price">Max Price</label>
                                <div class="shop-price-input">
                                    <span aria-hidden="true">$</span>
                                    <input id="max-price" name="max_price" type="number" min="0" step="1" placeholder="200">
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="shop-filter-actions">
                        <button class="button button--dark shop-filter-submit" type="submit">Apply Filters</button>
                        <button class="shop-clear-filters" type="button">Clear Filters</button>
                    </div>
                </form>
            </aside>

            <section class="shop-catalogue" aria-labelledby="catalogue-heading">
                <form class="shop-search" action="shop.php" method="get">
                    <div class="shop-search__field">
                        <label class="shop-form-label" for="catalogue-search">Search products</label>
                        <input id="catalogue-search" name="search" type="search" placeholder="Search products..." autocomplete="off">
                    </div>
                    <button class="button button--dark shop-search__submit" type="submit">Search</button>
                </form>

                <div class="shop-toolbar">
                    <p id="catalogue-heading" class="shop-result-count">Showing 1&ndash;12 of 24 Products</p>
                    <div class="shop-sort">
                        <label for="sort-products">Sort by</label>
                        <select id="sort-products" name="sort">
                            <option value="featured">Featured</option>
                            <option value="newest">Newest</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                        </select>
                    </div>
                </div>

                <section class="shop-empty-state is-hidden" aria-labelledby="empty-state-heading">
                    <div class="shop-empty-state__mark" aria-hidden="true">&#8212;</div>
                    <h2 id="empty-state-heading">No products found</h2>
                    <p>Try adjusting your search or filters to find something you love.</p>
                    <button class="button button--outline shop-empty-state__button" type="button">Clear Filters</button>
                </section>

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

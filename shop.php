<?php
$pageTitle = 'Shop';
$pageDescription = 'Browse the F2B SHOP collection.';
$pageStylesheet = 'css/shop.css';

include __DIR__ . '/includes/db.php';

$shopProducts = [];

if ($db !== null) {
    $catalogueQuery = <<<'SQL'
        SELECT
            p.id,
            p.name,
            p.price,
            p.original_price,
            p.rating,
            p.review_count,
            p.image,
            p.colour,
            p.is_new,
            p.is_top_selling,
            c.name AS category_name
        FROM products AS p
        INNER JOIN categories AS c ON c.id = p.category_id
        ORDER BY p.id ASC
    SQL;

    $catalogueResult = $db->query($catalogueQuery);

    if ($catalogueResult !== false) {
        while ($row = $catalogueResult->fetch_assoc()) {
            $price = (float) $row['price'];
            $originalPrice = $row['original_price'] !== null ? (float) $row['original_price'] : null;
            $discountPercent = $originalPrice !== null && $originalPrice > $price
                ? (int) round((1 - ($price / $originalPrice)) * 100)
                : 0;

            $shopProducts[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'category' => $row['category_name'],
                'price' => '$' . number_format($price, 2),
                'originalPrice' => $originalPrice !== null ? '$' . number_format($originalPrice, 2) : null,
                'discount' => $discountPercent > 0 ? '-' . $discountPercent . '%' : null,
                'rating' => number_format((float) $row['rating'], 1),
                'reviews' => (string) $row['review_count'],
                'image' => $row['image'],
                'alt' => $row['name'] . ' product image',
                'badge' => (int) $row['is_new'] === 1 ? 'New' : ($discountPercent > 0 ? 'Sale' : null),
            ];
        }

        $catalogueResult->free();
    } else {
        error_log('F2B SHOP catalogue query failed.');
    }
}

$shopProductCount = count($shopProducts);
$shopResultText = $shopProductCount > 0
    ? 'Showing 1–' . $shopProductCount . ' of ' . $shopProductCount . ' Products'
    : 'No products found';

include __DIR__ . '/includes/header.php';
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
                    <p id="catalogue-heading" class="shop-result-count"><?= htmlspecialchars($shopResultText, ENT_QUOTES, 'UTF-8') ?></p>
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

                <section class="shop-empty-state<?= $shopProductCount > 0 ? ' is-hidden' : '' ?>" aria-labelledby="empty-state-heading">
                    <div class="shop-empty-state__mark" aria-hidden="true">&#8212;</div>
                    <h2 id="empty-state-heading">No products found</h2>
                    <p>Try adjusting your search or filters to find something you love.</p>
                    <button class="button button--outline shop-empty-state__button" type="button">Clear Filters</button>
                </section>

                <div class="shop-product-grid">
                    <?php foreach ($shopProducts as $index => $product): ?>
                        <article class="product-card shop-product-card">
                            <a class="product-card__image-link" href="product.php?id=<?= $product['id'] ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="product-card__image">
                                    <?php if (!empty($product['badge'])): ?>
                                        <span class="product-card__badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['alt'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </a>
                            <div class="product-card__content">
                                <h2 class="product-card__name"><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h2>
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

<?php
$pageTitle = 'Shop';
$pageDescription = 'Browse the F2B SHOP collection.';
$pageStylesheet = 'css/shop.css';

include __DIR__ . '/includes/db.php';

$searchTerm = isset($_GET['search']) && is_scalar($_GET['search'])
    ? trim((string) $_GET['search'])
    : '';
$categorySlug = isset($_GET['category']) && is_scalar($_GET['category'])
    ? trim((string) $_GET['category'])
    : '';
$minPriceInput = isset($_GET['min_price']) && is_scalar($_GET['min_price'])
    ? trim((string) $_GET['min_price'])
    : '';
$maxPriceInput = isset($_GET['max_price']) && is_scalar($_GET['max_price'])
    ? trim((string) $_GET['max_price'])
    : '';

$allowedCategorySlugs = ['t-shirts', 'shirts', 'hoodies', 'jackets', 'pants', 'accessories'];
$categorySlug = in_array($categorySlug, $allowedCategorySlugs, true) ? $categorySlug : '';

$minPrice = is_numeric($minPriceInput) && (float) $minPriceInput >= 0
    ? (float) $minPriceInput
    : null;
$maxPrice = is_numeric($maxPriceInput) && (float) $maxPriceInput >= 0
    ? (float) $maxPriceInput
    : null;
$filterNotice = null;
$validPriceRange = true;

if ($minPriceInput !== '' && $minPrice === null) {
    $filterNotice = 'Enter a valid minimum price to use that filter.';
}

if ($maxPriceInput !== '' && $maxPrice === null) {
    $filterNotice = 'Enter a valid maximum price to use that filter.';
}

if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    $filterNotice = 'Minimum price cannot be greater than maximum price.';
    $validPriceRange = false;
}

$allowedSorts = [
    'featured' => 'p.is_top_selling DESC, p.is_new DESC, p.id ASC',
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_asc' => 'p.price ASC, p.id ASC',
    'price_desc' => 'p.price DESC, p.id DESC',
];
$sortKey = isset($_GET['sort']) && is_scalar($_GET['sort'])
    ? (string) $_GET['sort']
    : 'featured';
$sortKey = array_key_exists($sortKey, $allowedSorts) ? $sortKey : 'featured';

$shopProducts = [];

if ($db !== null && $validPriceRange) {
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
        WHERE 1 = 1
    SQL;

    $filterTypes = '';
    $filterParams = [];

    if ($searchTerm !== '') {
        $catalogueQuery .= ' AND p.name LIKE ?';
        $filterTypes .= 's';
        $filterParams[] = '%' . $searchTerm . '%';
    }

    if ($categorySlug !== '') {
        $catalogueQuery .= ' AND c.slug = ?';
        $filterTypes .= 's';
        $filterParams[] = $categorySlug;
    }

    if ($minPrice !== null) {
        $catalogueQuery .= ' AND p.price >= ?';
        $filterTypes .= 'd';
        $filterParams[] = $minPrice;
    }

    if ($maxPrice !== null) {
        $catalogueQuery .= ' AND p.price <= ?';
        $filterTypes .= 'd';
        $filterParams[] = $maxPrice;
    }

    $catalogueQuery .= ' ORDER BY ' . $allowedSorts[$sortKey];
    $catalogueStatement = $db->prepare($catalogueQuery);

    if ($catalogueStatement !== false) {
        if ($filterTypes !== '') {
            $bindValues = [$filterTypes];
            foreach ($filterParams as $index => &$filterParam) {
                $bindValues[] = &$filterParam;
            }
            call_user_func_array([$catalogueStatement, 'bind_param'], $bindValues);
            unset($filterParam);
        }

        if ($catalogueStatement->execute()) {
            $catalogueStatement->bind_result(
                $productId,
                $productName,
                $productPrice,
                $productOriginalPrice,
                $productRating,
                $productReviewCount,
                $productImage,
                $productColour,
                $productIsNew,
                $productIsTopSelling,
                $productCategoryName
            );

            while ($catalogueStatement->fetch()) {
                $price = (float) $productPrice;
                $originalPrice = $productOriginalPrice !== null ? (float) $productOriginalPrice : null;
                $discountPercent = $originalPrice !== null && $originalPrice > $price
                    ? (int) round((1 - ($price / $originalPrice)) * 100)
                    : 0;

                $shopProducts[] = [
                    'id' => (int) $productId,
                    'name' => $productName,
                    'category' => $productCategoryName,
                    'price' => '$' . number_format($price, 2),
                    'originalPrice' => $originalPrice !== null ? '$' . number_format($originalPrice, 2) : null,
                    'discount' => $discountPercent > 0 ? '-' . $discountPercent . '%' : null,
                    'rating' => number_format((float) $productRating, 1),
                    'reviews' => (string) $productReviewCount,
                    'image' => $productImage,
                    'alt' => $productName . ' product image',
                    'badge' => (int) $productIsNew === 1 ? 'New' : ($discountPercent > 0 ? 'Sale' : null),
                ];
            }
        } else {
            error_log('F2B SHOP filtered catalogue query failed.');
        }

        $catalogueStatement->close();
    } else {
        error_log('F2B SHOP could not prepare the filtered catalogue query.');
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
                    <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8') ?>">
                    <section class="shop-filter-section" aria-labelledby="category-heading">
                        <h3 id="category-heading">Categories</h3>
                        <div class="shop-filter-options">
                            <label class="shop-filter-option">
                                <input type="radio" name="category" value="t-shirts"<?= $categorySlug === 't-shirts' ? ' checked' : '' ?>>
                                <span>T-Shirts</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="radio" name="category" value="shirts"<?= $categorySlug === 'shirts' ? ' checked' : '' ?>>
                                <span>Shirts</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="radio" name="category" value="hoodies"<?= $categorySlug === 'hoodies' ? ' checked' : '' ?>>
                                <span>Hoodies</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="radio" name="category" value="jackets"<?= $categorySlug === 'jackets' ? ' checked' : '' ?>>
                                <span>Jackets</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="radio" name="category" value="pants"<?= $categorySlug === 'pants' ? ' checked' : '' ?>>
                                <span>Pants</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="radio" name="category" value="accessories"<?= $categorySlug === 'accessories' ? ' checked' : '' ?>>
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
                                    <input id="min-price" name="min_price" type="number" min="0" step="1" placeholder="0" value="<?= htmlspecialchars($minPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <div class="shop-price-field">
                                <label class="shop-form-label" for="max-price">Max Price</label>
                                <div class="shop-price-input">
                                    <span aria-hidden="true">$</span>
                                    <input id="max-price" name="max_price" type="number" min="0" step="1" placeholder="200" value="<?= htmlspecialchars($maxPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>
                    </section>

                    <?php if ($filterNotice !== null): ?>
                        <p class="shop-filter-feedback" role="status"><?= htmlspecialchars($filterNotice, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <div class="shop-filter-actions">
                        <button class="button button--dark shop-filter-submit" type="submit">Apply Filters</button>
                        <a class="shop-clear-filters" href="shop.php">Clear Filters</a>
                    </div>
                </form>
            </aside>

            <section class="shop-catalogue" aria-labelledby="catalogue-heading">
                <form class="shop-search" action="shop.php" method="get">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="min_price" value="<?= htmlspecialchars($minPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="max_price" value="<?= htmlspecialchars($maxPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="shop-search__field">
                        <label class="shop-form-label" for="catalogue-search">Search products</label>
                        <input id="catalogue-search" name="search" type="search" placeholder="Search products..." autocomplete="off" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <button class="button button--dark shop-search__submit" type="submit">Search</button>
                </form>

                <div class="shop-toolbar">
                    <p id="catalogue-heading" class="shop-result-count"><?= htmlspecialchars($shopResultText, ENT_QUOTES, 'UTF-8') ?></p>
                    <form class="shop-sort" action="shop.php" method="get">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="min_price" value="<?= htmlspecialchars($minPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="max_price" value="<?= htmlspecialchars($maxPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                        <label for="sort-products">Sort by</label>
                        <select id="sort-products" name="sort">
                            <option value="featured"<?= $sortKey === 'featured' ? ' selected' : '' ?>>Featured</option>
                            <option value="newest"<?= $sortKey === 'newest' ? ' selected' : '' ?>>Newest</option>
                            <option value="price_asc"<?= $sortKey === 'price_asc' ? ' selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc"<?= $sortKey === 'price_desc' ? ' selected' : '' ?>>Price: High to Low</option>
                        </select>
                        <button class="button button--outline button--small shop-sort-submit" type="submit">Apply</button>
                    </form>
                </div>

                <section class="shop-empty-state<?= $shopProductCount > 0 ? ' is-hidden' : '' ?>" aria-labelledby="empty-state-heading">
                    <div class="shop-empty-state__mark" aria-hidden="true">&#8212;</div>
                    <h2 id="empty-state-heading">No products found</h2>
                    <p>Try adjusting your search or filters to find something you love.</p>
                    <a class="button button--outline shop-empty-state__button" href="shop.php">Clear Filters</a>
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

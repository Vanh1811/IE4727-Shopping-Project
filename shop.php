<?php
$pageTitle = 'Shop';
$pageDescription = 'Browse the F2B SHOP collection.';
$pageStylesheet = 'css/shop.css';

include __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/product_colour.php';

$priceFloor = 0;
$priceCeiling = 200;
$availableColours = [];
if ($db !== null) {
    $priceBoundsResult = $db->query('SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM products');
    if ($priceBoundsResult !== false) {
        $priceBounds = $priceBoundsResult->fetch_assoc();
        if ($priceBounds && $priceBounds['min_price'] !== null && $priceBounds['max_price'] !== null) {
            $priceFloor = (int) floor((float) $priceBounds['min_price']);
            $priceCeiling = (int) ceil((float) $priceBounds['max_price']);
        }
        $priceBoundsResult->free();
    }
    $colourResult = $db->query("SELECT DISTINCT colour FROM products WHERE colour <> '' ORDER BY colour");
    if ($colourResult !== false) {
        while ($colourRow = $colourResult->fetch_assoc()) {
            $availableColours[] = $colourRow['colour'];
        }
        $colourResult->free();
    }
}

$searchInput = isset($_GET['search']) ? $_GET['search'] : (isset($_GET['q']) ? $_GET['q'] : null);
$searchTerm = is_scalar($searchInput)
    ? trim((string) $searchInput)
    : '';
$categoryInput = $_GET['category'] ?? [];
$categoryInputs = is_array($categoryInput) ? $categoryInput : [$categoryInput];
$categorySlugs = [];
foreach ($categoryInputs as $categoryValue) {
    if (is_scalar($categoryValue)) {
        $categorySlugs[] = trim((string) $categoryValue);
    }
}
$colourInput = $_GET['colour'] ?? [];
$colourInputs = is_array($colourInput) ? $colourInput : [$colourInput];
$selectedColours = [];
foreach ($colourInputs as $colourValue) {
    if (is_scalar($colourValue)) {
        $selectedColours[] = trim((string) $colourValue);
    }
}
$minPriceInput = isset($_GET['min_price']) && is_scalar($_GET['min_price'])
    ? trim((string) $_GET['min_price'])
    : '';
$maxPriceInput = isset($_GET['max_price']) && is_scalar($_GET['max_price'])
    ? trim((string) $_GET['max_price'])
    : '';

$allowedCategorySlugs = ['t-shirts', 'shirts', 'hoodies', 'jackets', 'pants', 'accessories'];
$categorySlugs = array_values(array_unique(array_intersect($categorySlugs, $allowedCategorySlugs)));
$selectedColours = array_values(array_unique(array_intersect($selectedColours, $availableColours)));

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

    if ($categorySlugs !== []) {
        $categoryPlaceholders = implode(', ', array_fill(0, count($categorySlugs), '?'));
        $catalogueQuery .= ' AND c.slug IN (' . $categoryPlaceholders . ')';
        $filterTypes .= str_repeat('s', count($categorySlugs));
        foreach ($categorySlugs as $selectedCategory) {
            $filterParams[] = $selectedCategory;
        }
    }

    if ($selectedColours !== []) {
        $colourPlaceholders = implode(', ', array_fill(0, count($selectedColours), '?'));
        $catalogueQuery .= ' AND p.colour IN (' . $colourPlaceholders . ')';
        $filterTypes .= str_repeat('s', count($selectedColours));
        foreach ($selectedColours as $selectedColour) {
            $filterParams[] = $selectedColour;
        }
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
                $ratingValue = (float) $productRating;
                $filledStars = max(0, min(5, (int) round($ratingValue)));

                $shopProducts[] = [
                    'id' => (int) $productId,
                    'name' => $productName,
                    'category' => $productCategoryName,
                    'colour' => $productColour,
                    'colourHex' => f2bProductColourHex($productColour),
                    'price' => '$' . number_format($price, 2),
                    'originalPrice' => $originalPrice !== null ? '$' . number_format($originalPrice, 2) : null,
                    'discount' => $discountPercent > 0 ? '-' . $discountPercent . '%' : null,
                    'rating' => number_format($ratingValue, 1),
                    'stars' => str_repeat('&#9733;', $filledStars) . str_repeat('&#9734;', 5 - $filledStars),
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
    ? number_format($shopProductCount) . ($shopProductCount === 1 ? ' product' : ' products')
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

        <div class="shop-layout">
            <aside class="shop-sidebar" aria-labelledby="filters-heading">
                <div class="shop-sidebar__heading">
                    <h2 id="filters-heading">Filters</h2>
                </div>

                <form class="shop-filter-form" action="shop.php" method="get">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="min_price" value="<?= htmlspecialchars($minPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="max_price" value="<?= htmlspecialchars($maxPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                    <section class="shop-filter-section" aria-labelledby="category-heading">
                        <h3 id="category-heading">Categories</h3>
                        <div class="shop-filter-options">
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="t-shirts"<?= in_array('t-shirts', $categorySlugs, true) ? ' checked' : '' ?>>
                                <span>T-Shirts</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="shirts"<?= in_array('shirts', $categorySlugs, true) ? ' checked' : '' ?>>
                                <span>Shirts</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="hoodies"<?= in_array('hoodies', $categorySlugs, true) ? ' checked' : '' ?>>
                                <span>Hoodies</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="jackets"<?= in_array('jackets', $categorySlugs, true) ? ' checked' : '' ?>>
                                <span>Jackets</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="pants"<?= in_array('pants', $categorySlugs, true) ? ' checked' : '' ?>>
                                <span>Pants</span>
                            </label>
                            <label class="shop-filter-option">
                                <input type="checkbox" name="category[]" value="accessories"<?= in_array('accessories', $categorySlugs, true) ? ' checked' : '' ?>>
                                <span>Accessories</span>
                            </label>
                        </div>
                    </section>

                    <?php if ($availableColours !== []): ?>
                        <section class="shop-filter-section" aria-labelledby="colour-heading">
                            <h3 id="colour-heading">Colour</h3>
                            <div class="shop-filter-options shop-filter-options--colours">
                                <?php foreach ($availableColours as $availableColour): ?>
                                    <label class="shop-filter-option shop-filter-option--colour">
                                        <input type="checkbox" name="colour[]" value="<?= htmlspecialchars($availableColour, ENT_QUOTES, 'UTF-8') ?>"<?= in_array($availableColour, $selectedColours, true) ? ' checked' : '' ?>>
                                        <span class="product-colour-dot" style="--swatch-colour: <?= htmlspecialchars(f2bProductColourHex($availableColour), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span>
                                        <span><?= htmlspecialchars($availableColour, ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="shop-filter-section" aria-labelledby="price-heading">
                        <h3 id="price-heading">Price</h3>
                        <div class="shop-price-range" data-min="<?= $priceFloor ?>" data-max="<?= $priceCeiling ?>">
                            <div class="shop-price-range__values" aria-live="polite">
                                <span id="price-min-value">Min: $<?= $minPrice !== null ? number_format($minPrice, 0) : number_format($priceFloor, 0) ?></span>
                                <span id="price-max-value">Max: $<?= $maxPrice !== null ? number_format($maxPrice, 0) : number_format($priceCeiling, 0) ?></span>
                            </div>
                            <div class="shop-price-range__controls" id="price-range-controls" role="group" aria-label="Choose price range">
                                <div class="shop-price-range__track" aria-hidden="true"><span id="price-range-fill"></span></div>
                                <button id="min-price-range" class="shop-price-range__handle" type="button" role="slider" aria-label="Minimum price" aria-valuemin="<?= $priceFloor ?>" aria-valuemax="<?= $priceCeiling ?>" aria-valuenow="<?= $minPrice !== null ? max($priceFloor, min($priceCeiling, (int) $minPrice)) : $priceFloor ?>" style="left: <?= $priceCeiling > $priceFloor && $minPrice !== null ? ((max($priceFloor, min($priceCeiling, (int) $minPrice)) - $priceFloor) / ($priceCeiling - $priceFloor) * 100) : 0 ?>%"></button>
                                <button id="max-price-range" class="shop-price-range__handle" type="button" role="slider" aria-label="Maximum price" aria-valuemin="<?= $priceFloor ?>" aria-valuemax="<?= $priceCeiling ?>" aria-valuenow="<?= $maxPrice !== null ? max($priceFloor, min($priceCeiling, (int) $maxPrice)) : $priceCeiling ?>" style="left: <?= $priceCeiling > $priceFloor && $maxPrice !== null ? ((max($priceFloor, min($priceCeiling, (int) $maxPrice)) - $priceFloor) / ($priceCeiling - $priceFloor) * 100) : 100 ?>%"></button>
                            </div>
                            <p class="shop-price-range__hint">Drag a handle or tap the bar to adjust prices.</p>
                        </div>
                    </section>

                    <?php if ($filterNotice !== null): ?>
                        <p class="shop-filter-feedback" role="status"><?= htmlspecialchars($filterNotice, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                </form>
            </aside>

            <section class="shop-catalogue" aria-labelledby="catalogue-heading">
                <form class="shop-search" action="shop.php" method="get">
                    <?php foreach ($categorySlugs as $selectedCategory): ?>
                        <input type="hidden" name="category[]" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                    <?php foreach ($selectedColours as $selectedColour): ?>
                        <input type="hidden" name="colour[]" value="<?= htmlspecialchars($selectedColour, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="min_price" value="<?= htmlspecialchars($minPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="max_price" value="<?= htmlspecialchars($maxPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="shop-search__field">
                        <label class="shop-form-label" for="catalogue-search">Search products</label>
                        <input id="catalogue-search" name="search" type="search" placeholder="Search products..." autocomplete="off" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </form>

                <div class="shop-toolbar">
                    <p id="catalogue-heading" class="shop-result-count" aria-live="polite"><?= htmlspecialchars($shopResultText, ENT_QUOTES, 'UTF-8') ?></p>
                    <form class="shop-sort" action="shop.php" method="get">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($categorySlugs as $selectedCategory): ?>
                            <input type="hidden" name="category[]" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                        <?php foreach ($selectedColours as $selectedColour): ?>
                            <input type="hidden" name="colour[]" value="<?= htmlspecialchars($selectedColour, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="min_price" value="<?= htmlspecialchars($minPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="max_price" value="<?= htmlspecialchars($maxPriceInput, ENT_QUOTES, 'UTF-8') ?>">
                        <label for="sort-products">Sort by</label>
                        <select id="sort-products" name="sort">
                            <option value="featured"<?= $sortKey === 'featured' ? ' selected' : '' ?>>Featured</option>
                            <option value="newest"<?= $sortKey === 'newest' ? ' selected' : '' ?>>Newest</option>
                            <option value="price_asc"<?= $sortKey === 'price_asc' ? ' selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc"<?= $sortKey === 'price_desc' ? ' selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </form>
                </div>

                <section class="shop-empty-state<?= $shopProductCount > 0 ? ' is-hidden' : '' ?>" aria-labelledby="empty-state-heading">
                    <div class="shop-empty-state__mark" aria-hidden="true">&#8212;</div>
                    <h2 id="empty-state-heading">No products found</h2>
                    <p>Try adjusting your search or filters to find something you love.</p>
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
                                <p class="shop-product-card__category"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></p>
                                <h2 class="product-card__name"><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                                <p class="shop-product-card__colour"><span class="product-colour-dot" style="--swatch-colour: <?= htmlspecialchars($product['colourHex'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span><?= htmlspecialchars($product['colour'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="product-card__rating" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5 from <?= htmlspecialchars($product['reviews'], ENT_QUOTES, 'UTF-8') ?> reviews">
                                    <span class="product-card__stars" aria-hidden="true"><?= $product['stars'] ?></span>
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

<script src="js/shop.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>

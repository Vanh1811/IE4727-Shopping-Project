<?php
$pageTitle = 'Home';
$pageDescription = 'F2B SHOP — modern fashion essentials designed for your everyday style.';

include __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/product_colour.php';

$mapHomepageProduct = static function (array $row): array {
    $price = (float) $row['price'];
    $originalPrice = $row['original_price'] !== null ? (float) $row['original_price'] : null;
    $discountPercent = $originalPrice !== null && $originalPrice > $price
        ? (int) round((1 - ($price / $originalPrice)) * 100)
        : 0;
    $badge = (int) $row['is_new'] === 1
        ? 'New'
        : ($discountPercent > 0
            ? '-' . $discountPercent . '%'
            : ((int) $row['is_top_selling'] === 1 ? 'Best seller' : null));
    $filledStars = max(0, min(5, (int) round((float) $row['rating'])));

    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'price' => '$' . number_format($price, 2),
        'oldPrice' => $originalPrice !== null ? '$' . number_format($originalPrice, 2) : null,
        'meta' => $row['category_name'] . ' / ' . $row['colour'],
        'colourHex' => f2bProductColourHex($row['colour']),
        'image' => $row['image'],
        'rating' => number_format((float) $row['rating'], 1),
        'stars' => str_repeat('&#9733;', $filledStars) . str_repeat('&#9734;', 5 - $filledStars),
        'badge' => $badge,
    ];
};

$loadHomepageProducts = static function ($connection, string $flagColumn) use ($mapHomepageProduct): array {
    $allowedFlagColumns = ['is_new', 'is_top_selling'];

    if (!in_array($flagColumn, $allowedFlagColumns, true)) {
        return [];
    }

    $query = 'SELECT p.id, p.name, p.price, p.original_price, p.rating, p.image, p.colour, '
        . 'p.is_new, p.is_top_selling, c.name AS category_name '
        . 'FROM products AS p '
        . 'INNER JOIN categories AS c ON c.id = p.category_id '
        . 'WHERE p.' . $flagColumn . ' = 1 '
        . 'ORDER BY p.created_at DESC, p.id DESC LIMIT 4';
    $result = $connection->query($query);
    $products = [];

    if ($result === false) {
        error_log('F2B SHOP homepage catalogue query failed.');
        return $products;
    }

    while ($row = $result->fetch_assoc()) {
        $products[] = $mapHomepageProduct($row);
    }

    $result->free();
    return $products;
};

$newArrivals = $db !== null ? $loadHomepageProducts($db, 'is_new') : [];
$topSelling = $db !== null ? $loadHomepageProducts($db, 'is_top_selling') : [];

$catalogueStats = null;
if ($db !== null) {
    $statsResult = $db->query('SELECT COUNT(*) AS product_count, COUNT(DISTINCT category_id) AS category_count, AVG(rating) AS average_rating FROM products');
    if ($statsResult !== false) {
        $catalogueStats = $statsResult->fetch_assoc();
        $statsResult->free();
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="container hero-layout">
        <div class="hero-copy">
            <p class="eyebrow">F2B SHOP / The everyday edit</p>
            <h1 class="hero-heading">Wear your <span>style.</span></h1>
            <p class="hero-text">Modern essentials and expressive layers for the way you move, made to help every outfit feel like your own.</p>
            <div class="button-row">
                <a class="button button--dark" href="shop.php">Shop Now <span aria-hidden="true">→</span></a>
                <a class="text-link" href="#new-arrivals">Explore the edit <span aria-hidden="true">↘</span></a>
            </div>
            <?php if ($catalogueStats !== null && (int) $catalogueStats['product_count'] > 0): ?>
                <div class="hero-stats" aria-label="F2B SHOP catalogue highlights">
                    <div><strong><?= number_format((int) $catalogueStats['product_count']) ?></strong><span>styles to explore</span></div>
                    <div><strong><?= (int) $catalogueStats['category_count'] ?></strong><span>categories</span></div>
                    <div><strong><?= number_format((float) $catalogueStats['average_rating'], 1) ?>/5</strong><span>average product rating</span></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="hero-art">
            <img class="hero-art__image" src="images/branding/hero-fashion.jpg" alt="Model in a neutral outfit seated beside a studio window" fetchpriority="high">
            <div class="hero-art__overlay" aria-hidden="true">
                <span class="hero-art__label">F2B SHOP / 01</span>
                <span class="hero-art__caption">Everyday, reimagined.</span>
            </div>
        </div>
    </div>
</section>

<section class="brand-strip" aria-label="F2B SHOP values">
    <div class="brand-strip__viewport">
        <div class="brand-strip__track">
            <div class="brand-strip__sequence">
                <span class="brand-strip__statement"><span class="brand-strip__number" aria-hidden="true">01</span><span class="brand-strip__copy">Original <em>by design</em></span></span>
                <span class="brand-strip__separator" aria-hidden="true">✦</span>
                <span class="brand-strip__statement"><span class="brand-strip__number" aria-hidden="true">02</span><span class="brand-strip__copy">Made for <em>every day</em></span></span>
                <span class="brand-strip__separator" aria-hidden="true">✦</span>
                <span class="brand-strip__statement"><span class="brand-strip__number" aria-hidden="true">03</span><span class="brand-strip__copy">Made to be <em>yours</em></span></span>
                <span class="brand-strip__separator" aria-hidden="true">✦</span>
            </div>
        </div>
    </div>
</section>

<section class="section" id="new-arrivals">
    <div class="container">
        <div class="section-heading-row" data-scroll-reveal>
            <div>
                <p class="eyebrow">Just in</p>
                <h2 class="section-heading">New arrivals</h2>
            </div>
            <a class="button button--outline button--small" href="shop.php">View all <span aria-hidden="true">→</span></a>
        </div>
        <div class="product-grid product-grid--four">
            <?php foreach ($newArrivals as $product): ?>
                <article class="product-card" data-scroll-reveal="card">
                    <a class="product-card__image product-card__image--photo" href="product.php?id=<?= $product['id'] ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> product image">
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h3><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p class="product-card__meta"><span class="product-colour-dot" style="--swatch-colour: <?= htmlspecialchars($product['colourHex'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span><?= htmlspecialchars($product['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="price-group">
                            <strong><?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($product['oldPrice'])): ?><del><?= htmlspecialchars($product['oldPrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?>
                        </div>
                    </div>
                    <div class="rating" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5"><span aria-hidden="true"><?= $product['stars'] ?></span> <span><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?></span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--muted" id="top-selling">
    <div class="container">
        <div class="section-heading-row" data-scroll-reveal>
            <div>
                <p class="eyebrow">Community favourites</p>
                <h2 class="section-heading">Top selling</h2>
            </div>
            <a class="button button--outline button--small" href="shop.php">View all <span aria-hidden="true">→</span></a>
        </div>
        <div class="product-grid product-grid--four">
            <?php foreach ($topSelling as $product): ?>
                <article class="product-card">
                    <a class="product-card__image product-card__image--photo" href="product.php?id=<?= $product['id'] ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> product image">
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h3><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p class="product-card__meta"><span class="product-colour-dot" style="--swatch-colour: <?= htmlspecialchars($product['colourHex'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span><?= htmlspecialchars($product['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="price-group">
                            <strong><?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($product['oldPrice'])): ?><del><?= htmlspecialchars($product['oldPrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?>
                        </div>
                    </div>
                    <div class="rating" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5"><span aria-hidden="true"><?= $product['stars'] ?></span> <span><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?></span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="styles">
    <div class="container">
        <div class="section-heading-row" data-scroll-reveal>
            <div>
                <p class="eyebrow">Find your direction</p>
                <h2 class="section-heading">Browse by style</h2>
            </div>
        </div>
        <div class="style-grid">
            <a class="style-card style-card--casual" href="shop.php?category=t-shirts">
                <img class="style-card__image" src="images/categories/casual-fashion.jpg" alt="Casual knitwear styled for an everyday outing" loading="lazy">
                <span class="style-card__number">01</span>
                <span class="style-card__name">Casual</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--formal" href="shop.php?category=shirts">
                <img class="style-card__image" src="images/categories/formal-fashion.jpg" alt="Model wearing a light tailored suit" loading="lazy">
                <span class="style-card__number">02</span>
                <span class="style-card__name">Formal</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--party" href="shop.php?category=jackets">
                <img class="style-card__image" src="images/categories/party-fashion.jpg" alt="Model wearing an evening dress" loading="lazy">
                <span class="style-card__number">03</span>
                <span class="style-card__name">Party</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--gym" href="shop.php?category=hoodies">
                <img class="style-card__image" src="images/categories/gym-fashion.jpg" alt="Two women wearing activewear outdoors" loading="lazy">
                <span class="style-card__number">04</span>
                <span class="style-card__name">Gym</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
        </div>
    </div>
</section>

<section class="newsletter-section" id="contact">
    <div class="container newsletter-box">
        <div class="newsletter-copy">
            <p class="eyebrow">The F2B letter</p>
            <h2 class="section-heading">Good things, in your inbox.</h2>
            <p class="body-text">A monthly note about new drops, design stories, and pieces worth wearing.</p>
            <p class="newsletter-note">No noise. Just the good stuff.</p>
        </div>
        <form class="newsletter-form" action="index.php" method="post">
            <label class="sr-only" for="newsletter-email">Email address</label>
            <input id="newsletter-email" name="email" type="email" placeholder="Your email address" autocomplete="email" required>
            <button class="button button--dark" type="submit">Subscribe <span aria-hidden="true">→</span></button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
$pageTitle = 'Home';
$pageDescription = 'F2B SHOP — modern fashion essentials designed for your everyday style.';

include __DIR__ . '/includes/db.php';

$homepageToneByColour = [
    'Black' => 'ink',
    'White' => 'cream',
    'Navy' => 'blue',
    'Green' => 'olive',
    'Olive' => 'olive',
    'Blue' => 'blue',
    'Cream' => 'cream',
    'Coral' => 'coral',
    'Mist' => 'mist',
    'Sand' => 'sand',
    'Slate' => 'slate',
    'Ink' => 'ink',
];

$mapHomepageProduct = static function (array $row) use ($homepageToneByColour): array {
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

    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'price' => '$' . number_format($price, 2),
        'oldPrice' => $originalPrice !== null ? '$' . number_format($originalPrice, 2) : null,
        'meta' => $row['category_name'] . ' / ' . $row['colour'],
        'tone' => $homepageToneByColour[$row['colour']] ?? 'mist',
        'image' => $row['image'],
        'rating' => number_format((float) $row['rating'], 1),
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
            <div class="hero-stats" aria-label="F2B SHOP highlights">
                <div><strong>200+</strong><span>curated styles</span></div>
                <div><strong>30k+</strong><span>happy customers</span></div>
                <div><strong>4.8/5</strong><span>community rating</span></div>
            </div>
        </div>
        <div class="hero-art">
            <img class="hero-art__image" src="images/branding/f2b-hero.svg" alt="A F2B SHOP fashion look styled in an editorial setting">
            <div class="hero-art__overlay" aria-hidden="true">
                <span class="hero-art__label">F2B SHOP / 01</span>
                <span class="hero-art__caption">Everyday, reimagined.</span>
            </div>
        </div>
    </div>
</section>

<section class="brand-strip" aria-label="F2B SHOP values">
    <div class="container brand-strip__items">
        <span>Original by design</span>
        <span class="brand-strip__dot" aria-hidden="true"></span>
        <span>Made for every day</span>
        <span class="brand-strip__dot" aria-hidden="true"></span>
        <span>Made to be yours</span>
    </div>
</section>

<section class="section" id="new-arrivals">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">Just in</p>
                <h2 class="section-heading">NEW ARRIVALS</h2>
            </div>
            <a class="button button--outline button--small" href="shop.php">View all <span aria-hidden="true">→</span></a>
        </div>
        <div class="product-grid product-grid--four">
            <?php foreach ($newArrivals as $product): ?>
                <article class="product-card">
                    <a class="product-card__image product-card__image--<?= htmlspecialchars($product['tone'], ENT_QUOTES, 'UTF-8') ?>" href="product.php?id=<?= $product['id'] ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> product image">
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h3><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p><?= htmlspecialchars($product['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="price-group">
                            <strong><?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($product['oldPrice'])): ?><del><?= htmlspecialchars($product['oldPrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?>
                        </div>
                    </div>
                    <div class="rating" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5">★★★★★ <span><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?></span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--muted" id="top-selling">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">Community favourites</p>
                <h2 class="section-heading">TOP SELLING</h2>
            </div>
            <a class="button button--outline button--small" href="shop.php">View all <span aria-hidden="true">→</span></a>
        </div>
        <div class="product-grid product-grid--four">
            <?php foreach ($topSelling as $product): ?>
                <article class="product-card">
                    <a class="product-card__image product-card__image--<?= htmlspecialchars($product['tone'], ENT_QUOTES, 'UTF-8') ?>" href="product.php?id=<?= $product['id'] ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> product image">
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h3><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p><?= htmlspecialchars($product['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="price-group">
                            <strong><?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($product['oldPrice'])): ?><del><?= htmlspecialchars($product['oldPrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?>
                        </div>
                    </div>
                    <div class="rating" aria-label="Rated <?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?> out of 5">★★★★★ <span><?= htmlspecialchars($product['rating'], ENT_QUOTES, 'UTF-8') ?></span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="styles">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">Find your direction</p>
                <h2 class="section-heading">BROWSE BY STYLE</h2>
            </div>
        </div>
        <div class="style-grid">
            <a class="style-card style-card--casual" href="shop.php">
                <img class="style-card__image" src="images/categories/casual.svg" alt="Casual fashion style collection">
                <span class="style-card__number">01</span>
                <span class="style-card__name">Casual</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--formal" href="shop.php">
                <img class="style-card__image" src="images/categories/formal.svg" alt="Formal fashion style collection">
                <span class="style-card__number">02</span>
                <span class="style-card__name">Formal</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--party" href="shop.php">
                <img class="style-card__image" src="images/categories/party.svg" alt="Party fashion style collection">
                <span class="style-card__number">03</span>
                <span class="style-card__name">Party</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--gym" href="shop.php">
                <img class="style-card__image" src="images/categories/gym.svg" alt="Gym fashion style collection">
                <span class="style-card__number">04</span>
                <span class="style-card__name">Gym</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
        </div>
    </div>
</section>

<section class="feature-panel" id="about">
    <div class="container feature-panel__layout">
        <div class="feature-panel__art" aria-label="F2B SHOP custom design preview graphic" role="img">
            <span class="feature-panel__stamp">MAKE<br>IT YOURS</span>
            <span class="feature-panel__line"></span>
            <span class="feature-panel__circle"></span>
        </div>
        <div class="feature-panel__copy">
            <p class="eyebrow">F2B CUSTOM / Design your own</p>
            <h2 class="section-heading">Create something that feels like you.</h2>
            <p>Shape your design, review the mock-up, and approve the final look before production. Your next favourite piece should start with your point of view.</p>
            <div class="feature-panel__actions">
                <a class="button button--light" href="mockup.php">Create Your Style <span aria-hidden="true">→</span></a>
                <a class="feature-panel__secondary" href="mockup.php">How Custom Orders Work <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </div>
</section>

<section class="section section--reviews" id="reviews">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">From the community</p>
                <h2 class="section-heading">WHAT PEOPLE ARE SAYING</h2>
            </div>
        </div>
        <div class="review-grid">
            <article class="review-card">
                <div class="review-card__header">
                    <div class="review-card__identity"><span class="review-card__initial" aria-hidden="true">A</span><div><h3>Aisha Tan</h3><p>Singapore</p></div></div>
                    <span class="review-card__mark" aria-hidden="true">“</span>
                </div>
                <div class="review-card__rating" aria-label="Rated 5 out of 5"><span aria-hidden="true">★★★★★</span><span class="sr-only">5 out of 5 stars</span></div>
                <blockquote cite="Aisha Tan">The kind of pieces that make getting dressed feel effortless.</blockquote>
            </article>
            <article class="review-card">
                <div class="review-card__header">
                    <div class="review-card__identity"><span class="review-card__initial" aria-hidden="true">M</span><div><h3>Marcus Lim</h3><p>Kuala Lumpur</p></div></div>
                    <span class="review-card__mark" aria-hidden="true">“</span>
                </div>
                <div class="review-card__rating" aria-label="Rated 5 out of 5"><span aria-hidden="true">★★★★★</span><span class="sr-only">5 out of 5 stars</span></div>
                <blockquote cite="Marcus Lim">Thoughtful shapes, easy colours, and quality I can feel.</blockquote>
            </article>
            <article class="review-card">
                <div class="review-card__header">
                    <div class="review-card__identity"><span class="review-card__initial" aria-hidden="true">J</span><div><h3>Jamie Wong</h3><p>Melbourne</p></div></div>
                    <span class="review-card__mark" aria-hidden="true">“</span>
                </div>
                <div class="review-card__rating" aria-label="Rated 4 out of 5"><span aria-hidden="true">★★★★☆</span><span class="sr-only">4 out of 5 stars</span></div>
                <blockquote cite="Jamie Wong">F2B SHOP gives my wardrobe a point of view without trying too hard.</blockquote>
            </article>
            <article class="review-card">
                <div class="review-card__header">
                    <div class="review-card__identity"><span class="review-card__initial" aria-hidden="true">N</span><div><h3>Nur Farah</h3><p>Johor Bahru</p></div></div>
                    <span class="review-card__mark" aria-hidden="true">“</span>
                </div>
                <div class="review-card__rating" aria-label="Rated 5 out of 5"><span aria-hidden="true">★★★★★</span><span class="sr-only">5 out of 5 stars</span></div>
                <blockquote cite="Nur Farah">The fit feels considered, and every piece works harder than I expected.</blockquote>
            </article>
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

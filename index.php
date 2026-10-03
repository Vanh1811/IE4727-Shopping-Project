<?php
$pageTitle = 'Home';
$pageDescription = 'F2B SHOP — modern fashion essentials designed for your everyday style.';
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
            <?php
            $newArrivals = [
                ['name' => 'Studio Overshirt', 'price' => '$68', 'meta' => 'Layering essential', 'tone' => 'sand', 'image' => 'images/products/studio-overshirt.svg', 'rating' => '4.8', 'badge' => 'New'],
                ['name' => 'Everyday Pleat Trouser', 'price' => '$74', 'oldPrice' => '$88', 'meta' => 'Relaxed tailoring', 'tone' => 'mist', 'image' => 'images/products/everyday-pleat-trouser.svg', 'rating' => '4.7', 'badge' => '-16%'],
                ['name' => 'Contour Rib Tee', 'price' => '$36', 'meta' => 'Soft cotton jersey', 'tone' => 'coral', 'image' => 'images/products/contour-rib-tee.svg', 'rating' => '4.9', 'badge' => 'New'],
                ['name' => 'Transit Crossbody', 'price' => '$52', 'oldPrice' => '$64', 'meta' => 'Daily carry', 'tone' => 'slate', 'image' => 'images/products/transit-crossbody.svg', 'rating' => '4.8', 'badge' => '-19%'],
            ];
            foreach ($newArrivals as $product):
            ?>
                <article class="product-card">
                    <a class="product-card__image product-card__image--<?= htmlspecialchars($product['tone'], ENT_QUOTES, 'UTF-8') ?>" href="product.php" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h3><a href="product.php"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
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
            <?php
            $topSelling = [
                ['name' => 'Form Tee', 'price' => '$32', 'meta' => 'Heavyweight cotton', 'tone' => 'ink', 'image' => 'images/products/form-tee.svg', 'rating' => '4.9', 'badge' => 'Best seller'],
                ['name' => 'Open Line Shirt', 'price' => '$58', 'meta' => 'Easy fit shirt', 'tone' => 'cream', 'image' => 'images/products/open-line-shirt.svg', 'rating' => '4.8'],
                ['name' => 'Arc Denim', 'price' => '$82', 'oldPrice' => '$96', 'meta' => 'Straight-leg denim', 'tone' => 'blue', 'image' => 'images/products/arc-denim.svg', 'rating' => '4.9', 'badge' => '-15%'],
                ['name' => 'Soft Frame Tote', 'price' => '$46', 'meta' => 'Everyday carry', 'tone' => 'olive', 'image' => 'images/products/soft-frame-tote.svg', 'rating' => '4.7'],
            ];
            foreach ($topSelling as $product):
            ?>
                <article class="product-card">
                    <a class="product-card__image product-card__image--<?= htmlspecialchars($product['tone'], ENT_QUOTES, 'UTF-8') ?>" href="product.php" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <img class="product-card__image-art" src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h3><a href="product.php"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
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
                <h2 class="section-heading">Browse by style</h2>
            </div>
        </div>
        <div class="style-grid">
            <a class="style-card style-card--minimal" href="shop.php">
                <span class="style-card__number">01</span>
                <span class="style-card__name">Minimal</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--utility" href="shop.php">
                <span class="style-card__number">02</span>
                <span class="style-card__name">Utility</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
            <a class="style-card style-card--soft" href="shop.php">
                <span class="style-card__number">03</span>
                <span class="style-card__name">Soft form</span>
                <span class="style-card__link">Explore <span aria-hidden="true">↗</span></span>
            </a>
        </div>
    </div>
</section>

<section class="feature-panel" id="about">
    <div class="container feature-panel__layout">
        <div class="feature-panel__art" aria-label="Abstract customisation placeholder" role="img">
            <span class="feature-panel__stamp">MAKE<br>IT YOURS</span>
            <span class="feature-panel__line"></span>
            <span class="feature-panel__circle"></span>
        </div>
        <div class="feature-panel__copy">
            <p class="eyebrow">Your idea, in form</p>
            <h2 class="section-heading">A little more you.</h2>
            <p>Our upcoming mock-up experience will help you shape a design, review the details, and make it yours before anything is final.</p>
            <a class="button button--outline" href="mockup.php">See mock-up preview <span aria-hidden="true">→</span></a>
        </div>
    </div>
</section>

<section class="section" id="reviews">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">From the community</p>
                <h2 class="section-heading">What people are saying</h2>
            </div>
        </div>
        <div class="review-grid">
            <blockquote class="review-card"><div class="rating" aria-label="Rated 5 out of 5">★★★★★</div><p>“The kind of pieces that make getting dressed feel effortless.”</p><cite>— Aisha, Singapore</cite></blockquote>
            <blockquote class="review-card"><div class="rating" aria-label="Rated 5 out of 5">★★★★★</div><p>“Thoughtful shapes, easy colours, and quality I can feel.”</p><cite>— Marcus, Kuala Lumpur</cite></blockquote>
            <blockquote class="review-card"><div class="rating" aria-label="Rated 4 out of 5">★★★★☆</div><p>“F2B SHOP gives my wardrobe a point of view without trying too hard.”</p><cite>— Jamie, Melbourne</cite></blockquote>
        </div>
    </div>
</section>

<section class="newsletter-section" id="contact">
    <div class="container newsletter-box">
        <div>
            <p class="eyebrow">Stay in the loop</p>
            <h2 class="section-heading">The next good thing, in your inbox.</h2>
            <p class="body-text">A quiet note about new drops, design stories, and things worth wearing.</p>
        </div>
        <form class="newsletter-form" action="index.php" method="post">
            <label class="sr-only" for="newsletter-email">Email address</label>
            <input id="newsletter-email" name="email" type="email" placeholder="Your email address" required>
            <button class="button button--dark" type="submit">Subscribe <span aria-hidden="true">→</span></button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

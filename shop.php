<?php
$pageTitle = 'Shop';
$pageDescription = 'Browse the F2B SHOP collection.';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero__content">
        <p class="eyebrow">The collection</p>
        <h1 class="page-heading">Shop all</h1>
        <p class="page-intro">A considered edit of everyday pieces and expressive layers.</p>
    </div>
</section>

<section class="section section--shop">
    <div class="container">
        <div class="shop-toolbar">
            <p class="helper-text">Showing placeholder products</p>
            <div class="toolbar-actions">
                <button class="filter-button" type="button" disabled>Filters <span aria-hidden="true">+</span></button>
                <label class="sr-only" for="sort-products">Sort products</label>
                <select id="sort-products" disabled>
                    <option>Sort: Featured</option>
                </select>
            </div>
        </div>
        <div class="product-grid product-grid--four">
            <?php
            $shopProducts = [
                ['name' => 'Studio Overshirt', 'price' => '$68', 'meta' => 'Layering essential', 'tone' => 'sand', 'badge' => 'New'],
                ['name' => 'Everyday Pleat Trouser', 'price' => '$74', 'meta' => 'Relaxed tailoring', 'tone' => 'mist'],
                ['name' => 'Contour Rib Tee', 'price' => '$36', 'meta' => 'Soft cotton jersey', 'tone' => 'coral'],
                ['name' => 'Transit Crossbody', 'price' => '$52', 'meta' => 'Daily carry', 'tone' => 'slate'],
                ['name' => 'Form Tee', 'price' => '$32', 'meta' => 'Heavyweight cotton', 'tone' => 'ink'],
                ['name' => 'Open Line Shirt', 'price' => '$58', 'meta' => 'Easy fit shirt', 'tone' => 'cream'],
                ['name' => 'Arc Denim', 'price' => '$82', 'oldPrice' => '$96', 'meta' => 'Straight-leg denim', 'tone' => 'blue', 'badge' => '-15%'],
                ['name' => 'Soft Frame Tote', 'price' => '$46', 'meta' => 'Everyday carry', 'tone' => 'olive'],
            ];
            foreach ($shopProducts as $product):
            ?>
                <article class="product-card">
                    <a class="product-card__image product-card__image--<?= htmlspecialchars($product['tone'], ENT_QUOTES, 'UTF-8') ?>" href="product.php" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <span class="image-placeholder" aria-hidden="true">F2B SHOP / 01</span>
                    </a>
                    <div class="product-card__details">
                        <div>
                            <h2><a href="product.php"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                            <p><?= htmlspecialchars($product['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="price-group">
                            <strong><?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($product['oldPrice'])): ?><del><?= htmlspecialchars($product['oldPrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?>
                        </div>
                    </div>
                    <div class="rating" aria-label="Rated 4.8 out of 5">★★★★★ <span>4.8</span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/cart.php';

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$cartItemCount = f2bCartTotalQuantity();

function navLinkClass(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'nav-link is-active' : 'nav-link';
}
?>
<header class="site-header">
    <div class="container nav-shell">
        <a class="brand" href="index.php" aria-label="F2B SHOP home">
            <span class="brand-mark" aria-hidden="true">F2B</span>
            <span>F2B SHOP</span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu">
            <span class="sr-only">Toggle navigation</span>
            <span class="menu-toggle-line" aria-hidden="true"></span>
            <span class="menu-toggle-line" aria-hidden="true"></span>
            <span class="menu-toggle-line" aria-hidden="true"></span>
        </button>

        <div class="site-menu" id="site-menu">
            <nav class="primary-nav" aria-label="Primary navigation">
                <a class="<?= navLinkClass('shop.php', $currentPage) ?>" href="shop.php">Shop</a>
                <a class="nav-link" href="index.php#new-arrivals">New Arrivals</a>
                <a class="nav-link" href="index.php#styles">Styles</a>
            </nav>

            <div class="nav-actions">
                <form class="search-form" action="shop.php" method="get" role="search">
                    <label class="sr-only" for="site-search">Search products</label>
                    <input id="site-search" name="search" type="search" placeholder="Search" autocomplete="off">
                    <button class="search-submit" type="submit" aria-label="Submit search">
                        <span aria-hidden="true">⌕</span>
                    </button>
                </form>
                <a class="nav-icon-link" href="cart.php" aria-label="Shopping cart, <?= (int) $cartItemCount ?> <?= $cartItemCount === 1 ? 'item' : 'items' ?>">
                    <span aria-hidden="true">Bag</span>
                    <span class="cart-count"><?= (int) $cartItemCount ?></span>
                </a>
                <a class="nav-account" href="orders.php">Account</a>
            </div>
        </div>
    </div>
</header>

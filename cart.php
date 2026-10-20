<?php
$pageTitle = 'Your Cart';
$pageDescription = 'Review your F2B SHOP cart.';
$pageStylesheet = 'css/cart.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order_access.php';
require_once __DIR__ . '/includes/cart.php';

$cartItems = f2bCartLoadItems($db);
$cartTotals = f2bCartCalculateTotals($cartItems);
$cartFlash = f2bCartConsumeFlash();
$cartCsrfToken = f2bCsrfToken('cart');

include __DIR__ . '/includes/header.php';
?>

<div class="cart-page">
    <div class="container">
        <header class="cart-page__header">
            <p class="eyebrow">Your selection</p>
            <h1 class="cart-page__title">YOUR CART</h1>
            <p>Review your pieces before moving on to checkout.</p>
        </header>

        <?php if ($cartFlash): ?>
            <p class="cart-feedback cart-feedback--<?= htmlspecialchars($cartFlash['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
                <?= htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <div class="cart-layout">
            <section class="cart-items-panel" aria-labelledby="cart-items-title">
                <?php if ($cartItems): ?>
                    <div class="cart-items__header" id="cart-items-header">
                        <h2 id="cart-items-title">Selected items</h2>
                        <span><?= htmlspecialchars((string) $cartTotals['item_count'], ENT_QUOTES, 'UTF-8') ?> <?= $cartTotals['item_count'] === 1 ? 'item' : 'items' ?></span>
                    </div>

                    <div class="cart-items-list">
                        <?php foreach ($cartItems as $index => $item): ?>
                            <?php $quantity = (int) $item['quantity']; ?>
                            <article class="cart-item">
                                <div class="cart-item__image">
                                    <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> product image">
                                </div>
                                <div class="cart-item__content">
                                    <div class="cart-item__topline">
                                        <div>
                                            <p class="cart-item__eyebrow">F2B SHOP / <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></p>
                                            <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        </div>
                                        <form class="cart-item__remove-form" action="cart-action.php" method="post">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $cartCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="key" value="<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="cart-item__remove" type="submit" aria-label="Remove <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> from cart">Remove</button>
                                        </form>
                                    </div>
                                    <dl class="cart-item__details">
                                        <div><dt>Size</dt><dd><?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        <div><dt>Colour</dt><dd><?= htmlspecialchars($item['colour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        <div><dt>Unit price</dt><dd>$<?= number_format($item['price'], 2) ?></dd></div>
                                    </dl>
                                    <div class="cart-item__footer">
                                        <strong>$<?= number_format($item['price'] * $quantity, 2) ?></strong>
                                        <form class="cart-quantity" action="cart-action.php" method="post" aria-label="Quantity for <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $cartCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="key" value="<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" name="quantity" value="<?= max(1, $quantity - 1) ?>" aria-label="Decrease <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> quantity">−</button>
                                            <output aria-live="polite"><?= htmlspecialchars((string) $quantity, ENT_QUOTES, 'UTF-8') ?></output>
                                            <button type="submit" name="quantity" value="<?= min(F2B_CART_MAX_QUANTITY, $quantity + 1) ?>" aria-label="Increase <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> quantity">+</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="cart-empty" id="cart-empty">
                        <div class="cart-empty__mark" aria-hidden="true">+</div>
                        <h2 id="cart-items-title">YOUR CART IS EMPTY</h2>
                        <p>You haven't added any products yet.</p>
                        <a class="button button--dark" href="shop.php">Continue shopping <span aria-hidden="true">→</span></a>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($cartItems): ?>
                <aside class="cart-summary" id="cart-summary" aria-labelledby="cart-summary-title">
                    <p class="eyebrow">Order overview</p>
                    <h2 id="cart-summary-title">ORDER SUMMARY</h2>
                    <div class="cart-summary__row"><span>Subtotal</span><strong id="cart-subtotal">$<?= number_format($cartTotals['subtotal'], 2) ?></strong></div>
                    <div class="cart-summary__row"><span>Discount (-<?= number_format($cartTotals['discount_rate'] * 100, 0) ?>%)</span><strong id="cart-discount">-$<?= number_format($cartTotals['discount'], 2) ?></strong></div>
                    <div class="cart-summary__row"><span>Delivery Fee</span><strong id="cart-delivery">$<?= number_format($cartTotals['delivery'], 2) ?></strong></div>
                    <div class="cart-summary__total"><span>Total</span><strong id="cart-total">$<?= number_format($cartTotals['total'], 2) ?></strong></div>
                    <a class="button button--dark cart-summary__checkout" href="checkout.php">Go to checkout <span aria-hidden="true">→</span></a>
                    <form class="cart-summary__clear" action="cart-action.php" method="post">
                        <input type="hidden" name="action" value="clear">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $cartCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Clear cart</button>
                    </form>
                    <a class="cart-summary__continue" href="shop.php">Continue shopping <span aria-hidden="true">↗</span></a>
                    <p class="cart-summary__note">Your discount is applied automatically. Delivery is a flat $<?= number_format($cartTotals['delivery'], 2) ?> for this demo.</p>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

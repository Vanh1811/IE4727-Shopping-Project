<?php
$pageTitle = 'Your Cart';
$pageDescription = 'Review your F2B SHOP cart.';
$pageStylesheet = 'css/cart.css';

// Static demonstration data. Cart persistence will be added with PHP sessions later.
$cartItems = [
    [
        'name' => 'Studio Overshirt',
        'size' => 'M',
        'colour' => 'Black',
        'price' => 68.00,
        'quantity' => 2,
        'image' => 'images/products/studio-overshirt.svg',
        'alt' => 'Studio Overshirt product image',
    ],
    [
        'name' => 'Form Tee',
        'size' => 'L',
        'colour' => 'White',
        'price' => 32.00,
        'quantity' => 1,
        'image' => 'images/products/form-tee.svg',
        'alt' => 'Form Tee product image',
    ],
    [
        'name' => 'Arc Denim',
        'size' => 'M',
        'colour' => 'Navy',
        'price' => 82.00,
        'quantity' => 1,
        'image' => 'images/products/arc-denim.svg',
        'alt' => 'Arc Denim product image',
    ],
];

$cartSubtotal = 0;
$cartItemCount = 0;
$cartDiscountRate = 0.10;
$cartDeliveryFee = 5.00;

foreach ($cartItems as $item) {
    $cartSubtotal += $item['price'] * $item['quantity'];
    $cartItemCount += $item['quantity'];
}

$cartDiscount = $cartSubtotal * $cartDiscountRate;
$cartTotal = $cartSubtotal - $cartDiscount + $cartDeliveryFee;

include __DIR__ . '/includes/header.php';
?>

<div class="cart-page">
    <div class="container">
        <header class="cart-page__header">
            <p class="eyebrow">Your selection</p>
            <h1 class="cart-page__title">YOUR CART</h1>
            <p>Review your pieces before moving on to checkout.</p>
        </header>

        <div class="cart-layout">
            <section class="cart-items-panel" aria-labelledby="cart-items-title">
                <div class="cart-items__header" id="cart-items-header">
                    <h2 id="cart-items-title">Selected items</h2>
                    <span id="cart-items-count"><?= $cartItemCount ?> items</span>
                </div>

                <div class="cart-items-list">
                    <?php foreach ($cartItems as $index => $item): ?>
                        <article class="cart-item" data-cart-item data-price="<?= htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8') ?>" data-quantity="<?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="cart-item__image">
                                <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['alt'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="cart-item__content">
                                <div class="cart-item__topline">
                                    <div>
                                        <p class="cart-item__eyebrow">F2B SHOP / <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></p>
                                        <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    </div>
                                    <button class="cart-item__remove" type="button" data-cart-remove aria-label="Remove <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> from cart">Remove</button>
                                </div>
                                <dl class="cart-item__details">
                                    <div><dt>Size</dt><dd><?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <div><dt>Colour</dt><dd><?= htmlspecialchars($item['colour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <div><dt>Unit price</dt><dd>$<?= number_format($item['price'], 2) ?></dd></div>
                                </dl>
                                <div class="cart-item__footer">
                                    <strong data-cart-line-total>$<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                    <div class="cart-quantity" aria-label="Quantity for <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="button" data-cart-quantity="decrease" aria-label="Decrease <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> quantity">−</button>
                                        <output data-cart-quantity-value aria-live="polite"><?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?></output>
                                        <button type="button" data-cart-quantity="increase" aria-label="Increase <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> quantity">+</button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="cart-empty" id="cart-empty" hidden>
                    <div class="cart-empty__mark" aria-hidden="true">+</div>
                    <h2>YOUR CART IS EMPTY</h2>
                    <p>You haven't added any products yet.</p>
                    <a class="button button--dark" href="shop.php">Continue shopping <span aria-hidden="true">→</span></a>
                </div>
            </section>

            <aside class="cart-summary" id="cart-summary" data-discount-rate="<?= htmlspecialchars((string) $cartDiscountRate, ENT_QUOTES, 'UTF-8') ?>" data-delivery-fee="<?= htmlspecialchars((string) $cartDeliveryFee, ENT_QUOTES, 'UTF-8') ?>" aria-labelledby="cart-summary-title">
                <p class="eyebrow">Order overview</p>
                <h2 id="cart-summary-title">ORDER SUMMARY</h2>
                <div class="cart-summary__row"><span>Subtotal</span><strong id="cart-subtotal">$<?= number_format($cartSubtotal, 2) ?></strong></div>
                <div class="cart-summary__row"><span>Discount (-<?= number_format($cartDiscountRate * 100, 0) ?>%)</span><strong id="cart-discount">-$<?= number_format($cartDiscount, 2) ?></strong></div>
                <div class="cart-summary__row"><span>Delivery Fee</span><strong id="cart-delivery">$<?= number_format($cartDeliveryFee, 2) ?></strong></div>
                <div class="cart-summary__total"><span>Total</span><strong id="cart-total">$<?= number_format($cartTotal, 2) ?></strong></div>
                <a class="button button--dark cart-summary__checkout" href="checkout.php">Go to checkout <span aria-hidden="true">→</span></a>
                <a class="cart-summary__continue" href="shop.php">Continue shopping <span aria-hidden="true">↗</span></a>
                <p class="cart-summary__note">Your discount is applied automatically. Delivery is a flat $<?= number_format($cartDeliveryFee, 2) ?> for this demo.</p>
            </aside>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

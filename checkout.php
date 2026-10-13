<?php
$pageTitle = 'Checkout';
$pageDescription = 'Complete your F2B SHOP checkout details.';
$pageStylesheet = 'css/checkout.css';

// Static demonstration data for the frontend-only checkout page.
$checkoutItems = [
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

$checkoutSubtotal = 0;
$checkoutDiscountRate = 0.10;
$checkoutDeliveryFee = 5.00;

foreach ($checkoutItems as $item) {
    $checkoutSubtotal += $item['price'] * $item['quantity'];
}

$checkoutDiscount = $checkoutSubtotal * $checkoutDiscountRate;
$checkoutTotal = $checkoutSubtotal - $checkoutDiscount + $checkoutDeliveryFee;

include __DIR__ . '/includes/header.php';
?>

<div class="checkout-page">
    <div class="container">
        <nav class="checkout-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">&gt;</span>
            <a href="cart.php">Cart</a>
            <span aria-hidden="true">&gt;</span>
            <span aria-current="page">Checkout</span>
        </nav>

        <header class="checkout-page__header">
            <p class="eyebrow">Almost yours</p>
            <h1 class="checkout-page__title">CHECKOUT</h1>
            <p>Enter your delivery details and review your order before placing it.</p>
        </header>

        <div class="checkout-layout">
            <section class="checkout-form-card" aria-labelledby="customer-information-title">
                <div class="checkout-section-heading">
                    <p class="eyebrow">Step 01</p>
                    <h2 id="customer-information-title">Customer information</h2>
                    <p>We will use these details for delivery updates.</p>
                </div>

                <form class="checkout-form" id="checkout-form" action="checkout.php" method="post">
                    <div class="checkout-form__fields">
                        <div class="form-field">
                            <label for="full-name">Full Name <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="full-name" name="full_name" type="text" autocomplete="name" placeholder="e.g. Alex Tan" required>
                        </div>

                        <div class="form-field">
                            <label for="email-address">Email Address <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="email-address" name="email" type="email" autocomplete="email" placeholder="e.g. alex@example.com" required>
                        </div>

                        <div class="form-field">
                            <label for="phone-number">Phone Number <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="phone-number" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="e.g. +65 9123 4567" pattern="[0-9+() .-]{7,20}" title="Enter a valid phone number." required>
                        </div>

                        <div class="form-field">
                            <label for="postal-code">Postal Code <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="postal-code" name="postal_code" type="text" autocomplete="postal-code" inputmode="numeric" placeholder="e.g. 238801" pattern="[0-9A-Za-z -]{3,10}" title="Enter a valid postal code." required>
                        </div>

                        <div class="form-field form-field--full">
                            <label for="delivery-address">Delivery Address <span class="required-marker" aria-hidden="true">*</span></label>
                            <textarea id="delivery-address" name="delivery_address" autocomplete="street-address" rows="3" placeholder="Block, street name, unit number" required></textarea>
                        </div>

                        <div class="form-field form-field--full">
                            <label for="special-instructions">Special Instructions <span class="optional-note">(optional)</span></label>
                            <textarea id="special-instructions" name="special_instructions" rows="3" placeholder="Add delivery notes, if needed"></textarea>
                        </div>
                    </div>

                    <p class="checkout-form__required-note"><span aria-hidden="true">*</span> Required fields</p>
                    <button class="button button--dark button--wide checkout-submit" type="submit">
                        PLACE ORDER <span aria-hidden="true">&rarr;</span>
                    </button>
                    <p class="checkout-form__feedback" id="checkout-demo-message" role="status" aria-live="polite" hidden></p>
                </form>
            </section>

            <aside class="order-summary" id="payment" aria-labelledby="order-summary-title">
                <p class="eyebrow">Step 02</p>
                <h2 id="order-summary-title">ORDER SUMMARY</h2>

                <div class="order-summary__items">
                    <?php foreach ($checkoutItems as $item): ?>
                        <article class="order-item">
                            <div class="order-item__image">
                                <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['alt'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="order-item__details">
                                <div class="order-item__heading">
                                    <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <strong>$<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                </div>
                                <dl>
                                    <div><dt>Size</dt><dd><?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <div><dt>Colour</dt><dd><?= htmlspecialchars($item['colour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <div><dt>Qty</dt><dd><?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                </dl>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary__totals">
                    <div class="order-summary__row">
                        <span>Subtotal</span>
                        <strong>$<?= number_format($checkoutSubtotal, 2) ?></strong>
                    </div>
                    <div class="order-summary__row">
                        <span>Discount (-<?= number_format($checkoutDiscountRate * 100, 0) ?>%)</span>
                        <strong>-$<?= number_format($checkoutDiscount, 2) ?></strong>
                    </div>
                    <div class="order-summary__row">
                        <span>Delivery Fee</span>
                        <strong>$<?= number_format($checkoutDeliveryFee, 2) ?></strong>
                    </div>
                    <div class="order-summary__total">
                        <span>Total</span>
                        <strong>$<?= number_format($checkoutTotal, 2) ?></strong>
                    </div>
                </div>

                <p class="order-summary__note">This is a frontend demonstration. No order or payment will be processed.</p>
            </aside>
        </div>
    </div>
</div>

<script>
    (() => {
        'use strict';

        const form = document.querySelector('#checkout-form');
        const feedback = document.querySelector('#checkout-demo-message');

        if (!form || !feedback) {
            return;
        }

        const showFeedback = (message) => {
            form.classList.add('was-validated');
            feedback.hidden = false;
            feedback.textContent = message;
        };

        form.addEventListener('invalid', () => {
            showFeedback('Please complete the highlighted fields before continuing.');
        }, true);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            showFeedback('Demo only — no order has been placed or saved.');
        });
    })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

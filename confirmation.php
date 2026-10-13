<?php
$pageTitle = 'Order Confirmation';
$pageDescription = 'Review your F2B SHOP order confirmation.';
$pageStylesheet = 'css/confirmation.css';

// Static demonstration data kept consistent with checkout.php.
$confirmedItems = [
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

$confirmedSubtotal = 0;
$confirmedDiscountRate = 0.10;
$confirmedDeliveryFee = 5.00;

foreach ($confirmedItems as $item) {
    $confirmedSubtotal += $item['price'] * $item['quantity'];
}

$confirmedDiscount = $confirmedSubtotal * $confirmedDiscountRate;
$confirmedTotal = $confirmedSubtotal - $confirmedDiscount + $confirmedDeliveryFee;

include __DIR__ . '/includes/header.php';
?>

<div class="confirmation-page">
    <div class="container">
        <nav class="confirmation-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">&gt;</span>
            <a href="checkout.php">Checkout</a>
            <span aria-hidden="true">&gt;</span>
            <span aria-current="page">Confirmation</span>
        </nav>

        <div class="confirmation-layout">
            <section class="confirmation-intro" aria-labelledby="confirmation-title">
                <div class="confirmation-success-mark" aria-hidden="true">&#10003;</div>
                <p class="eyebrow">F2B SHOP / DEMO ORDER</p>
                <h1 class="confirmation-page__title" id="confirmation-title">ORDER CONFIRMED</h1>
                <p class="confirmation-intro__message">Thank you, Alex Tan. Your order details are ready for review and the next step is to prepare your design mock-up.</p>

                <div class="confirmation-note" role="note">
                    <strong>Frontend demonstration only.</strong>
                    <span>No real order has been created, submitted, or charged.</span>
                </div>

                <dl class="confirmation-meta">
                    <div>
                        <dt>Order number</dt>
                        <dd>#1028</dd>
                    </div>
                    <div>
                        <dt>Customer</dt>
                        <dd>Alex Tan</dd>
                    </div>
                    <div>
                        <dt>Placed on</dt>
                        <dd>13 October 2026</dd>
                    </div>
                    <div>
                        <dt>Current status</dt>
                        <dd><span class="status-badge status-badge--review">DESIGN REVIEW</span></dd>
                    </div>
                </dl>
            </section>

            <aside class="confirmation-summary" aria-labelledby="confirmation-summary-title">
                <p class="eyebrow">Order #1028</p>
                <h2 id="confirmation-summary-title">YOUR ORDER</h2>

                <div class="confirmation-summary__items">
                    <?php foreach ($confirmedItems as $item): ?>
                        <article class="confirmation-item">
                            <div class="confirmation-item__image">
                                <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['alt'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="confirmation-item__details">
                                <div class="confirmation-item__heading">
                                    <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <strong>$<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                </div>
                                <p>Unit price $<?= number_format($item['price'], 2) ?> <span aria-hidden="true">&middot;</span> Size <?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&middot;</span> <?= htmlspecialchars($item['colour'], ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&middot;</span> Qty <?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="confirmation-summary__totals">
                    <div><span>Subtotal</span><strong>$<?= number_format($confirmedSubtotal, 2) ?></strong></div>
                    <div><span>Discount (-<?= number_format($confirmedDiscountRate * 100, 0) ?>%)</span><strong>-$<?= number_format($confirmedDiscount, 2) ?></strong></div>
                    <div><span>Delivery Fee</span><strong>$<?= number_format($confirmedDeliveryFee, 2) ?></strong></div>
                    <div class="confirmation-summary__total"><span>Total</span><strong>$<?= number_format($confirmedTotal, 2) ?></strong></div>
                </div>
            </aside>
        </div>

        <section class="confirmation-next-steps" aria-labelledby="next-steps-title">
            <div class="confirmation-next-steps__heading">
                <p class="eyebrow">The design journey</p>
                <h2 id="next-steps-title">WHAT HAPPENS NEXT?</h2>
                <p>The mock-up review is part of the ordering process. You will be able to review the proposed design before the order moves into processing.</p>
            </div>
            <ol class="confirmation-steps">
                <li><span>01</span><strong>Order received</strong><p>Your order details are recorded for the design team.</p></li>
                <li><span>02</span><strong>Design mock-up prepared</strong><p>A visual mock-up is prepared for your review.</p></li>
                <li><span>03</span><strong>Customer reviews the mock-up</strong><p>Check the proposed design and details.</p></li>
                <li><span>04</span><strong>Approve or request changes</strong><p>Share your decision before production begins.</p></li>
                <li><span>05</span><strong>Order proceeds to processing</strong><p>Once approved, the order moves forward.</p></li>
            </ol>
        </section>

        <div class="confirmation-actions">
            <a class="button button--dark" href="orders.php">VIEW MY ORDERS <span aria-hidden="true">&rarr;</span></a>
            <a class="button button--outline" href="shop.php">CONTINUE SHOPPING <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

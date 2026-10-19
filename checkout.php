<?php
$pageTitle = 'Checkout';
$pageDescription = 'Complete your F2B SHOP checkout details.';
$pageStylesheet = 'css/checkout.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/order.php';

$checkoutItems = f2bCartLoadItems($db);

if (!$checkoutItems) {
    f2bCartSetFlash('Your cart is empty. Add an item before checking out.', 'error');
    header('Location: cart.php', true, 303);
    exit;
}

$checkoutTotals = f2bCartCalculateTotals($checkoutItems);
$checkoutValues = array(
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'postal_code' => '',
    'delivery_address' => '',
    'special_instructions' => '',
);
$checkoutErrors = array();
$checkoutFeedback = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $validation = f2bOrderValidateCheckout($_POST);
    $checkoutValues = $validation['values'];
    $checkoutErrors = $validation['errors'];

    if (!$checkoutErrors) {
        $orderResult = f2bOrderCreate($db, $checkoutValues, $checkoutItems, $checkoutTotals);

        if ($orderResult['success']) {
            f2bCartClear();
            f2bCartEnsureSession();
            $_SESSION['last_order_id'] = $orderResult['order_id'];
            header('Location: confirmation.php?order=' . $orderResult['order_id'], true, 303);
            exit;
        }

        $checkoutFeedback = 'We could not place your order. Your cart is still available; please try again.';
    } else {
        $checkoutFeedback = 'Please correct the highlighted fields before continuing.';
    }
}

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

                <form class="checkout-form<?= $checkoutErrors ? ' was-validated' : '' ?>" id="checkout-form" action="checkout.php" method="post" novalidate>
                    <div class="checkout-form__fields">
                        <div class="form-field">
                            <label for="full-name">Full Name <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="full-name" name="full_name" type="text" autocomplete="name" placeholder="e.g. Alex Tan" value="<?= htmlspecialchars($checkoutValues['full_name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="160" required aria-invalid="<?= isset($checkoutErrors['full_name']) ? 'true' : 'false' ?>">
                            <?php if (isset($checkoutErrors['full_name'])): ?><p class="checkout-form__field-error"><?= htmlspecialchars($checkoutErrors['full_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="email-address">Email Address <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="email-address" name="email" type="email" autocomplete="email" placeholder="e.g. alex@example.com" value="<?= htmlspecialchars($checkoutValues['email'], ENT_QUOTES, 'UTF-8') ?>" maxlength="254" required aria-invalid="<?= isset($checkoutErrors['email']) ? 'true' : 'false' ?>">
                            <?php if (isset($checkoutErrors['email'])): ?><p class="checkout-form__field-error"><?= htmlspecialchars($checkoutErrors['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="phone-number">Phone Number <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="phone-number" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="e.g. +65 9123 4567" value="<?= htmlspecialchars($checkoutValues['phone'], ENT_QUOTES, 'UTF-8') ?>" maxlength="30" required aria-invalid="<?= isset($checkoutErrors['phone']) ? 'true' : 'false' ?>">
                            <?php if (isset($checkoutErrors['phone'])): ?><p class="checkout-form__field-error"><?= htmlspecialchars($checkoutErrors['phone'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="postal-code">Postal Code <span class="required-marker" aria-hidden="true">*</span></label>
                            <input id="postal-code" name="postal_code" type="text" autocomplete="postal-code" inputmode="numeric" placeholder="e.g. 238801" value="<?= htmlspecialchars($checkoutValues['postal_code'], ENT_QUOTES, 'UTF-8') ?>" maxlength="20" required aria-invalid="<?= isset($checkoutErrors['postal_code']) ? 'true' : 'false' ?>">
                            <?php if (isset($checkoutErrors['postal_code'])): ?><p class="checkout-form__field-error"><?= htmlspecialchars($checkoutErrors['postal_code'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        </div>

                        <div class="form-field form-field--full">
                            <label for="delivery-address">Delivery Address <span class="required-marker" aria-hidden="true">*</span></label>
                            <textarea id="delivery-address" name="delivery_address" autocomplete="street-address" rows="3" maxlength="500" placeholder="Block, street name, unit number" required aria-invalid="<?= isset($checkoutErrors['delivery_address']) ? 'true' : 'false' ?>"><?= htmlspecialchars($checkoutValues['delivery_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?php if (isset($checkoutErrors['delivery_address'])): ?><p class="checkout-form__field-error"><?= htmlspecialchars($checkoutErrors['delivery_address'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        </div>

                        <div class="form-field form-field--full">
                            <label for="special-instructions">Special Instructions <span class="optional-note">(optional)</span></label>
                            <textarea id="special-instructions" name="special_instructions" rows="3" maxlength="1000" placeholder="Add delivery notes, if needed" aria-invalid="<?= isset($checkoutErrors['special_instructions']) ? 'true' : 'false' ?>"><?= htmlspecialchars($checkoutValues['special_instructions'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?php if (isset($checkoutErrors['special_instructions'])): ?><p class="checkout-form__field-error"><?= htmlspecialchars($checkoutErrors['special_instructions'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        </div>
                    </div>

                    <p class="checkout-form__required-note"><span aria-hidden="true">*</span> Required fields</p>
                    <button class="button button--dark button--wide checkout-submit" type="submit">
                        PLACE ORDER <span aria-hidden="true">&rarr;</span>
                    </button>
                    <?php if ($checkoutFeedback !== ''): ?>
                        <p class="checkout-form__feedback" id="checkout-feedback" role="status" aria-live="polite"><?= htmlspecialchars($checkoutFeedback, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <a class="checkout-demo-link" href="cart.php">RETURN TO CART <span aria-hidden="true">&larr;</span></a>
                </form>
            </section>

            <aside class="order-summary" id="payment" aria-labelledby="order-summary-title">
                <p class="eyebrow">Step 02</p>
                <h2 id="order-summary-title">ORDER SUMMARY</h2>

                <div class="order-summary__items">
                    <?php foreach ($checkoutItems as $item): ?>
                        <article class="order-item">
                            <div class="order-item__image">
                                <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> product image">
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
                        <strong>$<?= number_format($checkoutTotals['subtotal'], 2) ?></strong>
                    </div>
                    <div class="order-summary__row">
                        <span>Discount (-<?= number_format($checkoutTotals['discount_rate'] * 100, 0) ?>%)</span>
                        <strong>-$<?= number_format($checkoutTotals['discount'], 2) ?></strong>
                    </div>
                    <div class="order-summary__row">
                        <span>Delivery Fee</span>
                        <strong>$<?= number_format($checkoutTotals['delivery'], 2) ?></strong>
                    </div>
                    <div class="order-summary__total">
                        <span>Total</span>
                        <strong>$<?= number_format($checkoutTotals['total'], 2) ?></strong>
                    </div>
                </div>

                <p class="order-summary__note">Totals are calculated from your current cart and verified again when the order is placed.</p>
            </aside>
        </div>
    </div>
</div>

<script>
    (() => {
        'use strict';

        const form = document.querySelector('#checkout-form');

        if (!form) {
            return;
        }

        form.addEventListener('invalid', () => {
            form.classList.add('was-validated');
        }, true);
    })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
$pageTitle = 'Order Confirmation';
$pageDescription = 'Review your F2B SHOP order confirmation.';
$pageStylesheet = 'css/confirmation.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order.php';
require_once __DIR__ . '/includes/order_access.php';

$requestedOrderId = isset($_GET['order']) && is_scalar($_GET['order']) ? $_GET['order'] : null;
$orderId = f2bOrderValidateId($requestedOrderId);
$hasOrderAccess = $orderId !== null && f2bOrderHasSessionAccess($orderId);
$orderData = $hasOrderAccess ? f2bOrderLoad($db, $orderId) : null;
$order = $orderData !== null ? $orderData['order'] : null;
$confirmedItems = $orderData !== null ? $orderData['items'] : array();
$initialAccessCode = $order !== null ? f2bOrderConsumeInitialAccessCode($orderId) : null;
$mailStatus = null;
$mailMessage = '';

if ($order !== null && f2bOrderAccessStartSession()) {
    $mailOrderId = isset($_SESSION['last_order_mail_order_id']) ? (int) $_SESSION['last_order_mail_order_id'] : 0;
    if ($mailOrderId === (int) $order['id']) {
        $mailStatus = isset($_SESSION['last_order_mail_status']) ? (string) $_SESSION['last_order_mail_status'] : null;
        $mailMessage = isset($_SESSION['last_order_mail_message']) ? (string) $_SESSION['last_order_mail_message'] : '';
    }
}

if ($order !== null && !in_array($mailStatus, array('sent', 'already_sent', 'disabled', 'failed'), true)) {
    $mailStatus = $order['email_sent_at'] !== '' ? 'already_sent' : null;
}

$orderDate = $order !== null ? date('j F Y', strtotime($order['created_at'])) : '';
$statusLabel = $order !== null ? strtoupper(str_replace('_', ' ', $order['status'])) : '';

include __DIR__ . '/includes/header.php';
?>

<div class="confirmation-page">
    <div class="container">
        <nav class="confirmation-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">&gt;</span>
            <a href="checkout.php">Checkout</a>
            <span aria-hidden="true">&gt;</span>
            <span aria-current="page"><?= $order === null ? 'Order Not Found' : 'Confirmation' ?></span>
        </nav>

        <?php if ($order === null): ?>
            <section class="confirmation-not-found" aria-labelledby="confirmation-not-found-title">
                <p class="eyebrow">Order lookup</p>
                <h1 id="confirmation-not-found-title">ORDER NOT FOUND OR ACCESS REQUIRED</h1>
                <p>We could not verify access to that order. Use the guest order access form on My Orders with your order number and saved access code.</p>
                <a class="button button--dark" href="shop.php">Continue Shopping <span aria-hidden="true">&rarr;</span></a>
            </section>
        <?php else: ?>
            <div class="confirmation-layout">
                <section class="confirmation-intro" aria-labelledby="confirmation-title">
                    <div class="confirmation-success-mark" aria-hidden="true">&#10003;</div>
                    <p class="eyebrow">F2B SHOP / ORDER <?= (int) $order['id'] ?></p>
                    <h1 class="confirmation-page__title" id="confirmation-title">ORDER PLACED</h1>
                    <p class="confirmation-intro__message">Thank you, <?= htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8') ?>. Your order is saved and ready for the next step: preparing your design mock-up.</p>

                    <?php if ($initialAccessCode !== null): ?>
                        <div class="confirmation-note" role="note">
                            <strong>Save your guest access code</strong>
                            <span>Your one-time access code is <code><?= htmlspecialchars($initialAccessCode, ENT_QUOTES, 'UTF-8') ?></code>. Save it with order #<?= (int) $order['id'] ?> to access this order from another session.</span>
                        </div>
                    <?php endif; ?>

                    <div class="confirmation-note confirmation-note--mail" role="status" aria-live="polite">
                        <strong>Local email acknowledgement status</strong>
                        <?php if ($mailStatus === 'sent'): ?>
                            <span><?= htmlspecialchars($mailMessage !== '' ? $mailMessage : 'Accepted by the configured local mail transport. Check your own local inbox.', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php elseif ($mailStatus === 'already_sent'): ?>
                            <span>The local acknowledgement was previously handled for this order. No new email was sent.</span>
                        <?php elseif ($mailStatus === 'failed'): ?>
                            <span><?= htmlspecialchars($mailMessage !== '' ? $mailMessage : 'The order was saved, but the local email was not accepted or recorded. No automatic retry was made.', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php elseif ($mailStatus === 'disabled'): ?>
                            <span><?= htmlspecialchars($mailMessage !== '' ? $mailMessage : 'Local email is not configured. Your order is saved; no email was attempted.', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span>No local email handoff is recorded for this order. Confirmation refresh does not send email.</span>
                        <?php endif; ?>
                    </div>

                    <dl class="confirmation-meta">
                        <div>
                            <dt>Order number</dt>
                            <dd>#<?= (int) $order['id'] ?></dd>
                        </div>
                        <div>
                            <dt>Customer</dt>
                            <dd><?= htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>Placed on</dt>
                            <dd><?= htmlspecialchars($orderDate, ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>Current status</dt>
                            <dd><span class="status-badge status-badge--review"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></dd>
                        </div>
                    </dl>
                </section>

                <aside class="confirmation-summary" aria-labelledby="confirmation-summary-title">
                    <p class="eyebrow">Order #<?= (int) $order['id'] ?></p>
                    <h2 id="confirmation-summary-title">YOUR ORDER</h2>

                    <div class="confirmation-summary__items">
                        <?php foreach ($confirmedItems as $item): ?>
                            <article class="confirmation-item">
                                <div class="confirmation-item__image">
                                    <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> product image">
                                </div>
                                <div class="confirmation-item__details">
                                    <div class="confirmation-item__heading">
                                        <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <strong>$<?= number_format($item['line_total'], 2) ?></strong>
                                    </div>
                                    <p>Unit price $<?= number_format($item['price'], 2) ?> <span aria-hidden="true">&middot;</span> Size <?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&middot;</span> <?= htmlspecialchars($item['colour'], ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&middot;</span> Qty <?= (int) $item['quantity'] ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="confirmation-summary__totals">
                        <div><span>Subtotal</span><strong>$<?= number_format($order['subtotal'], 2) ?></strong></div>
                        <div><span>Discount</span><strong>-$<?= number_format($order['discount'], 2) ?></strong></div>
                        <div><span>Delivery Fee</span><strong>$<?= number_format($order['delivery_fee'], 2) ?></strong></div>
                        <div class="confirmation-summary__total"><span>Total</span><strong>$<?= number_format($order['total'], 2) ?></strong></div>
                    </div>
                </aside>
            </div>

            <div class="confirmation-actions">
                <a class="button button--dark" href="orders.php">VIEW MY ORDERS <span aria-hidden="true">&rarr;</span></a>
                <a class="button button--outline" href="shop.php">CONTINUE SHOPPING <span aria-hidden="true">&rarr;</span></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

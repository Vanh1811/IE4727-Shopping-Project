<?php
$pageTitle = 'My Orders';
$pageDescription = 'Access an F2B SHOP order with its order number and private access code.';
$pageStylesheet = 'css/orders.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order.php';

function f2bCustomerReadInput($input, $key)
{
    if (!is_array($input) || !array_key_exists($key, $input) || !is_scalar($input[$key])) {
        return '';
    }

    return trim((string) $input[$key]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if (!f2bCsrfValidate($csrfToken, 'guest-order-access')) {
        f2bOrderSetFlash('We could not verify that request. Please try again.', 'error');
    } else {
        $orderId = f2bCustomerReadInput($_POST, 'order_id');
        $accessCode = f2bCustomerReadInput($_POST, 'access_code');

        if (f2bOrderVerifyAccessCode($db, $orderId, $accessCode)) {
            header('Location: confirmation.php?order=' . rawurlencode($orderId), true, 303);
            exit;
        }

        f2bOrderSetFlash('We could not find an order with those access details.', 'error');
    }

    header('Location: orders.php', true, 303);
    exit;
}

$flash = f2bOrderConsumeFlash();
$accessCsrfToken = f2bCsrfToken('guest-order-access');

include __DIR__ . '/includes/header.php';
?>

<div class="orders-page">
    <div class="container">
        <nav class="orders-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">&gt;</span>
            <span aria-current="page">My Orders</span>
        </nav>

        <header class="orders-page__header">
            <p class="eyebrow">Guest order access</p>
            <h1 class="orders-page__title">MY ORDERS</h1>
            <p>Enter your order number and private access code to view that order. Your orders are not listed here.</p>
        </header>

        <?php if ($flash): ?>
            <p class="orders-feedback orders-feedback--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="orders-access-card" aria-labelledby="orders-access-title">
            <div>
                <p class="eyebrow">Returning guest</p>
                <h2 id="orders-access-title">ACCESS AN ORDER</h2>
                <p>Use the order number and private access code shown on your confirmation page. We never reveal whether an unknown order exists.</p>
            </div>
            <form class="orders-access-form" action="orders.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $accessCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="orders-access-form__fields">
                    <div>
                        <label for="order-id">Order number</label>
                        <input id="order-id" name="order_id" inputmode="numeric" pattern="[0-9]+" autocomplete="off" required>
                    </div>
                    <div>
                        <label for="access-code">Access code</label>
                        <input id="access-code" name="access_code" autocomplete="off" required>
                    </div>
                </div>
                <button class="button button--dark button--small" type="submit">VIEW ORDER <span aria-hidden="true">&rarr;</span></button>
            </form>
        </section>

        <div class="orders-page__footer-action">
            <a class="text-link" href="shop.php">CONTINUE SHOPPING <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

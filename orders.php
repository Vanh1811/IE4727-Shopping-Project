<?php
$pageTitle = 'My Orders';
$pageDescription = 'Review your authorized F2B SHOP orders and design progress.';
$pageStylesheet = 'css/orders.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order.php';

function f2bCustomerOrderStatusClass($status)
{
    $classes = array(
        'ORDER_PLACED' => 'placed',
        'DESIGN_REVIEW' => 'review',
        'CHANGES_REQUESTED' => 'changes',
        'CONFIRMED' => 'confirmed',
        'PROCESSING' => 'processing',
        'COMPLETED' => 'completed',
    );

    return isset($classes[$status]) ? $classes[$status] : 'placed';
}

function f2bCustomerOrderStatusLabel($status)
{
    return str_replace('_', ' ', (string) $status);
}

function f2bCustomerReadInput($input, $key)
{
    if (!is_array($input) || !array_key_exists($key, $input) || !is_scalar($input[$key])) {
        return '';
    }

    return trim((string) $input[$key]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accessToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if (!f2bCsrfValidate($accessToken, 'guest-order-access')) {
        f2bOrderSetFlash('We could not verify that request. Please try again.', 'error');
    } else {
        $orderId = f2bCustomerReadInput($_POST, 'order_id');
        $accessCode = f2bCustomerReadInput($_POST, 'access_code');
        if (!f2bOrderVerifyAccessCode($db, $orderId, $accessCode)) {
            f2bOrderSetFlash('We could not find an order with those access details.', 'error');
        } else {
            f2bOrderSetFlash('Order access granted.', 'success');
        }
    }

    header('Location: orders.php', true, 303);
    exit;
}

$authorizedOrderIds = f2bOrderReadSessionOrderIds();
$orderViews = array();
$loadError = '';

if ($authorizedOrderIds && is_object($db) && method_exists($db, 'prepare')) {
    foreach ($authorizedOrderIds as $authorizedOrderId) {
        $record = f2bOrderLoad($db, $authorizedOrderId);
        if (!is_array($record) || !isset($record['order']) || !isset($record['items'])) {
            continue;
        }

        $order = $record['order'];
        $items = $record['items'];
        $mockups = array();
        $mockupStatement = $db->prepare(
            'SELECT m.id, m.status, oi.product_name '
            . 'FROM mockups AS m INNER JOIN order_items AS oi ON oi.id = m.order_item_id '
            . 'WHERE m.order_id = ? ORDER BY m.id ASC'
        );

        if ($mockupStatement !== false) {
            $mockupStatement->bind_param('i', $authorizedOrderId);
            if ($mockupStatement->execute()) {
                $mockupId = null;
                $mockupStatus = null;
                $mockupProductName = null;
                $mockupStatement->bind_result($mockupId, $mockupStatus, $mockupProductName);
                while ($mockupStatement->fetch()) {
                    $mockups[] = array(
                        'id' => (int) $mockupId,
                        'status' => (string) $mockupStatus,
                        'product_name' => (string) $mockupProductName,
                    );
                }
            }
            $mockupStatement->close();
        } else {
            $loadError = 'Some order design details could not be loaded.';
        }

        $productSummary = array();
        $quantityTotal = 0;
        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $quantityTotal += $quantity;
            $productSummary[] = (string) $item['name'] . ' ×' . $quantity;
        }

        $readyMockup = null;
        foreach ($mockups as $mockup) {
            if ($mockup['status'] === 'READY_FOR_REVIEW') {
                $readyMockup = $mockup;
                break;
            }
        }

        $orderViews[] = array(
            'id' => (int) $order['id'],
            'date' => (string) $order['created_at'],
            'summary' => implode(', ', $productSummary),
            'items' => count($items),
            'quantity' => $quantityTotal,
            'total' => (float) $order['total'],
            'status' => (string) $order['status'],
            'ready_mockup' => $readyMockup,
        );
    }
} elseif ($authorizedOrderIds) {
    $loadError = 'Your order history is temporarily unavailable.';
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
            <p>Review orders you have placed in this browser, or enter the access code from your order confirmation to retrieve another order.</p>
        </header>

        <?php if ($flash): ?>
            <p class="orders-feedback orders-feedback--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($loadError !== ''): ?>
            <p class="orders-feedback orders-feedback--error" role="alert"><?= htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8') ?></p>
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

        <?php if ($orderViews): ?>
            <section class="orders-table-card" aria-labelledby="recent-orders-title">
                <div class="orders-table-card__header">
                    <div>
                        <p class="eyebrow">Authorized orders</p>
                        <h2 id="recent-orders-title">Recent orders</h2>
                    </div>
                    <span class="orders-table-card__count"><?= count($orderViews) ?> <?= count($orderViews) === 1 ? 'order' : 'orders' ?></span>
                </div>

                <div class="orders-table-wrap">
                    <table class="orders-table">
                        <caption class="sr-only">Authorized F2B SHOP order history</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Placed</th>
                                <th scope="col">Products</th>
                                <th scope="col">Items</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderViews as $orderView): ?>
                                <tr>
                                    <th scope="row"><span class="order-number">#<?= (int) $orderView['id'] ?></span></th>
                                    <td><?= htmlspecialchars(date('j F Y', strtotime($orderView['date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="order-products"><?= htmlspecialchars($orderView['summary'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= (int) $orderView['quantity'] ?> across <?= (int) $orderView['items'] ?> <?= $orderView['items'] === 1 ? 'item' : 'items' ?></td>
                                    <td><strong>$<?= number_format($orderView['total'], 2) ?></strong></td>
                                    <td><span class="status-badge status-badge--<?= htmlspecialchars(f2bCustomerOrderStatusClass($orderView['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(f2bCustomerOrderStatusLabel($orderView['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <div class="order-actions">
                                            <a class="order-action-link" href="confirmation.php?order=<?= (int) $orderView['id'] ?>">VIEW DETAILS</a>
                                            <?php if ($orderView['ready_mockup'] !== null): ?>
                                                <a class="order-action-link order-action-link--accent" href="mockup.php?mockup=<?= (int) $orderView['ready_mockup']['id'] ?>">REVIEW MOCK-UP</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="orders-status-legend" aria-label="Order status legend">
                    <span class="orders-status-legend__label">Status guide</span>
                    <span class="status-badge status-badge--placed">ORDER PLACED</span>
                    <span class="status-badge status-badge--review">DESIGN REVIEW</span>
                    <span class="status-badge status-badge--changes">CHANGES REQUESTED</span>
                    <span class="status-badge status-badge--confirmed">CONFIRMED</span>
                    <span class="status-badge status-badge--processing">PROCESSING</span>
                    <span class="status-badge status-badge--completed">COMPLETED</span>
                </div>
            </section>
        <?php else: ?>
            <section class="orders-empty-state" aria-labelledby="empty-orders-title">
                <div class="orders-empty-state__mark" aria-hidden="true">+</div>
                <p class="eyebrow">Your order history</p>
                <h2 id="empty-orders-title">No authorized orders yet.</h2>
                <p>Place an order, or use the private access details from a previous confirmation to view it here.</p>
                <a class="button button--dark" href="shop.php">START SHOPPING <span aria-hidden="true">&rarr;</span></a>
            </section>
        <?php endif; ?>

        <div class="orders-page__footer-action">
            <a class="text-link" href="shop.php">CONTINUE SHOPPING <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

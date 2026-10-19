<?php
$pageTitle = 'Staff Orders';
$pageDescription = 'Protected F2B SHOP staff order management.';
$pageStylesheet = 'css/admin.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/admin_auth.php';

f2bAdminRequireAuth();

$perPage = 10;
$page = isset($_GET['page']) && is_scalar($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$selectedOrderId = isset($_GET['order']) && is_scalar($_GET['order']) ? (int) $_GET['order'] : 0;
$offset = ($page - 1) * $perPage;
$orders = array();
$itemsByOrder = array();
$loadError = '';
$totalOrders = 0;

if (is_object($db) && method_exists($db, 'prepare')) {
    $countStatement = $db->prepare('SELECT COUNT(*) FROM orders');
    if ($countStatement !== false && $countStatement->execute()) {
        $countStatement->bind_result($totalOrders);
        $countStatement->fetch();
        $countStatement->close();
    }

    $orderStatement = $db->prepare(
        'SELECT o.id, o.full_name, o.email, o.created_at, o.total, o.status, '
        . '(SELECT COUNT(*) FROM order_items AS count_items WHERE count_items.order_id = o.id) AS item_count, '
        . '(SELECT COALESCE(SUM(quantity), 0) FROM order_items AS count_quantities WHERE count_quantities.order_id = o.id) AS quantity_count '
        . 'FROM orders AS o ORDER BY o.created_at DESC, o.id DESC LIMIT ? OFFSET ?'
    );

    if ($orderStatement !== false) {
        $orderStatement->bind_param('ii', $perPage, $offset);
        if ($orderStatement->execute()) {
            $orderId = null;
            $fullName = null;
            $email = null;
            $createdAt = null;
            $total = null;
            $status = null;
            $itemCount = null;
            $quantityCount = null;
            $orderStatement->bind_result($orderId, $fullName, $email, $createdAt, $total, $status, $itemCount, $quantityCount);

            while ($orderStatement->fetch()) {
                $orders[] = array(
                    'id' => (int) $orderId,
                    'full_name' => (string) $fullName,
                    'email' => (string) $email,
                    'created_at' => (string) $createdAt,
                    'total' => (float) $total,
                    'status' => (string) $status,
                    'item_count' => (int) $itemCount,
                    'quantity_count' => (int) $quantityCount,
                );
            }
        } else {
            $loadError = 'Orders could not be loaded right now.';
        }
        $orderStatement->close();
    } else {
        $loadError = 'Orders could not be loaded right now.';
    }

    foreach ($orders as $order) {
        $itemStatement = $db->prepare(
            'SELECT oi.id, oi.product_name, oi.size, oi.colour, oi.quantity, oi.unit_price, oi.line_total, '
            . 'm.id AS mockup_id, m.status AS mockup_status, m.revision, '
            . '(SELECT latest_review.change_request FROM mockup_reviews AS latest_review '
            . 'WHERE latest_review.mockup_id = m.id ORDER BY latest_review.id DESC LIMIT 1) AS latest_change_request '
            . 'FROM order_items AS oi LEFT JOIN mockups AS m ON m.order_item_id = oi.id '
            . 'WHERE oi.order_id = ? ORDER BY oi.id ASC'
        );

        if ($itemStatement === false) {
            $loadError = 'Some order details could not be loaded.';
            continue;
        }

        $itemOrderId = $order['id'];
        $itemStatement->bind_param('i', $itemOrderId);
        if (!$itemStatement->execute()) {
            $itemStatement->close();
            $loadError = 'Some order details could not be loaded.';
            continue;
        }

        $itemId = null;
        $productName = null;
        $size = null;
        $colour = null;
        $quantity = null;
        $unitPrice = null;
        $lineTotal = null;
        $mockupId = null;
        $mockupStatus = null;
        $revision = null;
        $latestChangeRequest = null;
        $itemStatement->bind_result(
            $itemId,
            $productName,
            $size,
            $colour,
            $quantity,
            $unitPrice,
            $lineTotal,
            $mockupId,
            $mockupStatus,
            $revision,
            $latestChangeRequest
        );

        $itemsByOrder[$order['id']] = array();
        while ($itemStatement->fetch()) {
            $itemsByOrder[$order['id']][] = array(
                'id' => (int) $itemId,
                'product_name' => (string) $productName,
                'size' => (string) $size,
                'colour' => (string) $colour,
                'quantity' => (int) $quantity,
                'unit_price' => (float) $unitPrice,
                'line_total' => (float) $lineTotal,
                'mockup_id' => $mockupId === null ? null : (int) $mockupId,
                'mockup_status' => $mockupStatus === null ? 'PENDING' : (string) $mockupStatus,
                'revision' => $revision === null ? null : (int) $revision,
                'latest_change_request' => $latestChangeRequest === null ? '' : (string) $latestChangeRequest,
            );
        }
        $itemStatement->close();
    }
} else {
    $loadError = 'The database is not available.';
}

$totalPages = max(1, (int) ceil($totalOrders / $perPage));
$flash = f2bOrderConsumeFlash();
$actionCsrfToken = f2bCsrfToken('staff-action');

include __DIR__ . '/includes/header.php';
?>

<div class="admin-page admin-orders-page">
    <div class="container">
        <header class="admin-page__header">
            <div>
                <p class="eyebrow">F2B SHOP / Staff area</p>
                <h1 class="admin-page__title">ORDER MANAGEMENT</h1>
                <p>Review customer orders, mock-up readiness, and saved feedback before taking the next production step.</p>
            </div>
            <form class="admin-logout-form" action="admin-logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $actionCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button class="button button--outline button--small" type="submit">SIGN OUT</button>
            </form>
        </header>

        <?php if ($flash): ?>
            <p class="admin-feedback admin-feedback--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($loadError !== ''): ?>
            <p class="admin-feedback admin-feedback--error" role="alert"><?= htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="admin-table-card" aria-labelledby="admin-orders-title">
            <div class="admin-table-card__heading">
                <div>
                    <p class="eyebrow">Protected dashboard</p>
                    <h2 id="admin-orders-title">Customer orders</h2>
                </div>
                <span><?= htmlspecialchars((string) $totalOrders, ENT_QUOTES, 'UTF-8') ?> total</span>
            </div>

            <?php if ($orders): ?>
                <div class="admin-table-wrap">
                    <table class="admin-orders-table">
                        <caption class="sr-only">Staff customer order management table</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Placed</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Items</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr id="order-<?= (int) $order['id'] ?>">
                                    <th scope="row"><span class="admin-order-number">#<?= (int) $order['id'] ?></span></th>
                                    <td><?= htmlspecialchars(date('j M Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><?= (int) $order['quantity_count'] ?> across <?= (int) $order['item_count'] ?> variants</td>
                                    <td><strong>$<?= number_format($order['total'], 2) ?></strong></td>
                                    <td><span class="admin-status admin-status--<?= htmlspecialchars(strtolower($order['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_replace('_', ' ', $order['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><a class="admin-table-link" href="admin-orders.php?page=<?= (int) $page ?>#order-<?= (int) $order['id'] ?>">VIEW DETAILS</a></td>
                                </tr>
                                <tr class="admin-order-detail-row">
                                    <td colspan="7">
                                        <details class="admin-order-details"<?= $selectedOrderId === $order['id'] ? ' open' : '' ?>>
                                            <summary>Order #<?= (int) $order['id'] ?> items and mock-up records</summary>
                                            <div class="admin-order-items">
                                                <?php foreach ($itemsByOrder[$order['id']] ?? array() as $item): ?>
                                                    <article class="admin-order-item">
                                                        <div>
                                                            <h3><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                                            <p>Size <?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['colour'], ENT_QUOTES, 'UTF-8') ?> · Qty <?= (int) $item['quantity'] ?></p>
                                                        </div>
                                                        <div class="admin-order-item__meta">
                                                            <span>$<?= number_format($item['line_total'], 2) ?></span>
                                                            <span class="admin-status admin-status--<?= htmlspecialchars(strtolower($item['mockup_status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_replace('_', ' ', $item['mockup_status']), ENT_QUOTES, 'UTF-8') ?></span>
                                                            <?php if ($item['mockup_id'] !== null): ?>
                                                                <a class="admin-table-link" href="admin-orders.php?page=<?= (int) $page ?>&order=<?= (int) $order['id'] ?>#order-<?= (int) $order['id'] ?>">MOCK-UP #<?= (int) $item['mockup_id'] ?></a>
                                                            <?php else: ?>
                                                                <span class="admin-muted">No mock-up yet</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($item['latest_change_request'] !== ''): ?>
                                                            <p class="admin-order-item__feedback"><strong>Latest customer feedback:</strong> <?= htmlspecialchars($item['latest_change_request'], ENT_QUOTES, 'UTF-8') ?></p>
                                                        <?php endif; ?>
                                                    </article>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="admin-pagination" aria-label="Order pages">
                        <?php if ($page > 1): ?><a href="admin-orders.php?page=<?= $page - 1 ?>">Previous</a><?php endif; ?>
                        <span>Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
                        <?php if ($page < $totalPages): ?><a href="admin-orders.php?page=<?= $page + 1 ?>">Next</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="admin-empty-state">
                    <h2>No customer orders yet.</h2>
                    <p>Orders created through the checkout flow will appear here.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

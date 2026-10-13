<?php
$pageTitle = 'My Orders';
$pageDescription = 'Review your F2B SHOP orders and design progress.';
$pageStylesheet = 'css/orders.css';

// Static fictional orders for the frontend demonstration.
$orders = [
    [
        'number' => '#1028',
        'date' => '13 October 2026',
        'summary' => 'Studio Overshirt ×2, Form Tee, Arc Denim',
        'items' => 4,
        'total' => 230.00,
        'status' => 'DESIGN REVIEW',
        'statusClass' => 'review',
        'mockup' => true,
    ],
    [
        'number' => '#1027',
        'date' => '08 October 2026',
        'summary' => 'Form Tee ×2, Soft Frame Tote',
        'items' => 3,
        'total' => 116.00,
        'status' => 'CONFIRMED',
        'statusClass' => 'confirmed',
        'mockup' => false,
    ],
    [
        'number' => '#1026',
        'date' => '29 September 2026',
        'summary' => 'Studio Overshirt, Arc Denim',
        'items' => 2,
        'total' => 150.00,
        'status' => 'COMPLETED',
        'statusClass' => 'completed',
        'mockup' => false,
    ],
];

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
            <p class="eyebrow">Your F2B SHOP account</p>
            <h1 class="orders-page__title">MY ORDERS</h1>
            <p>Review your sample order history, track design progress, and revisit the next step in your order journey.</p>
        </header>

        <section class="orders-table-card" aria-labelledby="recent-orders-title">
            <div class="orders-table-card__header">
                <div>
                    <p class="eyebrow">Static frontend preview</p>
                    <h2 id="recent-orders-title">Recent orders</h2>
                </div>
                <span class="orders-table-card__count">3 sample orders</span>
            </div>

            <div class="orders-table-wrap">
                <table class="orders-table">
                    <caption class="sr-only">Sample F2B SHOP order history</caption>
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
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <th scope="row"><span class="order-number"><?= htmlspecialchars($order['number'], ENT_QUOTES, 'UTF-8') ?></span></th>
                                <td><?= htmlspecialchars($order['date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="order-products"><?= htmlspecialchars($order['summary'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string) $order['items'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><strong>$<?= number_format($order['total'], 2) ?></strong></td>
                                <td><span class="status-badge status-badge--<?= htmlspecialchars($order['statusClass'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <div class="order-actions">
                                        <a class="order-action-link" href="#order-details">VIEW DETAILS</a>
                                        <?php if ($order['mockup']): ?>
                                            <a class="order-action-link order-action-link--accent" href="mockup.php">REVIEW MOCK-UP</a>
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
                <span class="status-badge status-badge--confirmed">CONFIRMED</span>
                <span class="status-badge status-badge--processing">PROCESSING</span>
                <span class="status-badge status-badge--completed">COMPLETED</span>
            </div>

            <p class="orders-table-card__note">All orders shown are fictional examples for this frontend demonstration. Order details are not connected to a database.</p>
        </section>

        <section class="order-detail-preview" id="order-details" aria-labelledby="order-detail-title">
            <div class="order-detail-preview__heading">
                <p class="eyebrow">Details preview</p>
                <h2 id="order-detail-title">Order #1028</h2>
                <p>This static preview shows the latest sample order while order-specific retrieval is outside the current scope.</p>
            </div>
            <dl class="order-detail-preview__meta">
                <div><dt>Products</dt><dd>Studio Overshirt ×2, Form Tee, Arc Denim</dd></div>
                <div><dt>Status</dt><dd><span class="status-badge status-badge--review">DESIGN REVIEW</span></dd></div>
                <div><dt>Total</dt><dd>$230.00</dd></div>
            </dl>
            <a class="button button--dark button--small" href="mockup.php">REVIEW MOCK-UP <span aria-hidden="true">&rarr;</span></a>
        </section>

        <section class="orders-empty-state" hidden aria-labelledby="empty-orders-title">
            <div class="orders-empty-state__mark" aria-hidden="true">+</div>
            <p class="eyebrow">Your order history</p>
            <h2 id="empty-orders-title">You haven't placed any orders yet.</h2>
            <a class="button button--dark" href="shop.php">START SHOPPING <span aria-hidden="true">&rarr;</span></a>
        </section>

        <div class="orders-page__footer-action">
            <a class="text-link" href="shop.php">CONTINUE SHOPPING <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

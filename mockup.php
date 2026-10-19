<?php
$pageTitle = 'Design Review';
$pageDescription = 'Review the proposed F2B SHOP design mock-up.';
$pageStylesheet = 'css/mockup.css';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order_access.php';

function f2bCustomerMockupSafeImage($value)
{
    if (!is_scalar($value)) {
        return null;
    }

    $path = trim((string) $value);
    if ($path === '' || strpos($path, '..') !== false || preg_match('#^images/products/[A-Za-z0-9][A-Za-z0-9._/-]*$#', $path) !== 1) {
        return null;
    }

    $productsRoot = realpath(__DIR__ . '/images/products');
    $realPath = realpath(__DIR__ . '/' . $path);
    if ($productsRoot === false || $realPath === false || !is_file($realPath)) {
        return null;
    }

    $productsPrefix = rtrim($productsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return strpos($realPath, $productsPrefix) === 0 ? $path : null;
}

function f2bCustomerMockupStatusClass($status)
{
    $classes = array(
        'PENDING' => 'pending',
        'READY_FOR_REVIEW' => 'ready',
        'CHANGES_REQUESTED' => 'changes',
        'APPROVED' => 'approved',
    );

    return isset($classes[$status]) ? $classes[$status] : 'pending';
}

function f2bCustomerMockupStatusLabel($status)
{
    return str_replace('_', ' ', (string) $status);
}

$mockupId = isset($_GET['mockup']) && is_scalar($_GET['mockup'])
    ? f2bOrderAccessValidateId($_GET['mockup'])
    : null;
$mockup = null;
$lookupFailed = false;

if ($mockupId !== null && is_object($db) && method_exists($db, 'prepare')) {
    $ownerStatement = $db->prepare('SELECT order_id FROM mockups WHERE id = ? LIMIT 1');
    if ($ownerStatement !== false) {
        $ownerStatement->bind_param('i', $mockupId);
        if ($ownerStatement->execute()) {
            $ownerOrderId = null;
            $ownerStatement->bind_result($ownerOrderId);
            $hasMockup = $ownerStatement->fetch();
            if ($hasMockup && f2bOrderHasSessionAccess($ownerOrderId)) {
                $ownerStatement->close();
                $detailStatement = $db->prepare(
                    'SELECT m.id, m.order_id, m.preview_image, m.print_position, m.print_colour, m.custom_text, '
                    . 'm.status, m.revision, m.created_at, m.updated_at, o.status, o.created_at, '
                    . 'oi.product_name, oi.size, oi.colour, p.image, '
                    . '(SELECT latest_review.change_request FROM mockup_reviews AS latest_review '
                    . 'WHERE latest_review.mockup_id = m.id ORDER BY latest_review.id DESC LIMIT 1) AS latest_change_request '
                    . 'FROM mockups AS m '
                    . 'INNER JOIN orders AS o ON o.id = m.order_id '
                    . 'INNER JOIN order_items AS oi ON oi.id = m.order_item_id AND oi.order_id = m.order_id '
                    . 'LEFT JOIN products AS p ON p.id = oi.product_id '
                    . 'WHERE m.id = ? AND m.order_id = ? LIMIT 1'
                );

                if ($detailStatement !== false) {
                    $detailStatement->bind_param('ii', $mockupId, $ownerOrderId);
                    if ($detailStatement->execute()) {
                        $rowMockupId = null;
                        $rowOrderId = null;
                        $previewImage = null;
                        $printPosition = null;
                        $printColour = null;
                        $customText = null;
                        $mockupStatus = null;
                        $revision = null;
                        $mockupCreatedAt = null;
                        $mockupUpdatedAt = null;
                        $orderStatus = null;
                        $orderCreatedAt = null;
                        $productName = null;
                        $size = null;
                        $colour = null;
                        $productImage = null;
                        $latestChangeRequest = null;
                        $detailStatement->bind_result(
                            $rowMockupId,
                            $rowOrderId,
                            $previewImage,
                            $printPosition,
                            $printColour,
                            $customText,
                            $mockupStatus,
                            $revision,
                            $mockupCreatedAt,
                            $mockupUpdatedAt,
                            $orderStatus,
                            $orderCreatedAt,
                            $productName,
                            $size,
                            $colour,
                            $productImage,
                            $latestChangeRequest
                        );

                        if ($detailStatement->fetch()) {
                            $safePreview = f2bCustomerMockupSafeImage($previewImage);
                            if ($safePreview === null) {
                                $safePreview = f2bCustomerMockupSafeImage($productImage);
                            }
                            if ($safePreview !== null) {
                                $mockup = array(
                                    'id' => (int) $rowMockupId,
                                    'order_id' => (int) $rowOrderId,
                                    'preview_image' => $safePreview,
                                    'print_position' => (string) $printPosition,
                                    'print_colour' => (string) $printColour,
                                    'custom_text' => $customText === null ? '' : (string) $customText,
                                    'status' => (string) $mockupStatus,
                                    'revision' => (int) $revision,
                                    'created_at' => (string) $mockupCreatedAt,
                                    'updated_at' => (string) $mockupUpdatedAt,
                                    'order_status' => (string) $orderStatus,
                                    'order_created_at' => (string) $orderCreatedAt,
                                    'product_name' => (string) $productName,
                                    'size' => (string) $size,
                                    'colour' => (string) $colour,
                                    'latest_change_request' => $latestChangeRequest === null ? '' : (string) $latestChangeRequest,
                                );
                            }
                        }
                    }
                    $detailStatement->close();
                }
            } else {
                $ownerStatement->close();
            }
        } else {
            $ownerStatement->close();
        }
    }
} elseif ($mockupId !== null) {
    $lookupFailed = true;
}

$reviewHistory = array();
if ($mockup !== null && is_object($db) && method_exists($db, 'prepare')) {
    $historyStatement = $db->prepare(
        'SELECT decision, change_request, revision, created_at '
        . 'FROM mockup_reviews WHERE mockup_id = ? ORDER BY id DESC'
    );
    if ($historyStatement !== false) {
        $historyStatement->bind_param('i', $mockup['id']);
        if ($historyStatement->execute()) {
            $historyDecision = null;
            $historyChangeRequest = null;
            $historyRevision = null;
            $historyCreatedAt = null;
            $historyStatement->bind_result($historyDecision, $historyChangeRequest, $historyRevision, $historyCreatedAt);
            while ($historyStatement->fetch()) {
                $reviewHistory[] = array(
                    'decision' => (string) $historyDecision,
                    'change_request' => $historyChangeRequest === null ? '' : (string) $historyChangeRequest,
                    'revision' => (int) $historyRevision,
                    'created_at' => (string) $historyCreatedAt,
                );
            }
        }
        $historyStatement->close();
    }
}

$reviewCsrfToken = $mockup !== null && $mockup['status'] === 'READY_FOR_REVIEW'
    ? f2bCsrfToken('mockup-review')
    : null;
$flash = f2bOrderConsumeFlash();

include __DIR__ . '/includes/header.php';
?>

<div class="mockup-page">
    <div class="container">
        <?php if ($mockup === null): ?>
            <section class="mockup-not-found" aria-labelledby="mockup-not-found-title">
                <p class="eyebrow">Private design area</p>
                <h1 id="mockup-not-found-title">DESIGN PREVIEW UNAVAILABLE</h1>
                <p><?= $lookupFailed ? 'This design preview is temporarily unavailable.' : 'This design preview was not found, or it is not available to this guest session.' ?></p>
                <a class="button button--dark" href="orders.php">BACK TO MY ORDERS <span aria-hidden="true">&rarr;</span></a>
            </section>
        <?php else: ?>
            <nav class="mockup-breadcrumb" aria-label="Breadcrumb">
                <a href="index.php">Home</a>
                <span aria-hidden="true">&gt;</span>
                <a href="orders.php">My Orders</a>
                <span aria-hidden="true">&gt;</span>
                <span aria-current="page">Design Review</span>
            </nav>

            <header class="mockup-page__header">
                <p class="eyebrow">Order #<?= (int) $mockup['order_id'] ?> / Mock-up stage</p>
                <h1 class="mockup-page__title">DESIGN REVIEW</h1>
                <p>Inspect the proposed design for your customised product before it moves into the next order stage.</p>
            </header>

            <div class="mockup-layout">
                <section class="mockup-gallery" aria-labelledby="mockup-preview-title">
                    <div class="mockup-preview">
                        <span class="mockup-preview__label">DESIGN PREVIEW</span>
                        <span class="mockup-preview__edition">REVISION <?= (int) $mockup['revision'] ?></span>
                        <img class="mockup-preview__image" src="<?= htmlspecialchars($mockup['preview_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($mockup['product_name'] . ' design preview', ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($mockup['custom_text'] !== ''): ?>
                            <span class="mockup-preview__print"><?= nl2br(htmlspecialchars($mockup['custom_text'], ENT_QUOTES, 'UTF-8')) ?></span>
                        <?php endif; ?>
                        <span class="mockup-preview__line" aria-hidden="true"></span>
                    </div>
                    <div class="mockup-gallery__caption">
                        <div>
                            <p class="eyebrow">Staff-prepared view</p>
                            <h2 id="mockup-preview-title"><?= htmlspecialchars($mockup['product_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <span>Revision <?= (int) $mockup['revision'] ?></span>
                    </div>
                </section>

                <section class="mockup-details" aria-labelledby="mockup-details-title">
                    <div class="mockup-panel-heading">
                        <p class="eyebrow">Your order</p>
                        <h2 id="mockup-details-title">Review the details</h2>
                        <p>This preview is connected to the selected order item and its current design revision.</p>
                    </div>

                    <?php if ($flash): ?>
                        <p class="review-feedback <?= $flash['type'] === 'error' ? 'is-error' : 'is-success' ?>" id="review-feedback" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>" tabindex="-1"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <p class="review-feedback" id="review-feedback" role="status" tabindex="-1" hidden></p>
                    <?php endif; ?>

                    <dl class="mockup-order-meta">
                        <div>
                            <dt>Order number</dt>
                            <dd>#<?= (int) $mockup['order_id'] ?></dd>
                        </div>
                        <div>
                            <dt>Order date</dt>
                            <dd><?= htmlspecialchars(date('j F Y', strtotime($mockup['order_created_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>Order status</dt>
                            <dd><span class="status-badge status-badge--<?= htmlspecialchars(f2bCustomerMockupStatusClass($mockup['order_status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(f2bCustomerMockupStatusLabel($mockup['order_status']), ENT_QUOTES, 'UTF-8') ?></span></dd>
                        </div>
                        <div>
                            <dt>Mock-up status</dt>
                            <dd><span class="status-badge status-badge--<?= htmlspecialchars(f2bCustomerMockupStatusClass($mockup['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(f2bCustomerMockupStatusLabel($mockup['status']), ENT_QUOTES, 'UTF-8') ?></span></dd>
                        </div>
                        <div>
                            <dt>Current revision</dt>
                            <dd><?= (int) $mockup['revision'] ?></dd>
                        </div>
                        <div>
                            <dt>Last updated</dt>
                            <dd><?= htmlspecialchars(date('j F Y, g:i a', strtotime($mockup['updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                    </dl>

                    <section class="customization-card" aria-labelledby="customization-title">
                        <p class="eyebrow">Product specifications</p>
                        <h2 id="customization-title">YOUR CUSTOMIZATION</h2>
                        <dl class="customization-details">
                            <div><dt>Product</dt><dd><?= htmlspecialchars($mockup['product_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Size</dt><dd><?= htmlspecialchars($mockup['size'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Colour</dt><dd><?= htmlspecialchars($mockup['colour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Print position</dt><dd><?= htmlspecialchars($mockup['print_position'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Print colour</dt><dd><?= htmlspecialchars($mockup['print_colour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Custom text</dt><dd><?= $mockup['custom_text'] !== '' ? nl2br(htmlspecialchars($mockup['custom_text'], ENT_QUOTES, 'UTF-8')) : 'None specified' ?></dd></div>
                        </dl>
                    </section>

                    <?php if ($mockup['latest_change_request'] !== ''): ?>
                        <section class="review-feedback review-feedback--saved" aria-labelledby="saved-feedback-title">
                            <p class="eyebrow">Saved customer feedback</p>
                            <h2 id="saved-feedback-title">LATEST REQUEST</h2>
                            <p><?= nl2br(htmlspecialchars($mockup['latest_change_request'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </section>
                    <?php endif; ?>

                    <?php if ($reviewHistory): ?>
                        <section class="review-history" aria-labelledby="review-history-title">
                            <div class="review-history__heading">
                                <p class="eyebrow">Read-only record</p>
                                <h2 id="review-history-title">REVIEW HISTORY</h2>
                            </div>
                            <ol class="review-history__list">
                                <?php foreach ($reviewHistory as $historyEntry): ?>
                                    <li>
                                        <div class="review-history__entry-heading">
                                            <strong><?= $historyEntry['decision'] === 'approve' ? 'Design approved' : 'Changes requested' ?></strong>
                                            <span>Revision <?= (int) $historyEntry['revision'] ?></span>
                                        </div>
                                        <time datetime="<?= htmlspecialchars(date('c', strtotime($historyEntry['created_at'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('j F Y, g:i a', strtotime($historyEntry['created_at'])), ENT_QUOTES, 'UTF-8') ?></time>
                                        <?php if ($historyEntry['change_request'] !== ''): ?>
                                            <p><?= nl2br(htmlspecialchars($historyEntry['change_request'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </section>
                    <?php endif; ?>

                    <?php if ($mockup['status'] === 'READY_FOR_REVIEW'): ?>
                        <section class="review-card" aria-labelledby="review-title">
                            <div class="review-card__heading">
                                <p class="eyebrow">Your decision</p>
                                <h2 id="review-title">REVIEW YOUR DESIGN</h2>
                            </div>

                            <form class="review-form" id="mockup-review-form" action="mockup-action.php" method="post" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $reviewCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="mockup_id" value="<?= (int) $mockup['id'] ?>">
                                <input type="hidden" name="expected_revision" value="<?= (int) $mockup['revision'] ?>">
                                <fieldset aria-describedby="decision-error">
                                    <legend class="sr-only">Choose a review decision</legend>
                                    <label class="review-option">
                                        <input type="radio" name="review_decision" value="approve" required>
                                        <span class="review-option__content">
                                            <span>
                                                <strong>Approve Design</strong>
                                                <small>The proposed design looks correct and requires no further changes.</small>
                                            </span>
                                            <span class="review-option__indicator" aria-hidden="true"></span>
                                        </span>
                                    </label>
                                    <label class="review-option">
                                        <input type="radio" name="review_decision" value="request_changes">
                                        <span class="review-option__content">
                                            <span>
                                                <strong>Request Changes</strong>
                                                <small>Tell the staff team what should be adjusted before approval.</small>
                                            </span>
                                            <span class="review-option__indicator" aria-hidden="true"></span>
                                        </span>
                                    </label>
                                </fieldset>
                                <p class="review-form__error" id="decision-error" role="alert" hidden>Please choose Approve Design or Request Changes.</p>
                                <div class="change-request-field" id="change-request-field" hidden>
                                    <label for="change-request">Describe Your Requested Changes</label>
                                    <p class="change-request-field__help" id="change-request-help">Tell us what to adjust, such as print placement, size, text, colour, or another design detail.</p>
                                    <textarea id="change-request" name="change_request" rows="4" maxlength="500" aria-describedby="change-request-help change-request-error" disabled></textarea>
                                    <p class="review-form__error" id="change-request-error" role="alert" hidden>Please describe the changes you would like to request.</p>
                                </div>
                                <button class="button button--dark button--wide review-submit" type="submit">SUBMIT REVIEW <span aria-hidden="true">&rarr;</span></button>
                                <p class="review-form__note">Your decision will be saved to this mock-up revision.</p>
                            </form>
                        </section>
                    <?php elseif ($mockup['status'] === 'PENDING'): ?>
                        <div class="mockup-state-message">
                            <p class="eyebrow">Design in progress</p>
                            <h2>YOUR MOCK-UP IS BEING PREPARED</h2>
                            <p>The staff team will publish a reviewable design here when it is ready.</p>
                        </div>
                    <?php elseif ($mockup['status'] === 'CHANGES_REQUESTED'): ?>
                        <div class="mockup-state-message mockup-state-message--attention">
                            <p class="eyebrow">Awaiting revision</p>
                            <h2>CHANGES HAVE BEEN REQUESTED</h2>
                            <p>The staff team is preparing an updated revision. A new decision can be submitted once it is published.</p>
                        </div>
                    <?php else: ?>
                        <div class="mockup-state-message mockup-state-message--success">
                            <p class="eyebrow">Design approved</p>
                            <h2>YOUR DESIGN IS APPROVED</h2>
                            <p>This revision has been approved and no further review submission is available.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="review-instructions" aria-labelledby="instructions-title">
                <div>
                    <p class="eyebrow">Take a closer look</p>
                    <h2 id="instructions-title">BEFORE YOU APPROVE</h2>
                    <p class="review-instructions__note">Check the prepared preview and specifications before approving this revision.</p>
                </div>
                <ul>
                    <li>Check the product colour and size.</li>
                    <li>Check the text, logo, and print placement.</li>
                    <li>Review the current revision number.</li>
                    <li>Request changes if something needs adjustment.</li>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</div>

<script src="js/mockup.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>

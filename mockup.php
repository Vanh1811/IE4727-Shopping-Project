<?php
$pageTitle = 'Design Review';
$pageDescription = 'Review the proposed F2B SHOP design mock-up.';
$pageStylesheet = 'css/mockup.css';

// Static demonstration data consistent with order #1028 from B2.
$mockupOrder = [
    'number' => '#1028',
    'date' => '13 October 2026',
    'orderStatus' => 'DESIGN REVIEW',
    'mockupStatus' => 'READY FOR REVIEW',
    'product' => 'Form Tee',
    'size' => 'L',
    'colour' => 'White',
    'printPosition' => 'Front',
    'printColour' => 'Black',
    'customText' => 'ACES CHEER',
    'image' => 'images/products/form-tee.svg',
    'imageAlt' => 'Proposed Form Tee design mock-up with ACES CHEER front print',
];

include __DIR__ . '/includes/header.php';
?>

<div class="mockup-page">
    <div class="container">
        <nav class="mockup-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">&gt;</span>
            <a href="orders.php">My Orders</a>
            <span aria-hidden="true">&gt;</span>
            <span aria-current="page">Design Review</span>
        </nav>

        <header class="mockup-page__header">
            <p class="eyebrow">Order <?= htmlspecialchars($mockupOrder['number'], ENT_QUOTES, 'UTF-8') ?> / Mock-up stage</p>
            <h1 class="mockup-page__title">DESIGN REVIEW</h1>
            <p>Inspect the proposed design for your customised product before it moves into processing.</p>
        </header>

        <div class="mockup-layout">
            <section class="mockup-gallery" aria-labelledby="mockup-preview-title">
                <div class="mockup-preview">
                    <span class="mockup-preview__label">DESIGN PREVIEW</span>
                    <span class="mockup-preview__edition">FRONT / 01</span>
                    <img class="mockup-preview__image" src="<?= htmlspecialchars($mockupOrder['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($mockupOrder['imageAlt'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="mockup-preview__print" aria-hidden="true">ACES<br>CHEER</span>
                    <span class="mockup-preview__line" aria-hidden="true"></span>
                </div>
                <div class="mockup-gallery__caption">
                    <div>
                        <p class="eyebrow">Proposed final view</p>
                        <h2 id="mockup-preview-title">Front print placement</h2>
                    </div>
                    <span>View 01 / 01</span>
                </div>
            </section>

            <section class="mockup-details" aria-labelledby="mockup-details-title">
                <div class="mockup-panel-heading">
                    <p class="eyebrow">Your order</p>
                    <h2 id="mockup-details-title">Review the details</h2>
                    <p>Everything shown here is a static preview of the design prepared for this order.</p>
                </div>

                <dl class="mockup-order-meta">
                    <div>
                        <dt>Order number</dt>
                        <dd><?= htmlspecialchars($mockupOrder['number'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Order date</dt>
                        <dd><?= htmlspecialchars($mockupOrder['date'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Order status</dt>
                        <dd><span class="status-badge status-badge--review"><?= htmlspecialchars($mockupOrder['orderStatus'], ENT_QUOTES, 'UTF-8') ?></span></dd>
                    </div>
                    <div>
                        <dt>Mock-up status</dt>
                        <dd><span class="status-badge status-badge--ready"><?= htmlspecialchars($mockupOrder['mockupStatus'], ENT_QUOTES, 'UTF-8') ?></span></dd>
                    </div>
                </dl>

                <section class="customization-card" aria-labelledby="customization-title">
                    <p class="eyebrow">Product specifications</p>
                    <h2 id="customization-title">YOUR CUSTOMIZATION</h2>
                    <dl class="customization-details">
                        <div><dt>Product</dt><dd><?= htmlspecialchars($mockupOrder['product'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Size</dt><dd><?= htmlspecialchars($mockupOrder['size'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Colour</dt><dd><?= htmlspecialchars($mockupOrder['colour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Print position</dt><dd><?= htmlspecialchars($mockupOrder['printPosition'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Print colour</dt><dd><?= htmlspecialchars($mockupOrder['printColour'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Custom text</dt><dd><?= htmlspecialchars($mockupOrder['customText'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                    </dl>
                </section>

                <section class="review-card" aria-labelledby="review-title">
                    <div class="review-card__heading">
                        <p class="eyebrow">Your decision</p>
                        <h2 id="review-title">REVIEW YOUR DESIGN</h2>
                    </div>

                    <form class="review-form" action="mockup.php" method="post">
                        <fieldset>
                            <legend class="sr-only">Choose a review decision</legend>
                            <label class="review-option">
                                <input type="radio" name="review_decision" value="approve" checked>
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
                                        <small>I would like to make changes before the design is finalized.</small>
                                    </span>
                                    <span class="review-option__indicator" aria-hidden="true"></span>
                                </span>
                            </label>
                        </fieldset>
                        <button class="button button--dark button--wide review-submit" type="button">SUBMIT REVIEW <span aria-hidden="true">&rarr;</span></button>
                        <p class="review-form__note">Demo interface only — no review decision will be saved or submitted.</p>
                    </form>
                </section>
            </section>
        </div>

        <section class="review-instructions" aria-labelledby="instructions-title">
            <div>
                <p class="eyebrow">Take a closer look</p>
                <h2 id="instructions-title">BEFORE YOU APPROVE</h2>
            </div>
            <ul>
                <li>Check the product colour and size.</li>
                <li>Check the text, logo, and print placement.</li>
                <li>Review the proposed design carefully.</li>
                <li>Request changes if something needs adjustment.</li>
            </ul>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// Pages set $pageTitle before including this file.
$pageTitle = $pageTitle ?? 'F2B SHOP';
$pageDescription = $pageDescription ?? 'F2B SHOP — Wear Your Style.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | F2B SHOP</title>
    <link rel="stylesheet" href="css/style.css">
    <?php if (!empty($pageStylesheet)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($pageStylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <?php require __DIR__ . '/navbar.php'; ?>
    <main id="main-content">

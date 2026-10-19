<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/review.php';

$mockupId = f2bReviewPositiveId(f2bReviewReadScalar($_POST, 'mockup_id'));
$redirect = 'orders.php';
if ($mockupId !== null) {
    $redirect = 'mockup.php?mockup=' . $mockupId;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    f2bOrderSetFlash('Review actions must use a POST request.', 'error');
    header('Location: ' . $redirect, true, 303);
    exit;
}

if (!f2bCsrfValidate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null, 'mockup-review')) {
    f2bOrderSetFlash('Your review session expired. Please try again.', 'error');
    header('Location: ' . $redirect, true, 303);
    exit;
}

$result = f2bReviewSubmit($db, $_POST);
f2bOrderSetFlash($result['message'], $result['success'] ? 'success' : 'error');
header('Location: ' . $redirect, true, 303);
exit;

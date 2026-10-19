<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_order.php';

f2bAdminRequireAuth();

$orderId = f2bAdminPositiveId(f2bAdminReadScalar($_POST, 'order_id'));
$redirect = 'admin-orders.php';
if ($orderId !== null) {
    $redirect .= '?order=' . $orderId . '#order-' . $orderId;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    f2bOrderSetFlash('Staff actions must use a POST request.', 'error');
    header('Location: ' . $redirect, true, 303);
    exit;
}

if (!f2bCsrfValidate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null, 'staff-action')) {
    f2bOrderSetFlash('Your staff session expired. Please try again.', 'error');
    header('Location: ' . $redirect, true, 303);
    exit;
}

$action = f2bAdminReadScalar($_POST, 'action');
if ($action === 'publish_mockup') {
    $result = f2bAdminPublishMockup($db, $_POST);
} elseif ($action === 'update_status') {
    $result = f2bAdminUpdateOrderStatus($db, $_POST);
} else {
    $result = array('success' => false, 'message' => 'The requested staff action is not available.');
}

f2bOrderSetFlash($result['message'], $result['success'] ? 'success' : 'error');
header('Location: ' . $redirect, true, 303);
exit;

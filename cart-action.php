<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order_access.php';
require_once __DIR__ . '/includes/cart.php';

$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$action = isset($_POST['action']) && is_scalar($_POST['action']) ? trim((string) $_POST['action']) : '';
$success = false;
$message = 'That cart action could not be completed.';

$csrfValid = $requestMethod === 'POST'
    && f2bCsrfValidate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null, 'cart');

if ($requestMethod === 'POST' && !$csrfValid) {
    $message = 'Your cart session expired. Refresh the page and try again.';
} elseif ($requestMethod === 'POST' && $action === 'add') {
    $success = f2bCartAdd(
        $db,
        isset($_POST['product_id']) ? $_POST['product_id'] : null,
        isset($_POST['size']) ? $_POST['size'] : null,
        isset($_POST['colour']) ? $_POST['colour'] : null,
        isset($_POST['quantity']) ? $_POST['quantity'] : null
    );
    $message = $success
        ? 'Item added to your cart.'
        : 'We could not add that item. Please choose a valid size and colour.';
} elseif ($requestMethod === 'POST' && $action === 'update') {
    $success = f2bCartUpdate(
        isset($_POST['key']) ? $_POST['key'] : null,
        isset($_POST['quantity']) ? $_POST['quantity'] : null
    );
    $message = $success
        ? 'Cart quantity updated.'
        : 'We could not update that quantity. Choose a whole number from 1 to 99.';
} elseif ($requestMethod === 'POST' && $action === 'remove') {
    $success = f2bCartRemove(isset($_POST['key']) ? $_POST['key'] : null);
    $message = $success ? 'Item removed from your cart.' : 'That cart item is no longer available.';
} elseif ($requestMethod === 'POST' && $action === 'clear') {
    $success = f2bCartClear();
    $message = 'Your cart has been cleared.';
} elseif ($requestMethod === 'POST') {
    $message = 'That cart action is not available.';
} else {
    $message = 'Cart changes must be submitted from the cart form.';
}

if ($requestMethod !== 'POST' || $action === '' || !$success) {
    f2bCartSetFlash($message, 'error');
} else {
    f2bCartSetFlash($message, 'success');
}

header('Location: cart.php', true, 303);
exit;

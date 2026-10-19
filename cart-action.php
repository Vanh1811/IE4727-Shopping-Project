<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cart.php';

$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$action = isset($_POST['action']) && is_scalar($_POST['action']) ? trim((string) $_POST['action']) : '';
$success = false;

if ($requestMethod === 'POST' && $action === 'add') {
    $success = f2bCartAdd(
        $db,
        isset($_POST['product_id']) ? $_POST['product_id'] : null,
        isset($_POST['size']) ? $_POST['size'] : null,
        isset($_POST['colour']) ? $_POST['colour'] : null,
        isset($_POST['quantity']) ? $_POST['quantity'] : null
    );

    f2bCartSetFlash(
        $success ? 'Item added to your cart.' : 'We could not add that item. Please choose a valid size and colour.',
        $success ? 'success' : 'error'
    );
} elseif ($requestMethod === 'POST') {
    f2bCartSetFlash('That cart action is not available yet.', 'error');
} else {
    f2bCartSetFlash('Cart changes must be submitted from the cart form.', 'error');
}

header('Location: cart.php', true, 303);
exit;

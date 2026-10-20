<?php

/**
 * Session-backed cart helpers.
 *
 * The session stores only the product id, selected variant and quantity. Product
 * names, images and prices are always read from the database before rendering.
 */

if (!defined('F2B_CART_MAX_QUANTITY')) {
    define('F2B_CART_MAX_QUANTITY', 99);
}

if (!defined('F2B_CART_DISCOUNT_RATE')) {
    define('F2B_CART_DISCOUNT_RATE', 0.10);
}

if (!defined('F2B_CART_DELIVERY_FEE')) {
    define('F2B_CART_DELIVERY_FEE', 5.00);
}

function f2bCartEnsureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    return true;
}

function f2bCartValidateProductId($value)
{
    if (is_bool($value) || is_float($value) || is_array($value) || is_object($value)) {
        return null;
    }

    $value = trim((string) $value);
    if ($value === '' || !preg_match('/^\d+$/', $value)) {
        return null;
    }

    $productId = (int) $value;
    return $productId > 0 ? $productId : null;
}

function f2bCartValidateQuantity($value)
{
    if (is_bool($value) || is_float($value) || is_array($value) || is_object($value)) {
        return null;
    }

    $value = trim((string) $value);
    if ($value === '' || !preg_match('/^\d+$/', $value)) {
        return null;
    }

    $quantity = (int) $value;
    if ($quantity < 1 || $quantity > F2B_CART_MAX_QUANTITY) {
        return null;
    }

    return $quantity;
}

function f2bCartValidateSize($value)
{
    if (is_bool($value) || is_array($value) || is_object($value)) {
        return null;
    }

    $size = strtoupper(trim((string) $value));
    $allowedSizes = array('S', 'M', 'L', 'XL');

    return in_array($size, $allowedSizes, true) ? $size : null;
}

function f2bCartValidateColour($value)
{
    if (is_bool($value) || is_array($value) || is_object($value)) {
        return null;
    }

    $colour = trim((string) $value);
    if ($colour === '' || strlen($colour) > 50 || preg_match('/[\x00-\x1F\x7F|]/', $colour)) {
        return null;
    }

    return ucwords(strtolower($colour));
}

function f2bCartBuildKey($productId, $size, $colour)
{
    return (int) $productId . '|' . $size . '|' . $colour;
}

function f2bCartParseKey($key)
{
    if (!is_string($key)) {
        return null;
    }

    $parts = explode('|', $key);
    if (count($parts) !== 3) {
        return null;
    }

    $productId = f2bCartValidateProductId($parts[0]);
    $size = f2bCartValidateSize($parts[1]);
    $colour = f2bCartValidateColour($parts[2]);

    if ($productId === null || $size === null || $colour === null) {
        return null;
    }

    $canonicalKey = f2bCartBuildKey($productId, $size, $colour);
    if ($canonicalKey !== $key) {
        return null;
    }

    return array(
        'product_id' => $productId,
        'size' => $size,
        'colour' => $colour,
    );
}

function f2bCartRead()
{
    if (!f2bCartEnsureSession()) {
        return array();
    }

    $cleanCart = array();

    foreach ($_SESSION['cart'] as $key => $item) {
        $parsedKey = f2bCartParseKey($key);
        if ($parsedKey === null || !is_array($item)) {
            continue;
        }

        $productId = f2bCartValidateProductId(isset($item['product_id']) ? $item['product_id'] : null);
        $size = f2bCartValidateSize(isset($item['size']) ? $item['size'] : null);
        $colour = f2bCartValidateColour(isset($item['colour']) ? $item['colour'] : null);
        $quantity = f2bCartValidateQuantity(isset($item['quantity']) ? $item['quantity'] : null);

        if (
            $productId === null ||
            $size === null ||
            $colour === null ||
            $quantity === null ||
            $parsedKey['product_id'] !== $productId ||
            $parsedKey['size'] !== $size ||
            $parsedKey['colour'] !== $colour ||
            f2bCartBuildKey($productId, $size, $colour) !== $key
        ) {
            continue;
        }

        $cleanCart[$key] = array(
            'product_id' => $productId,
            'size' => $size,
            'colour' => $colour,
            'quantity' => $quantity,
        );
    }

    $_SESSION['cart'] = $cleanCart;
    return $cleanCart;
}

function f2bCartFindProduct($db, $productId)
{
    if (!is_object($db) || !method_exists($db, 'prepare')) {
        return null;
    }

    $statement = $db->prepare(
        'SELECT id, name, description, price, original_price, rating, review_count, '
        . 'image, image_2, image_3, image_4, colour '
        . 'FROM products WHERE id = ? LIMIT 1'
    );

    if (!$statement || !$statement->bind_param('i', $productId) || !$statement->execute()) {
        if ($statement) {
            $statement->close();
        }
        return null;
    }

    $rowId = null;
    $name = null;
    $description = null;
    $price = null;
    $originalPrice = null;
    $rating = null;
    $reviewCount = null;
    $image = null;
    $imageTwo = null;
    $imageThree = null;
    $imageFour = null;
    $productColour = null;

    $statement->bind_result(
        $rowId,
        $name,
        $description,
        $price,
        $originalPrice,
        $rating,
        $reviewCount,
        $image,
        $imageTwo,
        $imageThree,
        $imageFour,
        $productColour
    );

    if (!$statement->fetch()) {
        $statement->close();
        return null;
    }

    $statement->close();

    return array(
        'id' => (int) $rowId,
        'name' => (string) $name,
        'description' => (string) $description,
        'price' => (float) $price,
        'original_price' => $originalPrice === null ? null : (float) $originalPrice,
        'rating' => (float) $rating,
        'review_count' => (int) $reviewCount,
        'image' => (string) $image,
        'image_2' => $imageTwo === null ? null : (string) $imageTwo,
        'image_3' => $imageThree === null ? null : (string) $imageThree,
        'image_4' => $imageFour === null ? null : (string) $imageFour,
        'colour' => $productColour === null ? null : (string) $productColour,
    );
}

function f2bCartAdd($db, $productIdValue, $sizeValue, $colourValue, $quantityValue)
{
    if (!f2bCartEnsureSession()) {
        return false;
    }

    $productId = f2bCartValidateProductId($productIdValue);
    $size = f2bCartValidateSize($sizeValue);
    $colour = f2bCartValidateColour($colourValue);
    $quantity = f2bCartValidateQuantity($quantityValue);

    if ($productId === null || $size === null || $colour === null || $quantity === null) {
        return false;
    }

    $product = f2bCartFindProduct($db, $productId);
    $productColour = $product !== null
        ? f2bCartValidateColour(isset($product['colour']) ? $product['colour'] : null)
        : null;

    if ($product === null || $productColour === null || $colour !== $productColour) {
        return false;
    }

    $key = f2bCartBuildKey($productId, $size, $colour);
    $cart = f2bCartRead();
    $existingQuantity = isset($cart[$key]) ? (int) $cart[$key]['quantity'] : 0;
    $newQuantity = min(F2B_CART_MAX_QUANTITY, $existingQuantity + $quantity);

    $_SESSION['cart'][$key] = array(
        'product_id' => $productId,
        'size' => $size,
        'colour' => $colour,
        'quantity' => $newQuantity,
    );

    return true;
}

function f2bCartUpdate($key, $quantityValue)
{
    if (!f2bCartEnsureSession()) {
        return false;
    }

    $parsedKey = f2bCartParseKey($key);
    $quantity = f2bCartValidateQuantity($quantityValue);
    $cart = f2bCartRead();

    if ($parsedKey === null || $quantity === null || !isset($cart[$key])) {
        return false;
    }

    $_SESSION['cart'][$key]['quantity'] = $quantity;
    return true;
}

function f2bCartRemove($key)
{
    if (!f2bCartEnsureSession()) {
        return false;
    }

    $cart = f2bCartRead();
    if (f2bCartParseKey($key) === null || !isset($cart[$key])) {
        return false;
    }

    unset($_SESSION['cart'][$key]);
    return true;
}

function f2bCartClear()
{
    if (!f2bCartEnsureSession()) {
        return false;
    }

    $_SESSION['cart'] = array();
    return true;
}

function f2bCartTotalQuantity()
{
    $total = 0;

    foreach (f2bCartRead() as $item) {
        $total += (int) $item['quantity'];
    }

    return $total;
}

function f2bCartLoadItems($db)
{
    if (!f2bCartEnsureSession()) {
        return array();
    }

    $items = array();
    $cart = f2bCartRead();

    foreach ($cart as $key => $item) {
        $product = f2bCartFindProduct($db, (int) $item['product_id']);
        if ($product === null) {
            unset($_SESSION['cart'][$key]);
            continue;
        }

        $items[] = array(
            'key' => $key,
            'product_id' => (int) $item['product_id'],
            'size' => $item['size'],
            'colour' => $item['colour'],
            'quantity' => (int) $item['quantity'],
            'name' => $product['name'],
            'price' => $product['price'],
            'original_price' => $product['original_price'],
            'image' => $product['image'],
        );
    }

    return $items;
}

function f2bCartCalculateTotals($items)
{
    $subtotal = 0.00;
    $itemCount = 0;

    foreach ($items as $item) {
        $quantity = (int) $item['quantity'];
        $subtotal += (float) $item['price'] * $quantity;
        $itemCount += $quantity;
    }

    $subtotal = round($subtotal, 2);
    $discount = round($subtotal * F2B_CART_DISCOUNT_RATE, 2);
    $delivery = $subtotal > 0 ? F2B_CART_DELIVERY_FEE : 0.00;
    $total = round(max(0, $subtotal - $discount + $delivery), 2);

    return array(
        'subtotal' => $subtotal,
        'discount_rate' => F2B_CART_DISCOUNT_RATE,
        'discount' => $discount,
        'delivery' => $delivery,
        'total' => $total,
        'item_count' => $itemCount,
    );
}

function f2bCartSetFlash($message, $type = 'success')
{
    if (!f2bCartEnsureSession()) {
        return;
    }

    $_SESSION['cart_flash'] = array(
        'message' => (string) $message,
        'type' => $type === 'error' ? 'error' : 'success',
    );
}

function f2bCartConsumeFlash()
{
    if (!f2bCartEnsureSession() || !isset($_SESSION['cart_flash'])) {
        return null;
    }

    $flash = $_SESSION['cart_flash'];
    unset($_SESSION['cart_flash']);

    return is_array($flash) ? $flash : null;
}

f2bCartEnsureSession();

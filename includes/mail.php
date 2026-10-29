<?php

/**
 * Optional local-only order acknowledgement mail helper.
 *
 * Delivery is disabled unless the feature flag, an exact local recipient
 * allowlist match, and a locally configured mail transport are present.
 * Customer-provided email addresses are never used as recipients.
 */

function f2bOrderMailSafeText($value, $fallback = '')
{
    if (!is_scalar($value)) {
        return $fallback;
    }

    $value = (string) $value;
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    $value = str_replace(array("\r\n", "\r"), "\n", $value);
    $value = trim($value);

    return $value === '' ? $fallback : $value;
}

function f2bOrderMailLocalAddress($address)
{
    if (!is_string($address) || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    $parts = explode('@', strtolower($address));
    $domain = isset($parts[1]) ? $parts[1] : '';

    return $domain === 'localhost'
        || substr($domain, -5) === '.test'
        || substr($domain, -10) === '.localhost'
        || substr($domain, -5) === '.local';
}

function f2bOrderMailResult($status, $message)
{
    return array('status' => $status, 'message' => $message);
}

function f2bOrderSendLocalAcknowledgement($db, $orderId, $customer, $totals)
{
    if (getenv('F2B_LOCAL_MAIL_ENABLED') !== '1') {
        return f2bOrderMailResult('disabled', 'Local acknowledgement email is not configured for this environment. Your order is still saved.');
    }

    $recipient = trim((string) getenv('F2B_LOCAL_MAIL_TO'));
    $allowedRecipient = trim((string) getenv('F2B_LOCAL_MAIL_ALLOWED_TO'));
    if (
        $recipient === '' ||
        $allowedRecipient === '' ||
        !f2bOrderMailLocalAddress($recipient) ||
        !f2bOrderMailLocalAddress($allowedRecipient) ||
        strcasecmp($recipient, $allowedRecipient) !== 0
    ) {
        return f2bOrderMailResult('disabled', 'Local acknowledgement email is not configured for an allowed local inbox. Your order is still saved.');
    }

    if (!function_exists('mail') || !is_object($db) || !method_exists($db, 'begin_transaction') || !method_exists($db, 'prepare')) {
        error_log('F2B SHOP local acknowledgement mail is unavailable.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $orderId = (int) $orderId;
    if ($orderId < 1 || !$db->begin_transaction()) {
        error_log('F2B SHOP could not start the local acknowledgement mail transaction.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $orderStatement = $db->prepare(
        'SELECT id, full_name, subtotal, discount, delivery_fee, total, status, email_sent_at, created_at '
        . 'FROM orders WHERE id = ? LIMIT 1 FOR UPDATE'
    );
    if ($orderStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not prepare the local acknowledgement order lookup.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $orderStatement->bind_param('i', $orderId);
    if (!$orderStatement->execute()) {
        $orderStatement->close();
        $db->rollback();
        error_log('F2B SHOP local acknowledgement order lookup failed.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $savedOrder = array(
        'id' => null,
        'full_name' => null,
        'subtotal' => null,
        'discount' => null,
        'delivery_fee' => null,
        'total' => null,
        'status' => null,
        'email_sent_at' => null,
        'created_at' => null,
    );
    $orderStatement->bind_result(
        $savedOrder['id'],
        $savedOrder['full_name'],
        $savedOrder['subtotal'],
        $savedOrder['discount'],
        $savedOrder['delivery_fee'],
        $savedOrder['total'],
        $savedOrder['status'],
        $savedOrder['email_sent_at'],
        $savedOrder['created_at']
    );
    $hasOrder = $orderStatement->fetch();
    $orderStatement->close();

    if (!$hasOrder) {
        $db->rollback();
        error_log('F2B SHOP could not find the order for local acknowledgement.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    if ($savedOrder['email_sent_at'] !== null) {
        $db->commit();
        return f2bOrderMailResult('already_sent', 'The local acknowledgement was already handled for this order.');
    }

    $itemStatement = $db->prepare(
        'SELECT product_name, size, colour, unit_price, quantity, line_total '
        . 'FROM order_items WHERE order_id = ? ORDER BY id ASC'
    );
    if ($itemStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not prepare the local acknowledgement item lookup.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $itemStatement->bind_param('i', $orderId);
    if (!$itemStatement->execute()) {
        $itemStatement->close();
        $db->rollback();
        error_log('F2B SHOP local acknowledgement item lookup failed.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $productName = $size = $colour = $unitPrice = $quantity = $lineTotal = null;
    $itemStatement->bind_result($productName, $size, $colour, $unitPrice, $quantity, $lineTotal);
    $items = array();
    while ($itemStatement->fetch()) {
        $items[] = array(
            'product_name' => f2bOrderMailSafeText($productName, 'Product'),
            'size' => f2bOrderMailSafeText($size, 'Not specified'),
            'colour' => f2bOrderMailSafeText($colour, 'Not specified'),
            'unit_price' => $unitPrice,
            'quantity' => (int) $quantity,
            'line_total' => $lineTotal,
        );
    }
    $itemStatement->close();

    if (!$items) {
        $db->rollback();
        error_log('F2B SHOP local acknowledgement has no saved order items.');
        return f2bOrderMailResult('failed', 'The order was saved, but its receipt could not be prepared.');
    }

    $timestamp = strtotime((string) $savedOrder['created_at']);
    $orderDate = $timestamp === false ? 'Date unavailable' : date('j F Y', $timestamp);
    $customerName = f2bOrderMailSafeText($savedOrder['full_name'], 'F2B SHOP customer');
    $body = "F2B SHOP\nORDER ACKNOWLEDGEMENT\n\n"
        . "Hello " . $customerName . ",\n\n"
        . "Thank you for shopping with F2B SHOP.\n"
        . "We have received your order. Your design mock-up will be prepared for review.\n\n"
        . "Order number: #" . (int) $savedOrder['id'] . "\n"
        . "Order date: " . $orderDate . "\n"
        . "Status: ORDER PLACED\n\n"
        . "ORDERED ITEMS\n";

    foreach ($items as $index => $item) {
        $body .= ($index + 1) . '. ' . $item['product_name'] . "\n"
            . '   Size: ' . $item['size'] . ' | Colour: ' . $item['colour'] . "\n"
            . '   Unit price: $' . number_format((float) $item['unit_price'], 2, '.', '')
            . ' | Quantity: ' . $item['quantity']
            . ' | Line total: $' . number_format((float) $item['line_total'], 2, '.', '') . "\n\n";
    }

    $body .= 'Subtotal: $' . number_format((float) $savedOrder['subtotal'], 2, '.', '') . "\n"
        . 'Discount: -$' . number_format((float) $savedOrder['discount'], 2, '.', '') . "\n"
        . 'Delivery fee: $' . number_format((float) $savedOrder['delivery_fee'], 2, '.', '') . "\n"
        . 'TOTAL: $' . number_format((float) $savedOrder['total'], 2, '.', '') . "\n\n"
        . "Next step: our team will publish a mock-up for your approval or change requests.\n"
        . "Thank you,\nF2B SHOP";

    $subject = 'F2B SHOP - Order #' . (int) $savedOrder['id'] . ' placed';
    $headers = "From: F2B SHOP <no-reply@f2b.test>\r\n"
        . "Reply-To: no-reply@f2b.test\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!@mail($recipient, $subject, $body, $headers)) {
        $db->rollback();
        error_log('F2B SHOP local acknowledgement mail transport rejected a message.');
        return f2bOrderMailResult('failed', 'The order was saved, but the local acknowledgement email was not accepted.');
    }

    $updateStatement = $db->prepare('UPDATE orders SET email_sent_at = NOW() WHERE id = ? AND email_sent_at IS NULL');
    if ($updateStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not prepare the local acknowledgement status update.');
        return f2bOrderMailResult('failed', 'The order was saved, but its local email handoff could not be recorded.');
    }

    $updateStatement->bind_param('i', $orderId);
    if (!$updateStatement->execute() || $updateStatement->affected_rows !== 1) {
        $updateStatement->close();
        $db->rollback();
        error_log('F2B SHOP local acknowledgement status update failed.');
        return f2bOrderMailResult('failed', 'The order was saved, but its local email handoff could not be recorded.');
    }
    $updateStatement->close();

    if (!$db->commit()) {
        $db->rollback();
        error_log('F2B SHOP local acknowledgement status commit failed.');
        return f2bOrderMailResult('failed', 'The order was saved, but its local email handoff could not be recorded.');
    }

    return f2bOrderMailResult('sent', 'Accepted by the configured local mail transport. Check your own local inbox.');
}

<?php

/**
 * Shared order input validation and persistence helpers.
 */

function f2bOrderReadInput($input, $key)
{
    if (!is_array($input) || !isset($input[$key]) || !is_scalar($input[$key])) {
        return '';
    }

    return trim((string) $input[$key]);
}

function f2bOrderHasControlCharacters($value)
{
    return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
}

function f2bOrderValidateCheckout($input)
{
    $values = array(
        'full_name' => f2bOrderReadInput($input, 'full_name'),
        'email' => f2bOrderReadInput($input, 'email'),
        'phone' => f2bOrderReadInput($input, 'phone'),
        'postal_code' => f2bOrderReadInput($input, 'postal_code'),
        'delivery_address' => f2bOrderReadInput($input, 'delivery_address'),
        'special_instructions' => f2bOrderReadInput($input, 'special_instructions'),
    );
    $errors = array();

    if ($values['full_name'] === '' || strlen($values['full_name']) < 2 || strlen($values['full_name']) > 160 || f2bOrderHasControlCharacters($values['full_name'])) {
        $errors['full_name'] = 'Enter your full name.';
    }

    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || strlen($values['email']) > 254) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (
        $values['phone'] === '' ||
        strlen($values['phone']) < 7 ||
        strlen($values['phone']) > 30 ||
        preg_match('/^[0-9+(). -]+$/', $values['phone']) !== 1 ||
        preg_match('/[0-9]{7,}/', $values['phone']) !== 1
    ) {
        $errors['phone'] = 'Enter a valid phone number.';
    }

    if (
        $values['postal_code'] === '' ||
        strlen($values['postal_code']) < 3 ||
        strlen($values['postal_code']) > 20 ||
        preg_match('/^[0-9A-Za-z][0-9A-Za-z -]{2,19}$/', $values['postal_code']) !== 1
    ) {
        $errors['postal_code'] = 'Enter a valid postal code.';
    }

    if (
        $values['delivery_address'] === '' ||
        strlen($values['delivery_address']) > 500 ||
        f2bOrderHasControlCharacters($values['delivery_address'])
    ) {
        $errors['delivery_address'] = 'Enter a delivery address up to 500 characters.';
    }

    if (strlen($values['special_instructions']) > 1000 || f2bOrderHasControlCharacters($values['special_instructions'])) {
        $errors['special_instructions'] = 'Special instructions must be 1,000 characters or fewer.';
    }

    return array(
        'values' => $values,
        'errors' => $errors,
    );
}

function f2bOrderCreate($db, $customer, $items, $totals)
{
    if (
        !is_object($db) ||
        !method_exists($db, 'begin_transaction') ||
        !is_array($customer) ||
        !is_array($items) ||
        !$items ||
        !is_array($totals)
    ) {
        return array('success' => false, 'order_id' => null);
    }

    if (!$db->begin_transaction()) {
        error_log('F2B SHOP could not begin the order transaction.');
        return array('success' => false, 'order_id' => null);
    }

    $orderStatement = $db->prepare(
        'INSERT INTO orders '
        . '(full_name, email, phone, delivery_address, postal_code, special_instructions, '
        . 'subtotal, discount, delivery_fee, total, status) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if ($orderStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not prepare the order insert.');
        return array('success' => false, 'order_id' => null);
    }

    $fullName = $customer['full_name'];
    $email = $customer['email'];
    $phone = $customer['phone'];
    $deliveryAddress = $customer['delivery_address'];
    $postalCode = $customer['postal_code'];
    $specialInstructions = $customer['special_instructions'] !== '' ? $customer['special_instructions'] : null;
    $subtotal = (float) $totals['subtotal'];
    $discount = (float) $totals['discount'];
    $deliveryFee = (float) $totals['delivery'];
    $total = (float) $totals['total'];
    $status = 'ORDER_PLACED';

    $orderStatement->bind_param(
        'ssssssdddds',
        $fullName,
        $email,
        $phone,
        $deliveryAddress,
        $postalCode,
        $specialInstructions,
        $subtotal,
        $discount,
        $deliveryFee,
        $total,
        $status
    );

    if (!$orderStatement->execute()) {
        $orderStatement->close();
        $db->rollback();
        error_log('F2B SHOP order insert failed.');
        return array('success' => false, 'order_id' => null);
    }

    $orderId = (int) $db->insert_id;
    $orderStatement->close();

    if ($orderId < 1) {
        $db->rollback();
        error_log('F2B SHOP order insert did not return an order ID.');
        return array('success' => false, 'order_id' => null);
    }

    $itemStatement = $db->prepare(
        'INSERT INTO order_items '
        . '(order_id, product_id, product_name, size, colour, unit_price, quantity, line_total) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if ($itemStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not prepare the order item insert.');
        return array('success' => false, 'order_id' => null);
    }

    $itemOrderId = $orderId;
    $itemProductId = 0;
    $itemName = '';
    $itemSize = '';
    $itemColour = '';
    $itemUnitPrice = 0.00;
    $itemQuantity = 0;
    $itemLineTotal = 0.00;

    $itemStatement->bind_param(
        'iisssdid',
        $itemOrderId,
        $itemProductId,
        $itemName,
        $itemSize,
        $itemColour,
        $itemUnitPrice,
        $itemQuantity,
        $itemLineTotal
    );

    foreach ($items as $item) {
        if (
            !is_array($item) ||
            !isset($item['product_id'], $item['name'], $item['size'], $item['colour'], $item['price'], $item['quantity'])
        ) {
            $itemStatement->close();
            $db->rollback();
            error_log('F2B SHOP order item data was invalid.');
            return array('success' => false, 'order_id' => null);
        }

        $itemProductId = (int) $item['product_id'];
        $itemName = (string) $item['name'];
        $itemSize = (string) $item['size'];
        $itemColour = (string) $item['colour'];
        $itemUnitPrice = (float) $item['price'];
        $itemQuantity = (int) $item['quantity'];
        $itemLineTotal = round($itemUnitPrice * $itemQuantity, 2);

        if (
            $itemProductId < 1 ||
            $itemName === '' ||
            $itemSize === '' ||
            $itemColour === '' ||
            $itemUnitPrice < 0 ||
            $itemQuantity < 1 ||
            $itemLineTotal < 0 ||
            !$itemStatement->execute()
        ) {
            $itemStatement->close();
            $db->rollback();
            error_log('F2B SHOP order item insert failed.');
            return array('success' => false, 'order_id' => null);
        }
    }

    $itemStatement->close();

    if (!$db->commit()) {
        $db->rollback();
        error_log('F2B SHOP order transaction commit failed.');
        return array('success' => false, 'order_id' => null);
    }

    return array('success' => true, 'order_id' => $orderId);
}

<?php

/**
 * Staff-only order workflow helpers.
 *
 * These functions keep validation and transaction boundaries outside the
 * dashboard template. They intentionally accept only repository-local image
 * paths and only allow the status transitions defined for Week 6.
 */

function f2bAdminReadScalar($input, $key)
{
    if (!is_array($input) || !array_key_exists($key, $input) || !is_scalar($input[$key])) {
        return null;
    }

    return trim((string) $input[$key]);
}

function f2bAdminPositiveId($value)
{
    if ($value === null || $value === '' || !is_scalar($value) || preg_match('/^\d+$/', (string) $value) !== 1) {
        return null;
    }

    $id = (int) $value;
    return $id > 0 ? $id : null;
}

function f2bAdminTextField($value, $maxLength, $required = true)
{
    if (!is_scalar($value)) {
        return null;
    }

    $value = trim((string) $value);
    if ($required && $value === '') {
        return null;
    }

    if (strlen($value) > $maxLength || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
        return null;
    }

    return $value === '' ? null : $value;
}

function f2bAdminLocalImagePath($value)
{
    if (!is_scalar($value)) {
        return null;
    }

    $path = trim((string) $value);
    if ($path === '' || strpos($path, '..') !== false || preg_match('#^images/products/[A-Za-z0-9][A-Za-z0-9._/-]*$#', $path) !== 1) {
        return null;
    }

    $projectRoot = realpath(__DIR__ . '/..');
    $productsRoot = realpath(__DIR__ . '/../images/products');
    $realPath = realpath(__DIR__ . '/../' . $path);
    if ($projectRoot === false || $productsRoot === false || $realPath === false || !is_file($realPath)) {
        return null;
    }

    $productsPrefix = rtrim($productsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($realPath, $productsPrefix) !== 0) {
        return null;
    }

    return $path;
}

function f2bAdminLoadOrderItems($db, $orderId)
{
    $items = array();
    $orderId = f2bAdminPositiveId($orderId);
    if ($orderId === null || !is_object($db) || !method_exists($db, 'prepare')) {
        return $items;
    }

    $statement = $db->prepare(
        'SELECT oi.id, oi.product_name, oi.size, oi.colour, oi.quantity, oi.unit_price, oi.line_total, p.image, '
        . 'm.id AS mockup_id, m.preview_image, m.print_position, m.print_colour, m.custom_text, '
        . 'm.status AS mockup_status, m.revision, '
        . '(SELECT latest_review.change_request FROM mockup_reviews AS latest_review '
        . 'WHERE latest_review.mockup_id = m.id ORDER BY latest_review.id DESC LIMIT 1) AS latest_change_request '
        . 'FROM order_items AS oi '
        . 'LEFT JOIN products AS p ON p.id = oi.product_id '
        . 'LEFT JOIN mockups AS m ON m.order_item_id = oi.id '
        . 'WHERE oi.order_id = ? ORDER BY oi.id ASC'
    );

    if ($statement === false) {
        return $items;
    }

    $statement->bind_param('i', $orderId);
    if (!$statement->execute()) {
        $statement->close();
        return $items;
    }

    $itemId = null;
    $productName = null;
    $size = null;
    $colour = null;
    $quantity = null;
    $unitPrice = null;
    $lineTotal = null;
    $productImage = null;
    $mockupId = null;
    $previewImage = null;
    $printPosition = null;
    $printColour = null;
    $customText = null;
    $mockupStatus = null;
    $revision = null;
    $latestChangeRequest = null;
    $statement->bind_result(
        $itemId,
        $productName,
        $size,
        $colour,
        $quantity,
        $unitPrice,
        $lineTotal,
        $productImage,
        $mockupId,
        $previewImage,
        $printPosition,
        $printColour,
        $customText,
        $mockupStatus,
        $revision,
        $latestChangeRequest
    );

    while ($statement->fetch()) {
        $items[] = array(
            'id' => (int) $itemId,
            'product_name' => (string) $productName,
            'size' => (string) $size,
            'colour' => (string) $colour,
            'quantity' => (int) $quantity,
            'unit_price' => (float) $unitPrice,
            'line_total' => (float) $lineTotal,
            'product_image' => $productImage === null ? '' : (string) $productImage,
            'mockup_id' => $mockupId === null ? null : (int) $mockupId,
            'preview_image' => $previewImage === null ? '' : (string) $previewImage,
            'print_position' => $printPosition === null ? 'Front' : (string) $printPosition,
            'print_colour' => $printColour === null ? 'Black' : (string) $printColour,
            'custom_text' => $customText === null ? '' : (string) $customText,
            'mockup_status' => $mockupStatus === null ? 'PENDING' : (string) $mockupStatus,
            'revision' => $revision === null ? null : (int) $revision,
            'latest_change_request' => $latestChangeRequest === null ? '' : (string) $latestChangeRequest,
        );
    }

    $statement->close();
    return $items;
}

function f2bAdminPublishMockup($db, $input)
{
    $orderId = f2bAdminPositiveId(f2bAdminReadScalar($input, 'order_id'));
    $orderItemId = f2bAdminPositiveId(f2bAdminReadScalar($input, 'order_item_id'));
    $previewImage = f2bAdminLocalImagePath(f2bAdminReadScalar($input, 'preview_image'));
    $printPosition = f2bAdminTextField(f2bAdminReadScalar($input, 'print_position'), 100);
    $printColour = f2bAdminTextField(f2bAdminReadScalar($input, 'print_colour'), 100);
    $customText = f2bAdminTextField(f2bAdminReadScalar($input, 'custom_text'), 500, false);

    if ($orderId === null || $orderItemId === null || $previewImage === null || $printPosition === null || $printColour === null) {
        return array('success' => false, 'message' => 'Please provide valid mock-up details and a local product image.');
    }

    if (!is_object($db) || !method_exists($db, 'begin_transaction')) {
        return array('success' => false, 'message' => 'The database is not available.');
    }

    if (!$db->begin_transaction()) {
        return array('success' => false, 'message' => 'The mock-up could not be started.');
    }

    $success = false;
    $message = 'The mock-up could not be published.';
    $orderStatus = null;
    $orderStatement = $db->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
    if ($orderStatement !== false) {
        $orderStatement->bind_param('i', $orderId);
        if ($orderStatement->execute()) {
            $orderStatement->bind_result($orderStatus);
            $hasOrder = $orderStatement->fetch();
            $orderStatement->close();

            if ($hasOrder && in_array((string) $orderStatus, array('ORDER_PLACED', 'DESIGN_REVIEW', 'CHANGES_REQUESTED'), true)) {
                $itemStatement = $db->prepare(
                    'SELECT oi.id, m.id AS mockup_id, m.status AS mockup_status, m.revision '
                    . 'FROM order_items AS oi LEFT JOIN mockups AS m ON m.order_item_id = oi.id '
                    . 'WHERE oi.id = ? AND oi.order_id = ? FOR UPDATE'
                );

                if ($itemStatement !== false) {
                    $itemStatement->bind_param('ii', $orderItemId, $orderId);
                    if ($itemStatement->execute()) {
                        $storedItemId = null;
                        $mockupId = null;
                        $mockupStatus = null;
                        $revision = null;
                        $itemStatement->bind_result($storedItemId, $mockupId, $mockupStatus, $revision);
                        $hasItem = $itemStatement->fetch();
                        $itemStatement->close();

                        if ($hasItem) {
                            $nextRevision = $revision === null ? 1 : (int) $revision;
                            if ($mockupId !== null && $mockupStatus === 'APPROVED') {
                                $message = 'Approved mock-ups cannot be overwritten.';
                            } elseif ($mockupId !== null && $mockupStatus === 'READY_FOR_REVIEW') {
                                $message = 'This mock-up is already waiting for customer review.';
                            } elseif ($mockupId !== null && $mockupStatus === 'CHANGES_REQUESTED') {
                                $nextRevision++;
                            }

                            if ($message === 'The mock-up could not be published.') {
                                $nextStatus = 'READY_FOR_REVIEW';
                                if ($mockupId === null) {
                                    $insert = $db->prepare(
                                        'INSERT INTO mockups '
                                        . '(order_id, order_item_id, preview_image, print_position, print_colour, custom_text, status, revision) '
                                        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                                    );
                                    if ($insert !== false) {
                                        $insert->bind_param(
                                            'iisssssi',
                                            $orderId,
                                            $orderItemId,
                                            $previewImage,
                                            $printPosition,
                                            $printColour,
                                            $customText,
                                            $nextStatus,
                                            $nextRevision
                                        );
                                        $success = $insert->execute();
                                        $insert->close();
                                    }
                                } else {
                                    $update = $db->prepare(
                                        'UPDATE mockups SET preview_image = ?, print_position = ?, print_colour = ?, '
                                        . 'custom_text = ?, status = \'READY_FOR_REVIEW\', revision = ? '
                                        . 'WHERE id = ? AND order_id = ? AND order_item_id = ?'
                                    );
                                    if ($update !== false) {
                                        $update->bind_param(
                                            'ssssiiii',
                                            $previewImage,
                                            $printPosition,
                                            $printColour,
                                            $customText,
                                            $nextRevision,
                                            $mockupId,
                                            $orderId,
                                            $orderItemId
                                        );
                                        $success = $update->execute() && $update->affected_rows >= 0;
                                        $update->close();
                                    }
                                }

                                if ($success) {
                                    $orderUpdate = $db->prepare('UPDATE orders SET status = \'DESIGN_REVIEW\' WHERE id = ?');
                                    if ($orderUpdate !== false) {
                                        $orderUpdate->bind_param('i', $orderId);
                                        $success = $orderUpdate->execute();
                                        $orderUpdate->close();
                                    } else {
                                        $success = false;
                                    }
                                    $message = 'Mock-up published for customer review.';
                                }
                            }
                        } else {
                            $message = 'The selected order item does not belong to this order.';
                        }
                    }
                    if ($itemStatement !== null && is_object($itemStatement)) {
                        @$itemStatement->close();
                    }
                }
            } else {
                $message = 'Mock-ups can only be published while the order is awaiting design work.';
            }
        } else {
            $orderStatement->close();
        }
        if ($orderStatement !== null && is_object($orderStatement)) {
            @$orderStatement->close();
        }
    }

    if ($success) {
        if (!$db->commit()) {
            $db->rollback();
            return array('success' => false, 'message' => 'The mock-up could not be committed.');
        }
        return array('success' => true, 'message' => $message);
    }

    $db->rollback();
    return array('success' => false, 'message' => $message);
}

function f2bAdminUpdateOrderStatus($db, $input)
{
    $orderId = f2bAdminPositiveId(f2bAdminReadScalar($input, 'order_id'));
    $nextStatus = f2bAdminReadScalar($input, 'next_status');
    if ($orderId === null || !in_array($nextStatus, array('PROCESSING', 'COMPLETED'), true)) {
        return array('success' => false, 'message' => 'The requested order status is not valid.');
    }

    if (!is_object($db) || !method_exists($db, 'begin_transaction')) {
        return array('success' => false, 'message' => 'The database is not available.');
    }

    if (!$db->begin_transaction()) {
        return array('success' => false, 'message' => 'The order status could not be started.');
    }

    $currentStatus = null;
    $orderStatement = $db->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
    $valid = false;
    if ($orderStatement !== false) {
        $orderStatement->bind_param('i', $orderId);
        if ($orderStatement->execute()) {
            $orderStatement->bind_result($currentStatus);
            $hasOrder = $orderStatement->fetch();
            if ($hasOrder) {
                $valid = ($nextStatus === 'PROCESSING' && $currentStatus === 'CONFIRMED')
                    || ($nextStatus === 'COMPLETED' && $currentStatus === 'PROCESSING');
            }
        }
        $orderStatement->close();
    }

    if (!$valid) {
        $db->rollback();
        return array('success' => false, 'message' => 'That order is not ready for the requested status.');
    }

    $itemCount = 0;
    $approvedCount = 0;
    $approvalStatement = $db->prepare(
        'SELECT COUNT(oi.id), COALESCE(SUM(CASE WHEN m.status = \'APPROVED\' THEN 1 ELSE 0 END), 0) '
        . 'FROM order_items AS oi LEFT JOIN mockups AS m ON m.order_item_id = oi.id WHERE oi.order_id = ?'
    );
    if ($approvalStatement === false) {
        $db->rollback();
        return array('success' => false, 'message' => 'Approval status could not be checked.');
    }

    $approvalStatement->bind_param('i', $orderId);
    if (!$approvalStatement->execute()) {
        $approvalStatement->close();
        $db->rollback();
        return array('success' => false, 'message' => 'Approval status could not be checked.');
    }
    $approvalStatement->bind_result($itemCount, $approvedCount);
    $approvalStatement->fetch();
    $approvalStatement->close();

    if ((int) $itemCount < 1 || (int) $approvedCount !== (int) $itemCount) {
        $db->rollback();
        return array('success' => false, 'message' => 'Every mock-up must be approved before production can continue.');
    }

    $update = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
    if ($update === false) {
        $db->rollback();
        return array('success' => false, 'message' => 'The order status could not be updated.');
    }
    $update->bind_param('si', $nextStatus, $orderId);
    $success = $update->execute();
    $update->close();

    if (!$success || !$db->commit()) {
        $db->rollback();
        return array('success' => false, 'message' => 'The order status could not be updated.');
    }

    return array('success' => true, 'message' => 'Order status updated to ' . str_replace('_', ' ', $nextStatus) . '.');
}

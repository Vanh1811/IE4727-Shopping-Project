<?php

/**
 * Optional local-only order acknowledgement mail helper.
 *
 * Delivery is disabled unless F2B_LOCAL_MAIL_ENABLED=1 and the permitted
 * local recipient is supplied through F2B_LOCAL_MAIL_TO. Customer-provided
 * email addresses are never used as recipients by this helper.
 */

function f2bOrderSendLocalAcknowledgement($db, $orderId, $customer, $totals)
{
    $disabledResult = array(
        'status' => 'disabled',
        'message' => 'Local acknowledgement email is not configured for this environment.',
    );

    if (getenv('F2B_LOCAL_MAIL_ENABLED') !== '1') {
        return $disabledResult;
    }

    $recipient = trim((string) getenv('F2B_LOCAL_MAIL_TO'));
    if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
        return $disabledResult;
    }

    if (
        !function_exists('mail') ||
        !is_object($db) ||
        !method_exists($db, 'begin_transaction') ||
        !is_array($customer) ||
        !is_array($totals)
    ) {
        error_log('F2B SHOP local acknowledgement mail is unavailable.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be sent.',
        );
    }

    $orderId = (int) $orderId;
    if ($orderId < 1 || !$db->begin_transaction()) {
        error_log('F2B SHOP could not start the local acknowledgement mail transaction.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be sent.',
        );
    }

    $checkStatement = $db->prepare('SELECT email_sent_at FROM orders WHERE id = ? LIMIT 1 FOR UPDATE');
    if ($checkStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not check acknowledgement mail state.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be sent.',
        );
    }

    $checkStatement->bind_param('i', $orderId);
    if (!$checkStatement->execute()) {
        $checkStatement->close();
        $db->rollback();
        error_log('F2B SHOP could not read acknowledgement mail state.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be sent.',
        );
    }

    $emailSentAt = null;
    $hasOrder = false;
    $checkStatement->bind_result($emailSentAt);
    $hasOrder = $checkStatement->fetch();
    $checkStatement->close();

    if (!$hasOrder) {
        $db->rollback();
        error_log('F2B SHOP could not find the order for acknowledgement mail.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be sent.',
        );
    }

    if ($emailSentAt !== null) {
        $db->commit();
        return array(
            'status' => 'already_sent',
            'message' => 'The local acknowledgement email has already been handled for this order.',
        );
    }

    $customerName = isset($customer['full_name']) ? (string) $customer['full_name'] : 'F2B SHOP customer';
    $total = isset($totals['total']) ? (float) $totals['total'] : 0.00;
    $subject = 'F2B SHOP order acknowledgement #' . $orderId;
    $body = "Hello " . $customerName . ",\n\n"
        . "Your F2B SHOP order #" . $orderId . " has been recorded.\n"
        . "Total: $" . number_format($total, 2) . "\n"
        . "Status: ORDER_PLACED\n\n"
        . "Next step: our team will prepare a design mock-up for your review.\n\n"
        . "Thank you,\nF2B SHOP";
    $headers = "From: F2B SHOP <no-reply@localhost>\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!mail($recipient, $subject, $body, $headers)) {
        $db->rollback();
        error_log('F2B SHOP local acknowledgement mail failed.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be sent.',
        );
    }

    $updateStatement = $db->prepare('UPDATE orders SET email_sent_at = NOW() WHERE id = ? AND email_sent_at IS NULL');
    if ($updateStatement === false) {
        $db->rollback();
        error_log('F2B SHOP could not record acknowledgement mail state.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be recorded.',
        );
    }

    $updateStatement->bind_param('i', $orderId);
    if (!$updateStatement->execute() || $updateStatement->affected_rows !== 1) {
        $updateStatement->close();
        $db->rollback();
        error_log('F2B SHOP acknowledgement mail state update failed.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be recorded.',
        );
    }
    $updateStatement->close();

    if (!$db->commit()) {
        $db->rollback();
        error_log('F2B SHOP acknowledgement mail transaction commit failed.');
        return array(
            'status' => 'failed',
            'message' => 'The order was saved, but the local acknowledgement email could not be recorded.',
        );
    }

    return array(
        'status' => 'sent',
        'message' => 'A local acknowledgement email was sent successfully.',
    );
}

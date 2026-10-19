<?php

require_once __DIR__ . '/order_access.php';

function f2bReviewReadScalar($input, $key)
{
    if (!is_array($input) || !array_key_exists($key, $input) || !is_scalar($input[$key])) {
        return null;
    }

    return trim((string) $input[$key]);
}

function f2bReviewPositiveId($value)
{
    if ($value === null || $value === '' || !is_scalar($value) || preg_match('/^\d+$/', (string) $value) !== 1) {
        return null;
    }

    $id = (int) $value;
    return $id > 0 ? $id : null;
}

function f2bReviewChangeText($value, $required = false)
{
    if (!is_scalar($value)) {
        return null;
    }

    $value = trim((string) $value);
    if (($required && $value === '') || strlen($value) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
        return null;
    }

    return $value === '' ? null : $value;
}

function f2bReviewSubmit($db, $input)
{
    $mockupId = f2bReviewPositiveId(f2bReviewReadScalar($input, 'mockup_id'));
    $expectedRevision = f2bReviewPositiveId(f2bReviewReadScalar($input, 'expected_revision'));
    $decision = f2bReviewReadScalar($input, 'review_decision');
    $changeRequest = f2bReviewChangeText(f2bReviewReadScalar($input, 'change_request'), $decision === 'request_changes');

    if ($mockupId === null || $expectedRevision === null || !in_array($decision, array('approve', 'request_changes'), true)) {
        return array('success' => false, 'message' => 'That review request is not valid.');
    }

    if ($decision === 'request_changes' && $changeRequest === null) {
        return array('success' => false, 'message' => 'Please describe the changes you would like to request.');
    }

    if (!is_object($db) || !method_exists($db, 'prepare') || !method_exists($db, 'begin_transaction')) {
        return array('success' => false, 'message' => 'The review service is temporarily unavailable.');
    }

    $ownerStatement = $db->prepare('SELECT order_id FROM mockups WHERE id = ? LIMIT 1');
    if ($ownerStatement === false) {
        return array('success' => false, 'message' => 'The review request could not be verified.');
    }

    $ownerStatement->bind_param('i', $mockupId);
    if (!$ownerStatement->execute()) {
        $ownerStatement->close();
        return array('success' => false, 'message' => 'The review request could not be verified.');
    }

    $ownerOrderId = null;
    $ownerStatement->bind_result($ownerOrderId);
    $hasMockup = $ownerStatement->fetch();
    $ownerStatement->close();
    if (!$hasMockup || !f2bOrderHasSessionAccess($ownerOrderId)) {
        return array('success' => false, 'message' => 'That review is not available to this guest session.');
    }

    if (!$db->begin_transaction()) {
        return array('success' => false, 'message' => 'The review could not be started.');
    }

    $storedOrderId = null;
    $storedOrderItemId = null;
    $storedStatus = null;
    $storedRevision = null;
    $mockupStatement = $db->prepare(
        'SELECT m.order_id, m.order_item_id, m.status, m.revision '
        . 'FROM mockups AS m INNER JOIN order_items AS oi '
        . 'ON oi.id = m.order_item_id AND oi.order_id = m.order_id '
        . 'WHERE m.id = ? AND m.order_id = ? FOR UPDATE'
    );
    if ($mockupStatement === false) {
        $db->rollback();
        return array('success' => false, 'message' => 'The review could not be checked.');
    }

    $mockupStatement->bind_param('ii', $mockupId, $ownerOrderId);
    if (!$mockupStatement->execute()) {
        $mockupStatement->close();
        $db->rollback();
        return array('success' => false, 'message' => 'The review could not be checked.');
    }
    $mockupStatement->bind_result($storedOrderId, $storedOrderItemId, $storedStatus, $storedRevision);
    $hasCurrentMockup = $mockupStatement->fetch();
    $mockupStatement->close();

    if (!$hasCurrentMockup || $storedStatus !== 'READY_FOR_REVIEW' || (int) $storedRevision !== $expectedRevision) {
        $db->rollback();
        return array('success' => false, 'message' => 'This mock-up revision is no longer awaiting review.');
    }

    $reviewChangeRequest = $decision === 'request_changes' ? $changeRequest : null;
    $reviewStatement = $db->prepare(
        'INSERT INTO mockup_reviews (mockup_id, decision, change_request, revision) VALUES (?, ?, ?, ?)'
    );
    if ($reviewStatement === false) {
        $db->rollback();
        return array('success' => false, 'message' => 'The review could not be saved.');
    }
    $reviewStatement->bind_param('issi', $mockupId, $decision, $reviewChangeRequest, $expectedRevision);
    if (!$reviewStatement->execute()) {
        $reviewStatement->close();
        $db->rollback();
        return array('success' => false, 'message' => 'The review could not be saved.');
    }
    $reviewStatement->close();

    $nextMockupStatus = $decision === 'approve' ? 'APPROVED' : 'CHANGES_REQUESTED';
    $mockupUpdate = $db->prepare('UPDATE mockups SET status = ? WHERE id = ? AND revision = ?');
    if ($mockupUpdate === false) {
        $db->rollback();
        return array('success' => false, 'message' => 'The mock-up status could not be updated.');
    }
    $mockupUpdate->bind_param('sii', $nextMockupStatus, $mockupId, $expectedRevision);
    if (!$mockupUpdate->execute() || $mockupUpdate->affected_rows !== 1) {
        $mockupUpdate->close();
        $db->rollback();
        return array('success' => false, 'message' => 'The mock-up status could not be updated.');
    }
    $mockupUpdate->close();

    $nextOrderStatus = 'CHANGES_REQUESTED';
    if ($decision === 'approve') {
        $itemCount = 0;
        $approvedCount = 0;
        $approvalStatement = $db->prepare(
            'SELECT COUNT(oi.id), COALESCE(SUM(CASE WHEN m.status = \'APPROVED\' THEN 1 ELSE 0 END), 0) '
            . 'FROM order_items AS oi LEFT JOIN mockups AS m ON m.order_item_id = oi.id '
            . 'WHERE oi.order_id = ?'
        );
        if ($approvalStatement === false) {
            $db->rollback();
            return array('success' => false, 'message' => 'The order status could not be checked.');
        }
        $approvalStatement->bind_param('i', $ownerOrderId);
        if (!$approvalStatement->execute()) {
            $approvalStatement->close();
            $db->rollback();
            return array('success' => false, 'message' => 'The order status could not be checked.');
        }
        $approvalStatement->bind_result($itemCount, $approvedCount);
        $approvalStatement->fetch();
        $approvalStatement->close();
        $nextOrderStatus = (int) $itemCount > 0 && (int) $approvedCount === (int) $itemCount
            ? 'CONFIRMED'
            : 'DESIGN_REVIEW';
    }

    $orderUpdate = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
    if ($orderUpdate === false) {
        $db->rollback();
        return array('success' => false, 'message' => 'The order status could not be updated.');
    }
    $orderUpdate->bind_param('si', $nextOrderStatus, $ownerOrderId);
    if (!$orderUpdate->execute()) {
        $orderUpdate->close();
        $db->rollback();
        return array('success' => false, 'message' => 'The order status could not be updated.');
    }
    $orderUpdate->close();

    if (!$db->commit()) {
        $db->rollback();
        return array('success' => false, 'message' => 'The review could not be committed.');
    }

    return array(
        'success' => true,
        'message' => $decision === 'approve'
            ? 'Your design approval has been saved.'
            : 'Your change request has been saved for the staff team.',
    );
}

<?php

/**
 * Shared guest-order authorization and CSRF helpers.
 *
 * API used by checkout, confirmation, orders and mock-up review pages:
 * - f2bOrderGenerateAccessCode() creates a raw guest code for a new order.
 * - f2bOrderVerifyAccessCode() validates order number + code and authorizes it.
 * - f2bOrderGrantSessionAccess() and f2bOrderHasSessionAccess() manage access.
 * - f2bOrderStoreInitialAccessCode() / f2bOrderConsumeInitialAccessCode() expose
 *   the code only once on the initial successful confirmation page.
 * - f2bCsrfToken() and f2bCsrfValidate() protect state-changing forms.
 */

function f2bOrderAccessStartSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }

    if (session_status() === PHP_SESSION_NONE && PHP_VERSION_ID >= 70300) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params(array(
            'httponly' => true,
            'secure' => $isSecure,
            'samesite' => 'Lax',
        ));
    }

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    return session_status() === PHP_SESSION_ACTIVE;
}

function f2bOrderAccessValidateId($value)
{
    if (is_bool($value) || is_float($value) || is_array($value) || is_object($value)) {
        return null;
    }

    $value = trim((string) $value);
    if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
        return null;
    }

    $orderId = (int) $value;
    return $orderId > 0 ? $orderId : null;
}

function f2bOrderGenerateAccessCode()
{
    try {
        return bin2hex(random_bytes(24));
    } catch (Throwable $exception) {
        error_log('F2B SHOP could not generate an order access code.');
        return null;
    }
}

function f2bOrderAccessHash($accessCode)
{
    if (!is_scalar($accessCode)) {
        return null;
    }

    $accessCode = trim((string) $accessCode);
    return $accessCode === '' ? null : hash('sha256', $accessCode);
}

function f2bOrderReadSessionOrderIds()
{
    if (!f2bOrderAccessStartSession()) {
        return array();
    }

    $authorizedIds = isset($_SESSION['f2b_authorized_order_ids']) && is_array($_SESSION['f2b_authorized_order_ids'])
        ? $_SESSION['f2b_authorized_order_ids']
        : array();
    $cleanIds = array();

    foreach ($authorizedIds as $orderId) {
        $validId = f2bOrderAccessValidateId($orderId);
        if ($validId !== null) {
            $cleanIds[(string) $validId] = $validId;
        }
    }

    $_SESSION['f2b_authorized_order_ids'] = array_values($cleanIds);
    return array_values($cleanIds);
}

function f2bOrderGrantSessionAccess($orderId, $regenerateSession = false)
{
    $orderId = f2bOrderAccessValidateId($orderId);
    if ($orderId === null || !f2bOrderAccessStartSession()) {
        return false;
    }

    if ($regenerateSession) {
        session_regenerate_id(true);
    }

    $authorizedIds = f2bOrderReadSessionOrderIds();
    if (!in_array($orderId, $authorizedIds, true)) {
        $authorizedIds[] = $orderId;
    }

    $_SESSION['f2b_authorized_order_ids'] = $authorizedIds;
    return true;
}

function f2bOrderHasSessionAccess($orderId)
{
    $orderId = f2bOrderAccessValidateId($orderId);
    return $orderId !== null && in_array($orderId, f2bOrderReadSessionOrderIds(), true);
}

function f2bOrderVerifyAccessCode($db, $orderIdValue, $accessCodeValue)
{
    $orderId = f2bOrderAccessValidateId($orderIdValue);
    if ($orderId === null || !is_scalar($accessCodeValue)) {
        return false;
    }

    $accessCode = trim((string) $accessCodeValue);
    if ($accessCode === '' || strlen($accessCode) > 128 || !is_object($db) || !method_exists($db, 'prepare')) {
        return false;
    }

    $statement = $db->prepare('SELECT access_token_hash FROM orders WHERE id = ? LIMIT 1');
    if ($statement === false) {
        return false;
    }

    $statement->bind_param('i', $orderId);
    if (!$statement->execute()) {
        $statement->close();
        return false;
    }

    $storedHash = null;
    $statement->bind_result($storedHash);
    $hasOrder = $statement->fetch();
    $statement->close();

    if (!$hasOrder || !is_string($storedHash) || strlen($storedHash) !== 64) {
        return false;
    }

    $submittedHash = f2bOrderAccessHash($accessCode);
    if ($submittedHash === null || !hash_equals($storedHash, $submittedHash)) {
        return false;
    }

    return f2bOrderGrantSessionAccess($orderId, true);
}

function f2bOrderStoreInitialAccessCode($orderId, $accessCode)
{
    $orderId = f2bOrderAccessValidateId($orderId);
    if ($orderId === null || !is_scalar($accessCode) || !f2bOrderAccessStartSession()) {
        return false;
    }

    $accessCode = trim((string) $accessCode);
    if ($accessCode === '') {
        return false;
    }

    $_SESSION['f2b_initial_order_access'] = array(
        'order_id' => $orderId,
        'access_code' => $accessCode,
    );
    return true;
}

function f2bOrderConsumeInitialAccessCode($orderId)
{
    $orderId = f2bOrderAccessValidateId($orderId);
    if ($orderId === null || !f2bOrderAccessStartSession()) {
        return null;
    }

    $initialAccess = isset($_SESSION['f2b_initial_order_access']) && is_array($_SESSION['f2b_initial_order_access'])
        ? $_SESSION['f2b_initial_order_access']
        : null;
    unset($_SESSION['f2b_initial_order_access']);

    if (
        $initialAccess === null ||
        (int) ($initialAccess['order_id'] ?? 0) !== $orderId ||
        !isset($initialAccess['access_code']) ||
        !is_string($initialAccess['access_code'])
    ) {
        return null;
    }

    return $initialAccess['access_code'];
}

function f2bCsrfToken($scope = 'default')
{
    if (!is_scalar($scope) || !f2bOrderAccessStartSession()) {
        return null;
    }

    $scope = (string) $scope;
    if (!isset($_SESSION['f2b_csrf_tokens']) || !is_array($_SESSION['f2b_csrf_tokens'])) {
        $_SESSION['f2b_csrf_tokens'] = array();
    }

    if (!isset($_SESSION['f2b_csrf_tokens'][$scope]) || !is_string($_SESSION['f2b_csrf_tokens'][$scope])) {
        try {
            $_SESSION['f2b_csrf_tokens'][$scope] = bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            error_log('F2B SHOP could not generate a CSRF token.');
            return null;
        }
    }

    return $_SESSION['f2b_csrf_tokens'][$scope];
}

function f2bCsrfValidate($token, $scope = 'default')
{
    if (!is_scalar($token) || !is_scalar($scope) || !f2bOrderAccessStartSession()) {
        return false;
    }

    $scope = (string) $scope;
    $expected = isset($_SESSION['f2b_csrf_tokens'][$scope]) && is_string($_SESSION['f2b_csrf_tokens'][$scope])
        ? $_SESSION['f2b_csrf_tokens'][$scope]
        : null;
    $submitted = (string) $token;

    return $expected !== null && $submitted !== '' && hash_equals($expected, $submitted);
}

function f2bOrderSetFlash($message, $type = 'success')
{
    if (!f2bOrderAccessStartSession()) {
        return false;
    }

    $_SESSION['f2b_order_flash'] = array(
        'message' => (string) $message,
        'type' => $type === 'error' ? 'error' : 'success',
    );
    return true;
}

function f2bOrderConsumeFlash()
{
    if (!f2bOrderAccessStartSession() || !isset($_SESSION['f2b_order_flash'])) {
        return null;
    }

    $flash = $_SESSION['f2b_order_flash'];
    unset($_SESSION['f2b_order_flash']);
    return is_array($flash) ? $flash : null;
}

f2bOrderAccessStartSession();

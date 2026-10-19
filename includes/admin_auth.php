<?php

require_once __DIR__ . '/order_access.php';

/**
 * Staff authentication uses only the server-provided password hash.
 * No staff credentials are stored in the repository.
 */

function f2bAdminIsConfigured()
{
    $passwordHash = getenv('F2B_STAFF_PASSWORD_HASH');
    return is_string($passwordHash) && $passwordHash !== '';
}

function f2bAdminIsAuthenticated()
{
    return f2bOrderAccessStartSession()
        && isset($_SESSION['f2b_staff_authenticated'])
        && $_SESSION['f2b_staff_authenticated'] === true;
}

function f2bAdminLogin($password)
{
    if (!is_scalar($password) || !f2bAdminIsConfigured()) {
        return false;
    }

    $passwordHash = (string) getenv('F2B_STAFF_PASSWORD_HASH');
    if (!password_verify((string) $password, $passwordHash) || !f2bOrderAccessStartSession()) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['f2b_staff_authenticated'] = true;
    $_SESSION['f2b_staff_login_at'] = time();
    return true;
}

function f2bAdminLogout()
{
    if (!f2bOrderAccessStartSession()) {
        return false;
    }

    unset($_SESSION['f2b_staff_authenticated'], $_SESSION['f2b_staff_login_at']);
    session_regenerate_id(true);
    return true;
}

function f2bAdminRequireAuth()
{
    if (f2bAdminIsAuthenticated()) {
        return true;
    }

    header('Location: admin-login.php', true, 303);
    exit;
}

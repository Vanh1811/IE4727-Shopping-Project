<?php
$pageTitle = 'Staff Login';
$pageDescription = 'Protected F2B SHOP staff access.';
$pageStylesheet = 'css/admin.css';

require_once __DIR__ . '/includes/admin_auth.php';

if (f2bAdminIsAuthenticated()) {
    header('Location: admin-orders.php', true, 303);
    exit;
}

$feedback = f2bOrderConsumeFlash();
$loginError = $feedback !== null && isset($feedback['message']) ? $feedback['message'] : '';
$csrfToken = f2bCsrfToken('staff-login');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;

    if (!f2bCsrfValidate($postedToken, 'staff-login')) {
        $loginError = 'Your session expired. Refresh the page and try again.';
    } elseif (f2bAdminLogin($password)) {
        header('Location: admin-orders.php', true, 303);
        exit;
    } else {
        $loginError = 'Staff access could not be verified.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-page admin-login-page">
    <div class="container narrow-content">
        <section class="admin-login-card" aria-labelledby="admin-login-title">
            <p class="eyebrow">F2B SHOP / Staff area</p>
            <h1 id="admin-login-title">STAFF ACCESS</h1>
            <p>Sign in with the configured staff secret to manage customer orders and design mock-ups.</p>

            <?php if ($loginError !== ''): ?>
                <p class="admin-feedback admin-feedback--error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if (!f2bAdminIsConfigured()): ?>
                <p class="admin-feedback admin-feedback--error" role="status">Staff access is not configured on this server. Set <code>F2B_STAFF_PASSWORD_HASH</code> before signing in.</p>
            <?php endif; ?>

            <form class="admin-login-form" action="admin-login.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="admin-form-field">
                    <label for="staff-password">Staff password</label>
                    <input id="staff-password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="button button--dark button--wide" type="submit">SIGN IN <span aria-hidden="true">&rarr;</span></button>
            </form>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

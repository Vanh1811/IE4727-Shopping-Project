<?php

require_once __DIR__ . '/includes/admin_auth.php';

$isPost = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
$postedToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

if ($isPost && f2bCsrfValidate($postedToken, 'staff-action')) {
    f2bAdminLogout();
    f2bOrderSetFlash('You have been signed out of the staff area.', 'success');
} elseif ($isPost) {
    f2bOrderSetFlash('That staff request could not be verified.', 'error');
}

header('Location: admin-login.php', true, 303);
exit;

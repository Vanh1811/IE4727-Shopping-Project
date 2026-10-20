<?php
/**
 * Shared database connection for the F2B SHOP catalogue.
 *
 * Local defaults keep the setup simple for XAMPP/MAMP. Environment variables
 * can override them without placing deployment credentials in source control.
 */
$dbHost = getenv('F2B_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('F2B_DB_NAME') ?: 'f2b_shop';
$dbUser = getenv('F2B_DB_USER') ?: 'root';
$dbPassword = getenv('F2B_DB_PASSWORD') ?: '';
$dbPort = (int) (getenv('F2B_DB_PORT') ?: 3306);

$db = null;

if (class_exists('mysqli')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $connection = @new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);

    if (!$connection->connect_errno) {
        $connection->set_charset('utf8mb4');
        $db = $connection;
    } else {
        error_log('F2B SHOP database connection could not be established.');
        // $connection->close();
    }
} else {
    error_log('F2B SHOP requires the PHP mysqli extension for catalogue access.');
}

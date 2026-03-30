<?php
$host   = getenv('DB_HOST') ?: "localhost";
$user   = getenv('DB_USER') ?: "root";
$pass   = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "ec_optical_db";
$port   = getenv('DB_PORT') ?: "3306";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

$success = mysqli_real_connect(
    $conn, 
    $host, 
    $user, 
    $pass, 
    $dbname, 
    $port, 
    NULL, 
    MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
);

if (!$success) {
    error_log("Connection failed: " . mysqli_connect_error());
    die("Database Connection Failed. Please contact the administrator.");
}

mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");
date_default_timezone_set('Asia/Manila');
mysqli_set_charset($conn, "utf8mb4");

if (!function_exists('clean')) {
    function clean($conn, $data) {
        if (empty($data)) return "";
        return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
    }
}
?>

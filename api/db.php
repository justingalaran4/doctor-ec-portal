<?php
/**
 * DB Connection for Doctor EC Dental Clinic Portal
 * Optimized for Vercel Deployment & Aiven MySQL
 */

// 1. Kunin ang credentials mula sa Environment Variables (Vercel)
// Kung wala (localhost), gagamit ito ng default values
$host   = getenv('DB_HOST') ?: "localhost";
$user   = getenv('DB_USER') ?: "root";
$pass   = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "ec_optical_db";
$port   = getenv('DB_PORT') ?: "3306";

// 2. Initialize MySQLi for SSL Support
$conn = mysqli_init();

// 3. Mandatory SSL settings para sa Aiven Cloud Database
// Ito ang nag-aayos ng "Connection Refused" o "Insecure connection" errors
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// 4. Establish the Connection
$success = mysqli_real_connect(
    $conn, 
    $host, 
    $user, 
    $pass, 
    $dbname, 
    $port, 
    NULL, 
    MYSQLI_CLIENT_SSL // Flag para pilitin ang SSL connection
);

// 5. Connection Error Handling
if (!$success) {
    // Kapag live na, mas maganda na simple lang ang error para safe
    error_log("Connection failed: " . mysqli_connect_error());
    die("Database Connection Failed. Please contact the administrator.");
}

// 6. Set Timezone & Charset
date_default_timezone_set('Asia/Manila');
mysqli_set_charset($conn, "utf8mb4");

/**
 * Security Helper: Linisin ang input para iwas SQL Injection
 */
function clean($conn, $data) {
    if (empty($data)) return "";
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
}
?>
<?php
/**
 * DB Connection for Doctor EC Optical Clinic Portal
 * Optimized for Vercel Deployment & Aiven MySQL
 */

// 1. Kunin ang credentials mula sa Environment Variables (Vercel)
// Siguraduhin na ang DB_NAME sa Vercel ay 'defaultdb'
$host   = getenv('DB_HOST') ?: "localhost";
$user   = getenv('DB_USER') ?: "root";
$pass   = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "ec_optical_db";
$port   = getenv('DB_PORT') ?: "3306";

// 2. Initialize MySQLi for SSL Support
$conn = mysqli_init();

// 3. Mandatory SSL settings para sa Aiven Cloud Database
// Kinakailangan ito dahil bawal ang insecure connection sa Aiven
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// 4. Establish the Connection
// Dinagdagan natin ng 'MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT' 
// para maiwasan ang "Access Denied" sa Vercel environment.
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

// 5. Connection Error Handling
if (!$success) {
    // Naglalagay ng error log sa Vercel console para sa debugging
    error_log("Connection failed: " . mysqli_connect_error());
    
    // Simple message lang para sa user para hindi makita ang DB credentials
    die("Database Connection Failed. Please contact the administrator.");
}

// 6. Security Override: Primary Key Rule
// Pinapatay nito ang requirement ni Aiven na kailangang may Primary Key ang bawat table
// para hindi mag-error ang iyong mga INSERT queries.
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// 7. Set Timezone & Charset
date_default_timezone_set('Asia/Manila');
mysqli_set_charset($conn, "utf8mb4");

/**
 * Security Helper: Linisin ang input para iwas SQL Injection
 */
if (!function_exists('clean')) {
    function clean($conn, $data) {
        if (empty($data)) return "";
        return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
    }
}
?>

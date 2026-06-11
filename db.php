<?php
// --- ADD THIS LINE TO FIX THE TIMEZONE ---
date_default_timezone_set('Asia/Kuala_Lumpur');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // ← change if needed
define('DB_PASS', '');         // ← change if needed
define('DB_NAME', 'coworking_dbproject');   // ERD database name

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("<h3 style='font-family:sans-serif;color:red;padding:40px'>
         ❌ Database connection failed: " . mysqli_connect_error() . "
         <br><small>Check your DB_USER / DB_PASS in db.php</small></h3>");
}

mysqli_set_charset($conn, 'utf8mb4');

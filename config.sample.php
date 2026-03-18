<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u295139030_sbt');
define('DB_USER', 'u295139030_sbt');
define('DB_PASS', 'Ankit9977498131@@@');

// JWT Secret Key
define('JWT_SECRET', 'sbt-secret-key-2024-secure-token');

// Start session
session_start();

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

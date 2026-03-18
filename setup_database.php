<?php
// Database Setup Script
require_once 'config.php';

echo "<h2>Setting up Database Tables</h2>";

// SQL to create tables if they don't exist
$sql_queries = [
    // Users table
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL UNIQUE,
        `mobile` varchar(20) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('admin','sub_admin','driver','parent') NOT NULL DEFAULT 'parent',
        `driver_code` varchar(20) DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Students table
    "CREATE TABLE IF NOT EXISTS `students` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `parent_id` int(11) NOT NULL,
        `student_name` varchar(255) NOT NULL,
        `student_class` varchar(50) NOT NULL,
        `home_address` text DEFAULT NULL,
        `home_latitude` decimal(10,8) DEFAULT NULL,
        `home_longitude` decimal(11,8) DEFAULT NULL,
        `emergency_contact` varchar(20) DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `parent_id` (`parent_id`),
        FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Buses table
    "CREATE TABLE IF NOT EXISTS `buses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bus_number` varchar(50) NOT NULL UNIQUE,
        `driver_id` int(11) DEFAULT NULL,
        `capacity` int(11) DEFAULT 50,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `driver_id` (`driver_id`),
        FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Routes table
    "CREATE TABLE IF NOT EXISTS `routes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `route_name` varchar(255) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Student Bus Assignments table
    "CREATE TABLE IF NOT EXISTS `student_bus_assignments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `bus_id` int(11) NOT NULL,
        `route_id` int(11) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`),
        KEY `bus_id` (`bus_id`),
        KEY `route_id` (`route_id`),
        FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Trips table
    "CREATE TABLE IF NOT EXISTS `trips` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bus_id` int(11) NOT NULL,
        `route_id` int(11) NOT NULL,
        `driver_id` int(11) NOT NULL,
        `start_time` timestamp NULL DEFAULT NULL,
        `end_time` timestamp NULL DEFAULT NULL,
        `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
        `trip_status` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `bus_id` (`bus_id`),
        KEY `route_id` (`route_id`),
        KEY `driver_id` (`driver_id`),
        FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// Sample data
$sample_data = [
    // Sample buses
    "INSERT IGNORE INTO `buses` (`id`, `bus_number`, `capacity`, `is_active`) VALUES 
    (1, 'SB001', 50, 1),
    (2, 'SB002', 45, 1),
    (3, 'SB003', 40, 1)",

    // Sample routes
    "INSERT IGNORE INTO `routes` (`id`, `route_name`, `is_active`) VALUES 
    (1, 'Route A - City Center', 1),
    (2, 'Route B - Suburbs', 1),
    (3, 'Route C - Industrial Area', 1)"
];

try {
    // Create tables
    foreach ($sql_queries as $sql) {
        $pdo->exec($sql);
        echo "✅ Table created/verified<br>";
    }
    
    echo "<br><h3>Adding Sample Data:</h3>";
    
    // Insert sample data
    foreach ($sample_data as $sql) {
        $pdo->exec($sql);
        echo "✅ Sample data inserted<br>";
    }
    
    echo "<br><h2>✅ Database setup completed successfully!</h2>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Test parent signup at: <a href='parent_signup.php'>parent_signup.php</a></li>";
    echo "<li>Login and add children at: <a href='index.php'>index.php</a></li>";
    echo "<li>Delete this setup file for security</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>

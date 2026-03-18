<?php
// Debug Database Connection and Tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u295139030_sbt');
define('DB_USER', 'u295139030_sbt');
define('DB_PASS', 'Ankit9977498131@@@');

echo "<h2>Database Connection Test</h2>";

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
    echo "✅ Database connection successful!<br><br>";
    
    // Check if students table exists
    echo "<h3>Checking Tables:</h3>";
    
    $tables = ['users', 'students', 'student_bus_assignments', 'buses', 'routes'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            echo "✅ Table '$table' exists<br>";
            
            if ($table == 'students') {
                echo "<strong>Students table structure:</strong><br>";
                while ($row = $stmt->fetch()) {
                    echo "- {$row['Field']} ({$row['Type']})<br>";
                }
                echo "<br>";
            }
        } catch (PDOException $e) {
            echo "❌ Table '$table' missing or error: " . $e->getMessage() . "<br>";
        }
    }
    
    // Test insert query
    echo "<h3>Testing Insert Query:</h3>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO students (parent_id, student_name, student_class, home_address, is_active, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        echo "✅ Insert query prepared successfully (columns: parent_id, student_name, student_class, home_address)<br>";
    } catch (PDOException $e) {
        echo "❌ Insert query error: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<br><h3>Next Steps:</h3>";
echo "1. If connection failed, check database credentials<br>";
echo "2. If tables are missing, create them using the SQL schema<br>";
echo "3. If everything looks good, the issue might be with session or user ID<br>";
?>

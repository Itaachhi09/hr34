<?php
// Basic test to check PHP and database
echo "PHP is working\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

// Test database connection
try {
    $host = 'localhost';
    $dbname = 'hr_integrated_db';
    $username = 'root';
    $password = '';
    
    echo "Attempting database connection...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connected successfully\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Make sure XAMPP MySQL is running and the database exists\n";
}
?>

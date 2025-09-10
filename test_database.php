<?php
/**
 * Database Connectivity Test Script
 * Tests database connection and verifies table structure
 */

echo "HR34 Database Connectivity Test\n";
echo str_repeat("=", 50) . "\n\n";

// Test database connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'php/db_connect.php';
    echo "✓ Database connection successful\n";
    echo "  Host: " . getenv('DB_HOST') ?: 'localhost' . "\n";
    echo "  Database: " . getenv('DB_NAME') ?: 'hr_integrated_db' . "\n";
    echo "  User: " . getenv('DB_USER') ?: 'root' . "\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test table existence
echo "\n2. Testing Table Structure...\n";
$required_tables = [
    'Employees', 'Users', 'Roles', 'departments', 'jobroles',
    'attendancerecords', 'salaries', 'bonuses', 'deductions',
    'PayrollRuns', 'payslips', 'hmoproviders', 'benefitsplans',
    'employeebenefits', 'Claims', 'claimtypes', 'notifications'
];

$existing_tables = [];
$missing_tables = [];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $existing_tables[] = $table;
            echo "✓ Table '{$table}' exists\n";
        } else {
            $missing_tables[] = $table;
            echo "✗ Table '{$table}' missing\n";
        }
    } catch (Exception $e) {
        $missing_tables[] = $table;
        echo "✗ Error checking table '{$table}': " . $e->getMessage() . "\n";
    }
}

// Test data existence
echo "\n3. Testing Data Availability...\n";
$data_tests = [
    'Employees' => 'SELECT COUNT(*) as count FROM Employees',
    'Users' => 'SELECT COUNT(*) as count FROM Users',
    'Roles' => 'SELECT COUNT(*) as count FROM Roles',
    'departments' => 'SELECT COUNT(*) as count FROM departments',
    'jobroles' => 'SELECT COUNT(*) as count FROM jobroles'
];

foreach ($data_tests as $table => $query) {
    try {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch();
        $count = $result['count'];
        echo "✓ Table '{$table}' has {$count} records\n";
    } catch (Exception $e) {
        echo "✗ Error querying table '{$table}': " . $e->getMessage() . "\n";
    }
}

// Test sample queries
echo "\n4. Testing Sample Queries...\n";
$sample_queries = [
    'Employee with Department' => "
        SELECT e.FirstName, e.LastName, d.DepartmentName 
        FROM Employees e 
        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID 
        LIMIT 5
    ",
    'User with Role' => "
        SELECT u.Username, r.RoleName 
        FROM Users u 
        JOIN Roles r ON u.RoleID = r.RoleID 
        LIMIT 5
    ",
    'Active Employees Count' => "
        SELECT COUNT(*) as active_count 
        FROM Employees 
        WHERE Status = 'Active'
    "
];

foreach ($sample_queries as $name => $query) {
    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Query '{$name}' executed successfully\n";
        if (count($results) > 0) {
            echo "  Sample result: " . json_encode($results[0]) . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Query '{$name}' failed: " . $e->getMessage() . "\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "DATABASE TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total required tables: " . count($required_tables) . "\n";
echo "Existing tables: " . count($existing_tables) . "\n";
echo "Missing tables: " . count($missing_tables) . "\n";

if (count($missing_tables) > 0) {
    echo "\nMissing tables:\n";
    foreach ($missing_tables as $table) {
        echo "  - {$table}\n";
    }
}

if (count($missing_tables) == 0) {
    echo "\n✓ All required tables are present!\n";
    echo "✓ Database is ready for API testing!\n";
} else {
    echo "\n✗ Some tables are missing. Please check your database setup.\n";
    echo "Make sure to import the hr_integrated_db.sql file.\n";
}

echo "\nNext steps:\n";
echo "1. Run the API test script: php test_api.php\n";
echo "2. Import the Postman collection\n";
echo "3. Test individual API endpoints\n";
echo str_repeat("=", 50) . "\n";
?>

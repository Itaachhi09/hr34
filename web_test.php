<?php
// Web-accessible test
header('Content-Type: text/plain');

echo "HR34 Web Test\n";
echo "=============\n\n";

echo "1. PHP is working\n";
echo "2. Current time: " . date('Y-m-d H:i:s') . "\n\n";

// Test database connection
try {
    echo "3. Testing database connection...\n";
    require_once 'php/db_connect.php';
    echo "   ✓ Database connection successful\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
    $result = $stmt->fetch();
    echo "   ✓ Users in database: " . $result['count'] . "\n";
    
    // Check for test user
    $stmt = $pdo->query("SELECT * FROM Users WHERE Username = 'testuser'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "   ✓ Test user exists\n";
        echo "   - Username: " . $user['Username'] . "\n";
        echo "   - Active: " . ($user['IsActive'] ? 'Yes' : 'No') . "\n";
        
        if (password_verify('testpass123', $user['PasswordHash'])) {
            echo "   ✓ Password is correct\n";
        } else {
            echo "   ✗ Password is incorrect\n";
        }
    } else {
        echo "   ✗ Test user not found\n";
        echo "   Creating test user...\n";
        
        // Create test user
        $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
        
        // Get or create System Admin role
        $stmt = $pdo->query("SELECT RoleID FROM Roles WHERE RoleName = 'System Admin' LIMIT 1");
        $role = $stmt->fetch();
        
        if (!$role) {
            $pdo->exec("INSERT INTO Roles (RoleName, Description) VALUES ('System Admin', 'System Administrator')");
            $role_id = $pdo->lastInsertId();
        } else {
            $role_id = $role['RoleID'];
        }
        
        // Create test employee
        $pdo->exec("INSERT INTO Employees (FirstName, LastName, Email, HireDate, Status) VALUES ('Test', 'User', 'testuser@company.com', CURDATE(), 'Active')");
        $employee_id = $pdo->lastInsertId();
        
        // Create test user
        $stmt = $pdo->prepare("INSERT INTO Users (Username, PasswordHash, RoleID, EmployeeID, IsActive, IsTwoFactorEnabled) VALUES ('testuser', ?, ?, ?, 1, 0)");
        $stmt->execute([$hashed_password, $role_id, $employee_id]);
        
        echo "   ✓ Test user created successfully\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing API Gateway...\n";
$api_url = 'http://localhost/hr34/api_gateway/';
$response = @file_get_contents($api_url);

if ($response !== false) {
    echo "   ✓ API Gateway is accessible\n";
    $data = json_decode($response, true);
    if ($data && isset($data['service'])) {
        echo "   - Service: " . $data['service'] . "\n";
    }
} else {
    echo "   ✗ API Gateway is not accessible\n";
    echo "   Make sure XAMPP Apache is running\n";
}

echo "\n5. Test Login Credentials:\n";
echo "   Username: testuser\n";
echo "   Password: testpass123\n";
echo "   Role: System Admin\n";

echo "\nTest completed!\n";
?>

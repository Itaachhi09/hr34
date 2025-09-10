<?php
echo "Simple Database Test\n";
echo "==================\n\n";

try {
    echo "1. Including db_connect.php...\n";
    require_once 'php/db_connect.php';
    echo "✓ Database connection file loaded\n";
    
    echo "2. Testing database query...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
    $result = $stmt->fetch();
    echo "✓ Database query successful\n";
    echo "  Users in database: " . $result['count'] . "\n";
    
    echo "3. Checking for test user...\n";
    $stmt = $pdo->query("SELECT * FROM Users WHERE Username = 'testuser'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Test user found\n";
        echo "  Username: " . $user['Username'] . "\n";
        echo "  Active: " . ($user['IsActive'] ? 'Yes' : 'No') . "\n";
        
        // Test password
        if (password_verify('testpass123', $user['PasswordHash'])) {
            echo "✓ Password is correct\n";
        } else {
            echo "✗ Password is incorrect\n";
        }
    } else {
        echo "✗ Test user not found\n";
        echo "Creating test user...\n";
        
        // Create test user
        $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
        
        // Get System Admin role
        $stmt = $pdo->query("SELECT RoleID FROM Roles WHERE RoleName = 'System Admin' LIMIT 1");
        $role = $stmt->fetch();
        
        if (!$role) {
            echo "Creating System Admin role...\n";
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
        
        echo "✓ Test user created successfully\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>

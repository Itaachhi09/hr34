<?php
/**
 * Create Test Account Script
 * Creates a test user account for API testing and login form access
 */

// Include database connection
require_once 'php/db_connect.php';

echo "HR34 Test Account Creation\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // Check if test account already exists
    $sql_check = "SELECT COUNT(*) as count FROM Users WHERE Username = 'testuser'";
    $stmt_check = $pdo->query($sql_check);
    $existing_count = $stmt_check->fetchColumn();
    
    if ($existing_count > 0) {
        echo "Test account already exists. Updating password...\n";
        
        // Update existing test account
        $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
        $sql_update = "UPDATE Users SET PasswordHash = :password_hash, IsActive = 1 WHERE Username = 'testuser'";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':password_hash', $hashed_password);
        $stmt_update->execute();
        
        echo "✓ Test account password updated successfully!\n";
    } else {
        echo "Creating new test account...\n";
        
        // First, ensure we have a role (System Admin)
        $sql_role = "SELECT RoleID FROM Roles WHERE RoleName = 'System Admin' LIMIT 1";
        $stmt_role = $pdo->query($sql_role);
        $role = $stmt_role->fetch(PDO::FETCH_ASSOC);
        
        if (!$role) {
            // Create System Admin role if it doesn't exist
            $sql_create_role = "INSERT INTO Roles (RoleName, Description) VALUES ('System Admin', 'System Administrator with full access')";
            $pdo->exec($sql_create_role);
            $role_id = $pdo->lastInsertId();
            echo "✓ Created System Admin role\n";
        } else {
            $role_id = $role['RoleID'];
            echo "✓ Using existing System Admin role (ID: {$role_id})\n";
        }
        
        // Create test employee first
        $sql_employee = "INSERT INTO Employees (FirstName, LastName, Email, HireDate, DepartmentID, JobRoleID, Status) 
                        VALUES ('Test', 'User', 'testuser@company.com', CURDATE(), 1, 1, 'Active')";
        $pdo->exec($sql_employee);
        $employee_id = $pdo->lastInsertId();
        echo "✓ Created test employee (ID: {$employee_id})\n";
        
        // Create test user account
        $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
        $sql_user = "INSERT INTO Users (Username, PasswordHash, RoleID, EmployeeID, IsActive, IsTwoFactorEnabled) 
                     VALUES ('testuser', :password_hash, :role_id, :employee_id, 1, 0)";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->bindParam(':password_hash', $hashed_password);
        $stmt_user->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt_user->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt_user->execute();
        
        echo "✓ Created test user account successfully!\n";
    }
    
    // Display login credentials
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "TEST ACCOUNT CREDENTIALS\n";
    echo str_repeat("=", 50) . "\n";
    echo "Username: testuser\n";
    echo "Password: testpass123\n";
    echo "Role: System Admin\n";
    echo "2FA: Disabled (for easy testing)\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Test the login
    echo "Testing login credentials...\n";
    
    $sql_test = "SELECT u.UserID, u.Username, u.PasswordHash, r.RoleName, e.FirstName, e.LastName 
                 FROM Users u 
                 JOIN Roles r ON u.RoleID = r.RoleID 
                 LEFT JOIN Employees e ON u.EmployeeID = e.EmployeeID 
                 WHERE u.Username = 'testuser' AND u.IsActive = 1";
    
    $stmt_test = $pdo->query($sql_test);
    $user = $stmt_test->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify('testpass123', $user['PasswordHash'])) {
        echo "✓ Login test successful!\n";
        echo "  User ID: {$user['UserID']}\n";
        echo "  Full Name: {$user['FirstName']} {$user['LastName']}\n";
        echo "  Role: {$user['RoleName']}\n";
    } else {
        echo "✗ Login test failed!\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "NEXT STEPS\n";
    echo str_repeat("=", 50) . "\n";
    echo "1. Use these credentials in your login form\n";
    echo "2. Test API endpoints with this account\n";
    echo "3. Use Postman collection with these credentials\n";
    echo "4. The account has System Admin privileges\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error creating test account: " . $e->getMessage() . "\n";
    echo "Make sure your database is properly set up and accessible.\n";
}
?>

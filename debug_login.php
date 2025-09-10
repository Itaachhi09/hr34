<?php
/**
 * Debug Login Issues
 * Comprehensive debugging for login problems
 */

echo "HR34 Login Debug Script\n";
echo str_repeat("=", 50) . "\n\n";

// Step 1: Check database connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'php/db_connect.php';
    echo "✓ Database connection successful\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
    $result = $stmt->fetch();
    echo "✓ Users table accessible - {$result['count']} users found\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Check if test user exists
echo "\n2. Checking Test User...\n";
try {
    $sql = "SELECT u.*, r.RoleName, e.FirstName, e.LastName 
            FROM Users u 
            LEFT JOIN Roles r ON u.RoleID = r.RoleID 
            LEFT JOIN Employees e ON u.EmployeeID = e.EmployeeID 
            WHERE u.Username = 'testuser'";
    
    $stmt = $pdo->query($sql);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ Test user found:\n";
        echo "  - User ID: {$user['UserID']}\n";
        echo "  - Username: {$user['Username']}\n";
        echo "  - Role: {$user['RoleName']}\n";
        echo "  - Active: " . ($user['IsActive'] ? 'Yes' : 'No') . "\n";
        echo "  - 2FA: " . ($user['IsTwoFactorEnabled'] ? 'Yes' : 'No') . "\n";
        echo "  - Employee: {$user['FirstName']} {$user['LastName']}\n";
        
        // Test password
        if (password_verify('testpass123', $user['PasswordHash'])) {
            echo "✓ Password verification successful\n";
        } else {
            echo "✗ Password verification failed\n";
            echo "  - Stored hash: " . substr($user['PasswordHash'], 0, 20) . "...\n";
        }
    } else {
        echo "✗ Test user not found\n";
        echo "Creating test user...\n";
        
        // Create test user
        $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
        
        // Get or create System Admin role
        $sql_role = "SELECT RoleID FROM Roles WHERE RoleName = 'System Admin' LIMIT 1";
        $stmt_role = $pdo->query($sql_role);
        $role = $stmt_role->fetch(PDO::FETCH_ASSOC);
        
        if (!$role) {
            $sql_create_role = "INSERT INTO Roles (RoleName, Description) VALUES ('System Admin', 'System Administrator')";
            $pdo->exec($sql_create_role);
            $role_id = $pdo->lastInsertId();
        } else {
            $role_id = $role['RoleID'];
        }
        
        // Create test employee
        $sql_employee = "INSERT INTO Employees (FirstName, LastName, Email, HireDate, Status) 
                        VALUES ('Test', 'User', 'testuser@company.com', CURDATE(), 'Active')";
        $pdo->exec($sql_employee);
        $employee_id = $pdo->lastInsertId();
        
        // Create test user
        $sql_user = "INSERT INTO Users (Username, PasswordHash, RoleID, EmployeeID, IsActive, IsTwoFactorEnabled) 
                     VALUES ('testuser', :password_hash, :role_id, :employee_id, 1, 0)";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->bindParam(':password_hash', $hashed_password);
        $stmt_user->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt_user->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt_user->execute();
        
        echo "✓ Test user created successfully\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error checking/creating test user: " . $e->getMessage() . "\n";
}

// Step 3: Test API Gateway
echo "\n3. Testing API Gateway...\n";
$api_url = 'http://localhost/hr34/api_gateway/';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "✗ cURL Error: " . $error . "\n";
} elseif ($http_code == 200) {
    echo "✓ API Gateway accessible (HTTP {$http_code})\n";
    $data = json_decode($response, true);
    if ($data && isset($data['service'])) {
        echo "  - Service: {$data['service']}\n";
        echo "  - Version: {$data['version']}\n";
    }
} else {
    echo "✗ API Gateway not accessible (HTTP {$http_code})\n";
    echo "  Response: " . substr($response, 0, 200) . "\n";
}

// Step 4: Test Login Endpoint
echo "\n4. Testing Login Endpoint...\n";
$login_url = 'http://localhost/hr34/api_gateway/api/v1/auth/login';
$login_data = json_encode(['username' => 'testuser', 'password' => 'testpass123']);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $login_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $login_data,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($login_data)
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true
]);
$login_response = curl_exec($ch);
$login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$login_error = curl_error($ch);
curl_close($ch);

if ($login_error) {
    echo "✗ cURL Error: " . $login_error . "\n";
} else {
    echo "Login endpoint response (HTTP {$login_http_code}):\n";
    echo "Response: " . $login_response . "\n";
    
    $login_data = json_decode($login_response, true);
    if ($login_data) {
        if (isset($login_data['message'])) {
            echo "✓ Login successful: {$login_data['message']}\n";
            if (isset($login_data['user'])) {
                echo "  - User: {$login_data['user']['username']}\n";
                echo "  - Role: {$login_data['user']['role_name']}\n";
            }
        } elseif (isset($login_data['error'])) {
            echo "✗ Login failed: {$login_data['error']}\n";
        }
    }
}

// Step 5: Check file permissions
echo "\n5. Checking File Permissions...\n";
$files_to_check = [
    'api_gateway/index.php',
    'microservices/auth_service.php',
    'php/db_connect.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ {$file} exists\n";
        if (is_readable($file)) {
            echo "  - Readable: Yes\n";
        } else {
            echo "  - Readable: No\n";
        }
    } else {
        echo "✗ {$file} missing\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "DEBUG SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "If you see any ✗ errors above, those need to be fixed first.\n";
echo "Common issues:\n";
echo "1. XAMPP not running (Apache + MySQL)\n";
echo "2. Database not imported\n";
echo "3. Wrong file paths\n";
echo "4. PHP errors in files\n";
echo str_repeat("=", 50) . "\n";
?>

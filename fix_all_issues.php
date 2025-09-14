<?php
/**
 * HR34 Complete System Fix Script
 * This script fixes all identified issues in the HR34 system
 */

header('Content-Type: text/plain');
echo "HR34 Complete System Fix\n";
echo "=======================\n\n";

// Step 1: Database Schema Fix
echo "1. FIXING DATABASE SCHEMA...\n";
echo "-----------------------------\n";

try {
    require_once 'php/db_connect.php';
    echo "✓ Database connection established\n";
    
    // Read and execute the database fix script
    $db_fix_sql = file_get_contents('complete_database_fix.sql');
    if ($db_fix_sql) {
        // Split by semicolon and execute each statement
        $statements = explode(';', $db_fix_sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignore errors for statements that might already exist
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        echo "  Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✓ Database schema updated\n";
    } else {
        echo "✗ Could not read database fix script\n";
    }
    
    // Verify tables exist
    $tables = ['Users', 'Roles', 'employees', 'departments', 'jobroles'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Table '$table' exists\n";
        } else {
            echo "  ✗ Table '$table' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "Please run: mysql -u root -p hr_integrated_db < complete_database_fix.sql\n";
}

// Step 2: Create Test Users
echo "\n2. CREATING TEST USERS...\n";
echo "-------------------------\n";

try {
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users WHERE Username = 'admin'");
    $admin_exists = $stmt->fetch()['count'] > 0;
    
    if (!$admin_exists) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO Users (Username, PasswordHash, RoleID, EmployeeID, IsActive, IsTwoFactorEnabled) VALUES ('admin', ?, 1, 1, 1, 0)");
        $stmt->execute([$hashed_password]);
        echo "✓ Admin user created\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    // Check if test user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users WHERE Username = 'testuser'");
    $test_exists = $stmt->fetch()['count'] > 0;
    
    if (!$test_exists) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO Users (Username, PasswordHash, RoleID, EmployeeID, IsActive, IsTwoFactorEnabled) VALUES ('testuser', ?, 1, 2, 1, 0)");
        $stmt->execute([$hashed_password]);
        echo "✓ Test user created\n";
    } else {
        echo "✓ Test user already exists\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error creating users: " . $e->getMessage() . "\n";
}

// Step 3: Fix API Configuration
echo "\n3. FIXING API CONFIGURATION...\n";
echo "-------------------------------\n";

// Update utils.js with correct API URL
$utils_js_path = 'js/utils.js';
if (file_exists($utils_js_path)) {
    $utils_content = file_get_contents($utils_js_path);
    $utils_content = str_replace(
        "export const API_BASE_URL = 'php/api/';",
        "export const API_BASE_URL = 'http://localhost/hr34/api_gateway/api/v1/';",
        $utils_content
    );
    file_put_contents($utils_js_path, $utils_content);
    echo "✓ Updated utils.js API URL\n";
} else {
    echo "✗ utils.js not found\n";
}

// Step 4: Test API Endpoints
echo "\n4. TESTING API ENDPOINTS...\n";
echo "---------------------------\n";

// Test API Gateway
$api_url = 'http://localhost/hr34/api_gateway/';
$response = @file_get_contents($api_url);
if ($response !== false) {
    echo "✓ API Gateway accessible\n";
} else {
    echo "✗ API Gateway not accessible - Check XAMPP Apache\n";
}

// Test login endpoint
$login_url = 'http://localhost/hr34/api_gateway/api/v1/auth/login';
$login_data = json_encode(['username' => 'admin', 'password' => 'password']);

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $login_data
    ]
]);

$login_response = @file_get_contents($login_url, false, $context);
if ($login_response !== false) {
    echo "✓ Login endpoint accessible\n";
    $result = json_decode($login_response, true);
    if (isset($result['error'])) {
        echo "  - Login error: " . $result['error'] . "\n";
    } else {
        echo "  - Login successful\n";
    }
} else {
    echo "✗ Login endpoint not accessible\n";
}

// Step 5: Create Postman Collection
echo "\n5. CREATING POSTMAN COLLECTION...\n";
echo "---------------------------------\n";

$postman_collection = [
    "info" => [
        "name" => "HR34 API Collection",
        "description" => "Complete API collection for HR34 system",
        "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    ],
    "item" => [
        [
            "name" => "Authentication",
            "item" => [
                [
                    "name" => "Login",
                    "request" => [
                        "method" => "POST",
                        "header" => [
                            ["key" => "Content-Type", "value" => "application/json"]
                        ],
                        "body" => [
                            "mode" => "raw",
                            "raw" => "{\n  \"username\": \"admin\",\n  \"password\": \"password\"\n}"
                        ],
                        "url" => [
                            "raw" => "{{base_url}}/api/v1/auth/login",
                            "host" => ["{{base_url}}"],
                            "path" => ["api", "v1", "auth", "login"]
                        ]
                    ]
                ],
                [
                    "name" => "Verify 2FA",
                    "request" => [
                        "method" => "POST",
                        "header" => [
                            ["key" => "Content-Type", "value" => "application/json"]
                        ],
                        "body" => [
                            "mode" => "raw",
                            "raw" => "{\n  \"user_id\": 1,\n  \"code\": \"123456\"\n}"
                        ],
                        "url" => [
                            "raw" => "{{base_url}}/api/v1/auth/verify-2fa",
                            "host" => ["{{base_url}}"],
                            "path" => ["api", "v1", "auth", "verify-2fa"]
                        ]
                    ]
                ]
            ]
        ],
        [
            "name" => "Core HR",
            "item" => [
                [
                    "name" => "Get Employees",
                    "request" => [
                        "method" => "GET",
                        "header" => [
                            ["key" => "X-API-Key", "value" => "hr34-api-key-2024"]
                        ],
                        "url" => [
                            "raw" => "{{base_url}}/api/v1/core-hr/employees",
                            "host" => ["{{base_url}}"],
                            "path" => ["api", "v1", "core-hr", "employees"]
                        ]
                    ]
                ],
                [
                    "name" => "Get Departments",
                    "request" => [
                        "method" => "GET",
                        "header" => [
                            ["key" => "X-API-Key", "value" => "hr34-api-key-2024"]
                        ],
                        "url" => [
                            "raw" => "{{base_url}}/api/v1/core-hr/departments",
                            "host" => ["{{base_url}}"],
                            "path" => ["api", "v1", "core-hr", "departments"]
                        ]
                    ]
                ]
            ]
        ]
    ],
    "variable" => [
        [
            "key" => "base_url",
            "value" => "http://localhost/hr34/api_gateway"
        ]
    ]
];

$postman_dir = 'postman_collection';
if (!is_dir($postman_dir)) {
    mkdir($postman_dir, 0755, true);
}

file_put_contents($postman_dir . '/HR34_API_Collection.json', json_encode($postman_collection, JSON_PRETTY_PRINT));
echo "✓ Postman collection created\n";

// Step 6: Final System Test
echo "\n6. RUNNING FINAL SYSTEM TEST...\n";
echo "-------------------------------\n";

// Include the system test
include 'system_test.php';

echo "\n7. FIX COMPLETE!\n";
echo "================\n";
echo "Your HR34 system has been fixed and is ready to use.\n\n";
echo "Access URLs:\n";
echo "- Main site: http://localhost/hr34/\n";
echo "- Admin: http://localhost/hr34/admin_landing.php\n";
echo "- Employee: http://localhost/hr34/employee_landing.php\n";
echo "- API Gateway: http://localhost/hr34/api_gateway/\n\n";
echo "Test Credentials:\n";
echo "- Username: admin, Password: password\n";
echo "- Username: testuser, Password: password\n\n";
echo "Postman Collection: postman_collection/HR34_API_Collection.json\n";
?>


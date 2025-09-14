<?php
/**
 * Comprehensive System Test Script
 * Tests all components: Database, API Gateway, Authentication, Frontend
 */

header('Content-Type: text/plain');
echo "HR34 Complete System Test\n";
echo "========================\n\n";

$errors = [];
$warnings = [];

// Test 1: Database Connection and Schema
echo "1. DATABASE TESTS\n";
echo "-----------------\n";

try {
    require_once 'php/db_connect.php';
    echo "✓ Database connection successful\n";
    
    // Test essential tables
    $essential_tables = ['Users', 'Roles', 'employees', 'departments', 'jobroles'];
    foreach ($essential_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
            
            // Test table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "  - Columns: " . implode(', ', $columns) . "\n";
        } else {
            $errors[] = "Table '$table' missing";
            echo "✗ Table '$table' missing\n";
        }
    }
    
    // Test data integrity
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
    $user_count = $stmt->fetch()['count'];
    echo "✓ Users in database: $user_count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
    $emp_count = $stmt->fetch()['count'];
    echo "✓ Employees in database: $emp_count\n";
    
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// Test 2: API Gateway
echo "\n2. API GATEWAY TESTS\n";
echo "--------------------\n";

$api_base = 'http://localhost/hr34/api_gateway/';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($api_base, false, $context);
if ($response !== false) {
    echo "✓ API Gateway accessible\n";
    $data = json_decode($response, true);
    if ($data && isset($data['service'])) {
        echo "  - Service: " . $data['service'] . "\n";
        echo "  - Version: " . $data['version'] . "\n";
    }
} else {
    $errors[] = "API Gateway not accessible";
    echo "✗ API Gateway not accessible\n";
}

// Test 3: Authentication Endpoints
echo "\n3. AUTHENTICATION TESTS\n";
echo "------------------------\n";

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
    $login_result = json_decode($login_response, true);
    if (isset($login_result['error'])) {
        echo "  - Login error: " . $login_result['error'] . "\n";
        $warnings[] = "Login failed: " . $login_result['error'];
    } else {
        echo "  - Login successful\n";
    }
} else {
    $errors[] = "Login endpoint not accessible";
    echo "✗ Login endpoint not accessible\n";
}

// Test 4: Core HR Endpoints
echo "\n4. CORE HR API TESTS\n";
echo "--------------------\n";

$core_hr_url = 'http://localhost/hr34/api_gateway/api/v1/core-hr/employees';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'X-API-Key: hr34-api-key-2024'
    ]
]);

$core_hr_response = @file_get_contents($core_hr_url, false, $context);
if ($core_hr_response !== false) {
    echo "✓ Core HR endpoint accessible\n";
    $employees = json_decode($core_hr_response, true);
    if (is_array($employees)) {
        echo "  - Retrieved " . count($employees) . " employee records\n";
    }
} else {
    $errors[] = "Core HR endpoint not accessible";
    echo "✗ Core HR endpoint not accessible\n";
}

// Test 5: File System Tests
echo "\n5. FILE SYSTEM TESTS\n";
echo "--------------------\n";

$critical_files = [
    'api_gateway/index.php' => 'API Gateway',
    'microservices/auth_service.php' => 'Auth Service',
    'microservices/core_hr_service.php' => 'Core HR Service',
    'php/db_connect.php' => 'Database Connection',
    'php/api/auth/login.php' => 'Login API',
    'js/main.js' => 'Main JavaScript',
    'js/utils.js' => 'Utils JavaScript',
    'index.php' => 'Main Index',
    'admin_landing.php' => 'Admin Landing',
    'employee_landing.php' => 'Employee Landing'
];

foreach ($critical_files as $file => $description) {
    if (file_exists($file)) {
        echo "✓ $description ($file)\n";
        if (is_readable($file)) {
            echo "  - Readable: Yes\n";
        } else {
            $warnings[] = "$description not readable";
            echo "  - Readable: No\n";
        }
    } else {
        $errors[] = "$description missing";
        echo "✗ $description ($file) missing\n";
    }
}

// Test 6: Frontend Tests
echo "\n6. FRONTEND TESTS\n";
echo "-----------------\n";

// Check if main.js has correct API URL
$main_js_content = file_get_contents('js/main.js');
if (strpos($main_js_content, 'API_BASE_URL') !== false) {
    echo "✓ Main.js has API configuration\n";
} else {
    $warnings[] = "Main.js missing API configuration";
    echo "✗ Main.js missing API configuration\n";
}

// Check if utils.js has correct API URL
$utils_js_content = file_get_contents('js/utils.js');
if (strpos($utils_js_content, 'api_gateway') !== false) {
    echo "✓ Utils.js has correct API Gateway URL\n";
} else {
    $warnings[] = "Utils.js has incorrect API URL";
    echo "✗ Utils.js has incorrect API URL\n";
}

// Test 7: Postman Collection
echo "\n7. POSTMAN COLLECTION TESTS\n";
echo "---------------------------\n";

if (file_exists('postman_collection/HR34_API_Collection.json')) {
    echo "✓ Postman collection exists\n";
    $collection = json_decode(file_get_contents('postman_collection/HR34_API_Collection.json'), true);
    if ($collection && isset($collection['item'])) {
        echo "  - Collection has " . count($collection['item']) . " items\n";
    }
} else {
    $warnings[] = "Postman collection missing";
    echo "✗ Postman collection missing\n";
}

// Summary
echo "\n8. TEST SUMMARY\n";
echo "===============\n";

if (empty($errors)) {
    echo "✓ ALL CRITICAL TESTS PASSED\n";
} else {
    echo "✗ CRITICAL ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠ WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

echo "\n9. RECOMMENDED FIXES\n";
echo "====================\n";

if (in_array("Table 'Users' missing", $errors) || in_array("Table 'Roles' missing", $errors)) {
    echo "1. Run database schema fix:\n";
    echo "   mysql -u root -p hr_integrated_db < complete_database_fix.sql\n\n";
}

if (in_array("API Gateway not accessible", $errors)) {
    echo "2. Start XAMPP services:\n";
    echo "   - Open XAMPP Control Panel\n";
    echo "   - Start Apache\n";
    echo "   - Start MySQL\n\n";
}

if (in_array("Login endpoint not accessible", $errors)) {
    echo "3. Check API Gateway routing:\n";
    echo "   - Verify api_gateway/index.php exists\n";
    echo "   - Check .htaccess configuration\n\n";
}

echo "4. Test login credentials:\n";
echo "   Username: admin\n";
echo "   Password: password\n";
echo "   Username: testuser\n";
echo "   Password: password\n\n";

echo "5. Access URLs:\n";
echo "   - Main site: http://localhost/hr34/\n";
echo "   - Admin: http://localhost/hr34/admin_landing.php\n";
echo "   - Employee: http://localhost/hr34/employee_landing.php\n";
echo "   - API Gateway: http://localhost/hr34/api_gateway/\n\n";

echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
?>


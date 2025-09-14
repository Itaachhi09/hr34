<?php
/**
 * API Connection Test and Fix Script
 * Tests all API endpoints and provides fixes for common issues
 */

header('Content-Type: text/plain');
echo "HR34 API Connection Test\n";
echo "========================\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'php/db_connect.php';
    echo "   ✓ Database connection successful\n";
    
    // Test if Users and Roles tables exist
    $tables = ['Users', 'Roles', 'employees', 'departments', 'jobroles'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' missing\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
    echo "   Run: mysql -u root -p hr_integrated_db < fix_database_schema.sql\n";
}

// Test 2: API Gateway
echo "\n2. Testing API Gateway...\n";
$api_url = 'http://localhost/hr34/api_gateway/';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($api_url, false, $context);
if ($response !== false) {
    echo "   ✓ API Gateway accessible\n";
    $data = json_decode($response, true);
    if ($data && isset($data['service'])) {
        echo "   - Service: " . $data['service'] . "\n";
        echo "   - Version: " . $data['version'] . "\n";
    }
} else {
    echo "   ✗ API Gateway not accessible\n";
    echo "   Check if XAMPP Apache is running\n";
}

// Test 3: Authentication Endpoints
echo "\n3. Testing Authentication Endpoints...\n";

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
    echo "   ✓ Login endpoint accessible\n";
    $login_result = json_decode($login_response, true);
    if (isset($login_result['error'])) {
        echo "   - Login error: " . $login_result['error'] . "\n";
    } else {
        echo "   - Login response received\n";
    }
} else {
    echo "   ✗ Login endpoint not accessible\n";
}

// Test 4: Core HR Endpoints
echo "\n4. Testing Core HR Endpoints...\n";
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
    echo "   ✓ Core HR endpoint accessible\n";
} else {
    echo "   ✗ Core HR endpoint not accessible\n";
}

// Test 5: Check file permissions
echo "\n5. Checking File Permissions...\n";
$critical_files = [
    'api_gateway/index.php',
    'microservices/auth_service.php',
    'php/db_connect.php',
    'php/api/auth/login.php'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        echo "   ✓ $file exists\n";
        if (is_readable($file)) {
            echo "     - Readable: Yes\n";
        } else {
            echo "     - Readable: No\n";
        }
    } else {
        echo "   ✗ $file missing\n";
    }
}

echo "\n6. Postman Collection Test...\n";
if (file_exists('postman_collection/HR34_API_Collection.json')) {
    echo "   ✓ Postman collection exists\n";
    $collection = json_decode(file_get_contents('postman_collection/HR34_API_Collection.json'), true);
    if ($collection && isset($collection['item'])) {
        echo "   - Collection has " . count($collection['item']) . " items\n";
    }
} else {
    echo "   ✗ Postman collection missing\n";
}

echo "\n=== API Test Summary ===\n";
echo "If you see any ✗ errors above, those need to be fixed.\n";
echo "Common fixes:\n";
echo "1. Start XAMPP (Apache + MySQL)\n";
echo "2. Import database schema: mysql -u root -p hr_integrated_db < fix_database_schema.sql\n";
echo "3. Check file permissions\n";
echo "4. Verify API Gateway routing\n";
?>


<?php
/**
 * Test Login Script
 * Tests the login functionality with the created test account
 */

// Configuration
$base_url = 'http://localhost/hr34/api_gateway';
$test_username = 'testuser';
$test_password = 'testpass123';

echo "HR34 Login Test\n";
echo str_repeat("=", 50) . "\n\n";

function testLogin($url, $username, $password) {
    $ch = curl_init();
    
    $data = json_encode([
        'username' => $username,
        'password' => $password
    ]);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ],
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error
    ];
}

// Test 1: API Gateway Health Check
echo "1. Testing API Gateway...\n";
$health_url = $base_url . '/';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $health_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);
$health_response = curl_exec($ch);
$health_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($health_code == 200) {
    echo "✓ API Gateway is accessible\n";
} else {
    echo "✗ API Gateway is not accessible (HTTP {$health_code})\n";
    echo "Make sure XAMPP is running and the API gateway is accessible.\n";
    exit(1);
}

// Test 2: Login via API
echo "\n2. Testing Login via API...\n";
$login_url = $base_url . '/api/v1/auth/login';
$login_result = testLogin($login_url, $test_username, $test_password);

if ($login_result['error']) {
    echo "✗ cURL Error: " . $login_result['error'] . "\n";
} elseif ($login_result['http_code'] == 200) {
    $response_data = json_decode($login_result['response'], true);
    if ($response_data && isset($response_data['message'])) {
        echo "✓ Login successful!\n";
        echo "  Message: " . $response_data['message'] . "\n";
        if (isset($response_data['user'])) {
            echo "  User: " . $response_data['user']['username'] . "\n";
            echo "  Role: " . $response_data['user']['role_name'] . "\n";
            echo "  Full Name: " . $response_data['user']['full_name'] . "\n";
        }
    } else {
        echo "✗ Login failed - Invalid response format\n";
        echo "Response: " . $login_result['response'] . "\n";
    }
} else {
    echo "✗ Login failed (HTTP {$login_result['http_code']})\n";
    echo "Response: " . $login_result['response'] . "\n";
}

// Test 3: Test API endpoint with session
echo "\n3. Testing API endpoint access...\n";
$test_url = $base_url . '/api/v1/core-hr/employees';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $test_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-API-Key: hr34-api-key-2024'
    ],
    CURLOPT_TIMEOUT => 10
]);
$api_response = curl_exec($ch);
$api_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($api_code == 200) {
    echo "✓ API endpoint accessible\n";
    $api_data = json_decode($api_response, true);
    if (is_array($api_data)) {
        echo "  Retrieved " . count($api_data) . " employee records\n";
    }
} else {
    echo "✗ API endpoint not accessible (HTTP {$api_code})\n";
    echo "Response: " . $api_response . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "LOGIN TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Test Account: {$test_username}\n";
echo "Password: {$test_password}\n";
echo "API Base URL: {$base_url}\n";
echo "\nYou can now use these credentials in:\n";
echo "1. Your login form\n";
echo "2. Postman collection\n";
echo "3. API testing scripts\n";
echo str_repeat("=", 50) . "\n";
?>

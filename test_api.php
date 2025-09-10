<?php
/**
 * HR34 API Test Script
 * Tests all microservices and database connectivity
 */

// Configuration
$base_url = 'http://localhost/hr34/api_gateway';
$api_key = 'hr34-api-key-2024';
$test_username = 'admin';
$test_password = 'password';

// Test results
$tests = [];
$passed = 0;
$failed = 0;

function runTest($name, $url, $method = 'GET', $data = null, $headers = []) {
    global $tests, $passed, $failed, $api_key;
    
    $ch = curl_init();
    
    $default_headers = [
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json'
    ];
    
    $headers = array_merge($default_headers, $headers);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $result = [
        'name' => $name,
        'url' => $url,
        'method' => $method,
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error,
        'success' => false
    ];
    
    if ($error) {
        $result['error_message'] = "cURL Error: " . $error;
    } elseif ($http_code >= 200 && $http_code < 300) {
        $result['success'] = true;
        $passed++;
    } else {
        $result['error_message'] = "HTTP Error: " . $http_code;
        $failed++;
    }
    
    $tests[] = $result;
    return $result;
}

function printTestResults() {
    global $tests, $passed, $failed;
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "HR34 API TEST RESULTS\n";
    echo str_repeat("=", 80) . "\n";
    echo "Total Tests: " . count($tests) . "\n";
    echo "Passed: " . $passed . "\n";
    echo "Failed: " . $failed . "\n";
    echo "Success Rate: " . round(($passed / count($tests)) * 100, 2) . "%\n";
    echo str_repeat("=", 80) . "\n\n";
    
    foreach ($tests as $test) {
        $status = $test['success'] ? '✓ PASS' : '✗ FAIL';
        echo sprintf("%-50s %s\n", $test['name'], $status);
        
        if (!$test['success']) {
            echo "  URL: " . $test['url'] . "\n";
            echo "  Method: " . $test['method'] . "\n";
            echo "  HTTP Code: " . $test['http_code'] . "\n";
            if (isset($test['error_message'])) {
                echo "  Error: " . $test['error_message'] . "\n";
            }
            echo "\n";
        }
    }
}

// Start testing
echo "Starting HR34 API Tests...\n";
echo "Base URL: " . $base_url . "\n";
echo "API Key: " . $api_key . "\n\n";

// Test 1: API Gateway Health Check
echo "Testing API Gateway...\n";
runTest('API Gateway Health Check', $base_url . '/');

// Test 2: Authentication Service
echo "Testing Authentication Service...\n";
runTest('Login Endpoint', $base_url . '/api/v1/auth/login', 'POST', [
    'username' => $test_username,
    'password' => $test_password
], ['X-API-Key: ']); // Remove API key for auth endpoints

// Test 3: Core HR Service
echo "Testing Core HR Service...\n";
runTest('Get Employees', $base_url . '/api/v1/core-hr/employees');
runTest('Get Departments', $base_url . '/api/v1/core-hr/departments');
runTest('Get Attendance', $base_url . '/api/v1/core-hr/attendance');
runTest('Get Org Structure', $base_url . '/api/v1/core-hr/org-structure');

// Test 4: Payroll Service
echo "Testing Payroll Service...\n";
runTest('Get Salaries', $base_url . '/api/v1/payroll/salaries');
runTest('Get Bonuses', $base_url . '/api/v1/payroll/bonuses');
runTest('Get Deductions', $base_url . '/api/v1/payroll/deductions');
runTest('Get Payroll Runs', $base_url . '/api/v1/payroll/payroll-runs');
runTest('Get Payslips', $base_url . '/api/v1/payroll/payslips');

// Test 5: HMO Service
echo "Testing HMO Service...\n";
runTest('Get HMO Providers', $base_url . '/api/v1/hmo/providers');
runTest('Get Benefits Plans', $base_url . '/api/v1/hmo/benefits-plans');
runTest('Get Claim Types', $base_url . '/api/v1/hmo/claim-types');

// Test 6: Analytics Service
echo "Testing Analytics Service...\n";
runTest('Dashboard Summary', $base_url . '/api/v1/analytics/dashboard-summary');
runTest('Key Metrics', $base_url . '/api/v1/analytics/key-metrics');
runTest('HR Analytics', $base_url . '/api/v1/analytics/hr-analytics');
runTest('Available Reports', $base_url . '/api/v1/analytics/reports');

// Test 7: Notifications Service
echo "Testing Notifications Service...\n";
runTest('Get Notifications', $base_url . '/api/v1/notifications/');
runTest('Notification Types', $base_url . '/api/v1/notifications/types');
runTest('Notification Priorities', $base_url . '/api/v1/notifications/priorities');

// Test 8: Compensation Service
echo "Testing Compensation Service...\n";
runTest('Get Compensation Plans', $base_url . '/api/v1/compensation/plans');
runTest('Get Salary Adjustments', $base_url . '/api/v1/compensation/salary-adjustments');
runTest('Get Incentives', $base_url . '/api/v1/compensation/incentives');
runTest('Get Compensation Analytics', $base_url . '/api/v1/compensation/analytics');

// Test 9: Database Connectivity
echo "Testing Database Connectivity...\n";
try {
    require_once 'php/db_connect.php';
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees");
    $result = $stmt->fetch();
    
    $tests[] = [
        'name' => 'Database Connection',
        'url' => 'Direct Database Query',
        'method' => 'SQL',
        'http_code' => 200,
        'response' => json_encode(['employee_count' => $result['count']]),
        'error' => null,
        'success' => true
    ];
    $passed++;
    
    echo "Database connection successful. Employee count: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Database Connection',
        'url' => 'Direct Database Query',
        'method' => 'SQL',
        'http_code' => 500,
        'response' => null,
        'error' => $e->getMessage(),
        'success' => false
    ];
    $failed++;
    
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Print results
printTestResults();

// Additional information
echo "\n" . str_repeat("=", 80) . "\n";
echo "ADDITIONAL INFORMATION\n";
echo str_repeat("=", 80) . "\n";
echo "1. Make sure XAMPP is running with Apache and MySQL\n";
echo "2. Verify the database 'hr_integrated_db' exists and has data\n";
echo "3. Check that all PHP files are accessible via web server\n";
echo "4. Import the Postman collection for detailed API testing\n";
echo "5. Review the API documentation for endpoint details\n";
echo str_repeat("=", 80) . "\n";

// Exit with appropriate code
exit($failed > 0 ? 1 : 0);
?>

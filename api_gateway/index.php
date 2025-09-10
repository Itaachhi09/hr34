<?php
/**
 * HR34 API Gateway
 * Central entry point for all microservices
 * Handles routing, authentication, and request forwarding
 */

// Error reporting and headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once '../php/db_connect.php';

// API Configuration
$api_config = [
    'version' => 'v1',
    'base_url' => '/api/v1',
    'services' => [
        'auth' => [
            'path' => '/auth',
            'handler' => '../microservices/auth_service.php'
        ],
        'core-hr' => [
            'path' => '/core-hr',
            'handler' => '../microservices/core_hr_service.php'
        ],
        'payroll' => [
            'path' => '/payroll',
            'handler' => '../microservices/payroll_service.php'
        ],
        'hmo' => [
            'path' => '/hmo',
            'handler' => '../microservices/hmo_service.php'
        ],
        'analytics' => [
            'path' => '/analytics',
            'handler' => '../microservices/analytics_service.php'
        ],
        'notifications' => [
            'path' => '/notifications',
            'handler' => '../microservices/notifications_service.php'
        ],
        'compensation' => [
            'path' => '/compensation',
            'handler' => '../microservices/compensation_service.php'
        ]
    ]
];

// Helper functions
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

function sendError($message, $status_code = 400) {
    sendResponse(['error' => $message], $status_code);
}

function validateApiKey($api_key) {
    // Simple API key validation - in production, use proper authentication
    $valid_keys = ['hr34-api-key-2024', 'hr34-dev-key'];
    return in_array($api_key, $valid_keys);
}

function getRequestData() {
    $method = $_SERVER['REQUEST_METHOD'];
    $data = [];
    
    if ($method === 'GET') {
        $data = $_GET;
    } else {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendError('Invalid JSON payload');
        }
    }
    
    return $data;
}

// Main routing logic
try {
    $request_uri = $_SERVER['REQUEST_URI'];
    $request_method = $_SERVER['REQUEST_METHOD'];
    
    // Remove query string and base path
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = str_replace('/api_gateway', '', $path);
    
    // API Key validation (except for auth endpoints)
    if (!str_starts_with($path, '/auth')) {
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
        if (!$api_key || !validateApiKey($api_key)) {
            sendError('Invalid or missing API key', 401);
        }
    }
    
    // Route to appropriate microservice
    $service_found = false;
    foreach ($api_config['services'] as $service_name => $service_config) {
        if (str_starts_with($path, $service_config['path'])) {
            $service_found = true;
            
            // Prepare environment for microservice
            $_ENV['SERVICE_NAME'] = $service_name;
            $_ENV['REQUEST_METHOD'] = $request_method;
            $_ENV['REQUEST_PATH'] = $path;
            $_ENV['REQUEST_DATA'] = getRequestData();
            
            // Include and execute microservice
            if (file_exists($service_config['handler'])) {
                include $service_config['handler'];
            } else {
                sendError("Service handler not found: {$service_name}", 500);
            }
            break;
        }
    }
    
    if (!$service_found) {
        // API documentation endpoint
        if ($path === '/' || $path === '') {
            sendResponse([
                'service' => 'HR34 API Gateway',
                'version' => $api_config['version'],
                'status' => 'active',
                'available_services' => array_keys($api_config['services']),
                'documentation' => '/api_gateway/docs',
                'endpoints' => [
                    'auth' => '/api/v1/auth/*',
                    'core-hr' => '/api/v1/core-hr/*',
                    'payroll' => '/api/v1/payroll/*',
                    'hmo' => '/api/v1/hmo/*',
                    'analytics' => '/api/v1/analytics/*',
                    'notifications' => '/api/v1/notifications/*',
                    'compensation' => '/api/v1/compensation/*'
                ]
            ]);
        } else {
            sendError('Service not found', 404);
        }
    }
    
} catch (Exception $e) {
    error_log("API Gateway Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

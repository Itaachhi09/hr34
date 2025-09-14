<?php
/**
 * Get User Profile API
 * Returns the current user's profile information
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // For landing pages, we'll use mock data based on role
    $role = $_GET['role'] ?? 'Guest';
    
    $mockProfiles = [
        'System Admin' => [
            'EmployeeID' => 1,
            'FirstName' => 'System',
            'LastName' => 'Administrator',
            'Email' => 'admin@company.com',
            'Phone' => '000-000-0000',
            'DateOfBirth' => '1990-01-01',
            'HireDate' => date('Y-m-d'),
            'DepartmentName' => 'Administration',
            'JobRoleName' => 'System Administrator',
            'Status' => 'Active'
        ],
        'HR Admin' => [
            'EmployeeID' => 2,
            'FirstName' => 'HR',
            'LastName' => 'Manager',
            'Email' => 'hrmanager@company.com',
            'Phone' => '000-000-0001',
            'DateOfBirth' => '1985-05-15',
            'HireDate' => date('Y-m-d', strtotime('-2 years')),
            'DepartmentName' => 'Human Resources',
            'JobRoleName' => 'HR Manager',
            'Status' => 'Active'
        ],
        'HR Staff' => [
            'EmployeeID' => 3,
            'FirstName' => 'HR',
            'LastName' => 'Staff',
            'Email' => 'hrstaff@company.com',
            'Phone' => '000-000-0002',
            'DateOfBirth' => '1992-08-20',
            'HireDate' => date('Y-m-d', strtotime('-1 year')),
            'DepartmentName' => 'Human Resources',
            'JobRoleName' => 'HR Staff',
            'Status' => 'Active'
        ]
    ];
    
    $profile = $mockProfiles[$role] ?? [
        'EmployeeID' => 0,
        'FirstName' => 'Guest',
        'LastName' => 'User',
        'Email' => 'guest@company.com',
        'Phone' => 'N/A',
        'DateOfBirth' => 'N/A',
        'HireDate' => 'N/A',
        'DepartmentName' => 'N/A',
        'JobRoleName' => 'Guest',
        'Status' => 'N/A'
    ];
    
    echo json_encode($profile);
    
} catch (Exception $e) {
    error_log("Get User Profile API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>
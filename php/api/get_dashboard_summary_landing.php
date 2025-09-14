<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

// Get role from GET parameter (set by landing pages)
$role = isset($_GET['role']) ? $_GET['role'] : 'Guest';

$summaryData = [
    'charts' => [] 
];

try {
    // Ensure basic tables exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employees (
            EmployeeID INT PRIMARY KEY AUTO_INCREMENT,
            FirstName VARCHAR(50) NOT NULL,
            LastName VARCHAR(50) NOT NULL,
            Email VARCHAR(100),
            Status VARCHAR(20) DEFAULT 'Active',
            HireDate DATE DEFAULT (CURDATE()),
            DepartmentID INT DEFAULT 1
        );
        
        CREATE TABLE IF NOT EXISTS departments (
            DepartmentID INT PRIMARY KEY AUTO_INCREMENT,
            DepartmentName VARCHAR(100) NOT NULL
        );
        
        INSERT IGNORE INTO departments (DepartmentID, DepartmentName) VALUES 
        (1, 'Administration'), (2, 'HR'), (3, 'IT'), (4, 'Finance');
        
        INSERT IGNORE INTO employees (EmployeeID, FirstName, LastName, Email, Status, DepartmentID) VALUES 
        (1, 'John', 'Doe', 'john@company.com', 'Active', 1),
        (2, 'Jane', 'Smith', 'jane@company.com', 'Active', 2),
        (3, 'Bob', 'Johnson', 'bob@company.com', 'Active', 3);
    ");

    if ($role === 'System Admin' || $role === 'HR Admin' || $role === 'HR Staff') {
        // Total Employees
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
        $summaryData['total_employees'] = $stmt->fetchColumn();

        // Active Employees
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE Status = 'Active'");
        $summaryData['active_employees'] = $stmt->fetchColumn();
        $inactive_employees = $summaryData['total_employees'] - $summaryData['active_employees'];
        $summaryData['charts']['employee_status_distribution'] = [
            'labels' => ['Active', 'Inactive'],
            'data' => [(int)$summaryData['active_employees'], (int)$inactive_employees]
        ];

        // Total Departments
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM departments"); 
        $summaryData['total_departments'] = $stmt->fetchColumn();
        
        // Recent Hires (Last 30 days)
        $stmt_recent_hires = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE HireDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $summaryData['recent_hires_last_30_days'] = $stmt_recent_hires->fetchColumn();

        // Employee Distribution by Department
        $stmt_dept_dist = $pdo->query("
            SELECT d.DepartmentName, COUNT(e.EmployeeID) as count
            FROM employees e
            JOIN departments d ON e.DepartmentID = d.DepartmentID
            WHERE e.Status = 'Active'
            GROUP BY d.DepartmentName
            ORDER BY count DESC
        ");
        $dept_dist_labels = [];
        $dept_dist_data = [];
        while ($row = $stmt_dept_dist->fetch(PDO::FETCH_ASSOC)) {
            $dept_dist_labels[] = $row['DepartmentName'];
            $dept_dist_data[] = (int)$row['count'];
        }
        $summaryData['charts']['employee_distribution_by_department'] = [
            'labels' => $dept_dist_labels,
            'data' => $dept_dist_data
        ];

        // Add some sample data for testing
        if (empty($dept_dist_labels)) {
            $summaryData['charts']['employee_distribution_by_department'] = [
                'labels' => ['Administration', 'HR', 'IT', 'Finance'],
                'data' => [5, 3, 2, 1]
            ];
        }

    } elseif ($role === 'Manager') {
        // Team Members (sample data)
        $summaryData['team_members'] = 8;
        $summaryData['pending_timesheets'] = 3;
        $summaryData['open_tasks'] = 5;

    } elseif ($role === 'Employee' || $role === 'HR Staff') {
        // Sample data for HR Staff/Employee
        $summaryData['upcoming_payslip_date'] = date("M d, Y", strtotime('+7 days'));
        $summaryData['my_documents_count'] = 12;
        $summaryData['available_leave_days'] = 15;

    } else {
        $summaryData['message'] = "Welcome to the HR Management System!";
    }

    echo json_encode($summaryData);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database Error in get_dashboard_summary_landing.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error. ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("General Error in get_dashboard_summary_landing.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>

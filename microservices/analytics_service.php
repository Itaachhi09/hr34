<?php
/**
 * Analytics Microservice
 * Handles HR analytics, reporting, and dashboard data
 */

// Error reporting and headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include database connection
require_once '../php/db_connect.php';

session_start();

// Helper functions
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

function sendError($message, $status_code = 400) {
    sendResponse(['error' => $message], $status_code);
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendError('Authentication required', 401);
    }
}

function requireRole($allowed_roles) {
    requireAuth();
    if (!in_array($_SESSION['role_name'], $allowed_roles)) {
        sendError('Insufficient permissions', 403);
    }
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$path = $_ENV['REQUEST_PATH'] ?? '';
$data = $_ENV['REQUEST_DATA'] ?? [];

// Route handling
try {
    // Remove /analytics prefix from path
    $endpoint = str_replace('/api/v1/analytics', '', $path);
    
    switch ($endpoint) {
        case '/dashboard-summary':
            if ($method === 'GET') {
                requireAuth();
                
                $role = $data['role'] ?? $_SESSION['role_name'];
                $loggedInUserId = $_SESSION['user_id'];
                $loggedInEmployeeId = $_SESSION['employee_id'] ?? null;
                
                $summaryData = ['charts' => []];
                
                if ($role === 'System Admin' || $role === 'HR Admin') {
                    // Total Employees
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees");
                    $summaryData['total_employees'] = $stmt->fetchColumn();
                    
                    // Active Employees
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees WHERE Status = 'Active'");
                    $summaryData['active_employees'] = $stmt->fetchColumn();
                    $inactive_employees = $summaryData['total_employees'] - $summaryData['active_employees'];
                    $summaryData['charts']['employee_status_distribution'] = [
                        'labels' => ['Active', 'Inactive'],
                        'data' => [(int)$summaryData['active_employees'], (int)$inactive_employees]
                    ];
                    
                    // Pending Leave Requests
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM LeaveRequests WHERE Status = 'Pending'");
                    $summaryData['pending_leave_requests'] = $stmt->fetchColumn();
                    
                    // Total Departments
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM departments");
                    $summaryData['total_departments'] = $stmt->fetchColumn();
                    
                    // Recent Hires (Last 30 days)
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees WHERE HireDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                    $summaryData['recent_hires_last_30_days'] = $stmt->fetchColumn();
                    
                    // Employee Distribution by Department
                    $stmt = $pdo->query("
                        SELECT d.DepartmentName, COUNT(e.EmployeeID) as count
                        FROM Employees e
                        JOIN departments d ON e.DepartmentID = d.DepartmentID
                        WHERE e.Status = 'Active'
                        GROUP BY d.DepartmentName
                        ORDER BY count DESC
                    ");
                    $dept_dist_labels = [];
                    $dept_dist_data = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $dept_dist_labels[] = $row['DepartmentName'];
                        $dept_dist_data[] = (int)$row['count'];
                    }
                    $summaryData['charts']['employee_distribution_by_department'] = [
                        'labels' => $dept_dist_labels,
                        'data' => $dept_dist_data
                    ];
                    
                } elseif ($role === 'Manager') {
                    if (!$loggedInEmployeeId) {
                        throw new Exception("Employee ID not found for manager.");
                    }
                    
                    // Team Members
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Employees WHERE ManagerID = :manager_id");
                    $stmt->bindParam(':manager_id', $loggedInEmployeeId, PDO::PARAM_INT);
                    $stmt->execute();
                    $summaryData['team_members'] = $stmt->fetchColumn();
                    
                    // Pending Team Leave
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM LeaveRequests lr
                        JOIN Employees e ON lr.EmployeeID = e.EmployeeID
                        WHERE e.ManagerID = :manager_id AND lr.Status = 'Pending'
                    ");
                    $stmt->bindParam(':manager_id', $loggedInEmployeeId, PDO::PARAM_INT);
                    $stmt->execute();
                    $summaryData['pending_team_leave'] = $stmt->fetchColumn();
                    
                    // Pending Timesheets for Team
                    $stmt = $pdo->prepare("
                        SELECT COUNT(t.TimesheetID) as count
                        FROM Timesheets t
                        JOIN Employees e ON t.EmployeeID = e.EmployeeID
                        WHERE e.ManagerID = :manager_id AND t.Status = 'Pending'
                    ");
                    $stmt->bindParam(':manager_id', $loggedInEmployeeId, PDO::PARAM_INT);
                    $stmt->execute();
                    $summaryData['pending_timesheets'] = $stmt->fetchColumn();
                    
                } elseif ($role === 'Employee') {
                    if (!$loggedInEmployeeId) {
                        throw new Exception("Employee ID not found for employee.");
                    }
                    
                    $currentYear = date('Y');
                    
                    // Available Leave Days
                    $stmt = $pdo->prepare("
                        SELECT SUM(lb.AvailableDays) as total_available 
                        FROM LeaveBalances lb 
                        WHERE lb.EmployeeID = :employee_id AND lb.BalanceYear = :year
                    ");
                    $stmt->bindParam(':employee_id', $loggedInEmployeeId, PDO::PARAM_INT);
                    $stmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
                    $stmt->execute();
                    $available_leave = $stmt->fetchColumn();
                    $summaryData['available_leave_days'] = $available_leave !== null ? floatval($available_leave) : 0;
                    
                    // My Pending Claims
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Claims WHERE EmployeeID = :employee_id AND Status = 'Submitted'");
                    $stmt->bindParam(':employee_id', $loggedInEmployeeId, PDO::PARAM_INT);
                    $stmt->execute();
                    $summaryData['pending_claims'] = $stmt->fetchColumn();
                    
                    // My Documents Count
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM employeedocuments WHERE EmployeeID = :employee_id");
                    $stmt->bindParam(':employee_id', $loggedInEmployeeId, PDO::PARAM_INT);
                    $stmt->execute();
                    $summaryData['my_documents_count'] = $stmt->fetchColumn();
                }
                
                sendResponse($summaryData);
            }
            break;
            
        case '/key-metrics':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $metrics = [];
                
                // Employee metrics
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees WHERE Status = 'Active'");
                $metrics['active_employees'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees WHERE HireDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                $metrics['new_hires_30_days'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                $metrics['terminations_30_days'] = $stmt->fetchColumn();
                
                // Leave metrics
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM LeaveRequests WHERE Status = 'Pending'");
                $metrics['pending_leave_requests'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM LeaveRequests WHERE Status = 'Approved' AND StartDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                $metrics['approved_leave_30_days'] = $stmt->fetchColumn();
                
                // Payroll metrics
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM PayrollRuns WHERE Status = 'Completed' AND PaymentDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                $metrics['completed_payrolls_30_days'] = $stmt->fetchColumn();
                
                // HMO metrics
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM employeebenefits WHERE Status = 'Active'");
                $metrics['active_benefits'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM Claims WHERE Status = 'Submitted'");
                $metrics['pending_claims'] = $stmt->fetchColumn();
                
                sendResponse($metrics);
            }
            break;
            
        case '/hr-analytics':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $analytics = [];
                
                // Employee distribution by department
                $stmt = $pdo->query("
                    SELECT d.DepartmentName, COUNT(e.EmployeeID) as employee_count
                    FROM departments d
                    LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.Status = 'Active'
                    GROUP BY d.DepartmentID, d.DepartmentName
                    ORDER BY employee_count DESC
                ");
                $analytics['department_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Employee distribution by role
                $stmt = $pdo->query("
                    SELECT jr.RoleName, COUNT(e.EmployeeID) as employee_count
                    FROM jobroles jr
                    LEFT JOIN Employees e ON jr.JobRoleID = e.JobRoleID AND e.Status = 'Active'
                    GROUP BY jr.JobRoleID, jr.RoleName
                    ORDER BY employee_count DESC
                ");
                $analytics['role_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Hiring trends (last 12 months)
                $stmt = $pdo->query("
                    SELECT DATE_FORMAT(HireDate, '%Y-%m') as month, COUNT(*) as hires
                    FROM Employees
                    WHERE HireDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(HireDate, '%Y-%m')
                    ORDER BY month
                ");
                $analytics['hiring_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Leave trends (last 12 months)
                $stmt = $pdo->query("
                    SELECT DATE_FORMAT(StartDate, '%Y-%m') as month, COUNT(*) as leave_requests
                    FROM LeaveRequests
                    WHERE StartDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND Status = 'Approved'
                    GROUP BY DATE_FORMAT(StartDate, '%Y-%m')
                    ORDER BY month
                ");
                $analytics['leave_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Average salary by department
                $stmt = $pdo->query("
                    SELECT d.DepartmentName, AVG(s.BaseSalary) as avg_salary, COUNT(e.EmployeeID) as employee_count
                    FROM departments d
                    LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.Status = 'Active'
                    LEFT JOIN salaries s ON e.EmployeeID = s.EmployeeID 
                        AND s.EffectiveDate <= CURDATE() 
                        AND (s.EndDate IS NULL OR s.EndDate >= CURDATE())
                    GROUP BY d.DepartmentID, d.DepartmentName
                    HAVING employee_count > 0
                    ORDER BY avg_salary DESC
                ");
                $analytics['salary_by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($analytics);
            }
            break;
            
        case '/reports':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $reports = [
                    [
                        'report_id' => 'employee_master',
                        'report_name' => 'Employee Master Report',
                        'description' => 'Complete employee information including personal, contact, and employment details',
                        'endpoint' => '/api/v1/analytics/reports/employee-master'
                    ],
                    [
                        'report_id' => 'payroll_summary',
                        'report_name' => 'Payroll Summary Report',
                        'description' => 'Payroll summary for a specific period including gross pay, deductions, and net pay',
                        'endpoint' => '/api/v1/analytics/reports/payroll-summary'
                    ],
                    [
                        'report_id' => 'leave_summary',
                        'report_name' => 'Leave Summary Report',
                        'description' => 'Leave requests and balances summary for employees',
                        'endpoint' => '/api/v1/analytics/reports/leave-summary'
                    ],
                    [
                        'report_id' => 'benefits_summary',
                        'report_name' => 'Benefits Summary Report',
                        'description' => 'Employee benefits enrollment and claims summary',
                        'endpoint' => '/api/v1/analytics/reports/benefits-summary'
                    ]
                ];
                
                sendResponse($reports);
            }
            break;
            
        case '/reports/employee-master':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $sql = "SELECT e.EmployeeID, e.FirstName, e.MiddleName, e.LastName, e.Suffix,
                               e.Email, e.PersonalEmail, e.Phone, e.DateOfBirth, e.Gender,
                               e.MaritalStatus, e.Nationality, e.AddressLine1, e.AddressLine2,
                               e.City, e.StateProvince, e.PostalCode, e.Country,
                               e.EmergencyContactName, e.EmergencyContactRelationship, e.EmergencyContactPhone,
                               e.HireDate, e.Status, e.TerminationDate, e.TerminationReason,
                               d.DepartmentName, jr.RoleName AS JobTitle,
                               CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName,
                               s.BaseSalary, s.EffectiveDate as SalaryEffectiveDate
                        FROM Employees e
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN jobroles jr ON e.JobRoleID = jr.JobRoleID
                        LEFT JOIN Employees m ON e.ManagerID = m.EmployeeID
                        LEFT JOIN salaries s ON e.EmployeeID = s.EmployeeID 
                            AND s.EffectiveDate <= CURDATE() 
                            AND (s.EndDate IS NULL OR s.EndDate >= CURDATE())
                        ORDER BY e.LastName, e.FirstName";
                
                $stmt = $pdo->query($sql);
                $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($employees);
            }
            break;
            
        case '/reports/payroll-summary':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                $date_from = $data['date_from'] ?? date('Y-m-01');
                $date_to = $data['date_to'] ?? date('Y-m-t');
                
                $sql = "SELECT pr.RunID, pr.PayPeriodStart, pr.PayPeriodEnd, pr.PaymentDate, pr.Status,
                               COUNT(ps.PayslipID) as payslip_count,
                               SUM(ps.GrossPay) as total_gross_pay,
                               SUM(ps.Bonuses) as total_bonuses,
                               SUM(ps.Deductions) as total_deductions,
                               SUM(ps.NetPay) as total_net_pay
                        FROM PayrollRuns pr
                        LEFT JOIN payslips ps ON pr.RunID = ps.PayrollRunID
                        WHERE pr.PayPeriodStart BETWEEN :date_from AND :date_to
                        GROUP BY pr.RunID
                        ORDER BY pr.PayPeriodStart DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':date_from', $date_from);
                $stmt->bindParam(':date_to', $date_to);
                $stmt->execute();
                $payroll_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($payroll_summary);
            }
            break;
            
        case '/reports/leave-summary':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $year = $data['year'] ?? date('Y');
                
                $sql = "SELECT e.EmployeeID, e.FirstName, e.LastName, d.DepartmentName,
                               lb.LeaveTypeID, lt.LeaveName, lb.AvailableDays, lb.UsedDays,
                               (SELECT COUNT(*) FROM LeaveRequests lr 
                                WHERE lr.EmployeeID = e.EmployeeID 
                                AND lr.LeaveTypeID = lb.LeaveTypeID 
                                AND YEAR(lr.StartDate) = :year 
                                AND lr.Status = 'Approved') as ApprovedRequests,
                               (SELECT COUNT(*) FROM LeaveRequests lr 
                                WHERE lr.EmployeeID = e.EmployeeID 
                                AND lr.LeaveTypeID = lb.LeaveTypeID 
                                AND YEAR(lr.StartDate) = :year 
                                AND lr.Status = 'Pending') as PendingRequests
                        FROM Employees e
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN LeaveBalances lb ON e.EmployeeID = lb.EmployeeID AND lb.BalanceYear = :year
                        LEFT JOIN LeaveTypes lt ON lb.LeaveTypeID = lt.LeaveTypeID
                        WHERE e.Status = 'Active'
                        ORDER BY e.LastName, e.FirstName, lt.LeaveName";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                $stmt->execute();
                $leave_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($leave_summary);
            }
            break;
            
        case '/reports/benefits-summary':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $sql = "SELECT e.EmployeeID, e.FirstName, e.LastName, d.DepartmentName,
                               eb.Status as BenefitStatus, eb.EnrollmentDate,
                               bp.PlanName, bp.CoverageType, bp.MonthlyPremium,
                               hp.ProviderName,
                               (SELECT COUNT(*) FROM Claims c 
                                WHERE c.EmployeeID = e.EmployeeID 
                                AND c.Status = 'Submitted') as PendingClaims,
                               (SELECT SUM(c.ClaimAmount) FROM Claims c 
                                WHERE c.EmployeeID = e.EmployeeID 
                                AND c.Status = 'Approved' 
                                AND YEAR(c.ClaimDate) = YEAR(CURDATE())) as TotalClaimsThisYear
                        FROM Employees e
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN employeebenefits eb ON e.EmployeeID = eb.EmployeeID
                        LEFT JOIN benefitsplans bp ON eb.PlanID = bp.PlanID
                        LEFT JOIN hmoproviders hp ON eb.ProviderID = hp.ProviderID
                        WHERE e.Status = 'Active'
                        ORDER BY e.LastName, e.FirstName";
                
                $stmt = $pdo->query($sql);
                $benefits_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($benefits_summary);
            }
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("Analytics Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("Analytics Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

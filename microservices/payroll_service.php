<?php
/**
 * Payroll Microservice
 * Handles payroll processing, salary management, bonuses, deductions, and payslips
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
    // Remove /payroll prefix from path
    $endpoint = str_replace('/api/v1/payroll', '', $path);
    
    switch ($endpoint) {
        case '/salaries':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT s.*, e.FirstName, e.LastName, e.EmployeeID, d.DepartmentName
                        FROM salaries s
                        JOIN Employees e ON s.EmployeeID = e.EmployeeID
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        ORDER BY e.LastName, e.FirstName";
                
                $stmt = $pdo->query($sql);
                $salaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($salaries);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                $required_fields = ['EmployeeID', 'BaseSalary', 'EffectiveDate'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        sendError("Field '{$field}' is required");
                    }
                }
                
                $sql = "INSERT INTO salaries (EmployeeID, BaseSalary, EffectiveDate, EndDate, Notes)
                        VALUES (:employee_id, :base_salary, :effective_date, :end_date, :notes)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':base_salary', $data['BaseSalary']);
                $stmt->bindParam(':effective_date', $data['EffectiveDate']);
                $stmt->bindParam(':end_date', $data['EndDate'] ?? null);
                $stmt->bindParam(':notes', $data['Notes'] ?? null);
                $stmt->execute();
                
                $salary_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Salary record created successfully', 'salary_id' => $salary_id], 201);
            }
            break;
            
        case '/bonuses':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? null;
                $date_from = $data['date_from'] ?? date('Y-m-01');
                $date_to = $data['date_to'] ?? date('Y-m-t');
                
                $sql = "SELECT b.*, e.FirstName, e.LastName, e.EmployeeID
                        FROM bonuses b
                        JOIN Employees e ON b.EmployeeID = e.EmployeeID
                        WHERE b.BonusDate BETWEEN :date_from AND :date_to";
                
                $params = [':date_from' => $date_from, ':date_to' => $date_to];
                
                if ($employee_id) {
                    $sql .= " AND b.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                }
                
                $sql .= " ORDER BY b.BonusDate DESC";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $bonuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($bonuses);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                if (empty($data['EmployeeID']) || empty($data['BonusAmount']) || empty($data['BonusDate'])) {
                    sendError('Employee ID, bonus amount, and bonus date are required');
                }
                
                $sql = "INSERT INTO bonuses (EmployeeID, BonusAmount, BonusDate, Description)
                        VALUES (:employee_id, :bonus_amount, :bonus_date, :description)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':bonus_amount', $data['BonusAmount']);
                $stmt->bindParam(':bonus_date', $data['BonusDate']);
                $stmt->bindParam(':description', $data['Description'] ?? null);
                $stmt->execute();
                
                $bonus_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Bonus added successfully', 'bonus_id' => $bonus_id], 201);
            }
            break;
            
        case '/deductions':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT d.*, e.FirstName, e.LastName, e.EmployeeID
                        FROM deductions d
                        JOIN Employees e ON d.EmployeeID = e.EmployeeID
                        ORDER BY d.DeductionDate DESC";
                
                $stmt = $pdo->query($sql);
                $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($deductions);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                if (empty($data['EmployeeID']) || empty($data['DeductionAmount']) || empty($data['DeductionDate'])) {
                    sendError('Employee ID, deduction amount, and deduction date are required');
                }
                
                $sql = "INSERT INTO deductions (EmployeeID, DeductionAmount, DeductionDate, DeductionType, Description)
                        VALUES (:employee_id, :deduction_amount, :deduction_date, :deduction_type, :description)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':deduction_amount', $data['DeductionAmount']);
                $stmt->bindParam(':deduction_date', $data['DeductionDate']);
                $stmt->bindParam(':deduction_type', $data['DeductionType'] ?? 'Other');
                $stmt->bindParam(':description', $data['Description'] ?? null);
                $stmt->execute();
                
                $deduction_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Deduction added successfully', 'deduction_id' => $deduction_id], 201);
            }
            break;
            
        case '/payroll-runs':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                $sql = "SELECT pr.*, COUNT(ps.PayslipID) as payslip_count
                        FROM PayrollRuns pr
                        LEFT JOIN payslips ps ON pr.RunID = ps.PayrollRunID
                        GROUP BY pr.RunID
                        ORDER BY pr.PayPeriodStart DESC";
                
                $stmt = $pdo->query($sql);
                $runs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($runs);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                if (empty($data['PayPeriodStart']) || empty($data['PayPeriodEnd'])) {
                    sendError('Pay period start and end dates are required');
                }
                
                $sql = "INSERT INTO PayrollRuns (PayPeriodStart, PayPeriodEnd, PaymentDate, Status, CreatedBy)
                        VALUES (:pay_period_start, :pay_period_end, :payment_date, :status, :created_by)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':pay_period_start', $data['PayPeriodStart']);
                $stmt->bindParam(':pay_period_end', $data['PayPeriodEnd']);
                $stmt->bindParam(':payment_date', $data['PaymentDate'] ?? $data['PayPeriodEnd']);
                $stmt->bindParam(':status', $data['Status'] ?? 'Draft');
                $stmt->bindParam(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();
                
                $run_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Payroll run created successfully', 'run_id' => $run_id], 201);
            }
            break;
            
        case '/payroll-runs/{id}/process':
            if ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                $run_id = $data['id'] ?? null;
                if (!$run_id) {
                    sendError('Payroll run ID is required');
                }
                
                // Get payroll run details
                $sql_run = "SELECT * FROM PayrollRuns WHERE RunID = :run_id";
                $stmt_run = $pdo->prepare($sql_run);
                $stmt_run->bindParam(':run_id', $run_id, PDO::PARAM_INT);
                $stmt_run->execute();
                $payroll_run = $stmt_run->fetch(PDO::FETCH_ASSOC);
                
                if (!$payroll_run) {
                    sendError('Payroll run not found', 404);
                }
                
                // Get active employees
                $sql_employees = "SELECT e.EmployeeID, e.FirstName, e.LastName, s.BaseSalary
                                 FROM Employees e
                                 LEFT JOIN salaries s ON e.EmployeeID = s.EmployeeID 
                                     AND s.EffectiveDate <= :pay_period_end 
                                     AND (s.EndDate IS NULL OR s.EndDate >= :pay_period_start)
                                 WHERE e.Status = 'Active'
                                 ORDER BY e.LastName, e.FirstName";
                
                $stmt_employees = $pdo->prepare($sql_employees);
                $stmt_employees->bindParam(':pay_period_start', $payroll_run['PayPeriodStart']);
                $stmt_employees->bindParam(':pay_period_end', $payroll_run['PayPeriodEnd']);
                $stmt_employees->execute();
                $employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);
                
                $processed_count = 0;
                foreach ($employees as $employee) {
                    // Calculate gross pay (assuming monthly salary)
                    $gross_pay = $employee['BaseSalary'] ?? 0;
                    
                    // Get bonuses for this period
                    $sql_bonuses = "SELECT SUM(BonusAmount) as total_bonuses
                                   FROM bonuses
                                   WHERE EmployeeID = :employee_id 
                                   AND BonusDate BETWEEN :pay_period_start AND :pay_period_end";
                    
                    $stmt_bonuses = $pdo->prepare($sql_bonuses);
                    $stmt_bonuses->bindParam(':employee_id', $employee['EmployeeID'], PDO::PARAM_INT);
                    $stmt_bonuses->bindParam(':pay_period_start', $payroll_run['PayPeriodStart']);
                    $stmt_bonuses->bindParam(':pay_period_end', $payroll_run['PayPeriodEnd']);
                    $stmt_bonuses->execute();
                    $bonuses = $stmt_bonuses->fetchColumn() ?: 0;
                    
                    // Get deductions for this period
                    $sql_deductions = "SELECT SUM(DeductionAmount) as total_deductions
                                      FROM deductions
                                      WHERE EmployeeID = :employee_id 
                                      AND DeductionDate BETWEEN :pay_period_start AND :pay_period_end";
                    
                    $stmt_deductions = $pdo->prepare($sql_deductions);
                    $stmt_deductions->bindParam(':employee_id', $employee['EmployeeID'], PDO::PARAM_INT);
                    $stmt_deductions->bindParam(':pay_period_start', $payroll_run['PayPeriodStart']);
                    $stmt_deductions->bindParam(':pay_period_end', $payroll_run['PayPeriodEnd']);
                    $stmt_deductions->execute();
                    $deductions = $stmt_deductions->fetchColumn() ?: 0;
                    
                    // Calculate net pay
                    $net_pay = $gross_pay + $bonuses - $deductions;
                    
                    // Create payslip
                    $sql_payslip = "INSERT INTO payslips (PayrollRunID, EmployeeID, GrossPay, Bonuses, Deductions, NetPay, Status)
                                   VALUES (:run_id, :employee_id, :gross_pay, :bonuses, :deductions, :net_pay, 'Generated')";
                    
                    $stmt_payslip = $pdo->prepare($sql_payslip);
                    $stmt_payslip->bindParam(':run_id', $run_id, PDO::PARAM_INT);
                    $stmt_payslip->bindParam(':employee_id', $employee['EmployeeID'], PDO::PARAM_INT);
                    $stmt_payslip->bindParam(':gross_pay', $gross_pay);
                    $stmt_payslip->bindParam(':bonuses', $bonuses);
                    $stmt_payslip->bindParam(':deductions', $deductions);
                    $stmt_payslip->bindParam(':net_pay', $net_pay);
                    $stmt_payslip->execute();
                    
                    $processed_count++;
                }
                
                // Update payroll run status
                $sql_update = "UPDATE PayrollRuns SET Status = 'Processed', ProcessedDate = NOW() WHERE RunID = :run_id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':run_id', $run_id, PDO::PARAM_INT);
                $stmt_update->execute();
                
                sendResponse([
                    'message' => 'Payroll processed successfully',
                    'processed_employees' => $processed_count,
                    'run_id' => $run_id
                ]);
            }
            break;
            
        case '/payslips':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                $run_id = $data['run_id'] ?? null;
                
                $sql = "SELECT ps.*, e.FirstName, e.LastName, e.EmployeeID, pr.PayPeriodStart, pr.PayPeriodEnd
                        FROM payslips ps
                        JOIN Employees e ON ps.EmployeeID = e.EmployeeID
                        JOIN PayrollRuns pr ON ps.PayrollRunID = pr.RunID";
                
                $params = [];
                
                if ($employee_id && $_SESSION['role_name'] !== 'System Admin' && $_SESSION['role_name'] !== 'HR Admin' && $_SESSION['role_name'] !== 'Payroll Admin') {
                    $sql .= " WHERE ps.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                } elseif ($employee_id) {
                    $sql .= " WHERE ps.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                }
                
                if ($run_id) {
                    $sql .= (empty($params) ? " WHERE" : " AND") . " ps.PayrollRunID = :run_id";
                    $params[':run_id'] = $run_id;
                }
                
                $sql .= " ORDER BY pr.PayPeriodStart DESC, e.LastName, e.FirstName";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($payslips);
            }
            break;
            
        case '/payslips/{id}':
            if ($method === 'GET') {
                requireAuth();
                
                $payslip_id = $data['id'] ?? null;
                if (!$payslip_id) {
                    sendError('Payslip ID is required');
                }
                
                $sql = "SELECT ps.*, e.FirstName, e.LastName, e.EmployeeID, e.Email,
                               pr.PayPeriodStart, pr.PayPeriodEnd, pr.PaymentDate
                        FROM payslips ps
                        JOIN Employees e ON ps.EmployeeID = e.EmployeeID
                        JOIN PayrollRuns pr ON ps.PayrollRunID = pr.RunID
                        WHERE ps.PayslipID = :payslip_id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':payslip_id', $payslip_id, PDO::PARAM_INT);
                $stmt->execute();
                $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payslip) {
                    sendError('Payslip not found', 404);
                }
                
                // Check if user can access this payslip
                if ($_SESSION['role_name'] !== 'System Admin' && $_SESSION['role_name'] !== 'HR Admin' && $_SESSION['role_name'] !== 'Payroll Admin') {
                    if ($payslip['EmployeeID'] != $_SESSION['employee_id']) {
                        sendError('Access denied', 403);
                    }
                }
                
                sendResponse($payslip);
            }
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("Payroll Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("Payroll Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

<?php
/**
 * Compensation Microservice
 * Handles compensation plans, salary adjustments, incentives, and compensation analytics
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
    // Remove /compensation prefix from path
    $endpoint = str_replace('/api/v1/compensation', '', $path);
    
    switch ($endpoint) {
        case '/plans':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT cp.*, COUNT(ec.EmployeeID) as assigned_employees
                        FROM compensationplans cp
                        LEFT JOIN employeecompensation ec ON cp.PlanID = ec.PlanID
                        GROUP BY cp.PlanID
                        ORDER BY cp.PlanName";
                
                $stmt = $pdo->query($sql);
                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($plans);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['PlanName'])) {
                    sendError('Plan name is required');
                }
                
                $sql = "INSERT INTO compensationplans (PlanName, Description, BaseSalary, BonusPercentage, 
                                                      CommissionRate, Allowances, Benefits, IsActive)
                        VALUES (:plan_name, :description, :base_salary, :bonus_percentage, 
                                :commission_rate, :allowances, :benefits, :is_active)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':plan_name', $data['PlanName']);
                $stmt->bindParam(':description', $data['Description'] ?? null);
                $stmt->bindParam(':base_salary', $data['BaseSalary'] ?? 0);
                $stmt->bindParam(':bonus_percentage', $data['BonusPercentage'] ?? 0);
                $stmt->bindParam(':commission_rate', $data['CommissionRate'] ?? 0);
                $stmt->bindParam(':allowances', $data['Allowances'] ?? null);
                $stmt->bindParam(':benefits', $data['Benefits'] ?? null);
                $stmt->bindParam(':is_active', $data['IsActive'] ?? 1, PDO::PARAM_INT);
                $stmt->execute();
                
                $plan_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Compensation plan created successfully', 'plan_id' => $plan_id], 201);
                
            } elseif ($method === 'PUT') {
                requireRole(['System Admin', 'HR Admin']);
                
                $plan_id = $data['id'] ?? null;
                if (!$plan_id) {
                    sendError('Plan ID is required');
                }
                
                // Build dynamic update query
                $update_fields = [];
                $allowed_fields = ['PlanName', 'Description', 'BaseSalary', 'BonusPercentage', 
                                 'CommissionRate', 'Allowances', 'Benefits', 'IsActive'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $update_fields[] = "{$field} = :{$field}";
                    }
                }
                
                if (empty($update_fields)) {
                    sendError('No valid fields to update');
                }
                
                $sql = "UPDATE compensationplans SET " . implode(', ', $update_fields) . " WHERE PlanID = :plan_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $stmt->bindParam(":{$field}", $data[$field]);
                    }
                }
                
                $stmt->execute();
                
                sendResponse(['message' => 'Compensation plan updated successfully']);
                
            } elseif ($method === 'DELETE') {
                requireRole(['System Admin', 'HR Admin']);
                
                $plan_id = $data['id'] ?? null;
                if (!$plan_id) {
                    sendError('Plan ID is required');
                }
                
                // Check if plan has assigned employees
                $sql_check = "SELECT COUNT(*) as count FROM employeecompensation WHERE PlanID = :plan_id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $assigned_count = $stmt_check->fetchColumn();
                
                if ($assigned_count > 0) {
                    sendError('Cannot delete plan with assigned employees. Please reassign employees first.');
                }
                
                $sql = "DELETE FROM compensationplans WHERE PlanID = :plan_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
                $stmt->execute();
                
                sendResponse(['message' => 'Compensation plan deleted successfully']);
            }
            break;
            
        case '/salary-adjustments':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? null;
                $date_from = $data['date_from'] ?? date('Y-m-01');
                $date_to = $data['date_to'] ?? date('Y-m-t');
                
                $sql = "SELECT sa.*, e.FirstName, e.LastName, e.EmployeeID
                        FROM salaryadjustments sa
                        JOIN Employees e ON sa.EmployeeID = e.EmployeeID
                        WHERE sa.AdjustmentDate BETWEEN :date_from AND :date_to";
                
                $params = [':date_from' => $date_from, ':date_to' => $date_to];
                
                if ($employee_id) {
                    $sql .= " AND sa.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                }
                
                $sql .= " ORDER BY sa.AdjustmentDate DESC";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($adjustments);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                if (empty($data['EmployeeID']) || empty($data['AdjustmentAmount']) || empty($data['AdjustmentDate'])) {
                    sendError('Employee ID, adjustment amount, and adjustment date are required');
                }
                
                $sql = "INSERT INTO salaryadjustments (EmployeeID, AdjustmentAmount, AdjustmentType, 
                                                      AdjustmentDate, Reason, ApprovedBy, Status)
                        VALUES (:employee_id, :adjustment_amount, :adjustment_type, :adjustment_date, 
                                :reason, :approved_by, :status)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':adjustment_amount', $data['AdjustmentAmount']);
                $stmt->bindParam(':adjustment_type', $data['AdjustmentType'] ?? 'Increase');
                $stmt->bindParam(':adjustment_date', $data['AdjustmentDate']);
                $stmt->bindParam(':reason', $data['Reason'] ?? null);
                $stmt->bindParam(':approved_by', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindParam(':status', $data['Status'] ?? 'Approved');
                $stmt->execute();
                
                $adjustment_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Salary adjustment created successfully', 'adjustment_id' => $adjustment_id], 201);
            }
            break;
            
        case '/incentives':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? null;
                $date_from = $data['date_from'] ?? date('Y-m-01');
                $date_to = $data['date_to'] ?? date('Y-m-t');
                
                $sql = "SELECT i.*, e.FirstName, e.LastName, e.EmployeeID
                        FROM incentives i
                        JOIN Employees e ON i.EmployeeID = e.EmployeeID
                        WHERE i.IncentiveDate BETWEEN :date_from AND :date_to";
                
                $params = [':date_from' => $date_from, ':date_to' => $date_to];
                
                if ($employee_id) {
                    $sql .= " AND i.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                }
                
                $sql .= " ORDER BY i.IncentiveDate DESC";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $incentives = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($incentives);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin', 'Payroll Admin']);
                
                if (empty($data['EmployeeID']) || empty($data['IncentiveAmount']) || empty($data['IncentiveDate'])) {
                    sendError('Employee ID, incentive amount, and incentive date are required');
                }
                
                $sql = "INSERT INTO incentives (EmployeeID, IncentiveAmount, IncentiveType, IncentiveDate, 
                                               Description, ApprovedBy, Status)
                        VALUES (:employee_id, :incentive_amount, :incentive_type, :incentive_date, 
                                :description, :approved_by, :status)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':incentive_amount', $data['IncentiveAmount']);
                $stmt->bindParam(':incentive_type', $data['IncentiveType'] ?? 'Performance');
                $stmt->bindParam(':incentive_date', $data['IncentiveDate']);
                $stmt->bindParam(':description', $data['Description'] ?? null);
                $stmt->bindParam(':approved_by', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindParam(':status', $data['Status'] ?? 'Approved');
                $stmt->execute();
                
                $incentive_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Incentive added successfully', 'incentive_id' => $incentive_id], 201);
            }
            break;
            
        case '/employee-compensation':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                
                if (!$employee_id) {
                    sendError('Employee ID is required');
                }
                
                $sql = "SELECT ec.*, e.FirstName, e.LastName, e.EmployeeID,
                               cp.PlanName, cp.BaseSalary, cp.BonusPercentage, cp.CommissionRate,
                               cp.Allowances, cp.Benefits
                        FROM employeecompensation ec
                        JOIN Employees e ON ec.EmployeeID = e.EmployeeID
                        JOIN compensationplans cp ON ec.PlanID = cp.PlanID
                        WHERE ec.EmployeeID = :employee_id
                        ORDER BY ec.EffectiveDate DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->execute();
                $compensation = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($compensation);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['EmployeeID']) || empty($data['PlanID'])) {
                    sendError('Employee ID and plan ID are required');
                }
                
                // Check if employee already has an active compensation plan
                $sql_check = "SELECT COUNT(*) as count FROM employeecompensation WHERE EmployeeID = :employee_id AND Status = 'Active'";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt_check->execute();
                $existing_count = $stmt_check->fetchColumn();
                
                if ($existing_count > 0) {
                    sendError('Employee already has an active compensation plan. Please deactivate the current plan first.');
                }
                
                $sql = "INSERT INTO employeecompensation (EmployeeID, PlanID, EffectiveDate, Status, Notes)
                        VALUES (:employee_id, :plan_id, :effective_date, :status, :notes)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':plan_id', $data['PlanID'], PDO::PARAM_INT);
                $stmt->bindParam(':effective_date', $data['EffectiveDate'] ?? date('Y-m-d'));
                $stmt->bindParam(':status', $data['Status'] ?? 'Active');
                $stmt->bindParam(':notes', $data['Notes'] ?? null);
                $stmt->execute();
                
                $compensation_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Employee compensation assigned successfully', 'compensation_id' => $compensation_id], 201);
                
            } elseif ($method === 'PUT') {
                requireRole(['System Admin', 'HR Admin']);
                
                $compensation_id = $data['id'] ?? null;
                if (!$compensation_id) {
                    sendError('Compensation ID is required');
                }
                
                // Build dynamic update query
                $update_fields = [];
                $allowed_fields = ['PlanID', 'Status', 'Notes'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $update_fields[] = "{$field} = :{$field}";
                    }
                }
                
                if (empty($update_fields)) {
                    sendError('No valid fields to update');
                }
                
                $sql = "UPDATE employeecompensation SET " . implode(', ', $update_fields) . " WHERE CompensationID = :compensation_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':compensation_id', $compensation_id, PDO::PARAM_INT);
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $stmt->bindParam(":{$field}", $data[$field]);
                    }
                }
                
                $stmt->execute();
                
                sendResponse(['message' => 'Employee compensation updated successfully']);
            }
            break;
            
        case '/analytics':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $analytics = [];
                
                // Compensation plan distribution
                $stmt = $pdo->query("
                    SELECT cp.PlanName, COUNT(ec.EmployeeID) as employee_count
                    FROM compensationplans cp
                    LEFT JOIN employeecompensation ec ON cp.PlanID = ec.PlanID AND ec.Status = 'Active'
                    GROUP BY cp.PlanID, cp.PlanName
                    ORDER BY employee_count DESC
                ");
                $analytics['plan_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
                
                // Salary adjustments trend (last 12 months)
                $stmt = $pdo->query("
                    SELECT DATE_FORMAT(AdjustmentDate, '%Y-%m') as month, 
                           COUNT(*) as adjustment_count,
                           AVG(AdjustmentAmount) as avg_adjustment
                    FROM salaryadjustments
                    WHERE AdjustmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(AdjustmentDate, '%Y-%m')
                    ORDER BY month
                ");
                $analytics['salary_adjustment_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Incentive trends (last 12 months)
                $stmt = $pdo->query("
                    SELECT DATE_FORMAT(IncentiveDate, '%Y-%m') as month, 
                           COUNT(*) as incentive_count,
                           SUM(IncentiveAmount) as total_incentives
                    FROM incentives
                    WHERE IncentiveDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(IncentiveDate, '%Y-%m')
                    ORDER BY month
                ");
                $analytics['incentive_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Top performers by incentives
                $stmt = $pdo->query("
                    SELECT e.FirstName, e.LastName, d.DepartmentName,
                           COUNT(i.IncentiveID) as incentive_count,
                           SUM(i.IncentiveAmount) as total_incentives
                    FROM Employees e
                    LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                    LEFT JOIN incentives i ON e.EmployeeID = i.EmployeeID 
                        AND i.IncentiveDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    WHERE e.Status = 'Active'
                    GROUP BY e.EmployeeID
                    HAVING incentive_count > 0
                    ORDER BY total_incentives DESC
                    LIMIT 10
                ");
                $analytics['top_performers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($analytics);
            }
            break;
            
        case '/reports/compensation-summary':
            if ($method === 'GET') {
                requireRole(['System Admin', 'HR Admin']);
                
                $year = $data['year'] ?? date('Y');
                
                $sql = "SELECT e.EmployeeID, e.FirstName, e.LastName, d.DepartmentName,
                               cp.PlanName, cp.BaseSalary, cp.BonusPercentage, cp.CommissionRate,
                               s.BaseSalary as CurrentSalary,
                               (SELECT SUM(sa.AdjustmentAmount) FROM salaryadjustments sa 
                                WHERE sa.EmployeeID = e.EmployeeID 
                                AND YEAR(sa.AdjustmentDate) = :year 
                                AND sa.Status = 'Approved') as TotalAdjustments,
                               (SELECT SUM(i.IncentiveAmount) FROM incentives i 
                                WHERE i.EmployeeID = e.EmployeeID 
                                AND YEAR(i.IncentiveDate) = :year 
                                AND i.Status = 'Approved') as TotalIncentives
                        FROM Employees e
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN employeecompensation ec ON e.EmployeeID = ec.EmployeeID AND ec.Status = 'Active'
                        LEFT JOIN compensationplans cp ON ec.PlanID = cp.PlanID
                        LEFT JOIN salaries s ON e.EmployeeID = s.EmployeeID 
                            AND s.EffectiveDate <= CURDATE() 
                            AND (s.EndDate IS NULL OR s.EndDate >= CURDATE())
                        WHERE e.Status = 'Active'
                        ORDER BY e.LastName, e.FirstName";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                $stmt->execute();
                $compensation_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($compensation_summary);
            }
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("Compensation Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("Compensation Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

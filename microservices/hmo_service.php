<?php
/**
 * HMO Microservice
 * Handles HMO provider management, benefits plans, and employee benefits
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
    // Remove /hmo prefix from path
    $endpoint = str_replace('/api/v1/hmo', '', $path);
    
    switch ($endpoint) {
        case '/providers':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT hp.*, COUNT(eb.EmployeeID) as enrolled_employees
                        FROM hmoproviders hp
                        LEFT JOIN employeebenefits eb ON hp.ProviderID = eb.ProviderID
                        GROUP BY hp.ProviderID
                        ORDER BY hp.ProviderName";
                
                $stmt = $pdo->query($sql);
                $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($providers);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['ProviderName'])) {
                    sendError('Provider name is required');
                }
                
                $sql = "INSERT INTO hmoproviders (ProviderName, ContactPerson, Phone, Email, Address, CoverageDetails, IsActive)
                        VALUES (:provider_name, :contact_person, :phone, :email, :address, :coverage_details, :is_active)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':provider_name', $data['ProviderName']);
                $stmt->bindParam(':contact_person', $data['ContactPerson'] ?? null);
                $stmt->bindParam(':phone', $data['Phone'] ?? null);
                $stmt->bindParam(':email', $data['Email'] ?? null);
                $stmt->bindParam(':address', $data['Address'] ?? null);
                $stmt->bindParam(':coverage_details', $data['CoverageDetails'] ?? null);
                $stmt->bindParam(':is_active', $data['IsActive'] ?? 1, PDO::PARAM_INT);
                $stmt->execute();
                
                $provider_id = $pdo->lastInsertId();
                sendResponse(['message' => 'HMO provider created successfully', 'provider_id' => $provider_id], 201);
                
            } elseif ($method === 'PUT') {
                requireRole(['System Admin', 'HR Admin']);
                
                $provider_id = $data['id'] ?? null;
                if (!$provider_id) {
                    sendError('Provider ID is required');
                }
                
                // Build dynamic update query
                $update_fields = [];
                $allowed_fields = ['ProviderName', 'ContactPerson', 'Phone', 'Email', 'Address', 'CoverageDetails', 'IsActive'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $update_fields[] = "{$field} = :{$field}";
                    }
                }
                
                if (empty($update_fields)) {
                    sendError('No valid fields to update');
                }
                
                $sql = "UPDATE hmoproviders SET " . implode(', ', $update_fields) . " WHERE ProviderID = :provider_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $stmt->bindParam(":{$field}", $data[$field]);
                    }
                }
                
                $stmt->execute();
                
                sendResponse(['message' => 'HMO provider updated successfully']);
                
            } elseif ($method === 'DELETE') {
                requireRole(['System Admin', 'HR Admin']);
                
                $provider_id = $data['id'] ?? null;
                if (!$provider_id) {
                    sendError('Provider ID is required');
                }
                
                // Check if provider has enrolled employees
                $sql_check = "SELECT COUNT(*) as count FROM employeebenefits WHERE ProviderID = :provider_id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $enrolled_count = $stmt_check->fetchColumn();
                
                if ($enrolled_count > 0) {
                    sendError('Cannot delete provider with enrolled employees. Please reassign employees first.');
                }
                
                $sql = "DELETE FROM hmoproviders WHERE ProviderID = :provider_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
                $stmt->execute();
                
                sendResponse(['message' => 'HMO provider deleted successfully']);
            }
            break;
            
        case '/benefits-plans':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT bp.*, hp.ProviderName, COUNT(eb.EmployeeID) as enrolled_employees
                        FROM benefitsplans bp
                        LEFT JOIN hmoproviders hp ON bp.ProviderID = hp.ProviderID
                        LEFT JOIN employeebenefits eb ON bp.PlanID = eb.PlanID
                        GROUP BY bp.PlanID
                        ORDER BY bp.PlanName";
                
                $stmt = $pdo->query($sql);
                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($plans);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['PlanName']) || empty($data['ProviderID'])) {
                    sendError('Plan name and provider ID are required');
                }
                
                $sql = "INSERT INTO benefitsplans (PlanName, ProviderID, CoverageType, MonthlyPremium, CoverageDetails, IsActive)
                        VALUES (:plan_name, :provider_id, :coverage_type, :monthly_premium, :coverage_details, :is_active)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':plan_name', $data['PlanName']);
                $stmt->bindParam(':provider_id', $data['ProviderID'], PDO::PARAM_INT);
                $stmt->bindParam(':coverage_type', $data['CoverageType'] ?? null);
                $stmt->bindParam(':monthly_premium', $data['MonthlyPremium'] ?? 0);
                $stmt->bindParam(':coverage_details', $data['CoverageDetails'] ?? null);
                $stmt->bindParam(':is_active', $data['IsActive'] ?? 1, PDO::PARAM_INT);
                $stmt->execute();
                
                $plan_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Benefits plan created successfully', 'plan_id' => $plan_id], 201);
                
            } elseif ($method === 'PUT') {
                requireRole(['System Admin', 'HR Admin']);
                
                $plan_id = $data['id'] ?? null;
                if (!$plan_id) {
                    sendError('Plan ID is required');
                }
                
                // Build dynamic update query
                $update_fields = [];
                $allowed_fields = ['PlanName', 'ProviderID', 'CoverageType', 'MonthlyPremium', 'CoverageDetails', 'IsActive'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $update_fields[] = "{$field} = :{$field}";
                    }
                }
                
                if (empty($update_fields)) {
                    sendError('No valid fields to update');
                }
                
                $sql = "UPDATE benefitsplans SET " . implode(', ', $update_fields) . " WHERE PlanID = :plan_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $stmt->bindParam(":{$field}", $data[$field]);
                    }
                }
                
                $stmt->execute();
                
                sendResponse(['message' => 'Benefits plan updated successfully']);
                
            } elseif ($method === 'DELETE') {
                requireRole(['System Admin', 'HR Admin']);
                
                $plan_id = $data['id'] ?? null;
                if (!$plan_id) {
                    sendError('Plan ID is required');
                }
                
                // Check if plan has enrolled employees
                $sql_check = "SELECT COUNT(*) as count FROM employeebenefits WHERE PlanID = :plan_id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $enrolled_count = $stmt_check->fetchColumn();
                
                if ($enrolled_count > 0) {
                    sendError('Cannot delete plan with enrolled employees. Please reassign employees first.');
                }
                
                $sql = "DELETE FROM benefitsplans WHERE PlanID = :plan_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
                $stmt->execute();
                
                sendResponse(['message' => 'Benefits plan deleted successfully']);
            }
            break;
            
        case '/employee-benefits':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                
                if (!$employee_id) {
                    sendError('Employee ID is required');
                }
                
                $sql = "SELECT eb.*, e.FirstName, e.LastName, e.EmployeeID,
                               bp.PlanName, bp.CoverageType, bp.MonthlyPremium,
                               hp.ProviderName, hp.ContactPerson, hp.Phone, hp.Email
                        FROM employeebenefits eb
                        JOIN Employees e ON eb.EmployeeID = e.EmployeeID
                        JOIN benefitsplans bp ON eb.PlanID = bp.PlanID
                        JOIN hmoproviders hp ON eb.ProviderID = hp.ProviderID
                        WHERE eb.EmployeeID = :employee_id
                        ORDER BY eb.EnrollmentDate DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->execute();
                $benefits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($benefits);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['EmployeeID']) || empty($data['PlanID']) || empty($data['ProviderID'])) {
                    sendError('Employee ID, plan ID, and provider ID are required');
                }
                
                // Check if employee already has an active benefit
                $sql_check = "SELECT COUNT(*) as count FROM employeebenefits WHERE EmployeeID = :employee_id AND Status = 'Active'";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt_check->execute();
                $existing_count = $stmt_check->fetchColumn();
                
                if ($existing_count > 0) {
                    sendError('Employee already has an active benefit plan. Please deactivate the current plan first.');
                }
                
                $sql = "INSERT INTO employeebenefits (EmployeeID, PlanID, ProviderID, EnrollmentDate, Status, Notes)
                        VALUES (:employee_id, :plan_id, :provider_id, :enrollment_date, :status, :notes)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':plan_id', $data['PlanID'], PDO::PARAM_INT);
                $stmt->bindParam(':provider_id', $data['ProviderID'], PDO::PARAM_INT);
                $stmt->bindParam(':enrollment_date', $data['EnrollmentDate'] ?? date('Y-m-d'));
                $stmt->bindParam(':status', $data['Status'] ?? 'Active');
                $stmt->bindParam(':notes', $data['Notes'] ?? null);
                $stmt->execute();
                
                $benefit_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Employee benefit assigned successfully', 'benefit_id' => $benefit_id], 201);
                
            } elseif ($method === 'PUT') {
                requireRole(['System Admin', 'HR Admin']);
                
                $benefit_id = $data['id'] ?? null;
                if (!$benefit_id) {
                    sendError('Benefit ID is required');
                }
                
                // Build dynamic update query
                $update_fields = [];
                $allowed_fields = ['PlanID', 'ProviderID', 'Status', 'Notes'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $update_fields[] = "{$field} = :{$field}";
                    }
                }
                
                if (empty($update_fields)) {
                    sendError('No valid fields to update');
                }
                
                $sql = "UPDATE employeebenefits SET " . implode(', ', $update_fields) . " WHERE BenefitID = :benefit_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':benefit_id', $benefit_id, PDO::PARAM_INT);
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $stmt->bindParam(":{$field}", $data[$field]);
                    }
                }
                
                $stmt->execute();
                
                sendResponse(['message' => 'Employee benefit updated successfully']);
            }
            break;
            
        case '/claims':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                $status = $data['status'] ?? null;
                
                $sql = "SELECT c.*, e.FirstName, e.LastName, e.EmployeeID,
                               ct.ClaimTypeName, hp.ProviderName
                        FROM Claims c
                        JOIN Employees e ON c.EmployeeID = e.EmployeeID
                        LEFT JOIN claimtypes ct ON c.ClaimTypeID = ct.ClaimTypeID
                        LEFT JOIN hmoproviders hp ON c.ProviderID = hp.ProviderID";
                
                $params = [];
                
                if ($employee_id && $_SESSION['role_name'] !== 'System Admin' && $_SESSION['role_name'] !== 'HR Admin') {
                    $sql .= " WHERE c.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                } elseif ($employee_id) {
                    $sql .= " WHERE c.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                }
                
                if ($status) {
                    $sql .= (empty($params) ? " WHERE" : " AND") . " c.Status = :status";
                    $params[':status'] = $status;
                }
                
                $sql .= " ORDER BY c.ClaimDate DESC";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($claims);
                
            } elseif ($method === 'POST') {
                requireAuth();
                
                if (empty($data['EmployeeID']) || empty($data['ClaimAmount']) || empty($data['ClaimDate'])) {
                    sendError('Employee ID, claim amount, and claim date are required');
                }
                
                $sql = "INSERT INTO Claims (EmployeeID, ClaimTypeID, ProviderID, ClaimAmount, ClaimDate, Description, Status, SubmittedDate)
                        VALUES (:employee_id, :claim_type_id, :provider_id, :claim_amount, :claim_date, :description, :status, :submitted_date)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
                $stmt->bindParam(':claim_type_id', $data['ClaimTypeID'] ?? null, PDO::PARAM_INT);
                $stmt->bindParam(':provider_id', $data['ProviderID'] ?? null, PDO::PARAM_INT);
                $stmt->bindParam(':claim_amount', $data['ClaimAmount']);
                $stmt->bindParam(':claim_date', $data['ClaimDate']);
                $stmt->bindParam(':description', $data['Description'] ?? null);
                $stmt->bindParam(':status', $data['Status'] ?? 'Submitted');
                $stmt->bindParam(':submitted_date', date('Y-m-d H:i:s'));
                $stmt->execute();
                
                $claim_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Claim submitted successfully', 'claim_id' => $claim_id], 201);
            }
            break;
            
        case '/claim-types':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT * FROM claimtypes ORDER BY ClaimTypeName";
                $stmt = $pdo->query($sql);
                $claim_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($claim_types);
            }
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("HMO Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("HMO Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

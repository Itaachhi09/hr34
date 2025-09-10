<?php
/**
 * Core HR Microservice
 * Handles employee management, organizational structure, documents, and attendance
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
    // Remove /core-hr prefix from path
    $endpoint = str_replace('/api/v1/core-hr', '', $path);
    
    switch ($endpoint) {
        case '/employees':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT e.EmployeeID, e.FirstName, e.MiddleName, e.LastName, e.Suffix,
                               e.Email, e.PersonalEmail, e.Phone as PhoneNumber, e.DateOfBirth,
                               e.Gender, e.MaritalStatus, e.Nationality, e.AddressLine1, e.AddressLine2,
                               e.City, e.StateProvince, e.PostalCode, e.Country, e.EmergencyContactName,
                               e.EmergencyContactRelationship, e.EmergencyContactPhone, e.HireDate,
                               jr.RoleName AS JobTitle, e.DepartmentID, d.DepartmentName, e.ManagerID,
                               CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName, e.Status,
                               e.TerminationDate, e.TerminationReason, e.EmployeePhotoPath, u.UserID
                        FROM Employees e
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN Employees m ON e.ManagerID = m.EmployeeID
                        LEFT JOIN jobroles jr ON e.JobRoleID = jr.JobRoleID
                        LEFT JOIN Users u ON e.EmployeeID = u.EmployeeID
                        ORDER BY e.LastName, e.FirstName";
                
                $stmt = $pdo->query($sql);
                $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format dates
                foreach ($employees as &$employee) {
                    if (!empty($employee['HireDate'])) {
                        $employee['HireDateFormatted'] = date('M d, Y', strtotime($employee['HireDate']));
                    }
                    if (!empty($employee['DateOfBirth'])) {
                        $employee['DateOfBirthFormatted'] = date('M d, Y', strtotime($employee['DateOfBirth']));
                    }
                    if (!empty($employee['TerminationDate'])) {
                        $employee['TerminationDateFormatted'] = date('M d, Y', strtotime($employee['TerminationDate']));
                    }
                    $employee['Status'] = $employee['Status'] ?: 'Active';
                }
                unset($employee);
                
                sendResponse($employees);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                $required_fields = ['FirstName', 'LastName', 'Email', 'HireDate', 'DepartmentID', 'JobRoleID'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        sendError("Field '{$field}' is required");
                    }
                }
                
                $sql = "INSERT INTO Employees (FirstName, MiddleName, LastName, Suffix, Email, PersonalEmail,
                                             Phone, DateOfBirth, Gender, MaritalStatus, Nationality,
                                             AddressLine1, AddressLine2, City, StateProvince, PostalCode,
                                             Country, EmergencyContactName, EmergencyContactRelationship,
                                             EmergencyContactPhone, HireDate, JobRoleID, DepartmentID,
                                             ManagerID, Status, EmployeePhotoPath)
                        VALUES (:first_name, :middle_name, :last_name, :suffix, :email, :personal_email,
                                :phone, :date_of_birth, :gender, :marital_status, :nationality,
                                :address_line1, :address_line2, :city, :state_province, :postal_code,
                                :country, :emergency_contact_name, :emergency_contact_relationship,
                                :emergency_contact_phone, :hire_date, :job_role_id, :department_id,
                                :manager_id, :status, :employee_photo_path)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':first_name', $data['FirstName']);
                $stmt->bindParam(':middle_name', $data['MiddleName'] ?? null);
                $stmt->bindParam(':last_name', $data['LastName']);
                $stmt->bindParam(':suffix', $data['Suffix'] ?? null);
                $stmt->bindParam(':email', $data['Email']);
                $stmt->bindParam(':personal_email', $data['PersonalEmail'] ?? null);
                $stmt->bindParam(':phone', $data['Phone'] ?? null);
                $stmt->bindParam(':date_of_birth', $data['DateOfBirth'] ?? null);
                $stmt->bindParam(':gender', $data['Gender'] ?? null);
                $stmt->bindParam(':marital_status', $data['MaritalStatus'] ?? null);
                $stmt->bindParam(':nationality', $data['Nationality'] ?? null);
                $stmt->bindParam(':address_line1', $data['AddressLine1'] ?? null);
                $stmt->bindParam(':address_line2', $data['AddressLine2'] ?? null);
                $stmt->bindParam(':city', $data['City'] ?? null);
                $stmt->bindParam(':state_province', $data['StateProvince'] ?? null);
                $stmt->bindParam(':postal_code', $data['PostalCode'] ?? null);
                $stmt->bindParam(':country', $data['Country'] ?? null);
                $stmt->bindParam(':emergency_contact_name', $data['EmergencyContactName'] ?? null);
                $stmt->bindParam(':emergency_contact_relationship', $data['EmergencyContactRelationship'] ?? null);
                $stmt->bindParam(':emergency_contact_phone', $data['EmergencyContactPhone'] ?? null);
                $stmt->bindParam(':hire_date', $data['HireDate']);
                $stmt->bindParam(':job_role_id', $data['JobRoleID']);
                $stmt->bindParam(':department_id', $data['DepartmentID']);
                $stmt->bindParam(':manager_id', $data['ManagerID'] ?? null);
                $stmt->bindParam(':status', $data['Status'] ?? 'Active');
                $stmt->bindParam(':employee_photo_path', $data['EmployeePhotoPath'] ?? null);
                
                $stmt->execute();
                $employee_id = $pdo->lastInsertId();
                
                sendResponse(['message' => 'Employee created successfully', 'employee_id' => $employee_id], 201);
            }
            break;
            
        case '/employees/{id}':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['id'] ?? null;
                if (!$employee_id) {
                    sendError('Employee ID is required');
                }
                
                $sql = "SELECT e.*, d.DepartmentName, jr.RoleName AS JobTitle,
                               CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName
                        FROM Employees e
                        LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN jobroles jr ON e.JobRoleID = jr.JobRoleID
                        LEFT JOIN Employees m ON e.ManagerID = m.EmployeeID
                        WHERE e.EmployeeID = :employee_id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->execute();
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$employee) {
                    sendError('Employee not found', 404);
                }
                
                sendResponse($employee);
                
            } elseif ($method === 'PUT') {
                requireRole(['System Admin', 'HR Admin']);
                
                $employee_id = $data['id'] ?? null;
                if (!$employee_id) {
                    sendError('Employee ID is required');
                }
                
                // Build dynamic update query
                $update_fields = [];
                $allowed_fields = ['FirstName', 'MiddleName', 'LastName', 'Suffix', 'Email', 'PersonalEmail',
                                 'Phone', 'DateOfBirth', 'Gender', 'MaritalStatus', 'Nationality',
                                 'AddressLine1', 'AddressLine2', 'City', 'StateProvince', 'PostalCode',
                                 'Country', 'EmergencyContactName', 'EmergencyContactRelationship',
                                 'EmergencyContactPhone', 'JobRoleID', 'DepartmentID', 'ManagerID', 'Status'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $update_fields[] = "{$field} = :{$field}";
                    }
                }
                
                if (empty($update_fields)) {
                    sendError('No valid fields to update');
                }
                
                $sql = "UPDATE Employees SET " . implode(', ', $update_fields) . " WHERE EmployeeID = :employee_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $stmt->bindParam(":{$field}", $data[$field]);
                    }
                }
                
                $stmt->execute();
                
                sendResponse(['message' => 'Employee updated successfully']);
            }
            break;
            
        case '/departments':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT d.*, COUNT(e.EmployeeID) as employee_count
                        FROM departments d
                        LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.Status = 'Active'
                        GROUP BY d.DepartmentID
                        ORDER BY d.DepartmentName";
                
                $stmt = $pdo->query($sql);
                $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($departments);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['DepartmentName'])) {
                    sendError('Department name is required');
                }
                
                $sql = "INSERT INTO departments (DepartmentName, Description, ManagerID)
                        VALUES (:department_name, :description, :manager_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':department_name', $data['DepartmentName']);
                $stmt->bindParam(':description', $data['Description'] ?? null);
                $stmt->bindParam(':manager_id', $data['ManagerID'] ?? null);
                $stmt->execute();
                
                $department_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Department created successfully', 'department_id' => $department_id], 201);
            }
            break;
            
        case '/attendance':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                $date_from = $data['date_from'] ?? date('Y-m-01');
                $date_to = $data['date_to'] ?? date('Y-m-t');
                
                $sql = "SELECT ar.*, e.FirstName, e.LastName
                        FROM attendancerecords ar
                        JOIN Employees e ON ar.EmployeeID = e.EmployeeID
                        WHERE ar.Date BETWEEN :date_from AND :date_to";
                
                $params = [':date_from' => $date_from, ':date_to' => $date_to];
                
                if ($employee_id && $_SESSION['role_name'] !== 'System Admin' && $_SESSION['role_name'] !== 'HR Admin') {
                    $sql .= " AND ar.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                } elseif ($employee_id) {
                    $sql .= " AND ar.EmployeeID = :employee_id";
                    $params[':employee_id'] = $employee_id;
                }
                
                $sql .= " ORDER BY ar.Date DESC, ar.CheckInTime DESC";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($attendance);
                
            } elseif ($method === 'POST') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                $date = $data['date'] ?? date('Y-m-d');
                $check_in_time = $data['check_in_time'] ?? date('H:i:s');
                $check_out_time = $data['check_out_time'] ?? null;
                $status = $data['status'] ?? 'Present';
                
                if (!$employee_id) {
                    sendError('Employee ID is required');
                }
                
                // Check if record already exists for this date
                $sql_check = "SELECT RecordID FROM attendancerecords WHERE EmployeeID = :employee_id AND Date = :date";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt_check->bindParam(':date', $date);
                $stmt_check->execute();
                
                if ($stmt_check->fetch()) {
                    // Update existing record
                    $sql = "UPDATE attendancerecords SET CheckInTime = :check_in_time, CheckOutTime = :check_out_time, Status = :status
                            WHERE EmployeeID = :employee_id AND Date = :date";
                } else {
                    // Insert new record
                    $sql = "INSERT INTO attendancerecords (EmployeeID, Date, CheckInTime, CheckOutTime, Status)
                            VALUES (:employee_id, :date, :check_in_time, :check_out_time, :status)";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->bindParam(':date', $date);
                $stmt->bindParam(':check_in_time', $check_in_time);
                $stmt->bindParam(':check_out_time', $check_out_time);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
                
                sendResponse(['message' => 'Attendance record saved successfully'], 201);
            }
            break;
            
        case '/documents':
            if ($method === 'GET') {
                requireAuth();
                
                $employee_id = $data['employee_id'] ?? $_SESSION['employee_id'];
                
                if (!$employee_id) {
                    sendError('Employee ID is required');
                }
                
                $sql = "SELECT ed.*, e.FirstName, e.LastName
                        FROM employeedocuments ed
                        JOIN Employees e ON ed.EmployeeID = e.EmployeeID
                        WHERE ed.EmployeeID = :employee_id
                        ORDER BY ed.UploadDate DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->execute();
                $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($documents);
            }
            break;
            
        case '/org-structure':
            if ($method === 'GET') {
                requireAuth();
                
                $sql = "SELECT d.DepartmentID, d.DepartmentName, d.Description,
                               m.EmployeeID as ManagerID, CONCAT(m.FirstName, ' ', m.LastName) as ManagerName,
                               COUNT(e.EmployeeID) as EmployeeCount
                        FROM departments d
                        LEFT JOIN Employees m ON d.ManagerID = m.EmployeeID
                        LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.Status = 'Active'
                        GROUP BY d.DepartmentID
                        ORDER BY d.DepartmentName";
                
                $stmt = $pdo->query($sql);
                $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse($structure);
            }
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("Core HR Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("Core HR Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

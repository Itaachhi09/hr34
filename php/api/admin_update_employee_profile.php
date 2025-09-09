<?php
/**
 * API Endpoint: Admin Update Employee Profile
 * Allows an admin to update an employee's profile information.
 */

// --- Error Reporting & Headers ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/../../php-error.log'); // Ensure this path is writable

session_start(); // Needed for authentication

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Access-Control-Allow-Methods: POST, OPTIONS'); // Use POST for updates
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Database Connection ---
$pdo = null;
try {
    require_once '../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception('DB connection failed');
    }
} catch (Throwable $e) {
    error_log("PHP Error in admin_update_employee_profile.php (db_connect include): " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Server configuration error.']);
    exit;
}

// --- Authorization Check ---
// Only allow System Admins (RoleID 1) to update employee profiles
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Permission denied. Administrator access required.']);
    exit;
}
// --- End Authorization Check ---

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

// --- Get Data from POST Request (expecting JSON) ---
$input_data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload received.']);
    exit;
}

// --- Extract and sanitize data ---
$employee_id_to_update = isset($input_data['employee_id_to_update']) ? filter_var($input_data['employee_id_to_update'], FILTER_VALIDATE_INT) : null;

// Employee Fields (allow null if not provided, they might not be updating all fields)
$first_name = isset($input_data['first_name']) ? trim(htmlspecialchars($input_data['first_name'])) : null;
$last_name = isset($input_data['last_name']) ? trim(htmlspecialchars($input_data['last_name'])) : null;
$middle_name = isset($input_data['middle_name']) ? trim(htmlspecialchars($input_data['middle_name'])) : null;
$suffix = isset($input_data['suffix']) ? trim(htmlspecialchars($input_data['suffix'])) : null;
$email = isset($input_data['email']) ? filter_var(trim($input_data['email']), FILTER_VALIDATE_EMAIL) : null;
$personal_email = isset($input_data['personal_email']) ? filter_var(trim($input_data['personal_email']), FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE) : null;
$phone_number = isset($input_data['phone_number']) ? trim(htmlspecialchars($input_data['phone_number'])) : null;
$date_of_birth = isset($input_data['date_of_birth']) ? $input_data['date_of_birth'] : null; // Expect YYYY-MM-DD
$gender = isset($input_data['gender']) ? trim(htmlspecialchars($input_data['gender'])) : null;
$marital_status = isset($input_data['marital_status']) ? trim(htmlspecialchars($input_data['marital_status'])) : null;
$nationality = isset($input_data['nationality']) ? trim(htmlspecialchars($input_data['nationality'])) : null;
$address_line1 = isset($input_data['address_line1']) ? trim(htmlspecialchars($input_data['address_line1'])) : null;
$address_line2 = isset($input_data['address_line2']) ? trim(htmlspecialchars($input_data['address_line2'])) : null;
$city = isset($input_data['city']) ? trim(htmlspecialchars($input_data['city'])) : null;
$state_province = isset($input_data['state_province']) ? trim(htmlspecialchars($input_data['state_province'])) : null;
$postal_code = isset($input_data['postal_code']) ? trim(htmlspecialchars($input_data['postal_code'])) : null;
$country = isset($input_data['country']) ? trim(htmlspecialchars($input_data['country'])) : null;
$emergency_contact_name = isset($input_data['emergency_contact_name']) ? trim(htmlspecialchars($input_data['emergency_contact_name'])) : null;
$emergency_contact_relationship = isset($input_data['emergency_contact_relationship']) ? trim(htmlspecialchars($input_data['emergency_contact_relationship'])) : null;
$emergency_contact_phone = isset($input_data['emergency_contact_phone']) ? trim(htmlspecialchars($input_data['emergency_contact_phone'])) : null;
$job_title = isset($input_data['job_title']) ? trim(htmlspecialchars($input_data['job_title'])) : null;
$department_id = isset($input_data['department_id']) ? filter_var($input_data['department_id'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null;
$manager_id = isset($input_data['manager_id']) ? filter_var($input_data['manager_id'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null;
$is_active_employee = isset($input_data['is_active_employee']) ? filter_var($input_data['is_active_employee'], FILTER_VALIDATE_INT) : null; // 0 or 1 for employee status
$hire_date = isset($input_data['hire_date']) ? $input_data['hire_date'] : null; // Expect YYYY-MM-DD

// --- Validate Input ---
$errors = [];
if (empty($employee_id_to_update) || $employee_id_to_update <= 0) {
    $errors['employee_id_to_update'] = 'A valid Employee ID to update is required.';
}
// Basic presence checks for core fields if they are being updated
if (array_key_exists('first_name', $input_data) && empty($first_name)) $errors['first_name'] = 'First Name cannot be empty if provided for update.';
if (array_key_exists('last_name', $input_data) && empty($last_name)) $errors['last_name'] = 'Last Name cannot be empty if provided for update.';
if (array_key_exists('email', $input_data) && empty($email)) $errors['email'] = 'A valid Work Email is required if provided for update.';
if (array_key_exists('job_title', $input_data) && empty($job_title)) $errors['job_title'] = 'Job Title cannot be empty if provided for update.';
if (array_key_exists('department_id', $input_data) && ($department_id === null || $department_id <= 0)) $errors['department_id'] = 'A valid Department ID is required if provided for update.';

if (array_key_exists('personal_email', $input_data) && !empty($input_data['personal_email']) && $personal_email === false) {
    $errors['personal_email'] = 'Personal Email is not a valid email format if provided.';
}
if (array_key_exists('phone_number', $input_data) && !empty($phone_number) && !preg_match('/^[0-9\+\-\s\(\)]*$/', $phone_number)) {
    $errors['phone_number'] = 'Invalid phone number format if provided.';
}
if (array_key_exists('emergency_contact_phone', $input_data) && !empty($emergency_contact_phone) && !preg_match('/^[0-9\+\-\s\(\)]*$/', $emergency_contact_phone)) {
    $errors['emergency_contact_phone'] = 'Invalid emergency contact phone format if provided.';
}
if (array_key_exists('date_of_birth', $input_data) && !empty($date_of_birth) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
    $errors['date_of_birth'] = 'Date of Birth must be YYYY-MM-DD if provided.';
}
if (array_key_exists('hire_date', $input_data) && !empty($hire_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hire_date)) {
    $errors['hire_date'] = 'Hire Date must be YYYY-MM-DD if provided.';
}
if ($is_active_employee !== null && !in_array($is_active_employee, [0, 1])) {
    $errors['is_active_employee'] = 'Employee active status must be 0 or 1 if provided.';
}


// Check for duplicate work email if it's being changed
if (empty($errors) && array_key_exists('email', $input_data) && $email) {
    try {
        $checkEmailSql = "SELECT EmployeeID FROM Employees WHERE Email = :email AND EmployeeID != :employee_id_to_update";
        $checkEmailStmt = $pdo->prepare($checkEmailSql);
        $checkEmailStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkEmailStmt->bindParam(':employee_id_to_update', $employee_id_to_update, PDO::PARAM_INT);
        $checkEmailStmt->execute();
        if ($checkEmailStmt->fetch()) {
             $errors['email_duplicate'] = 'This work email address is already in use by another employee.';
        }
    } catch (\PDOException $e) {
         error_log("API Error (admin_update_employee_profile - Work Email Check): " . $e->getMessage());
         $errors['database_check_work_email'] = 'Error checking work email uniqueness.';
    }
}
// Check for duplicate personal email if it's being changed
if (empty($errors) && array_key_exists('personal_email', $input_data) && $personal_email) {
    try {
        $checkPersonalEmailSql = "SELECT EmployeeID FROM Employees WHERE PersonalEmail = :personal_email AND EmployeeID != :employee_id_to_update";
        $checkPersonalEmailStmt = $pdo->prepare($checkPersonalEmailSql);
        $checkPersonalEmailStmt->bindParam(':personal_email', $personal_email, PDO::PARAM_STR);
        $checkPersonalEmailStmt->bindParam(':employee_id_to_update', $employee_id_to_update, PDO::PARAM_INT);
        $checkPersonalEmailStmt->execute();
        if ($checkPersonalEmailStmt->fetch()) {
             $errors['personal_email_duplicate'] = 'This personal email address is already in use by another employee.';
        }
    } catch (\PDOException $e) {
         error_log("API Error (admin_update_employee_profile - Personal Email Check): " . $e->getMessage());
         $errors['database_check_personal_email'] = 'Error checking personal email uniqueness.';
    }
}


if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation failed.', 'details' => $errors]);
    exit;
}
// --- End Validation ---

// --- Update Database ---
try {
    $pdo->beginTransaction();

    $update_fields_employee = [];
    $params_employee = [':employee_id_to_update' => $employee_id_to_update];

    // Dynamically build the SET part of the SQL query for Employees table
    if (array_key_exists('first_name', $input_data)) { $update_fields_employee[] = "FirstName = :first_name"; $params_employee[':first_name'] = $first_name; }
    if (array_key_exists('last_name', $input_data)) { $update_fields_employee[] = "LastName = :last_name"; $params_employee[':last_name'] = $last_name; }
    if (array_key_exists('middle_name', $input_data)) { $update_fields_employee[] = "MiddleName = :middle_name"; $params_employee[':middle_name'] = $middle_name ?: null; }
    if (array_key_exists('suffix', $input_data)) { $update_fields_employee[] = "Suffix = :suffix"; $params_employee[':suffix'] = $suffix ?: null; }
    if (array_key_exists('email', $input_data)) { $update_fields_employee[] = "Email = :email"; $params_employee[':email'] = $email; }
    if (array_key_exists('personal_email', $input_data)) { $update_fields_employee[] = "PersonalEmail = :personal_email"; $params_employee[':personal_email'] = $personal_email; }
    if (array_key_exists('phone_number', $input_data)) { $update_fields_employee[] = "PhoneNumber = :phone_number"; $params_employee[':phone_number'] = $phone_number; }
    if (array_key_exists('date_of_birth', $input_data)) { $update_fields_employee[] = "DateOfBirth = :date_of_birth"; $params_employee[':date_of_birth'] = $date_of_birth ?: null; }
    if (array_key_exists('gender', $input_data)) { $update_fields_employee[] = "Gender = :gender"; $params_employee[':gender'] = $gender ?: null; }
    if (array_key_exists('marital_status', $input_data)) { $update_fields_employee[] = "MaritalStatus = :marital_status"; $params_employee[':marital_status'] = $marital_status ?: null; }
    if (array_key_exists('nationality', $input_data)) { $update_fields_employee[] = "Nationality = :nationality"; $params_employee[':nationality'] = $nationality ?: null; }
    if (array_key_exists('address_line1', $input_data)) { $update_fields_employee[] = "AddressLine1 = :address_line1"; $params_employee[':address_line1'] = $address_line1 ?: null; }
    if (array_key_exists('address_line2', $input_data)) { $update_fields_employee[] = "AddressLine2 = :address_line2"; $params_employee[':address_line2'] = $address_line2 ?: null; }
    if (array_key_exists('city', $input_data)) { $update_fields_employee[] = "City = :city"; $params_employee[':city'] = $city ?: null; }
    if (array_key_exists('state_province', $input_data)) { $update_fields_employee[] = "StateProvince = :state_province"; $params_employee[':state_province'] = $state_province ?: null; }
    if (array_key_exists('postal_code', $input_data)) { $update_fields_employee[] = "PostalCode = :postal_code"; $params_employee[':postal_code'] = $postal_code ?: null; }
    if (array_key_exists('country', $input_data)) { $update_fields_employee[] = "Country = :country"; $params_employee[':country'] = $country ?: null; }
    if (array_key_exists('emergency_contact_name', $input_data)) { $update_fields_employee[] = "EmergencyContactName = :emergency_contact_name"; $params_employee[':emergency_contact_name'] = $emergency_contact_name ?: null; }
    if (array_key_exists('emergency_contact_relationship', $input_data)) { $update_fields_employee[] = "EmergencyContactRelationship = :emergency_contact_relationship"; $params_employee[':emergency_contact_relationship'] = $emergency_contact_relationship ?: null; }
    if (array_key_exists('emergency_contact_phone', $input_data)) { $update_fields_employee[] = "EmergencyContactPhone = :emergency_contact_phone"; $params_employee[':emergency_contact_phone'] = $emergency_contact_phone ?: null; }
    if (array_key_exists('job_title', $input_data)) { $update_fields_employee[] = "JobTitle = :job_title"; $params_employee[':job_title'] = $job_title; }
    if (array_key_exists('department_id', $input_data)) { $update_fields_employee[] = "DepartmentID = :department_id"; $params_employee[':department_id'] = $department_id; }
    if (array_key_exists('manager_id', $input_data)) { $update_fields_employee[] = "ManagerID = :manager_id"; $params_employee[':manager_id'] = $manager_id ?: null; }
    if (array_key_exists('hire_date', $input_data)) { $update_fields_employee[] = "HireDate = :hire_date"; $params_employee[':hire_date'] = $hire_date ?: null; }
    if ($is_active_employee !== null) { $update_fields_employee[] = "IsActive = :is_active_employee"; $params_employee[':is_active_employee'] = $is_active_employee; }


    $employee_updated = false;
    if (!empty($update_fields_employee)) {
        $sql_employee = "UPDATE Employees SET " . implode(", ", $update_fields_employee) . " WHERE EmployeeID = :employee_id_to_update";
        $stmt_employee = $pdo->prepare($sql_employee);
        $stmt_employee->execute($params_employee);
        if ($stmt_employee->rowCount() > 0) {
            $employee_updated = true;
        }
    }

    // If employee's IsActive status was changed, update the corresponding User's IsActive status
    $user_status_updated = false;
    if ($is_active_employee !== null) { // Only if 'is_active_employee' was in the input
        $sql_user_status = "UPDATE Users SET IsActive = :is_active_user WHERE EmployeeID = :employee_id_to_update";
        $stmt_user_status = $pdo->prepare($sql_user_status);
        $stmt_user_status->bindParam(':is_active_user', $is_active_employee, PDO::PARAM_INT);
        $stmt_user_status->bindParam(':employee_id_to_update', $employee_id_to_update, PDO::PARAM_INT);
        $stmt_user_status->execute();
        if ($stmt_user_status->rowCount() > 0) {
            $user_status_updated = true;
        }
    }

    if ($employee_updated || $user_status_updated) {
        $pdo->commit();
        http_response_code(200);
        echo json_encode([
            'message' => 'Employee profile updated successfully.',
            'employee_id' => $employee_id_to_update
        ]);
    } else {
        $pdo->rollBack(); // Nothing changed, or employee not found
        // Check if the employee exists
        $checkStmt = $pdo->prepare("SELECT EmployeeID FROM Employees WHERE EmployeeID = :employee_id_to_update");
        $checkStmt->bindParam(':employee_id_to_update', $employee_id_to_update, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->fetch()) {
            http_response_code(200);
            echo json_encode(['message' => 'Employee profile information submitted, but no changes were detected.']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found with the specified ID.']);
        }
    }

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("PHP PDOException in admin_update_employee_profile.php: " . $e->getMessage());
    if ($e->getCode() == '23000') {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Data conflict. This email or username might already be in use, or a foreign key (Department, Manager) is invalid.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error updating employee profile.']);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("PHP Throwable in admin_update_employee_profile.php: " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Unexpected server error updating employee profile.']);
}
exit;
?>

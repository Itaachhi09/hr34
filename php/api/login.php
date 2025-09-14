<?php
/**
 * Login API Endpoint
 * Handles user authentication and session management
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

$input_data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload received.']);
    exit;
}

$username = isset($input_data['username']) ? trim($input_data['username']) : null;
$password = isset($input_data['password']) ? $input_data['password'] : null;

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required.']);
    exit;
}

// Development bypass for localhost
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'])) {
    // Create a mock user based on username
    $mockUsers = [
        'admin' => ['role' => 'System Admin', 'user_id' => 1, 'employee_id' => 1],
        'hrmanager' => ['role' => 'HR Admin', 'user_id' => 2, 'employee_id' => 2],
        'hrstaff' => ['role' => 'HR Staff', 'user_id' => 3, 'employee_id' => 3]
    ];
    
    if (isset($mockUsers[$username])) {
        $user = $mockUsers[$username];
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['username'] = $username;
        $_SESSION['role_id'] = 1;
        $_SESSION['role_name'] = $user['role'];
        $_SESSION['full_name'] = ucfirst($username);
        
        echo json_encode([
            'message' => 'Login successful (dev mode).',
            'two_factor_required' => false,
            'user' => [
                'user_id' => $user['user_id'],
                'employee_id' => $user['employee_id'],
                'username' => $username,
                'full_name' => $_SESSION['full_name'],
                'role_name' => $user['role']
            ]
        ]);
        exit;
    }
}

try {
    // Check if Users table exists and has data
    $stmt = $pdo->query("SHOW TABLES LIKE 'Users'");
    if ($stmt->rowCount() === 0) {
        // Create basic tables if they don't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Roles (
                RoleID INT PRIMARY KEY AUTO_INCREMENT,
                RoleName VARCHAR(100) NOT NULL UNIQUE
            );
            
            CREATE TABLE IF NOT EXISTS Users (
                UserID INT PRIMARY KEY AUTO_INCREMENT,
                EmployeeID INT NULL,
                Username VARCHAR(100) NOT NULL UNIQUE,
                PasswordHash VARCHAR(255) NOT NULL,
                RoleID INT NOT NULL,
                IsActive TINYINT(1) NOT NULL DEFAULT 1,
                IsTwoFactorEnabled TINYINT(1) NOT NULL DEFAULT 0,
                CONSTRAINT fk_users_roles FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
            );
            
            INSERT IGNORE INTO Roles (RoleID, RoleName) VALUES 
            (1, 'System Admin'), (2, 'HR Admin'), (3, 'Employee');
            
            INSERT IGNORE INTO Users (UserID, EmployeeID, Username, PasswordHash, RoleID, IsActive) VALUES 
            (1, 1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
            (2, 2, 'hrmanager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1),
            (3, 3, 'hrstaff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1);
        ");
    }
    
    $sql = "SELECT
                u.UserID, u.EmployeeID, u.Username, u.PasswordHash, u.RoleID, u.IsActive,
                u.IsTwoFactorEnabled,
                r.RoleName,
                e.FirstName, e.LastName, e.Email AS EmployeeEmail
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            LEFT JOIN employees e ON u.EmployeeID = e.EmployeeID
            WHERE u.Username = :username";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['IsActive']) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password.']);
        exit;
    }

    $trimmedHash = trim($user['PasswordHash']);
    if (!password_verify($password, $trimmedHash)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['employee_id'] = $user['EmployeeID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role_id'] = $user['RoleID'];
    $_SESSION['role_name'] = $user['RoleName'];
    $_SESSION['full_name'] = $user['FirstName'] . ' ' . $user['LastName'];

    http_response_code(200);
    echo json_encode([
        'message' => 'Login successful.',
        'two_factor_required' => false,
        'user' => [
            'user_id' => $user['UserID'],
            'employee_id' => $user['EmployeeID'],
            'username' => $user['Username'],
            'full_name' => $_SESSION['full_name'],
            'role_name' => $user['RoleName']
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Login API Error (DB): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error. Please try again.']);
    exit;
} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during login.']);
    exit;
}
?>
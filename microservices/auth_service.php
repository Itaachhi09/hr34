<?php
/**
 * Authentication Microservice
 * Handles user authentication, authorization, and session management
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

// Include PHPMailer for 2FA
$pathToVendor = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($pathToVendor)) {
    require $pathToVendor;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

function send2FAEmail($email, $code, $username) {
    $gmailUser = getenv('GMAIL_USER');
    $gmailAppPassword = getenv('GMAIL_APP_PASSWORD');
    
    if (empty($gmailUser) || empty($gmailAppPassword)) {
        error_log("2FA Email Error: Gmail credentials not configured");
        return false;
    }
    
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailUser;
        $mail->Password = $gmailAppPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom($gmailUser, 'HR34 System');
        $mail->addAddress($email);
        $mail->isHTML(false);
        $mail->Subject = 'HR34 System - Two-Factor Authentication Code';
        $mail->Body = "Hello {$username},\n\nYour 2FA code is: {$code}\n\nThis code expires in 10 minutes.\n\nIf you didn't request this, please ignore this email.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("2FA Email Error: " . $e->getMessage());
        return false;
    }
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$path = $_ENV['REQUEST_PATH'] ?? '';
$data = $_ENV['REQUEST_DATA'] ?? [];

// Route handling
try {
    // Remove /auth prefix from path
    $endpoint = str_replace('/api/v1/auth', '', $path);
    
    switch ($endpoint) {
        case '/login':
            if ($method !== 'POST') {
                sendError('Method not allowed', 405);
            }
            
            $username = $data['username'] ?? null;
            $password = $data['password'] ?? null;
            
            if (empty($username) || empty($password)) {
                sendError('Username and password are required');
            }
            
            // Development bypass
            if (getenv('DEV_ALLOW_ANY_LOGIN') === '1' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = -1;
                $_SESSION['employee_id'] = null;
                $_SESSION['username'] = $username;
                $_SESSION['role_id'] = 1;
                $_SESSION['role_name'] = 'System Admin';
                $_SESSION['full_name'] = ucfirst($username);
                
                sendResponse([
                    'message' => 'Login successful (dev bypass)',
                    'two_factor_required' => false,
                    'user' => [
                        'user_id' => -1,
                        'employee_id' => null,
                        'username' => $username,
                        'full_name' => $_SESSION['full_name'],
                        'role_name' => 'System Admin'
                    ]
                ]);
            }
            
            // Authenticate user
            $sql = "SELECT u.UserID, u.EmployeeID, u.Username, u.PasswordHash, u.RoleID, u.IsActive,
                           u.IsTwoFactorEnabled, r.RoleName, e.FirstName, e.LastName, e.Email AS EmployeeEmail
                    FROM Users u
                    JOIN Roles r ON u.RoleID = r.RoleID
                    LEFT JOIN Employees e ON u.EmployeeID = e.EmployeeID
                    WHERE u.Username = :username";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !$user['IsActive']) {
                sendError('Invalid username or password', 401);
            }
            
            if (!password_verify($password, trim($user['PasswordHash']))) {
                sendError('Invalid username or password', 401);
            }
            
            // Handle 2FA
            if ($user['IsTwoFactorEnabled']) {
                if (empty($user['EmployeeEmail'])) {
                    sendError('Two-factor authentication cannot proceed. Please contact support.', 500);
                }
                
                $two_factor_code = sprintf("%06d", random_int(100000, 999999));
                $expiry_time = new DateTime('+10 minutes');
                $expiry_timestamp = $expiry_time->format('Y-m-d H:i:s');
                
                $sql_update_2fa = "UPDATE Users SET TwoFactorEmailCode = :code, TwoFactorCodeExpiry = :expiry WHERE UserID = :user_id";
                $stmt_update_2fa = $pdo->prepare($sql_update_2fa);
                $stmt_update_2fa->bindParam(':code', $two_factor_code, PDO::PARAM_STR);
                $stmt_update_2fa->bindParam(':expiry', $expiry_timestamp, PDO::PARAM_STR);
                $stmt_update_2fa->bindParam(':user_id', $user['UserID'], PDO::PARAM_INT);
                $stmt_update_2fa->execute();
                
                if (!send2FAEmail($user['EmployeeEmail'], $two_factor_code, $user['Username'])) {
                    sendError('Failed to send two-factor authentication code', 500);
                }
                
                sendResponse([
                    'two_factor_required' => true,
                    'message' => 'Two-factor authentication required. Please check your email.',
                    'user_id_temp' => $user['UserID']
                ]);
            } else {
                // Regular login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['employee_id'] = $user['EmployeeID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role_id'] = $user['RoleID'];
                $_SESSION['role_name'] = $user['RoleName'];
                $_SESSION['full_name'] = $user['FirstName'] . ' ' . $user['LastName'];
                
                sendResponse([
                    'message' => 'Login successful',
                    'two_factor_required' => false,
                    'user' => [
                        'user_id' => $user['UserID'],
                        'employee_id' => $user['EmployeeID'],
                        'username' => $user['Username'],
                        'full_name' => $_SESSION['full_name'],
                        'role_name' => $user['RoleName']
                    ]
                ]);
            }
            break;
            
        case '/verify-2fa':
            if ($method !== 'POST') {
                sendError('Method not allowed', 405);
            }
            
            $user_id = $data['user_id'] ?? null;
            $code = $data['code'] ?? null;
            
            if (empty($user_id) || empty($code)) {
                sendError('User ID and code are required');
            }
            
            $sql = "SELECT UserID, Username, TwoFactorEmailCode, TwoFactorCodeExpiry, RoleID, EmployeeID
                    FROM Users 
                    WHERE UserID = :user_id AND TwoFactorEmailCode = :code AND TwoFactorCodeExpiry > NOW()";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':code', $code, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                sendError('Invalid or expired code', 401);
            }
            
            // Get user details
            $sql_user = "SELECT u.*, r.RoleName, e.FirstName, e.LastName 
                        FROM Users u 
                        JOIN Roles r ON u.RoleID = r.RoleID 
                        LEFT JOIN Employees e ON u.EmployeeID = e.EmployeeID 
                        WHERE u.UserID = :user_id";
            
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_user->execute();
            $user_details = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            // Clear 2FA code
            $sql_clear = "UPDATE Users SET TwoFactorEmailCode = NULL, TwoFactorCodeExpiry = NULL WHERE UserID = :user_id";
            $stmt_clear = $pdo->prepare($sql_clear);
            $stmt_clear->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_clear->execute();
            
            // Create session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_details['UserID'];
            $_SESSION['employee_id'] = $user_details['EmployeeID'];
            $_SESSION['username'] = $user_details['Username'];
            $_SESSION['role_id'] = $user_details['RoleID'];
            $_SESSION['role_name'] = $user_details['RoleName'];
            $_SESSION['full_name'] = $user_details['FirstName'] . ' ' . $user_details['LastName'];
            
            sendResponse([
                'message' => 'Two-factor authentication successful',
                'user' => [
                    'user_id' => $user_details['UserID'],
                    'employee_id' => $user_details['EmployeeID'],
                    'username' => $user_details['Username'],
                    'full_name' => $_SESSION['full_name'],
                    'role_name' => $user_details['RoleName']
                ]
            ]);
            break;
            
        case '/logout':
            if ($method !== 'POST') {
                sendError('Method not allowed', 405);
            }
            
            session_destroy();
            sendResponse(['message' => 'Logout successful']);
            break;
            
        case '/check-session':
            if ($method !== 'GET') {
                sendError('Method not allowed', 405);
            }
            
            if (!isset($_SESSION['user_id'])) {
                sendError('No active session', 401);
            }
            
            sendResponse([
                'authenticated' => true,
                'user' => [
                    'user_id' => $_SESSION['user_id'],
                    'employee_id' => $_SESSION['employee_id'],
                    'username' => $_SESSION['username'],
                    'role_name' => $_SESSION['role_name'],
                    'full_name' => $_SESSION['full_name']
                ]
            ]);
            break;
            
        case '/change-password':
            if ($method !== 'POST') {
                sendError('Method not allowed', 405);
            }
            
            if (!isset($_SESSION['user_id'])) {
                sendError('Authentication required', 401);
            }
            
            $current_password = $data['current_password'] ?? null;
            $new_password = $data['new_password'] ?? null;
            
            if (empty($current_password) || empty($new_password)) {
                sendError('Current password and new password are required');
            }
            
            // Verify current password
            $sql = "SELECT PasswordHash FROM Users WHERE UserID = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, trim($user['PasswordHash']))) {
                sendError('Current password is incorrect', 401);
            }
            
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE Users SET PasswordHash = :password_hash WHERE UserID = :user_id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':password_hash', $new_hash, PDO::PARAM_STR);
            $stmt_update->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt_update->execute();
            
            sendResponse(['message' => 'Password changed successfully']);
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("Auth Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("Auth Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

<?php
/**
 * API Endpoint: Login (moved to auth/login.php)
 * Backward-compatible logic extracted from ../login.php
 */

// --- PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Error Reporting & Headers ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start(); // Start session early

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Composer Autoloader ---
$pathToVendor = __DIR__ . '/../../../vendor/autoload.php';
if (file_exists($pathToVendor)) {
    require $pathToVendor;
} else {
    error_log("Login API Error: PHPMailer vendor/autoload.php not found at " . $pathToVendor);
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error: Email library missing.']);
    exit;
}

// --- Database Connection ---
$pdo = null;
try {
    require_once __DIR__ . '/../../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception('DB connection object not created.');
    }
} catch (Throwable $e) {
    error_log("Login API Error (DB Connection): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error.']);
    exit;
}

// --- Email Sending Function (using PHPMailer) ---
function send_2fa_email_code_phpmailer(string $recipientEmail, string $code, string $username): bool {
    $gmailUser = getenv('GMAIL_USER');
    $gmailAppPassword = getenv('GMAIL_APP_PASSWORD');
    if (empty($gmailUser) || empty($gmailAppPassword)) {
        error_log("send_2fa_email_code_phpmailer: GMAIL_USER or GMAIL_APP_PASSWORD environment variables not set.");
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmailUser;
        $mail->Password   = $gmailAppPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom($gmailUser, 'HVILL Hospital HR System');
        $mail->addAddress($recipientEmail);
        $mail->isHTML(false);
        $mail->Subject = 'Your HVILL Hospital HR System Login Code';
        $mail->Body    = "Hello " . htmlspecialchars($username) . ",\n\n" .
                         "Your two-factor authentication code is: " . $code . "\n\n" .
                         "This code will expire in 10 minutes.\n\n" .
                         "If you did not request this code, please ignore this email or contact support.";
        $mail->AltBody = $mail->Body;
        $mail->send();
        error_log("2FA Email Sent successfully to: " . $recipientEmail);
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: Message could not be sent. Mailer Error: {$mail->ErrorInfo}. Exception: {$e->getMessage()}");
        return false;
    }
}

// --- Login Logic ---
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

// --- Development bypass: allow any credentials when env flag is enabled ---
if (getenv('DEV_ALLOW_ANY_LOGIN') === '1' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = -1;
    $_SESSION['employee_id'] = null;
    $_SESSION['username'] = $username;
    $_SESSION['role_id'] = 1;
    $_SESSION['role_name'] = 'System Admin';
    $_SESSION['full_name'] = ucfirst($username);
    echo json_encode([
        'message' => 'Login successful (dev bypass).',
        'two_factor_required' => false,
        'user' => [
            'user_id' => -1,
            'employee_id' => null,
            'username' => $username,
            'full_name' => $_SESSION['full_name'],
            'role_name' => 'System Admin'
        ]
    ]);
    exit;
}

try {
    $sql = "SELECT
                u.UserID, u.EmployeeID, u.Username, u.PasswordHash, u.RoleID, u.IsActive,
                u.IsTwoFactorEnabled,
                r.RoleName,
                e.FirstName, e.LastName, e.Email AS EmployeeEmail
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            LEFT JOIN Employees e ON u.EmployeeID = e.EmployeeID
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

    if ($user['IsTwoFactorEnabled']) {
        if (empty($user['EmployeeEmail'])) {
            error_log("2FA Error: UserID {$user['UserID']} has 2FA enabled but no email address in Employees table.");
            http_response_code(500);
            echo json_encode(['error' => 'Two-factor authentication cannot proceed. Please contact support (email missing).']);
            exit;
        }

        $two_factor_code = sprintf("%06d", random_int(100000, 999999));
        $expiry_time = new DateTime('+10 minutes');
        $expiry_timestamp = $expiry_time->format('Y-m-d H:i:s');

        $sql_update_2fa = "UPDATE Users
                           SET TwoFactorEmailCode = :code,
                               TwoFactorCodeExpiry = :expiry
                           WHERE UserID = :user_id";
        $stmt_update_2fa = $pdo->prepare($sql_update_2fa);
        $stmt_update_2fa->bindParam(':code', $two_factor_code, PDO::PARAM_STR);
        $stmt_update_2fa->bindParam(':expiry', $expiry_timestamp, PDO::PARAM_STR);
        $stmt_update_2fa->bindParam(':user_id', $user['UserID'], PDO::PARAM_INT);
        if (!$stmt_update_2fa->execute()) {
            error_log("2FA DB Error: Failed to store 2FA code for UserID {$user['UserID']}.");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to initiate two-factor authentication process.']);
            exit;
        }

        if (!send_2fa_email_code_phpmailer($user['EmployeeEmail'], $two_factor_code, $user['Username'])) {
            error_log("2FA Email Error: Failed to send 2FA code email to {$user['EmployeeEmail']} for UserID {$user['UserID']} using PHPMailer.");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send two-factor authentication code via email.']);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'two_factor_required' => true,
            'message' => 'Two-factor authentication required. Please check your email (' . htmlspecialchars($user['EmployeeEmail']) . ') for the code.',
            'user_id_temp' => $user['UserID']
        ]);
        exit;
    } else {
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
    }
} catch (\PDOException $e) {
    error_log("Login API Error (DB Query/Verify): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during login. Please try again.']);
    exit;
} catch (\Exception $e) {
    error_log("Login API Error (Code Generation/Date/PHPMailer): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An internal error occurred during the login process.']);
    exit;
} catch (Throwable $e) {
    error_log("Login API Error (General): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected server error occurred.']);
    exit;
}

?>



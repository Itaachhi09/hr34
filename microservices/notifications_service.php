<?php
/**
 * Notifications Microservice
 * Handles system notifications and alerts
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
    // Remove /notifications prefix from path
    $endpoint = str_replace('/api/v1/notifications', '', $path);
    
    switch ($endpoint) {
        case '/':
            if ($method === 'GET') {
                requireAuth();
                
                $user_id = $_SESSION['user_id'];
                $employee_id = $_SESSION['employee_id'] ?? null;
                $role = $_SESSION['role_name'];
                
                // Build notification query based on user role
                $sql = "SELECT n.*, 
                               CASE 
                                   WHEN n.TargetType = 'User' THEN u.Username
                                   WHEN n.TargetType = 'Employee' THEN CONCAT(e.FirstName, ' ', e.LastName)
                                   ELSE 'System'
                               END as TargetName
                        FROM notifications n
                        LEFT JOIN Users u ON n.TargetID = u.UserID AND n.TargetType = 'User'
                        LEFT JOIN Employees e ON n.TargetID = e.EmployeeID AND n.TargetType = 'Employee'
                        WHERE (n.TargetType = 'All' OR 
                               (n.TargetType = 'User' AND n.TargetID = :user_id) OR
                               (n.TargetType = 'Employee' AND n.TargetID = :employee_id) OR
                               (n.TargetType = 'Role' AND n.TargetID = :role_id))";
                
                $params = [
                    ':user_id' => $user_id,
                    ':employee_id' => $employee_id,
                    ':role_id' => $_SESSION['role_id']
                ];
                
                // Add role-specific notifications
                if ($role === 'System Admin' || $role === 'HR Admin') {
                    $sql .= " OR n.TargetType = 'Admin'";
                }
                
                $sql .= " ORDER BY n.CreatedDate DESC LIMIT 50";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Count unread notifications
                $sql_count = "SELECT COUNT(*) as unread_count FROM notifications n
                              WHERE n.IsRead = 0 AND (n.TargetType = 'All' OR 
                                   (n.TargetType = 'User' AND n.TargetID = :user_id) OR
                                   (n.TargetType = 'Employee' AND n.TargetID = :employee_id) OR
                                   (n.TargetType = 'Role' AND n.TargetID = :role_id))";
                
                if ($role === 'System Admin' || $role === 'HR Admin') {
                    $sql_count .= " OR n.TargetType = 'Admin'";
                }
                
                $stmt_count = $pdo->prepare($sql_count);
                foreach ($params as $key => $value) {
                    $stmt_count->bindValue($key, $value);
                }
                $stmt_count->execute();
                $unread_count = $stmt_count->fetchColumn();
                
                sendResponse([
                    'notifications' => $notifications,
                    'unread_count' => $unread_count
                ]);
                
            } elseif ($method === 'POST') {
                requireRole(['System Admin', 'HR Admin']);
                
                if (empty($data['Title']) || empty($data['Message'])) {
                    sendError('Title and message are required');
                }
                
                $sql = "INSERT INTO notifications (Title, Message, TargetType, TargetID, Priority, IsRead, CreatedDate)
                        VALUES (:title, :message, :target_type, :target_id, :priority, 0, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':title', $data['Title']);
                $stmt->bindParam(':message', $data['Message']);
                $stmt->bindParam(':target_type', $data['TargetType'] ?? 'All');
                $stmt->bindParam(':target_id', $data['TargetID'] ?? null);
                $stmt->bindParam(':priority', $data['Priority'] ?? 'Normal');
                $stmt->execute();
                
                $notification_id = $pdo->lastInsertId();
                sendResponse(['message' => 'Notification created successfully', 'notification_id' => $notification_id], 201);
            }
            break;
            
        case '/mark-read':
            if ($method === 'POST') {
                requireAuth();
                
                $notification_id = $data['notification_id'] ?? null;
                if (!$notification_id) {
                    sendError('Notification ID is required');
                }
                
                // Verify user can mark this notification as read
                $user_id = $_SESSION['user_id'];
                $employee_id = $_SESSION['employee_id'] ?? null;
                $role = $_SESSION['role_name'];
                
                $sql_check = "SELECT NotificationID FROM notifications 
                              WHERE NotificationID = :notification_id AND 
                                    (TargetType = 'All' OR 
                                     (TargetType = 'User' AND TargetID = :user_id) OR
                                     (TargetType = 'Employee' AND TargetID = :employee_id) OR
                                     (TargetType = 'Role' AND TargetID = :role_id))";
                
                if ($role === 'System Admin' || $role === 'HR Admin') {
                    $sql_check .= " OR TargetType = 'Admin'";
                }
                
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
                $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_check->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt_check->bindParam(':role_id', $_SESSION['role_id'], PDO::PARAM_INT);
                $stmt_check->execute();
                
                if (!$stmt_check->fetch()) {
                    sendError('Notification not found or access denied', 404);
                }
                
                $sql = "UPDATE notifications SET IsRead = 1, ReadDate = NOW() WHERE NotificationID = :notification_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
                $stmt->execute();
                
                sendResponse(['message' => 'Notification marked as read']);
            }
            break;
            
        case '/mark-all-read':
            if ($method === 'POST') {
                requireAuth();
                
                $user_id = $_SESSION['user_id'];
                $employee_id = $_SESSION['employee_id'] ?? null;
                $role = $_SESSION['role_name'];
                
                $sql = "UPDATE notifications SET IsRead = 1, ReadDate = NOW() 
                        WHERE IsRead = 0 AND (TargetType = 'All' OR 
                              (TargetType = 'User' AND TargetID = :user_id) OR
                              (TargetType = 'Employee' AND TargetID = :employee_id) OR
                              (TargetType = 'Role' AND TargetID = :role_id))";
                
                if ($role === 'System Admin' || $role === 'HR Admin') {
                    $sql .= " OR TargetType = 'Admin'";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->bindParam(':role_id', $_SESSION['role_id'], PDO::PARAM_INT);
                $stmt->execute();
                
                $affected_rows = $stmt->rowCount();
                sendResponse(['message' => "Marked {$affected_rows} notifications as read"]);
            }
            break;
            
        case '/delete':
            if ($method === 'DELETE') {
                requireRole(['System Admin', 'HR Admin']);
                
                $notification_id = $data['notification_id'] ?? null;
                if (!$notification_id) {
                    sendError('Notification ID is required');
                }
                
                $sql = "DELETE FROM notifications WHERE NotificationID = :notification_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
                $stmt->execute();
                
                sendResponse(['message' => 'Notification deleted successfully']);
            }
            break;
            
        case '/types':
            if ($method === 'GET') {
                requireAuth();
                
                $types = [
                    ['type' => 'All', 'description' => 'All users'],
                    ['type' => 'User', 'description' => 'Specific user'],
                    ['type' => 'Employee', 'description' => 'Specific employee'],
                    ['type' => 'Role', 'description' => 'Users with specific role'],
                    ['type' => 'Admin', 'description' => 'Administrators only']
                ];
                
                sendResponse($types);
            }
            break;
            
        case '/priorities':
            if ($method === 'GET') {
                requireAuth();
                
                $priorities = [
                    ['priority' => 'Low', 'description' => 'Low priority notification'],
                    ['priority' => 'Normal', 'description' => 'Normal priority notification'],
                    ['priority' => 'High', 'description' => 'High priority notification'],
                    ['priority' => 'Urgent', 'description' => 'Urgent notification']
                ];
                
                sendResponse($priorities);
            }
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
    
} catch (PDOException $e) {
    error_log("Notifications Service DB Error: " . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log("Notifications Service Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}
?>

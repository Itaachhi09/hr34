<?php
/**
 * Get Notifications API
 * Returns list of notifications for the user
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // Ensure notifications table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            NotificationID INT PRIMARY KEY AUTO_INCREMENT,
            UserID INT NOT NULL,
            Title VARCHAR(255) NOT NULL,
            Message TEXT NOT NULL,
            Type VARCHAR(50) DEFAULT 'info',
            IsRead TINYINT(1) DEFAULT 0,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            ReadDate DATETIME NULL
        );
        
        INSERT IGNORE INTO notifications (NotificationID, UserID, Title, Message, Type, IsRead) VALUES 
        (1, 1, 'Welcome to HR System', 'Welcome to the HR Management System!', 'info', 0),
        (2, 1, 'System Update', 'The system has been updated with new features.', 'info', 0),
        (3, 2, 'Payroll Processing', 'Payroll for February 2024 is ready for review.', 'warning', 0),
        (4, 3, 'Document Upload', 'New document uploaded for review.', 'success', 0),
        (5, 1, 'Security Alert', 'Please update your password for security.', 'error', 0);
    ");
    
    $userId = $_GET['user_id'] ?? 1; // Default to user 1 for demo
    
    $sql = "SELECT * FROM notifications 
            WHERE UserID = :user_id 
            ORDER BY CreatedDate DESC 
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($notifications);
    
} catch (PDOException $e) {
    error_log("Get Notifications API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
} catch (Exception $e) {
    error_log("Get Notifications API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>
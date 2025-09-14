<?php
/**
 * Get Shifts API
 * Returns list of shifts for dropdowns
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // Ensure shifts table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shifts (
            ShiftID INT PRIMARY KEY AUTO_INCREMENT,
            ShiftName VARCHAR(100) NOT NULL,
            StartTime TIME NOT NULL,
            EndTime TIME NOT NULL,
            BreakDuration INT DEFAULT 60,
            IsActive TINYINT(1) DEFAULT 1
        );
        
        INSERT IGNORE INTO shifts (ShiftID, ShiftName, StartTime, EndTime, BreakDuration) VALUES 
        (1, 'Day Shift', '08:00:00', '17:00:00', 60),
        (2, 'Night Shift', '22:00:00', '07:00:00', 60),
        (3, 'Evening Shift', '16:00:00', '01:00:00', 60);
    ");
    
    $sql = "SELECT ShiftID, ShiftName, StartTime, EndTime, BreakDuration 
            FROM shifts 
            WHERE IsActive = 1 
            ORDER BY ShiftName";
    
    $stmt = $pdo->query($sql);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format times for display
    foreach ($shifts as &$shift) {
        $shift['StartTimeFormatted'] = date('g:i A', strtotime($shift['StartTime']));
        $shift['EndTimeFormatted'] = date('g:i A', strtotime($shift['EndTime']));
    }
    
    echo json_encode($shifts);
    
} catch (PDOException $e) {
    error_log("Get Shifts API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
} catch (Exception $e) {
    error_log("Get Shifts API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>
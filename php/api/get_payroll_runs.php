<?php
/**
 * Get Payroll Runs API
 * Returns list of payroll runs for the payroll module
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // Ensure payroll runs table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payroll_runs (
            PayrollRunID INT PRIMARY KEY AUTO_INCREMENT,
            RunName VARCHAR(255) NOT NULL,
            PayPeriodStart DATE NOT NULL,
            PayPeriodEnd DATE NOT NULL,
            RunDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            Status VARCHAR(20) DEFAULT 'Draft',
            TotalEmployees INT DEFAULT 0,
            TotalGrossPay DECIMAL(15,2) DEFAULT 0.00,
            TotalDeductions DECIMAL(15,2) DEFAULT 0.00,
            TotalNetPay DECIMAL(15,2) DEFAULT 0.00,
            CreatedBy INT,
            ProcessedDate DATETIME NULL
        );
        
        INSERT IGNORE INTO payroll_runs (PayrollRunID, RunName, PayPeriodStart, PayPeriodEnd, Status, TotalEmployees, TotalGrossPay, TotalDeductions, TotalNetPay, ProcessedDate) VALUES 
        (1, 'January 2024 - First Half', '2024-01-01', '2024-01-15', 'Processed', 8, 45000.00, 9000.00, 36000.00, '2024-01-16 10:30:00'),
        (2, 'January 2024 - Second Half', '2024-01-16', '2024-01-31', 'Processed', 8, 45000.00, 9000.00, 36000.00, '2024-02-01 10:30:00'),
        (3, 'February 2024 - First Half', '2024-02-01', '2024-02-15', 'Processed', 8, 45000.00, 9000.00, 36000.00, '2024-02-16 10:30:00'),
        (4, 'February 2024 - Second Half', '2024-02-16', '2024-02-29', 'Draft', 8, 45000.00, 9000.00, 36000.00, NULL),
        (5, 'March 2024 - First Half', '2024-03-01', '2024-03-15', 'Draft', 8, 45000.00, 9000.00, 36000.00, NULL);
    ");
    
    $sql = "SELECT * FROM payroll_runs ORDER BY PayPeriodStart DESC, RunDate DESC";
    $stmt = $pdo->query($sql);
    $payrollRuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($payrollRuns);
    
} catch (PDOException $e) {
    error_log("Get Payroll Runs API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
} catch (Exception $e) {
    error_log("Get Payroll Runs API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>
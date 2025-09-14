<?php
/**
 * Get Employees API
 * Returns list of employees for dropdowns and displays
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // Ensure employees table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employees (
            EmployeeID INT PRIMARY KEY AUTO_INCREMENT,
            FirstName VARCHAR(50) NOT NULL,
            LastName VARCHAR(50) NOT NULL,
            Email VARCHAR(100),
            Status VARCHAR(20) DEFAULT 'Active',
            HireDate DATE DEFAULT (CURDATE()),
            DepartmentID INT DEFAULT 1
        );
        
        INSERT IGNORE INTO employees (EmployeeID, FirstName, LastName, Email, Status, DepartmentID) VALUES 
        (1, 'John', 'Doe', 'john@company.com', 'Active', 1),
        (2, 'Jane', 'Smith', 'jane@company.com', 'Active', 2),
        (3, 'Bob', 'Johnson', 'bob@company.com', 'Active', 3),
        (4, 'Alice', 'Brown', 'alice@company.com', 'Active', 1),
        (5, 'Charlie', 'Wilson', 'charlie@company.com', 'Active', 2);
    ");
    
    $sql = "SELECT EmployeeID, FirstName, LastName, Email, Status, HireDate, DepartmentID 
            FROM employees 
            WHERE Status = 'Active' 
            ORDER BY FirstName, LastName";
    
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($employees);
    
} catch (PDOException $e) {
    error_log("Get Employees API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
} catch (Exception $e) {
    error_log("Get Employees API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>
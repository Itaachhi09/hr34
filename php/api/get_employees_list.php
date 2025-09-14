<?php
/**
 * Get Employees List API
 * Returns list of employees for the employees module
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // Ensure employees table exists with more data
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employees (
            EmployeeID INT PRIMARY KEY AUTO_INCREMENT,
            FirstName VARCHAR(50) NOT NULL,
            LastName VARCHAR(50) NOT NULL,
            Email VARCHAR(100),
            Phone VARCHAR(20),
            DateOfBirth DATE,
            HireDate DATE DEFAULT (CURDATE()),
            DepartmentID INT DEFAULT 1,
            JobRoleID INT DEFAULT 1,
            Status VARCHAR(20) DEFAULT 'Active'
        );
        
        CREATE TABLE IF NOT EXISTS departments (
            DepartmentID INT PRIMARY KEY AUTO_INCREMENT,
            DepartmentName VARCHAR(100) NOT NULL
        );
        
        CREATE TABLE IF NOT EXISTS job_roles (
            JobRoleID INT PRIMARY KEY AUTO_INCREMENT,
            JobRoleName VARCHAR(100) NOT NULL
        );
        
        INSERT IGNORE INTO departments (DepartmentID, DepartmentName) VALUES 
        (1, 'Administration'), (2, 'Human Resources'), (3, 'Information Technology'), 
        (4, 'Finance'), (5, 'Operations'), (6, 'Marketing');
        
        INSERT IGNORE INTO job_roles (JobRoleID, JobRoleName) VALUES 
        (1, 'Manager'), (2, 'Staff'), (3, 'Senior Staff'), (4, 'Director'), (5, 'Coordinator');
        
        INSERT IGNORE INTO employees (EmployeeID, FirstName, LastName, Email, Phone, DateOfBirth, HireDate, DepartmentID, JobRoleID, Status) VALUES 
        (1, 'John', 'Doe', 'john.doe@company.com', '555-0101', '1985-03-15', '2020-01-15', 1, 1, 'Active'),
        (2, 'Jane', 'Smith', 'jane.smith@company.com', '555-0102', '1990-07-22', '2021-03-10', 2, 2, 'Active'),
        (3, 'Bob', 'Johnson', 'bob.johnson@company.com', '555-0103', '1988-11-08', '2019-06-01', 3, 3, 'Active'),
        (4, 'Alice', 'Brown', 'alice.brown@company.com', '555-0104', '1992-04-12', '2022-02-20', 4, 2, 'Active'),
        (5, 'Charlie', 'Wilson', 'charlie.wilson@company.com', '555-0105', '1987-09-30', '2020-11-05', 5, 1, 'Active'),
        (6, 'Diana', 'Davis', 'diana.davis@company.com', '555-0106', '1991-12-03', '2021-08-15', 2, 2, 'Active'),
        (7, 'Eve', 'Miller', 'eve.miller@company.com', '555-0107', '1989-06-18', '2020-04-12', 3, 3, 'Active'),
        (8, 'Frank', 'Garcia', 'frank.garcia@company.com', '555-0108', '1986-01-25', '2019-09-30', 4, 2, 'Active');
    ");
    
    $sql = "SELECT 
                e.EmployeeID, e.FirstName, e.LastName, e.Email, e.Phone, 
                e.DateOfBirth, e.HireDate, e.Status,
                d.DepartmentName, j.JobRoleName
            FROM employees e
            LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
            LEFT JOIN job_roles j ON e.JobRoleID = j.JobRoleID
            ORDER BY e.FirstName, e.LastName";
    
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($employees);
    
} catch (PDOException $e) {
    error_log("Get Employees List API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
} catch (Exception $e) {
    error_log("Get Employees List API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>


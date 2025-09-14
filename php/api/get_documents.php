<?php
/**
 * Get Documents API
 * Returns list of documents for the documents module
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../db_connect.php';

try {
    // Ensure documents table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documents (
            DocumentID INT PRIMARY KEY AUTO_INCREMENT,
            EmployeeID INT NOT NULL,
            DocumentName VARCHAR(255) NOT NULL,
            DocumentType VARCHAR(100) NOT NULL,
            FilePath VARCHAR(500),
            UploadDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            Status VARCHAR(20) DEFAULT 'Active',
            Description TEXT
        );
        
        INSERT IGNORE INTO documents (DocumentID, EmployeeID, DocumentName, DocumentType, FilePath, Description) VALUES 
        (1, 1, 'Employment Contract - John Doe.pdf', 'Contract', '/documents/contracts/emp_001.pdf', 'Initial employment contract'),
        (2, 1, 'ID Copy - John Doe.pdf', 'Identification', '/documents/id/id_001.pdf', 'Government issued ID'),
        (3, 2, 'Employment Contract - Jane Smith.pdf', 'Contract', '/documents/contracts/emp_002.pdf', 'Initial employment contract'),
        (4, 2, 'Resume - Jane Smith.pdf', 'Resume', '/documents/resumes/res_002.pdf', 'Updated resume'),
        (5, 3, 'Employment Contract - Bob Johnson.pdf', 'Contract', '/documents/contracts/emp_003.pdf', 'Initial employment contract'),
        (6, 3, 'Degree Certificate - Bob Johnson.pdf', 'Education', '/documents/education/deg_003.pdf', 'Bachelor degree certificate'),
        (7, 4, 'Employment Contract - Alice Brown.pdf', 'Contract', '/documents/contracts/emp_004.pdf', 'Initial employment contract'),
        (8, 5, 'Employment Contract - Charlie Wilson.pdf', 'Contract', '/documents/contracts/emp_005.pdf', 'Initial employment contract');
    ");
    
    $employeeId = $_GET['employee_id'] ?? null;
    
    if ($employeeId) {
        $sql = "SELECT d.*, e.FirstName, e.LastName 
                FROM documents d
                JOIN employees e ON d.EmployeeID = e.EmployeeID
                WHERE d.EmployeeID = :employee_id AND d.Status = 'Active'
                ORDER BY d.UploadDate DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "SELECT d.*, e.FirstName, e.LastName 
                FROM documents d
                JOIN employees e ON d.EmployeeID = e.EmployeeID
                WHERE d.Status = 'Active'
                ORDER BY d.UploadDate DESC";
        $stmt = $pdo->query($sql);
    }
    
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($documents);
    
} catch (PDOException $e) {
    error_log("Get Documents API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
} catch (Exception $e) {
    error_log("Get Documents API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred.']);
}
?>
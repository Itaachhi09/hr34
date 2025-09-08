<?php
/**
 * API Endpoint: Get Organizational Structure
 * Retrieves the hierarchical organizational structure (departments and their modules/sub-departments).
 * v2.0 - Updated to fetch hierarchical data including ParentDepartmentID.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/../../php-error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

try {
    // Fetch all departments/modules, including their parent ID to reconstruct hierarchy on frontend
    // Also fetching manager name if ManagerID is set
    $sql = "SELECT 
                os.DepartmentID, 
                os.DepartmentName, 
                os.ParentDepartmentID,
                os.Description,
                os.Icon,
                os.SortOrder,
                e.FirstName AS ManagerFirstName,
                e.LastName AS ManagerLastName,
                (SELECT COUNT(*) FROM Employees emp WHERE emp.DepartmentID = os.DepartmentID) AS EmployeeCount,
                parent_os.DepartmentName AS ParentDepartmentName
            FROM 
                OrganizationalStructure os
            LEFT JOIN 
                Employees e ON os.ManagerID = e.EmployeeID
            LEFT JOIN
                OrganizationalStructure parent_os ON os.ParentDepartmentID = parent_os.DepartmentID
            ORDER BY 
                os.ParentDepartmentID ASC, os.SortOrder ASC, os.DepartmentName ASC";
    
    $stmt = $pdo->query($sql);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_departments = [];
    foreach($departments as $dept) {
        $dept['ManagerName'] = null;
        if (!empty($dept['ManagerFirstName']) || !empty($dept['ManagerLastName'])) {
            $dept['ManagerName'] = trim(($dept['ManagerFirstName'] ?? '') . ' ' . ($dept['ManagerLastName'] ?? ''));
        }
        // Unset individual name parts if you only want ManagerName
        unset($dept['ManagerFirstName'], $dept['ManagerLastName']);
        $formatted_departments[] = $dept;
    }

    if (headers_sent()) { exit; } // Avoid "headers already sent"
    http_response_code(200);
    echo json_encode($formatted_departments);

} catch (PDOException $e) {
    error_log("API Error (get_org_structure): " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Failed to retrieve organizational structure. Details: ' . $e->getMessage()]);
} catch (Throwable $e) {
    error_log("PHP Throwable in get_org_structure.php: " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Unexpected server error.']);
}
exit;
?>

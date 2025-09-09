<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$employeeId = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
$benefitId = isset($data['benefit_id']) ? (int)$data['benefit_id'] : 0;
$date = isset($data['enrollment_date']) ? $data['enrollment_date'] : date('Y-m-d');

if ($employeeId <= 0 || $benefitId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'employee_id and benefit_id are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO EmployeeBenefits (EmployeeID, BenefitID, EnrollmentDate, Status) VALUES (:eid, :bid, :dt, 'Active')");
    $stmt->bindParam(':eid', $employeeId, PDO::PARAM_INT);
    $stmt->bindParam(':bid', $benefitId, PDO::PARAM_INT);
    $stmt->bindParam(':dt', $date, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode(['message' => 'Benefit assigned']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to assign benefit']);
}
?>



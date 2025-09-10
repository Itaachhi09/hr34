<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $id = (int)($_GET['BenefitID'] ?? $_POST['BenefitID'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'BenefitID is required']); exit; }

    $stmt = $pdo->prepare('DELETE FROM BenefitsPlans WHERE BenefitID = :id');
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete benefits plan']);
}
?>




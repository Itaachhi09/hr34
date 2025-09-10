<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $providerId = (int)($input['ProviderID'] ?? 0);
    $planName = trim($input['PlanName'] ?? '');
    $coverage = trim($input['CoverageDetails'] ?? '');
    $premium = isset($input['MonthlyPremium']) ? (float)$input['MonthlyPremium'] : 0.0;
    $isActive = isset($input['IsActive']) ? (int)!!$input['IsActive'] : 1;

    if ($providerId <= 0 || $planName === '') { http_response_code(400); echo json_encode(['error' => 'ProviderID and PlanName are required']); exit; }

    $stmt = $pdo->prepare("INSERT INTO BenefitsPlans (ProviderID, PlanName, CoverageDetails, MonthlyPremium, IsActive) VALUES (:pid, :name, :coverage, :premium, :active)");
    $stmt->execute([':pid' => $providerId, ':name' => $planName, ':coverage' => $coverage, ':premium' => $premium, ':active' => $isActive]);
    echo json_encode(['success' => true, 'BenefitID' => $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add benefits plan']);
}
?>




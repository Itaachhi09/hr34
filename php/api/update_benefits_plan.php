<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['BenefitID'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'BenefitID is required']); exit; }

    $fields = [];
    $params = [':id' => $id];
    if (isset($input['ProviderID'])) { $fields[] = 'ProviderID = :pid'; $params[':pid'] = (int)$input['ProviderID']; }
    if (isset($input['PlanName'])) { $fields[] = 'PlanName = :name'; $params[':name'] = trim($input['PlanName']); }
    if (isset($input['CoverageDetails'])) { $fields[] = 'CoverageDetails = :coverage'; $params[':coverage'] = trim($input['CoverageDetails']); }
    if (isset($input['MonthlyPremium'])) { $fields[] = 'MonthlyPremium = :premium'; $params[':premium'] = (float)$input['MonthlyPremium']; }
    if (isset($input['IsActive'])) { $fields[] = 'IsActive = :active'; $params[':active'] = (int)!!$input['IsActive']; }

    if (empty($fields)) { http_response_code(400); echo json_encode(['error' => 'No fields to update']); exit; }

    $sql = 'UPDATE BenefitsPlans SET ' . implode(', ', $fields) . ' WHERE BenefitID = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update benefits plan']);
}
?>




<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$providerId = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : null;

try {
    if ($providerId) {
        $stmt = $pdo->prepare("SELECT BenefitID, ProviderID, PlanName, CoverageDetails, MonthlyPremium, IsActive FROM BenefitsPlans WHERE ProviderID = :pid ORDER BY PlanName");
        $stmt->bindParam(':pid', $providerId, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->query("SELECT BenefitID, ProviderID, PlanName, CoverageDetails, MonthlyPremium, IsActive FROM BenefitsPlans ORDER BY PlanName");
    }
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['plans' => $plans]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch benefit plans']);
}
?>



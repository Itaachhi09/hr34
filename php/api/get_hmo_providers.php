<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $stmt = $pdo->query("SELECT ProviderID, ProviderName, ContactInfo, IsActive FROM HMOProviders ORDER BY ProviderName");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['providers' => $providers]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch HMO providers']);
}
?>



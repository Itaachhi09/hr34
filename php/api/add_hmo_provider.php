<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name = trim($input['ProviderName'] ?? '');
    $contact = trim($input['ContactInfo'] ?? '');
    $isActive = isset($input['IsActive']) ? (int)!!$input['IsActive'] : 1;

    if ($name === '') { http_response_code(400); echo json_encode(['error' => 'ProviderName is required']); exit; }

    $stmt = $pdo->prepare("INSERT INTO HMOProviders (ProviderName, ContactInfo, IsActive) VALUES (:name, :contact, :active)");
    $stmt->execute([':name' => $name, ':contact' => $contact, ':active' => $isActive]);
    echo json_encode(['success' => true, 'ProviderID' => $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add HMO provider']);
}
?>




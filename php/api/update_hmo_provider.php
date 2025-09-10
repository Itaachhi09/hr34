<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['ProviderID'] ?? 0);
    $name = isset($input['ProviderName']) ? trim($input['ProviderName']) : null;
    $contact = isset($input['ContactInfo']) ? trim($input['ContactInfo']) : null;
    $isActive = isset($input['IsActive']) ? (int)!!$input['IsActive'] : null;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'ProviderID is required']); exit; }

    $fields = [];
    $params = [':id' => $id];
    if ($name !== null) { $fields[] = 'ProviderName = :name'; $params[':name'] = $name; }
    if ($contact !== null) { $fields[] = 'ContactInfo = :contact'; $params[':contact'] = $contact; }
    if ($isActive !== null) { $fields[] = 'IsActive = :active'; $params[':active'] = $isActive; }
    if (empty($fields)) { http_response_code(400); echo json_encode(['error' => 'No fields to update']); exit; }

    $sql = 'UPDATE HMOProviders SET ' . implode(', ', $fields) . ' WHERE ProviderID = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update HMO provider']);
}
?>




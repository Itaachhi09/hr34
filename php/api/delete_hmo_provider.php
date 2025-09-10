<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $id = (int)($_GET['ProviderID'] ?? $_POST['ProviderID'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'ProviderID is required']); exit; }

    $stmt = $pdo->prepare('DELETE FROM HMOProviders WHERE ProviderID = :id');
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete HMO provider']);
}
?>




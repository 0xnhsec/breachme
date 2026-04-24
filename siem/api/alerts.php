<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $alert_id = (int)($input['alert_id'] ?? 0);
    $is_tp = (int)($input['is_true_positive'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE alerts SET is_true_positive = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_tp, $alert_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit;
}

// GET - fetch alerts
$limit = (int)($_GET['limit'] ?? 50);
$stmt = $conn->prepare("SELECT a.*, r.ip, r.method, r.endpoint, r.timestamp as req_time FROM alerts a JOIN request_logs r ON a.log_id = r.id ORDER BY a.created_at DESC LIMIT ?");
$stmt->bind_param("i", $limit);
$stmt->execute();
$alerts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $alerts]);

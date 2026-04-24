<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$limit = (int)($_GET['limit'] ?? 50);
$offset = (int)($_GET['offset'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM request_logs ORDER BY timestamp DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $logs, 'count' => count($logs)]);

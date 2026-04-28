<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$id = $_GET['id'] ?? '';
if (!preg_match('/^[0-9a-f-]{36}$/i', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing folder UUID']);
    exit;
}

$sql = file_get_contents(__DIR__ . '/../queries/folder_detail.sql');

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':folder_id' => $id]);
    $rows = $stmt->fetchAll();
    echo json_encode(['data' => $rows, 'count' => count($rows)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$sql = file_get_contents(__DIR__ . '/../queries/shared_passwords.sql');

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    echo json_encode(['data' => $rows, 'count' => count($rows)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}

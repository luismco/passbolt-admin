<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
$user = portal_auth_check();
portal_log($user, '/portal/api/shared_folders.php', null);
require_once __DIR__ . '/../auth.php';
portal_auth_check();

$sql = file_get_contents(__DIR__ . '/../queries/shared_folders.sql');

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    echo json_encode(['data' => $rows, 'count' => count($rows)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
$user = portal_auth_check();
portal_log($user, '/portal/api/user_detail.php', $_GET['id'] ?? null);
require_once __DIR__ . '/../auth.php';
portal_auth_check();

$id = $_GET['id'] ?? '';
if (!preg_match('/^[0-9a-f-]{36}$/i', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing user UUID']);
    exit;
}

$sql = file_get_contents(__DIR__ . '/../queries/user_detail.sql');

try {
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $id]);
    $rows = $stmt->fetchAll();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    echo json_encode(['data' => $rows, 'count' => count($rows)]);
} catch (PDOException $e) {
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}

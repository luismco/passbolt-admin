<?php
// config.php — loads .env, validates config, establishes DB connection, provides logging

// ── PHP version check ──────────────────────────────────────────────────
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    die(json_encode(['error' => 'PHP 8.0 or higher is required.']));
}

// ── Extension checks ───────────────────────────────────────────────────
foreach (['pdo_mysql', 'curl'] as $ext) {
    if (!extension_loaded($ext)) {
        http_response_code(500);
        die(json_encode(['error' => "Required PHP extension missing: {$ext}"]));
    }
}

// ── Load .env ──────────────────────────────────────────────────────────
$env_path = __DIR__ . '/.env';
if (!file_exists($env_path)) {
    http_response_code(500);
    die(json_encode(['error' => 'Missing .env file. Copy .env.example to .env and fill in your values.']));
}

foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

// ── Required keys ──────────────────────────────────────────────────────
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'PORTAL_LOG_PATH'];
$missing  = array_filter($required, fn($k) => empty($_ENV[$k]));
if (!empty($missing)) {
    http_response_code(500);
    die(json_encode(['error' => 'Missing required .env keys: ' . implode(', ', $missing)]));
}

// ── Log path validation ────────────────────────────────────────────────
$log_path = $_ENV['PORTAL_LOG_PATH'];

if (!str_starts_with($log_path, '/')) {
    http_response_code(500);
    die(json_encode(['error' => 'PORTAL_LOG_PATH must be an absolute path (starting with /).']));
}

$log_dir = dirname($log_path);

if (!is_dir($log_dir)) {
    if (!mkdir($log_dir, 0750, true)) {
        http_response_code(500);
        die(json_encode(['error' => "Log directory does not exist and could not be created: {$log_dir}"]));
    }
}

$test = @file_put_contents($log_path, '', FILE_APPEND);
if ($test === false) {
    http_response_code(500);
    die(json_encode(['error' => "Log path is not writable: {$log_path}. Check permissions and SELinux context."]));
}

// ── DB connection ──────────────────────────────────────────────────────
$dsn = sprintf('mysql:host=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_NAME']);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed. Check DB_* values in .env.']));
}

// ── Logger ─────────────────────────────────────────────────────────────
function portal_log(array $user, string $endpoint, ?string $resource_id = null, int $status = 200): void {
    $entry = json_encode([
        'timestamp'   => gmdate('Y-m-d\TH:i:s\Z'),
        'user'        => $user['username'] ?? 'unknown',
        'user_id'     => $user['id'] ?? 'unknown',
        'ip'          => $_SERVER['HTTP_X_FORWARDED_FOR']
                         ?? $_SERVER['REMOTE_ADDR']
                         ?? 'unknown',
        'method'      => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'endpoint'    => $endpoint,
        'resource_id' => $resource_id,
        'status'      => $status,
    ], JSON_UNESCAPED_SLASHES);

    file_put_contents($_ENV['PORTAL_LOG_PATH'], $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
}

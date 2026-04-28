<?php
// PDO database connection, reads credentials from .env

function parse_env(string $path): void {
    if (!file_exists($path)) {
        die("Missing .env file. Copy .env.example to .env and fill in your values.\n");
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

parse_env(__DIR__ . '/.env');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_CHARSET'] ?? 'utf8mb4'
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed.']));
}

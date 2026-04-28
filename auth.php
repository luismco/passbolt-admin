<?php
// app/auth.php — validates passbolt session and enforces admin role

function portal_auth_check(PDO $pdo): void {
    $cookie_name = 'passbolt_session';

    // 1. No session cookie → redirect to passbolt login
    if (empty($_COOKIE[$cookie_name])) {
        header('Location: /');
        exit;
    }

    $session_id = preg_replace('/[^a-z0-9]/', '', $_COOKIE[$cookie_name]);
    if (empty($session_id)) {
        header('Location: /');
        exit;
    }

    // 2. Find session file in systemd private tmp
    //    php-fpm runs in a private /tmp namespace under systemd, so we search
    //    for the known pattern rather than hardcoding the hash in the path
    $session_file = find_session_file($session_id);

    if (!$session_file || !is_readable($session_file)) {
        header('Location: /');
        exit;
    }

    // 3. Parse session data to extract user id
    $session_data = file_get_contents($session_file);
    $user_id = parse_session_user_id($session_data);

    if (!$user_id) {
        header('Location: /');
        exit;
    }

    // 4. Check user exists, is active, and has admin role
    try {
        $stmt = $pdo->prepare("
            SELECT u.id
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = :user_id
              AND u.active = 1
              AND u.deleted = 0
              AND r.name = 'admin'
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        http_response_code(500);
        die('Auth check failed.');
    }

    if (!$user) {
        http_response_code(403);
        die('
<!DOCTYPE html>
<html>
<head>
  <title>Access Denied</title>
  <style>
    body { background:#0d0f12; color:#d4d8e2; font-family:sans-serif;
           display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
    .box { text-align:center; }
    h1 { color:#e8354a; font-size:1.4rem; margin-bottom:.5rem; }
    p { color:#5a6070; font-size:.9rem; }
    a { color:#3a8fff; text-decoration:none; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Access Denied</h1>
    <p>This portal requires an admin account.</p>
    <p><a href="/">← Back to Passbolt</a></p>
  </div>
</body>
</html>');
    }
}

function find_session_file(string $session_id): ?string {
    // Direct path first (non-systemd or known path)
    $direct = "/tmp/sess_{$session_id}";
    if (file_exists($direct)) {
        return $direct;
    }

    // Search inside systemd private tmp namespaces for php-fpm
    $pattern = '/tmp/systemd-private-*/php-fpm.service-*/tmp/sess_' . $session_id;
    $matches = glob($pattern);
    if (!empty($matches)) {
        return $matches[0];
    }

    return null;
}

function parse_session_user_id(string $data): ?string {
    // PHP session format: key|serialized_value key2|serialized_value ...
    // We need Auth|a:1:{s:4:"user";a:1:{s:2:"id";s:36:"<uuid>"}}
    if (!preg_match('/Auth\|(.+?)(?=[A-Za-z_]+\||$)/s', $data, $m)) {
        return null;
    }

    $auth = @unserialize($m[1]);
    if (!is_array($auth) || empty($auth['user']['id'])) {
        return null;
    }

    $id = $auth['user']['id'];

    // Validate it looks like a UUID
    if (!preg_match('/^[0-9a-f-]{36}$/i', $id)) {
        return null;
    }

    return $id;
}

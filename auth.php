<?php
// auth.php — validate Passbolt session via API and enforce admin role

function portal_auth_check(): array {
    $api_url = get_passbolt_base_url() . '/users/me.json';
    $cookie  = $_SERVER['HTTP_COOKIE'] ?? '';

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Cookie: ' . $cookie],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        fail_auth();
    }

    $data = json_decode($response, true);

    if (
        empty($data['body']['role']['name']) ||
        $data['body']['role']['name'] !== 'admin'
    ) {
        deny_access();
    }

    // Return user data for logging
    return [
        'id'         => $data['body']['id']       ?? 'unknown',
        'username'   => $data['body']['username']  ?? 'unknown',
        'role'       => $data['body']['role']['name'] ?? 'unknown',
    ];
}

function get_passbolt_base_url(): string {
    $scheme = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
        ? $_SERVER['HTTP_X_FORWARDED_PROTO']
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function fail_auth(): void {
    // Passbolt doesn't support a redirect_to param natively,
    // so we show a simple page that links back after login
    $portal_path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    http_response_code(401);
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Login Required</title>
  <style>
    body { background:#0d0f12; color:#d4d8e2; font-family:sans-serif;
           display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
    .box { text-align:center; }
    h1 { color:#d4d8e2; font-size:1.4rem; margin-bottom:.5rem; }
    p { color:#5a6070; font-size:.9rem; margin-bottom:1rem; }
    a { color:#3a8fff; text-decoration:none; }
    .btn { display:inline-block; background:#e8354a; color:#fff; padding:8px 20px;
           border-radius:6px; font-size:.9rem; text-decoration:none; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Login Required</h1>
    <p>You need to be logged into Passbolt to access this portal.</p>
    <a class="btn" href="/">Login to Passbolt</a>
    <p style="margin-top:1rem;font-size:.8rem">
      After logging in, return to <a href="{$portal_path}">{$portal_path}</a>
    </p>
  </div>
</body>
</html>
HTML;
    exit;
}

function deny_access(): void {
    http_response_code(403);
    echo <<<HTML
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
</html>
HTML;
    exit;
}

<?php
// app/auth.php — validate Passbolt session via API and enforce admin role

function portal_auth_check(): void {
    $api_url = get_passbolt_base_url() . '/users/me.json';

    $cookie = $_SERVER['HTTP_COOKIE'] ?? '';

    $headers = [
        'Cookie: ' . $cookie
    ];

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 5,

        // Uncomment if using self-signed certs (homelab setups)
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        fail_auth();
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Not authenticated → redirect to Passbolt login
    if ($status !== 200) {
        fail_auth();
    }

    $data = json_decode($response, true);

    // Validate structure and enforce admin role
    if (
        empty($data['body']['role']['name']) ||
        $data['body']['role']['name'] !== 'admin'
    ) {
        deny_access();
    }
}


/**
 * Build Passbolt base URL dynamically based on current request
 */
function get_passbolt_base_url(): string {
    $scheme = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
        ? $_SERVER['HTTP_X_FORWARDED_PROTO']
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}


/**
 * Redirect to Passbolt login
 */
function fail_auth(): void {
    header('Location: /');
    exit;
}


/**
 * Deny access with simple UI
 */
function deny_access(): void {
    http_response_code(403);
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Access Denied</title>
  <style>
    body {
      background:#0d0f12;
      color:#d4d8e2;
      font-family:sans-serif;
      display:flex;
      align-items:center;
      justify-content:center;
      height:100vh;
      margin:0;
    }
    .box { text-align:center; }
    h1 {
      color:#e8354a;
      font-size:1.4rem;
      margin-bottom:.5rem;
    }
    p {
      color:#5a6070;
      font-size:.9rem;
    }
    a {
      color:#3a8fff;
      text-decoration:none;
    }
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

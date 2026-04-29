<?php
// index.php — auth gate and page load logger for the portal SPA
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$user = portal_auth_check();
portal_log($user, '/portal/', null, 200);

readfile(__DIR__ . '/index.html');

<?php
// app/index.php — auth gate for the portal SPA
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

portal_auth_check($pdo);

// Auth passed — serve the SPA
readfile(__DIR__ . '/index.html');

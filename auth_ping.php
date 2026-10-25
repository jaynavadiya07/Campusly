<?php
/**
 * Heartbeat endpoint to extend session activity (auth_ping.php)
 */
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (is_logged_in()) {
    $_SESSION['last_activity'] = time();
    echo json_encode([
        'status' => 'ok',
        'remaining' => SESSION_TIMEOUT_SECONDS,
        'user' => $_SESSION['user_name'] ?? ''
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'status' => 'unauthorized',
        'remaining' => 0
    ]);
}

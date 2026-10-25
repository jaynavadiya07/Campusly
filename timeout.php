<?php
/**
 * Campusly Session Timeout Handling (timeout.php)
 * System: Campusly Academic Portal
 * Enforces a strict 10-minute (600 seconds) inactivity timeout.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie params before starting session
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 10 minutes session timeout duration in seconds
define('SESSION_TIMEOUT_DURATION', 600);

// Simulator hook for live demonstration & testing
if (isset($_GET['action']) && $_GET['action'] === 'simulate_timeout') {
    $_SESSION['last_activity'] = time() - (SESSION_TIMEOUT_DURATION + 10);
}

// Check session inactivity timeout if user is authenticated
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity'])) {
        $inactive_seconds = time() - $_SESSION['last_activity'];

        if ($inactive_seconds > SESSION_TIMEOUT_DURATION) {
            // Unset all session variables
            $_SESSION = [];

            // Invalidate session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destroy session data on the server
            session_destroy();

            // Redirect user to login with timeout notification
            header("Location: login.php?msg=timeout");
            exit();
        }
    }

    // Refresh last activity timestamp for active user request
    $_SESSION['last_activity'] = time();
}

<?php
/**
 * Campusly Authentication Module (authenticate.php)
 * System: Campusly Academic Portal
 * Handles credential verification via password_verify() and session fixation defense via session_regenerate_id(true).
 */

// Initialize session with secure configuration
if (session_status() === PHP_SESSION_NONE) {
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

require_once __DIR__ . '/db.php';

// Record pre-authentication session ID to demonstrate regeneration
$pre_login_session_id = session_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: login.php?msg=invalid");
        exit();
    }

    if (empty($conn) || empty($db_connected)) {
        header("Location: login.php?msg=dberror");
        exit();
    }

    // Prepare MySQLi statement to fetch user record by email
    $stmt = $conn->prepare("SELECT user_id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Secure password verification against bcrypt/argon2 hash
        if ($user && password_verify($password, $user['password_hash'])) {
            // DEFENSE AGAINST SESSION FIXATION: Regenerate session ID and delete old session
            session_regenerate_id(true);
            $post_login_session_id = session_id();

            // Store user session payload
            $_SESSION['user_id']               = $user['user_id'];
            $_SESSION['name']                  = $user['name'];
            $_SESSION['email']                 = $user['email'];
            $_SESSION['role']                  = $user['role'];
            $_SESSION['last_activity']         = time();
            $_SESSION['pre_login_session_id']  = $pre_login_session_id;
            $_SESSION['post_login_session_id'] = $post_login_session_id;
            $_SESSION['session_regenerated']   = true;

            // Update last_login timestamp in database
            $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            if ($upd) {
                $upd->bind_param("i", $user['user_id']);
                $upd->execute();
                $upd->close();
            }

            // Log successful authentication in audit_logs
            $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_email, user_role, action, details, ip_address, created_at) VALUES (?, ?, ?, 'LOGIN_SUCCESS', 'User authenticated via password_verify() gateway', ?, NOW())");
            if ($audit_stmt) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $audit_stmt->bind_param("isss", $user['user_id'], $user['email'], $user['role'], $ip);
                $audit_stmt->execute();
                $audit_stmt->close();
            }

            // Role-Based Access Redirection (RBAC)
            if ($user['role'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            // Authentication failed
            header("Location: login.php?msg=invalid");
            exit();
        }
    } else {
        header("Location: login.php?msg=invalid");
        exit();
    }
} else {
    // Direct GET access to authenticate.php is redirected to login
    header("Location: login.php");
    exit();
}

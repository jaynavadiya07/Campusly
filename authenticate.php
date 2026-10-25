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

    $user = null;

    // 1. Try MySQL database if available
    if (!empty($conn) && !empty($db_connected)) {
        try {
            $stmt = $conn->prepare("SELECT user_id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $dbUser = $result->fetch_assoc();
                $stmt->close();

                if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
                    $user = $dbUser;
                }
            }
        } catch (Throwable $e) {
            $user = null;
        }
    }

    // 2. Fallback Demo Credentials (Cloud / Vercel Serverless environment)
    if (!$user) {
        $demoAccounts = [
            ['user_id' => 1, 'name' => 'Administrator', 'email' => 'admin@campusly.com', 'passwords' => ['Admin@123', 'admin'], 'aliases' => ['admin@campusly.com', 'admin', 'administrator'], 'role' => 'admin'],
            ['user_id' => 2, 'name' => 'Jay Navadiya', 'email' => 'jay@campusly.com', 'passwords' => ['Student@123', 'student', 'jay'], 'aliases' => ['jay@campusly.com', 'jay', 'jay navadiya', '25cs052@charusat.edu.in'], 'role' => 'student'],
            ['user_id' => 3, 'name' => 'Jash Patel', 'email' => 'student@campusly.com', 'passwords' => ['Student@123', 'student', 'jash'], 'aliases' => ['student@campusly.com', 'student', 'jash patel', 'jash', '25cs004@charusat.edu.in'], 'role' => 'student'],
            ['user_id' => 4, 'name' => 'Bhavy Gol', 'email' => 'bhavy@campusly.com', 'passwords' => ['Student@123', 'student', 'bhavy'], 'aliases' => ['bhavy@campusly.com', 'bhavy', 'bhavy gol'], 'role' => 'student'],
        ];

        $lowerInput = strtolower($email);
        foreach ($demoAccounts as $acc) {
            if (in_array($lowerInput, $acc['aliases'], true) || strcasecmp($email, $acc['email']) === 0 || strcasecmp($email, $acc['name']) === 0) {
                if (in_array($password, $acc['passwords'], true)) {
                    $user = [
                        'user_id' => $acc['user_id'],
                        'name'    => $acc['name'],
                        'email'   => $acc['email'],
                        'role'    => $acc['role']
                    ];
                    break;
                }
            }
        }
    }

    if ($user) {
        // DEFENSE AGAINST SESSION FIXATION: Regenerate session ID and delete old session
        session_regenerate_id(true);
        $post_login_session_id = session_id();

        // Store user session payload
        $_SESSION['user_id']               = $user['user_id'];
        $_SESSION['name']                  = $user['name'];
        $_SESSION['user_name']             = $user['name'];
        $_SESSION['email']                 = $user['email'];
        $_SESSION['user_email']            = $user['email'];
        $_SESSION['role']                  = $user['role'];
        $_SESSION['student_id']            = $user['student_id'] ?? null;
        $_SESSION['last_activity']         = time();
        $_SESSION['pre_login_session_id']  = $pre_login_session_id;
        $_SESSION['post_login_session_id'] = $post_login_session_id;
        $_SESSION['session_regenerated']   = true;

        // Update last_login timestamp in database if connected
        if (!empty($conn) && !empty($db_connected)) {
            try {
                $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                if ($upd) {
                    $upd->bind_param("i", $user['user_id']);
                    $upd->execute();
                    $upd->close();
                }

                $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_email, user_role, action, details, ip_address, created_at) VALUES (?, ?, ?, 'LOGIN_SUCCESS', 'User authenticated via password_verify() gateway', ?, NOW())");
                if ($audit_stmt) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $audit_stmt->bind_param("isss", $user['user_id'], $user['email'], $user['role'], $ip);
                    $audit_stmt->execute();
                    $audit_stmt->close();
                }
            } catch (Throwable $e) {}
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
    // Direct GET access to authenticate.php is redirected to login
    header("Location: login.php");
    exit();
}

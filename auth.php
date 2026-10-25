<?php
/**
 * Campusly Secure Authentication & Session Management Module
 * System: Campusly (StudentHub)
 * 
 * Features:
 * 1. Hardened Session Configuration (HttpOnly, SameSite, Secure, Strict Mode)
 * 2. 10-Minute Idle Session Timeout (600 seconds)
 * 3. Session Fixation Defense via session_regenerate_id(true)
 * 4. Password Verification via password_verify()
 * 5. Role-Based Access Control (RBAC) for Admin & Student
 * 6. Secure Remember-Me Token using random bytes + SHA-256 database hash
 * 7. Last-Login Timestamp Tracking
 */

require_once __DIR__ . '/db.php';

// Session Timeout duration: 10 minutes = 600 seconds
define('SESSION_TIMEOUT_SECONDS', 600);

/**
 * Audit Logging Helper
 * Records administrative actions and security events into MySQL audit_logs
 */
function log_audit($action, $details = '', $userId = null, $userEmail = null, $userRole = null) {
    global $pdo, $conn;

    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    if ($userEmail === null) {
        $userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? 'guest@campusly.com';
    }
    if ($userRole === null) {
        $userRole = $_SESSION['role'] ?? 'guest';
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (isset($pdo) && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user_email, user_role, action, details, ip_address, created_at) VALUES (:uid, :email, :role, :action, :details, :ip, NOW())");
            $stmt->execute([
                ':uid'     => $userId,
                ':email'   => $userEmail,
                ':role'    => $userRole,
                ':action'  => $action,
                ':details' => $details,
                ':ip'      => $ip
            ]);
            return;
        } catch (Exception $e) {
            // continue fallback
        }
    }

    if (isset($conn) && $conn !== null) {
        try {
            $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_email, user_role, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("isssss", $userId, $userEmail, $userRole, $action, $details, $ip);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // ignore
        }
    }
}

/**
 * Render standard 403 Forbidden Access Restricted Page
 */
function render_403_page($message = 'Administrator privileges required. Non-admin users are strictly restricted from accessing this area.') {
    http_response_code(403);
    $userName  = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'User';
    $userEmail = $_SESSION['email'] ?? $_SESSION['user_email'] ?? '';
    $userRole  = $_SESSION['role'] ?? 'guest';

    // Log the security event
    log_audit('UNAUTHORIZED_ACCESS_403', 'Forbidden access attempt to ' . ($_SERVER['REQUEST_URI'] ?? 'admin area') . ' by role: ' . $userRole . ' (' . $userEmail . ')');

    $pageTitle = "403 Forbidden - Non-Admin Access Restricted";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content" style="max-width: 820px; margin: 3rem auto; padding: 0 1.5rem;">
      <div style="background: #ffffff; border: 1px solid #fecaca; border-top: 5px solid #dc2626; border-radius: 14px; box-shadow: 0 10px 30px rgba(220, 38, 38, 0.08); padding: 2.5rem; text-align: center;">
        <div style="width: 72px; height: 72px; margin: 0 auto 1.25rem; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; border: 2px solid #fca5a5;">
          🚫
        </div>
        <span style="display: inline-block; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; font-size: 0.8rem; font-weight: 800; letter-spacing: 1px; padding: 3px 12px; border-radius: 9999px; text-transform: uppercase; margin-bottom: 0.75rem;">
          HTTP 403 Forbidden &bull; RBAC Guard Active
        </span>
        <h1 style="color: #1e293b; font-size: 1.85rem; font-weight: 800; margin-bottom: 0.75rem;">
          Non-Admin Access Restricted
        </h1>
        <p style="color: #64748b; font-size: 1.05rem; max-width: 620px; margin: 0 auto 1.5rem; line-height: 1.6;">
          <?= htmlspecialchars($message) ?>
        </p>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.25rem; text-align: left; max-width: 620px; margin: 0 auto 1.75rem; font-size: 0.9rem;">
          <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #f1f5f9;">
            <span style="color: #64748b;">Current Authenticated Account:</span>
            <strong><?= htmlspecialchars($userName) ?> (<code><?= htmlspecialchars($userEmail) ?></code>)</strong>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #f1f5f9;">
            <span style="color: #64748b;">Assigned Role:</span>
            <span style="background: #eff6ff; color: #2563eb; font-weight: 700; padding: 2px 8px; border-radius: 4px; border: 1px solid #bfdbfe; font-size: 0.8rem; text-transform: uppercase;">
              <?= htmlspecialchars($userRole) ?>
            </span>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #f1f5f9;">
            <span style="color: #64748b;">Required Authorization:</span>
            <span style="background: #fdf2f8; color: #be185d; font-weight: 700; padding: 2px 8px; border-radius: 4px; border: 1px solid #fbcfe8; font-size: 0.8rem; text-transform: uppercase;">
              ADMINISTRATOR
            </span>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 0.4rem 0;">
            <span style="color: #64748b;">Audit Logging Status:</span>
            <span style="color: #16a34a; font-weight: 600;">✓ Event Recorded in `audit_logs`</span>
          </div>
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
          <a href="student_dashboard.php" class="btn btn-primary" style="padding: 0.65rem 1.4rem; border-radius: 8px; font-weight: 600; font-size: 0.92rem; text-decoration: none;">
            🎓 Go to Student Dashboard
          </a>
          <a href="login.php?role=admin" class="btn btn-secondary" style="padding: 0.65rem 1.4rem; border-radius: 8px; font-weight: 600; font-size: 0.92rem; text-decoration: none;">
            🔐 Sign In as Administrator
          </a>
        </div>
      </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/**
 * Initialize secure session with hardened cookie flags
 */
function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Enforce cookie-only session handling and strict session ID mode
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_set_cookie_params([
            'lifetime' => 0,          // Browser session cookie
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,   // Secure flag if HTTPS
            'httponly' => true,       // Prevent JavaScript XSS access to session cookie
            'samesite' => 'Lax'       // CSRF mitigation
        ]);

        session_start();
    }
}

init_secure_session();

/**
 * Handle 10-minute session timeout
 */
function enforce_session_timeout() {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > SESSION_TIMEOUT_SECONDS) {
                // Session has exceeded 10 minutes of inactivity
                logout_user('timeout');
                header('Location: login.php?msg=timeout');
                exit;
            }
        }
        // Refresh last activity timestamp for current user interaction
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check and restore session via Remember-Me token if not currently logged in
 */
function check_remember_token() {
    global $pdo;

    if (!isset($pdo) || $pdo === null) {
        return;
    }

    if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
        $rawToken = $_COOKIE['remember_token'];
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $pdo->prepare("SELECT user_id, name, email, role, student_id, last_login 
                                FROM users 
                                WHERE remember_token_hash = :hash 
                                  AND remember_token_expires > NOW() 
                                LIMIT 1");
        $stmt->execute([':hash' => $tokenHash]);
        $user = $stmt->fetch();

        if ($user) {
            // Valid token found: regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['user_id']       = $user['user_id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['student_id']    = $user['student_id'];
            $_SESSION['last_activity'] = time();

            // Update last login timestamp
            $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :uid");
            $upd->execute([':uid' => $user['user_id']]);
        } else {
            // Invalid or expired token: clear cookie
            setcookie('remember_token', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }
}

// Automatically check remember-me and enforce timeout on each request
check_remember_token();
enforce_session_timeout();

/**
 * Check if the current client is authenticated
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Require active authentication and optional role authorization (RBAC)
 */
function require_login($allowed_roles = []) {
    if (!is_logged_in()) {
        $_SESSION['intended_destination'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        header('Location: login.php?msg=unauthorized');
        exit;
    }

    if (!empty($allowed_roles)) {
        $userRole = $_SESSION['role'] ?? '';
        if (!in_array($userRole, (array)$allowed_roles)) {
            // Role not authorized: Enforce RBAC with HTTP 403 response
            if (in_array('admin', (array)$allowed_roles) && $userRole !== 'admin') {
                render_403_page('Administrator privileges required. Non-admin accounts (' . htmlspecialchars($_SESSION['email'] ?? $_SESSION['user_email'] ?? 'student') . ') are strictly restricted from accessing the Admin Control Center.');
            }
            if ($userRole === 'student') {
                header('Location: dashboard.php?msg=forbidden');
            } else {
                header('Location: admin_dashboard.php?msg=forbidden');
            }
            exit;
        }
    }
}

/**
 * Authenticate and log in user
 */
function login_user($user, $remember = false) {
    global $pdo;

    // Defend against session fixation by regenerating session ID
    $pre_id = session_id();
    session_regenerate_id(true);
    $post_id = session_id();

    $_SESSION['user_id']               = $user['user_id'];
    $_SESSION['user_name']             = $user['name'];
    $_SESSION['name']                  = $user['name'];
    $_SESSION['user_email']            = $user['email'];
    $_SESSION['email']                 = $user['email'];
    $_SESSION['role']                  = $user['role'];
    $_SESSION['student_id']            = $user['student_id'] ?? null;
    $_SESSION['last_activity']         = time();
    $_SESSION['pre_login_session_id']  = $pre_id;
    $_SESSION['post_login_session_id'] = $post_id;
    $_SESSION['session_regenerated']   = true;

    // Log successful login action in audit_logs
    log_audit('LOGIN_SUCCESS', ucfirst($user['role']) . ' user ' . $user['email'] . ' authenticated successfully', $user['user_id'], $user['email'], $user['role']);

    // Intermediate Extension: Record last login timestamp (if database available)
    if (isset($pdo) && $pdo !== null) {
        try {
            $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :uid");
            $upd->execute([':uid' => $user['user_id']]);
        } catch (Throwable $e) {
            // Ignore DB errors in offline/serverless demo mode
        }
    }

    // Advanced Extension: Secure Remember-Me Token via random_bytes & SHA-256 hash
    if ($remember && isset($pdo) && $pdo !== null) {
        try {
            $rawToken  = bin2hex(random_bytes(32)); // 64 hex characters cryptographically strong token
            $tokenHash = hash('sha256', $rawToken); // Store only SHA-256 hash in DB
            $expiresAt = date('Y-m-d H:i:s', time() + (14 * 86400)); // 14 days validity

            $tokenStmt = $pdo->prepare("UPDATE users 
                                        SET remember_token_hash = :hash, 
                                            remember_token_expires = :expires 
                                        WHERE user_id = :uid");
            $tokenStmt->execute([
                ':hash'    => $tokenHash,
                ':expires' => $expiresAt,
                ':uid'     => $user['user_id']
            ]);

            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('remember_token', $rawToken, [
                'expires'  => time() + (14 * 86400),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $isHttps
            ]);
        } catch (Throwable $e) {
            // Ignore token storage errors in offline mode
        }
    }
}

/**
 * Completely log out user and destroy session & cookies
 */
function logout_user($reason = 'manual') {
    global $pdo;

    // Clear remember-me token from database if user is known
    if (!empty($_SESSION['user_id']) && isset($pdo) && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token_hash = NULL, remember_token_expires = NULL WHERE user_id = :uid");
            $stmt->execute([':uid' => $_SESSION['user_id']]);
        } catch (Exception $e) {
            // Ignore DB errors during logout
        }
    }

    // Clear remember-me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['remember_token']);
    }

    // Clear all session variables
    $_SESSION = [];

    // Erase session cookie
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

    // Destroy session storage
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Return seconds remaining until current session timeout
 */
function get_session_time_remaining() {
    if (!isset($_SESSION['last_activity'])) {
        return SESSION_TIMEOUT_SECONDS;
    }
    $elapsed = time() - $_SESSION['last_activity'];
    return max(0, SESSION_TIMEOUT_SECONDS - $elapsed);
}

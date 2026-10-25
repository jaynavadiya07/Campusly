<?php
/**
 * Campusly Authentication & Login Gateway (login.php)
 * System: Campusly
 * 
 * Features:
 * 1. Interactive Step-1 Role Selector: Choose between Student or Administrator
 * 2. Dedicated Portal Paths: login.php?role=student vs login.php?role=admin
 * 3. Role-Based Access Control (RBAC) verification on authentication
 * 4. 1-Click Role Switching ("← Choose Different Role")
 * 5. Faithful Campusly Auth Card Design (Segmented Pill Tabs, Brand Emblem, Clean Alerts)
 * 6. 10-Minute session timeout and remember-me integration
 */

$pageTitle = "Portal Gateway - Campusly";
$activePage = "login.php";

require_once __DIR__ . '/auth.php';

// If user is already authenticated, redirect to their role dashboard
if (is_logged_in()) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit;
}

$errorMessage = '';
$successMessage = '';

// Check query string status messages
$msg = $_GET['msg'] ?? '';
if ($msg === 'timeout') {
    $errorMessage = 'Your session expired after 10 minutes of inactivity. Please select your role and sign in again.';
} elseif ($msg === 'unauthorized') {
    $errorMessage = 'Access Denied: You must sign in to view that protected page.';
} elseif ($msg === 'forbidden') {
    $errorMessage = 'Access Forbidden: You do not possess the required permissions for that section.';
} elseif ($msg === 'failed') {
    $errorMessage = 'Invalid email credentials or password. Please verify and try again.';
} elseif ($msg === 'logged_out') {
    $successMessage = 'You have logged out successfully. Session securely terminated.';
}

// Determine currently selected role: 'student', 'admin', or empty (ask first)
$portalRole = $_POST['portal_role'] ?? $_GET['role'] ?? '';
if (!in_array($portalRole, ['student', 'admin'], true)) {
    $portalRole = '';
}

// Process POST Login or Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $remember   = !empty($_POST['remember_me']);
        $portalRole = trim($_POST['portal_role'] ?? '');

        if (empty($email) || empty($password)) {
            $errorMessage = 'Please enter both your email address and password.';
        } else {
            try {
                // Fetch user by email OR username (name)
                $stmt = $pdo->prepare("SELECT user_id, name, email, password_hash, role, student_id FROM users WHERE email = :email OR name = :name LIMIT 1");
                $stmt->execute([':email' => $email, ':name' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Strict Role Validation: Enforce path chosen at login
                    if ($portalRole === 'admin' && $user['role'] !== 'admin') {
                        $errorMessage = "Access Denied: Account '{$user['email']}' is registered as a Student. Please switch to the Student Gateway to sign in.";
                    } else {
                        // Successful authentication
                        login_user($user, $remember);

                        // Route to appropriate dashboard
                        if ($user['role'] === 'admin') {
                            header('Location: dashboard.php');
                        } else {
                            // Check if an intended destination was recorded
                            $dest = $_SESSION['intended_destination'] ?? 'student_dashboard.php';
                            unset($_SESSION['intended_destination']);
                            header("Location: $dest");
                        }
                        exit;
                    }
                } else {
                    $errorMessage = 'Invalid email address or password. Please check your credentials.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database authentication error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'register') {
        // Administrator self-registration is locked down
        if ($portalRole === 'admin') {
            $errorMessage = 'Administrator accounts cannot be self-registered. Please contact system management.';
        } else {
            $fullname         = trim($_POST['fullname'] ?? '');
            $email            = trim($_POST['email'] ?? '');
            $department       = trim($_POST['department'] ?? 'CSE');
            $password         = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($fullname) || empty($email) || empty($password)) {
                $errorMessage = 'All fields are required to register your student account.';
            } elseif ($password !== $confirm_password) {
                $errorMessage = 'Password and confirmation password do not match.';
            } elseif (strlen($password) < 6) {
                $errorMessage = 'Password must be at least 6 characters long.';
            } else {
                try {
                    // Check if email already registered
                    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
                    $checkStmt->execute([':email' => $email]);
                    if ($checkStmt->fetch()) {
                        $errorMessage = 'An account with this email address already exists. Please sign in instead.';
                    } else {
                        // 1. Insert into students table
                        $st = $pdo->prepare("INSERT INTO students (name, email, course) VALUES (:name, :email, :course)");
                        $st->execute([
                            ':name'   => $fullname,
                            ':email'  => $email,
                            ':course' => $department
                        ]);
                        $studentId = $pdo->lastInsertId();

                        // 2. Hash password & insert into users table
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $uStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, student_id) 
                                                VALUES (:name, :email, :hash, 'student', :sid)");
                        $uStmt->execute([
                            ':name'  => $fullname,
                            ':email' => $email,
                            ':hash'  => $hash,
                            ':sid'   => $studentId
                        ]);

                        // Automatically log in new student
                        $newUserId = $pdo->lastInsertId();
                        $newUser = [
                            'user_id'    => $newUserId,
                            'name'       => $fullname,
                            'email'      => $email,
                            'role'       => 'student',
                            'student_id' => $studentId
                        ];

                        login_user($newUser, false);
                        header('Location: dashboard.php?msg=registered');
                        exit;
                    }
                } catch (PDOException $e) {
                    $errorMessage = 'Database registration error: ' . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li><a href="index.php">Home</a></li>
      <li aria-current="page">Portal Gateway</li>
    </ol>
  </nav>

  <main id="main-content">

    <!-- ========================================================
         STEP 1: ROLE SELECTION SCREEN (Ask First at Login)
         ======================================================== -->
    <section 
      id="role-select-screen" 
      class="role-gateway-wrapper" 
      style="display: <?= empty($portalRole) ? 'block' : 'none' ?>;"
      aria-labelledby="role-select-title"
    >
      <div class="role-gateway-header">
        <span class="portal-badge">⚡ AUTHENTICATION PORTAL</span>
        <h1 id="role-select-title" class="role-gateway-title">Select Your Portal Gateway</h1>
        <p class="role-gateway-subtitle">
          Which type of user is logging in? Select your role below to proceed to your dedicated portal.
        </p>
      </div>

      <!-- Feedback Alerts for Role Selector Screen -->
      <?php if (!empty($errorMessage) && empty($portalRole)): ?>
        <div class="auth-notification auth-notification-error" style="max-width: 680px; margin: 0 auto 2rem auto;" role="alert">
          <div class="auth-notif-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="auth-notif-body">
            <div class="auth-notif-title">Authentication Alert</div>
            <div class="auth-notif-msg"><?= htmlspecialchars($errorMessage) ?></div>
          </div>
          <button type="button" class="auth-notif-close" onclick="this.closest('.auth-notification').remove()" aria-label="Dismiss Alert">&times;</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($successMessage) && empty($portalRole)): ?>
        <div class="auth-notification auth-notification-success" style="max-width: 680px; margin: 0 auto 2rem auto;" role="status">
          <div class="auth-notif-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <div class="auth-notif-body">
            <div class="auth-notif-title">Success</div>
            <div class="auth-notif-msg"><?= htmlspecialchars($successMessage) ?></div>
          </div>
          <button type="button" class="auth-notif-close" onclick="this.closest('.auth-notification').remove()" aria-label="Dismiss Alert">&times;</button>
        </div>
      <?php endif; ?>

      <!-- Two Interactive Gateway Cards -->
      <div class="role-cards-grid">

        <!-- 🎓 STUDENT GATEWAY CARD -->
        <div 
          class="role-gateway-card student-card" 
          onclick="selectRole('student')" 
          tabindex="0" 
          role="button" 
          aria-label="Continue as Student"
          onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); selectRole('student'); }"
        >
          <div class="role-card-badge-wrap">
            <div class="role-card-icon-box student-icon">
              🎓
            </div>
            <span class="role-pill-badge student-pill">Student Portal</span>
          </div>

          <h2 class="role-card-title">Student Gateway</h2>
          <p class="role-card-desc">
            Access personalized student dashboard, course materials, class schedules, campus events, and academic records.
          </p>

          <ul class="role-features-list">
            <li><span class="check-icon">✓</span> Course Materials &amp; Lecture Slides</li>
            <li><span class="check-icon">✓</span> Real-Time Semester III Timetable</li>
            <li><span class="check-icon">✓</span> Campus Events &amp; Tech Fests</li>
            <li><span class="check-icon">✓</span> Student Self-Registration Supported</li>
          </ul>

          <button type="button" class="role-action-btn student-btn" onclick="event.stopPropagation(); selectRole('student');">
            <span>Continue as Student</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </div>

        <!-- 🛡️ ADMINISTRATOR GATEWAY CARD -->
        <div 
          class="role-gateway-card admin-card" 
          onclick="selectRole('admin')" 
          tabindex="0" 
          role="button" 
          aria-label="Continue as Administrator"
          onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); selectRole('admin'); }"
        >
          <div class="role-card-badge-wrap">
            <div class="role-card-icon-box admin-icon">
              🛡️
            </div>
            <span class="role-pill-badge admin-pill">Executive Portal</span>
          </div>

          <h2 class="role-card-title">Administrator Gateway</h2>
          <p class="role-card-desc">
            Comprehensive system management, student and staff user directories, RBAC policy enforcement, and security auditing.
          </p>

          <ul class="role-features-list">
            <li><span class="check-icon">✓</span> User Account Provisioning &amp; Role Toggle</li>
            <li><span class="check-icon">✓</span> Campus Announcements &amp; Event Management</li>
            <li><span class="check-icon">✓</span> Session Security &amp; Fixation Auditing</li>
            <li><span class="check-icon">✓</span> Protected Relational Database Diagnostics</li>
          </ul>

          <button type="button" class="role-action-btn admin-btn" onclick="event.stopPropagation(); selectRole('admin');">
            <span>Continue as Administrator</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </div>

      </div>
    </section>

    <!-- ========================================================
         STEP 2: AUTHENTICATION FORM SCREEN (Dedicated per Role)
         ======================================================== -->
    <div 
      id="auth-card-container" 
      class="auth-card-container" 
      style="display: <?= !empty($portalRole) ? 'block' : 'none' ?>;"
    >
      <!-- Feedback Alert Messages -->
      <?php if (!empty($errorMessage) && !empty($portalRole)): ?>
        <div class="auth-notification auth-notification-error" role="alert">
          <div class="auth-notif-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="auth-notif-body">
            <div class="auth-notif-title">Authentication Alert</div>
            <div class="auth-notif-msg"><?= htmlspecialchars($errorMessage) ?></div>
          </div>
          <button type="button" class="auth-notif-close" onclick="this.closest('.auth-notification').remove()" aria-label="Dismiss Alert">&times;</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($successMessage) && !empty($portalRole)): ?>
        <div class="auth-notification auth-notification-success" role="status">
          <div class="auth-notif-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <div class="auth-notif-body">
            <div class="auth-notif-title">Success</div>
            <div class="auth-notif-msg"><?= htmlspecialchars($successMessage) ?></div>
          </div>
          <button type="button" class="auth-notif-close" onclick="this.closest('.auth-notification').remove()" aria-label="Dismiss Alert">&times;</button>
        </div>
      <?php endif; ?>

      <div class="auth-card">

        <!-- Role Switching Toolbar -->
        <div class="role-switch-toolbar">
          <button type="button" class="btn-switch-role" onclick="resetRoleSelection()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            <span>Choose Different Role</span>
          </button>

          <div id="active-role-indicator" class="active-role-indicator <?= $portalRole === 'admin' ? 'admin-mode' : 'student-mode' ?>">
            <span id="active-role-icon"><?= $portalRole === 'admin' ? '🛡️' : '🎓' ?></span>
            <span id="active-role-text"><?= $portalRole === 'admin' ? 'Administrator Gateway' : 'Student Gateway' ?></span>
          </div>
        </div>

        <!-- Header with Brand Logo Emblem -->
        <div class="auth-card-header">
          <div class="auth-card-badge">
            <img src="images/campusly-logo.png" alt="Campusly Logo" class="auth-card-logo">
          </div>
          <h2 class="auth-card-title">Welcome to <span class="brand-text"><span class="brand-campus">Campus</span><span class="brand-ly">ly</span></span></h2>
          <p class="auth-card-subtitle" id="auth-main-desc">
            <?= $portalRole === 'admin' ? 'Executive Administrative Portal for CHARUSAT Family' : 'Secure Academic Gateway for CHARUSAT Family' ?>
          </p>
        </div>

        <!-- Segmented Pill Tabs (Visible for Student mode, hidden for Admin) -->
        <div class="auth-tab-pills" id="auth-tab-pills" role="tablist" style="display: <?= $portalRole === 'admin' ? 'none' : 'flex' ?>;">
          <button type="button" id="tab-login" class="auth-tab-pill active" onclick="switchTab('login')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            Sign In
          </button>
          <button type="button" id="tab-register" class="auth-tab-pill" onclick="switchTab('register')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            Create Account
          </button>
        </div>

        <!-- LOGIN PANE -->
        <div id="login-pane" class="tab-pane active-pane">
          <form id="login-form" action="login.php" method="POST">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="portal_role" id="portal_role_input" value="<?= htmlspecialchars($portalRole) ?>">

            <div class="form-group">
              <label for="email" id="email-label">
                <?= $portalRole === 'admin' ? 'Administrative Email Address' : 'University Email Address or Username' ?>
              </label>
              <div class="input-icon-group">
                <span class="input-icon-prefix">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </span>
                <input 
                  type="text" 
                  id="email" 
                  name="email" 
                  class="input-with-icon" 
                  value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                  placeholder="<?= $portalRole === 'admin' ? 'admin@campusly.com' : 'e.g. Jay, jayyyy, or admin@campusly.com' ?>" 
                  required 
                  autocomplete="username"
                >
              </div>
            </div>

            <div class="form-group">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <label for="password" style="margin-bottom: 0;">Account Password</label>
              </div>
              <div class="input-icon-group">
                <span class="input-icon-prefix">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </span>
                <input 
                  type="password" 
                  id="password" 
                  name="password" 
                  class="input-with-icon" 
                  placeholder="••••••••••••" 
                  required 
                  autocomplete="current-password"
                >
                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password', this)" title="Show / Hide password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>

            <div style="display: flex; align-items: center; margin: 1.25rem 0 1.75rem; font-size: 0.9rem;">
              <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 500; cursor: pointer; margin-bottom: 0;">
                <input type="checkbox" id="remember_me" name="remember_me" value="1" style="width: 16px; height: 16px; accent-color: var(--primary);">
                <span>Remember me (14-Day Session)</span>
              </label>
            </div>

            <button type="submit" class="btn-auth-submit">
              <span id="btn-submit-text"><?= $portalRole === 'admin' ? 'Sign In as Administrator' : 'Sign In to Campusly' ?></span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>

            <!-- 1-Click Demo Accounts -->
            <div class="demo-creds-container">
              <div class="demo-creds-header">
                <span class="demo-creds-title">⚡ Quick Autofill Test Accounts:</span>
              </div>
              <div class="demo-pills" id="demo-pills-container">
                <?php if ($portalRole === 'admin'): ?>
                  <button type="button" class="demo-pill-btn" onclick="fillCreds('admin@campusly.com', 'Admin@123')">
                    <span>🛡️ Admin: admin@campusly.com</span>
                  </button>
                <?php else: ?>
                  <button type="button" class="demo-pill-btn" onclick="fillCreds('jay@campusly.com', 'Student@123')">
                    <span>👨‍🎓 Student: Jay</span>
                  </button>
                  <button type="button" class="demo-pill-btn" onclick="fillCreds('jayyyy@campusly.com', 'Student@123')">
                    <span>👨‍🎓 Student: jayyyy</span>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>

        <!-- REGISTER PANE (Exclusively for Students) -->
        <div id="register-pane" class="tab-pane" style="display: none;">
          <form id="register-form" action="login.php" method="POST">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="portal_role" value="student">

            <div class="form-group">
              <label for="fullname">Full Name</label>
              <div class="input-icon-group">
                <span class="input-icon-prefix">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <input type="text" id="fullname" name="fullname" class="input-with-icon" placeholder="e.g. Jay Navadiya" required>
              </div>
            </div>

            <div class="form-group">
              <label for="reg-email">University Email</label>
              <div class="input-icon-group">
                <span class="input-icon-prefix">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </span>
                <input type="email" id="reg-email" name="email" class="input-with-icon" placeholder="name@campusly.edu" required>
              </div>
            </div>

            <div class="form-group">
              <label for="department">Department / Course</label>
              <select id="department" name="department" required>
                <option value="CSE">Computer Science &amp; Engineering (CSE)</option>
                <option value="IT">Information Technology (IT)</option>
                <option value="CE">Computer Engineering (CE)</option>
              </select>
            </div>

            <div class="form-group">
              <label for="reg-password">Password</label>
              <div class="input-icon-group">
                <span class="input-icon-prefix">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </span>
                <input type="password" id="reg-password" name="password" class="input-with-icon" placeholder="Min. 6 characters" minlength="6" required>
                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('reg-password', this)" title="Show / Hide password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="confirm_password">Confirm Password</label>
              <div class="input-icon-group">
                <span class="input-icon-prefix">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </span>
                <input type="password" id="confirm_password" name="confirm_password" class="input-with-icon" placeholder="Re-type password" minlength="6" required>
              </div>
            </div>

            <button type="submit" class="btn-auth-submit" style="margin-top: 1rem;">
              <span>Complete Registration</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
          </form>
        </div>

      </div>
    </div>
  </main>

  <script>
    let currentRole = <?= json_encode($portalRole) ?>;

    /**
     * Role Selection Handler: Transitions seamlessly from Step 1 to Step 2
     */
    function selectRole(role) {
      currentRole = role;
      const roleSelectScreen = document.getElementById('role-select-screen');
      const authContainer = document.getElementById('auth-card-container');
      const roleInput = document.getElementById('portal_role_input');
      const indicator = document.getElementById('active-role-indicator');
      const icon = document.getElementById('active-role-icon');
      const text = document.getElementById('active-role-text');
      const desc = document.getElementById('auth-main-desc');
      const emailLabel = document.getElementById('email-label');
      const emailInput = document.getElementById('email');
      const submitText = document.getElementById('btn-submit-text');
      const tabPills = document.getElementById('auth-tab-pills');
      const demoBox = document.getElementById('demo-pills-container');

      // Update role hidden input
      if (roleInput) roleInput.value = role;

      if (role === 'admin') {
        indicator.className = 'active-role-indicator admin-mode';
        icon.textContent = '🛡️';
        text.textContent = 'Administrator Gateway';
        desc.textContent = 'Executive Administrative Portal for CHARUSAT Family';
        emailLabel.textContent = 'Administrative Email Address';
        emailInput.placeholder = 'admin@campusly.com';
        submitText.textContent = 'Sign In as Administrator';
        if (tabPills) tabPills.style.display = 'none';
        switchTab('login');

        if (demoBox) {
          demoBox.innerHTML = `
            <button type="button" class="demo-pill-btn" onclick="fillCreds('admin@campusly.com', 'Admin@123')">
              <span>🛡️ Admin: admin@campusly.com</span>
            </button>
          `;
        }
      } else {
        indicator.className = 'active-role-indicator student-mode';
        icon.textContent = '🎓';
        text.textContent = 'Student Gateway';
        desc.textContent = 'Secure Academic Gateway for CHARUSAT Family';
        emailLabel.textContent = 'University Email Address or Username';
        emailInput.placeholder = 'e.g. Jay, jayyyy, or admin@campusly.com';
        submitText.textContent = 'Sign In to Campusly';
        if (tabPills) tabPills.style.display = 'flex';
        switchTab('login');

        if (demoBox) {
          demoBox.innerHTML = `
            <button type="button" class="demo-pill-btn" onclick="fillCreds('jay@campusly.com', 'Student@123')">
              <span>👨‍🎓 Student: Jay</span>
            </button>
            <button type="button" class="demo-pill-btn" onclick="fillCreds('jayyyy@campusly.com', 'Student@123')">
              <span>👨‍🎓 Student: jayyyy</span>
            </button>
          `;
        }
      }

      // Update browser URL without reload
      if (window.history && window.history.pushState) {
        window.history.pushState({ role: role }, '', 'login.php?role=' + encodeURIComponent(role));
      }

      // Display auth container and hide selector
      roleSelectScreen.style.display = 'none';
      authContainer.style.display = 'block';
      authContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // Focus email field
      setTimeout(() => {
        if (emailInput) emailInput.focus();
      }, 100);
    }

    /**
     * Reset Role Selection: Returns from Step 2 to Step 1
     */
    function resetRoleSelection() {
      currentRole = '';
      const roleSelectScreen = document.getElementById('role-select-screen');
      const authContainer = document.getElementById('auth-card-container');
      const roleInput = document.getElementById('portal_role_input');

      if (roleInput) roleInput.value = '';

      if (window.history && window.history.pushState) {
        window.history.pushState({}, '', 'login.php');
      }

      authContainer.style.display = 'none';
      roleSelectScreen.style.display = 'block';
      roleSelectScreen.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Support browser back/forward buttons
    window.addEventListener('popstate', function(e) {
      const params = new URLSearchParams(window.location.search);
      const role = params.get('role');
      if (role === 'admin' || role === 'student') {
        selectRole(role);
      } else {
        resetRoleSelection();
      }
    });

    /**
     * Switch between Sign In and Registration tabs
     */
    function switchTab(tab) {
      const loginPane = document.getElementById('login-pane');
      const regPane = document.getElementById('register-pane');
      const tabLogin = document.getElementById('tab-login');
      const tabReg = document.getElementById('tab-register');

      if (tab === 'login') {
        if (loginPane) {
          loginPane.style.display = 'block';
          loginPane.classList.add('active-pane');
        }
        if (regPane) {
          regPane.style.display = 'none';
          regPane.classList.remove('active-pane');
        }
        if (tabLogin) tabLogin.classList.add('active');
        if (tabReg) tabReg.classList.remove('active');
      } else {
        if (loginPane) {
          loginPane.style.display = 'none';
          loginPane.classList.remove('active-pane');
        }
        if (regPane) {
          regPane.style.display = 'block';
          regPane.classList.add('active-pane');
        }
        if (tabLogin) tabLogin.classList.remove('active');
        if (tabReg) tabReg.classList.add('active');
      }
    }

    /**
     * Quick autofill credentials helper
     */
    function fillCreds(email, password) {
      switchTab('login');
      const emailInput = document.getElementById('email');
      const passInput = document.getElementById('password');
      if (emailInput && passInput) {
        emailInput.value = email;
        passInput.value = password;
        passInput.focus();
      }
    }

    /**
     * Password show / hide toggle
     */
    function togglePasswordVisibility(fieldId, btn) {
      const input = document.getElementById(fieldId);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
      } else {
        input.type = 'password';
        btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
      }
    }
  </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

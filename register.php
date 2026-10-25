<?php
/**
 * Campusly User Registration Gateway (register.php)
 * System: Campusly (CHARUSAT)
 * 
 * Objectives Implemented:
 * 1. Successful user registration.
 * 2. Empty-field validation.
 * 3. Invalid email validation.
 * 4. Password mismatch validation.
 * 5. Duplicate email/username validation.
 * 6. Password stored in hashed format in the MySQL database (bcrypt via password_hash).
 * 7. Successful database insertion (relational insertion into students and users).
 */

$pageTitle = "Student Registration - Campusly";
$activePage = "register.php";

require_once __DIR__ . '/auth.php';

// If user is already authenticated, redirect to role dashboard
if (is_logged_in()) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

$errorMessage = '';
$successMessage = '';

// Handle POST Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $course   = trim($_POST['department'] ?? 'CSE');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Requirement 2: Empty-field validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm)) {
        $errorMessage = 'Validation Error: All fields (Full Name, University Email, Department, Password, Confirm Password) are required.';
    
    // Requirement 3: Invalid email validation
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Validation Error: Invalid email format! Please provide a valid email address (e.g. student@charusat.edu.in).';
    
    // Requirement 4: Password mismatch validation
    } elseif ($password !== $confirm) {
        $errorMessage = 'Validation Error: Passwords do not match! Please verify and re-enter matching passwords.';
    
    // Additional security: Password minimum length
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Validation Error: Password must be at least 6 characters in length.';
    
    } else {
        if (empty($pdo) || empty($db_connected)) {
            $errorMessage = 'Database Offline: Cloud database is not connected. Please add your database environment variables in Vercel.';
        } else {
            try {
            // Requirement 5: Duplicate email/username validation
            $checkStmt = $pdo->prepare("SELECT user_id, email, name FROM users WHERE email = :email OR name = :name LIMIT 1");
            $checkStmt->execute([':email' => $email, ':name' => $fullname]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser) {
                if (strcasecmp($existingUser['email'], $email) === 0) {
                    $errorMessage = 'Validation Error: Duplicate email detected! An account with email "' . htmlspecialchars($email) . '" already exists.';
                } else {
                    $errorMessage = 'Validation Error: Duplicate username detected! An account with name "' . htmlspecialchars($fullname) . '" already exists.';
                }
            } else {
                // Requirement 6: Password stored in hashed format in MySQL (bcrypt $2y$ via PASSWORD_DEFAULT)
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Requirement 7: Successful database insertion (students table)
                $insertStudent = $pdo->prepare("INSERT INTO students (name, email, course) VALUES (:name, :email, :course)");
                $insertStudent->execute([
                    ':name'   => $fullname,
                    ':email'  => $email,
                    ':course' => $course
                ]);
                $newStudentId = $pdo->lastInsertId();

                // Requirement 7: Successful database insertion (users table linked via student_id)
                $insertUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, student_id) 
                                             VALUES (:name, :email, :hash, 'student', :sid)");
                $insertUser->execute([
                    ':name'  => $fullname,
                    ':email' => $email,
                    ':hash'  => $hashedPassword,
                    ':sid'   => $newStudentId
                ]);

                // Requirement 1: Successful user registration
                $successMessage = 'Registration Successful! Student record #' . $newStudentId . ' created with securely hashed password in MySQL database. You can now log in.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database insertion error: ' . htmlspecialchars($e->getMessage());
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
      <li aria-current="page">Student Registration</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <div class="auth-card-container">

      <?php if (!empty($errorMessage)): ?>
        <!-- Error Alert Toast -->
        <div class="auth-notification auth-notification-error" role="alert">
          <div class="auth-notif-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="auth-notif-body">
            <div class="auth-notif-title">Registration Error</div>
            <div class="auth-notif-msg"><?= htmlspecialchars($errorMessage) ?></div>
          </div>
          <button type="button" class="auth-notif-close" onclick="this.closest('.auth-notification').remove()" aria-label="Dismiss Alert">&times;</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($successMessage)): ?>
        <!-- Success Alert Toast -->
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

      <!-- High-End Registration Card -->
      <div class="auth-card">
        
        <!-- Header with Brand Logo Emblem -->
        <div class="auth-card-header">
          <div class="auth-card-badge">
            <img src="images/campusly-logo.png" alt="Campusly Logo" class="auth-card-logo">
          </div>
          <h2 class="auth-card-title">Join <span class="brand-text"><span class="brand-campus">Campus</span><span class="brand-ly">ly</span></span></h2>
          <p class="auth-card-subtitle">Secure Registration with Validation &amp; Hashed Storage</p>
        </div>

        <form id="registration-form" action="register.php" method="POST" novalidate>
          <div class="form-group">
            <label for="fullname">Full Name / Username</label>
            <div class="input-icon-group">
              <span class="input-icon-prefix">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </span>
              <input type="text" id="fullname" name="fullname" class="input-with-icon" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" placeholder="e.g. Jay Navadiya" required>
            </div>
          </div>

          <div class="form-group">
            <label for="email">University Email Address</label>
            <div class="input-icon-group">
              <span class="input-icon-prefix">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
              </span>
              <input type="text" id="email" name="email" class="input-with-icon" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@campusly.edu" required>
            </div>
          </div>

          <div class="form-group">
            <label for="department">Department / Course</label>
            <select id="department" name="department" required>
              <option value="CSE" <?= (($_POST['department'] ?? '') === 'CSE') ? 'selected' : '' ?>>Computer Science & Engineering (CSE)</option>
              <option value="IT" <?= (($_POST['department'] ?? '') === 'IT') ? 'selected' : '' ?>>Information Technology (IT)</option>
              <option value="CE" <?= (($_POST['department'] ?? '') === 'CE') ? 'selected' : '' ?>>Computer Engineering (CE)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-icon-group">
              <span class="input-icon-prefix">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </span>
              <input type="password" id="password" name="password" class="input-with-icon" placeholder="Min. 6 characters" minlength="6" required>
              <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password', this)" title="Show / Hide password">
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
              <input type="password" id="confirm_password" name="confirm_password" class="input-with-icon" placeholder="Re-enter password" minlength="6" required>
              <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('confirm_password', this)" title="Show / Hide password">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-auth-submit" style="margin-top: 1rem;">
            <span>Complete Registration</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>

          <div style="text-align: center; margin-top: 1.5rem; font-size: 0.92rem; color: var(--text-muted, #94a3b8);">
            Already have an account? <a href="login.php" style="color: var(--primary, #0066ff); font-weight: 600; text-decoration: none;">Sign In here &rarr;</a>
          </div>
        </form>

      </div>
    </div>
  </main>

  <script>
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

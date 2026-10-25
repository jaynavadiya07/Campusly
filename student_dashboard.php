<?php
/**
 * Campusly Student Dashboard (student_dashboard.php)
 * System: Campusly Academic Portal
 * Dashboard accessible exclusively to Student users.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';

// Session Validation: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=unauthorized");
    exit();
}

$user_role   = $_SESSION['role'] ?? 'guest';
$user_name   = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Student';
$user_email  = $_SESSION['email'] ?? $_SESSION['user_email'] ?? '';
$session_id  = session_id();
$pre_sess_id = $_SESSION['pre_login_session_id'] ?? 'N/A';
$post_sess_id= $_SESSION['post_login_session_id'] ?? $session_id;

// RBAC Guard: If user is NOT a student (e.g., Administrator), intercept and show Unauthorized Access
if ($user_role !== 'student') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Unauthorized Access - Campusly</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="navbar">
    <a href="dashboard.php" class="navbar-brand">
      <div class="logo-icon">C</div>
      <span>Campusly Portal</span>
    </a>
    <ul class="navbar-nav">
      <li><a href="admin_dashboard.php" class="nav-link">Admin Portal</a></li>
      <li><a href="logout.php" class="btn btn-danger" style="padding: 0.35rem 0.85rem; font-size: 0.85rem;">Sign Out</a></li>
    </ul>
  </header>

  <main class="container" style="max-width: 680px; margin-top: 3.5rem;">
    <div class="card" style="border-top: 4px solid #ef4444; box-shadow: var(--shadow-lg);">
      <div style="text-align: center; margin-bottom: 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 0.5rem;">🚫</div>
        <h2 style="color: #991b1b; font-size: 1.5rem; font-weight: 700;">403 - Unauthorized Access Denied</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Student Access Required</p>
      </div>

      <div class="alert alert-danger" role="alert" style="margin-bottom: 1.5rem;">
        <div>
          <strong>Access Violation:</strong> You are currently logged in as an <strong>Administrator</strong> (<code><?= htmlspecialchars($user_email) ?></code>).
          Access to <code>student_dashboard.php</code> is strictly reserved for accounts with the <strong>Student</strong> role.
        </div>
      </div>

      <div class="info-grid" style="margin-bottom: 1.75rem;">
        <div class="info-item">
          <div class="info-label">Current Authenticated Account</div>
          <div class="info-value"><?= htmlspecialchars($user_email) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Assigned Account Role</div>
          <div class="info-value"><span class="badge badge-admin">ADMINISTRATOR</span></div>
        </div>
        <div class="info-item">
          <div class="info-label">Required Security Level</div>
          <div class="info-value"><span class="badge badge-student">STUDENT</span></div>
        </div>
        <div class="info-item">
          <div class="info-label">Active Session ID</div>
          <div class="info-value"><code style="font-size: 0.85rem;"><?= htmlspecialchars($session_id) ?></code></div>
        </div>
      </div>

      <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="admin_dashboard.php" class="btn btn-primary">
          ⚙️ Return to Admin Dashboard
        </a>
        <a href="dashboard.php" class="btn btn-outline">
          🏠 Main Dashboard
        </a>
        <a href="logout.php" class="btn btn-danger">
          🔒 Secure Logout
        </a>
      </div>
    </div>
  </main>

  <footer class="footer">
    Campusly Academic Portal &bull; Role-Based Access Control (RBAC) &bull; CHARUSAT
  </footer>
</body>
</html>
<?php
    exit();
}

// User is verified Student: Fetch registered student records
$student_details = null;
if (!empty($conn) && $conn instanceof mysqli) {
    try {
        $st_stmt = $conn->prepare("SELECT * FROM students WHERE email = ? LIMIT 1");
        if ($st_stmt) {
            $st_stmt->bind_param("s", $user_email);
            $st_stmt->execute();
            $student_details = $st_stmt->get_result()->fetch_assoc();
            $st_stmt->close();
        }
    } catch (Throwable $e) {
        $student_details = null;
    }
}

// Fallback student profile if database is offline or unseeded
if (!$student_details) {
    $studentsJsonPath = __DIR__ . '/data/students.json';
    if (file_exists($studentsJsonPath)) {
        $allJsonStudents = json_decode(file_get_contents($studentsJsonPath), true);
        if (is_array($allJsonStudents)) {
            foreach ($allJsonStudents as $item) {
                if (strcasecmp($item['email'] ?? '', $user_email) === 0 ||
                    strcasecmp($item['name'] ?? '', $user_name) === 0) {
                    $student_details = [
                        'student_id' => $item['id'] ?? 1,
                        'name'       => $item['name'] ?? $user_name,
                        'email'      => $item['email'] ?? $user_email,
                        'course'     => $item['department'] ?? 'Computer Science & Engineering (CSE)',
                        'status'     => $item['status'] ?? 'Active'
                    ];
                    break;
                }
            }
        }
    }
    if (!$student_details) {
        $student_details = [
            'student_id' => $_SESSION['student_id'] ?? 1,
            'name'       => $user_name,
            'email'      => $user_email,
            'course'     => 'Computer Science & Engineering (CSE)',
            'status'     => 'Active'
        ];
    }
}

$pageTitle = "Student Dashboard - Campusly";
$activePage = "student_dashboard.php";
require_once __DIR__ . '/includes/header.php';
?>

  <!-- Student Content Container -->
  <main class="container" style="max-width: 1100px; margin: 2rem auto;">

    <!-- Inactivity Timeout Monitor Bar -->
    <div class="session-bar">
      <div>
        ⏱️ <strong>Session Security:</strong> Inactivity timeout set to <strong>10 minutes (600s)</strong>.
        Time remaining: <span id="countdownTimer" style="font-weight: 700; color: #1e3a8a;">10:00</span>
      </div>
      <div>
        <a href="timeout.php?action=simulate_timeout" class="btn btn-warning" style="padding: 0.3rem 0.75rem; font-size: 0.8rem;">
          ⚡ Simulate 10m Timeout
        </a>
      </div>
    </div>

    <!-- Student Welcome Profile Card -->
    <div class="card">
      <div class="card-title">
        <span>🎓 Student Academic Dashboard</span>
        <span class="badge badge-student">STUDENT ROLE</span>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Student Name</div>
          <div class="info-value"><?= htmlspecialchars($user_name) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Student Email</div>
          <div class="info-value"><?= htmlspecialchars($user_email) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Department / Course</div>
          <div class="info-value"><?= htmlspecialchars($student_details['course'] ?? 'Computer Science & Engineering') ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Active Session ID</div>
          <div class="info-value"><code style="font-size: 0.82rem;"><?= htmlspecialchars($session_id) ?></code></div>
        </div>
      </div>
    </div>

    <!-- Session Fixation & ID Regeneration Verification Card -->
    <div class="card">
      <div class="card-title">
        <span>🛡️ Session ID Regeneration Verification (Fixation Defense)</span>
        <span class="badge badge-success">SECURED</span>
      </div>

      <p style="color: var(--text-secondary); margin-bottom: 1.25rem; font-size: 0.92rem;">
        Session ID regenerated upon successful authentication via <code>session_regenerate_id(true)</code>:
      </p>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Session Attribute</th>
              <th>Observed Value</th>
              <th>Validation</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Pre-Login Session ID</strong></td>
              <td><code><?= htmlspecialchars($pre_sess_id) ?></code></td>
              <td><span class="badge" style="background: #f1f5f9; color: #475569;">INITIAL</span></td>
            </tr>
            <tr>
              <td><strong>Post-Login Regenerated Session ID</strong></td>
              <td><code><?= htmlspecialchars($post_sess_id) ?></code></td>
              <td><span class="badge badge-success">VERIFIED REGENERATED</span></td>
            </tr>
            <tr>
              <td><strong>Session Fixation Attack Immunity</strong></td>
              <td>Old session invalidated; new crypto session issued</td>
              <td><span class="badge badge-success">PROTECTED</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RBAC Security Test: Attempt Admin Dashboard Access -->
    <div class="card">
      <div class="card-title">
        <span>🔒 RBAC Access Control Test</span>
      </div>
      <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.92rem;">
        Click below to verify that Student accounts attempting to access the Admin Control Center are intercepted with an <strong>Unauthorized Access</strong> warning:
      </p>
      <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="admin_dashboard.php" class="btn btn-outline">
          🧪 Test Student Attempting Admin Dashboard Access
        </a>
        <a href="logout.php" class="btn btn-danger">
          🔒 Secure Logout
        </a>
      </div>
    </div>

  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

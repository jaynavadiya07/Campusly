<?php
/**
 * Campusly Profile (profile.php)
 */

$pageTitle = "My Profile - Campusly";
$activePage = "profile.php";

require_once __DIR__ . '/auth.php';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
require_login(['student']);

// Fetch user profile from database
$user = null;
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT u.user_id, u.name, u.email, u.role, u.student_id, u.last_login, s.course 
                               FROM users u 
                               LEFT JOIN students s ON u.student_id = s.student_id 
                               WHERE u.user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        $user = null;
    }
}

$userName  = $user['name'] ?? $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Student';
$userEmail = $user['email'] ?? $_SESSION['email'] ?? $_SESSION['user_email'] ?? 'student@campusly.com';
$userCourse = $user['course'] ?? 'Computer Science & Engineering (CSE)';
$userRole  = ucfirst($user['role'] ?? $_SESSION['role'] ?? 'student');
$lastLogin = !empty($user['last_login']) 
    ? date('d M Y, h:i:s A', strtotime($user['last_login'])) 
    : 'Active Session';

require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li><a href="index.php">Home</a></li>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li aria-current="page">Profile</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <h1>My Profile & Academic Credentials</h1>
    <p>Manage your account settings, enrolled program details, and authentication safeguards.</p>

    <div class="grid-cols-3">
      <!-- Profile Card -->
      <section class="panel" style="text-align: center;">
        <div style="width: 110px; height: 110px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2.5rem; font-weight: 800;">
          <?= strtoupper(substr($userName, 0, 1)) ?>
        </div>
        <h2><?= htmlspecialchars($userName) ?></h2>
        <p style="margin-bottom: 0.25rem; font-weight: 600; color: #64748b;">Batch: 2025-29</p>
        <p style="margin-bottom: 0.5rem; font-size: 0.9rem; color: #64748b;">Role: <strong><?= htmlspecialchars($userRole) ?></strong></p>
        <span style="background: #dcfce7; color: #166534; font-size: 0.78rem; font-weight: 700; padding: 3px 10px; border-radius: 9999px;">
          Active Enrollment
        </span>
      </section>

      <!-- Edit Form Card -->
      <section class="panel" style="grid-column: span 2;">
        <h2>Academic & Security Details</h2>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" value="<?= htmlspecialchars($userName) ?>" readonly style="background: #f8fafc;">
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" value="<?= htmlspecialchars($userEmail) ?>" readonly style="background: #f8fafc;">
          </div>
          <div class="form-group">
            <label>Department / Course</label>
            <input type="text" value="<?= htmlspecialchars($userCourse) ?>" readonly style="background: #f8fafc;">
          </div>
          <div class="form-group">
            <label>Last Login Activity</label>
            <input type="text" value="<?= htmlspecialchars($lastLogin) ?>" readonly style="background: #f8fafc;">
          </div>
        </div>
      </section>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

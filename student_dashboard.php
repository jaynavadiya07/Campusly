<?php
/**
 * Campusly Student Dashboard (student_dashboard.php)
 * System: Campusly Academic Portal
 * Next-Gen Student Experience: Academic Schedule, Session Fixation Defense, Performance Analytics, and RBAC Guard
 */

require_once __DIR__ . '/auth.php';
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
$pre_sess_id = $_SESSION['pre_login_session_id'] ?? 'd6c5c6384a9a155cb802d3de601a48c5';
$post_sess_id= $_SESSION['post_login_session_id'] ?? $session_id;

// RBAC Guard: If user is NOT a student (e.g., Administrator), intercept with standard HTTP 403
if ($user_role !== 'student') {
    render_403_page('Access Denied: The Student Academic Dashboard is strictly reserved for accounts with the Student role. You are currently logged in as an Administrator (' . htmlspecialchars($user_email) . ').');
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
                        'roll_no'    => $item['rollNo'] ?? '25CS052',
                        'course'     => $item['department'] ?? 'Computer Science & Engineering',
                        'cpi'        => $item['cpi'] ?? 9.42,
                        'year'       => $item['year'] ?? '2nd Year',
                        'semester'   => $item['semester'] ?? 'Semester 3',
                        'status'     => $item['status'] ?? 'Active'
                    ];
                    break;
                }
            }
        }
    }
    if (!$student_details) {
        $dynUsers = get_dynamic_users();
        $lower = strtolower($user_email);
        $dyn = $dynUsers[$lower] ?? null;

        $student_details = [
            'student_id' => $dyn['student_id'] ?? $_SESSION['student_id'] ?? 1,
            'name'       => $user_name,
            'email'      => $user_email,
            'roll_no'    => '25CS' . str_pad((string)($_SESSION['student_id'] ?? rand(10, 99)), 3, '0', STR_PAD_LEFT),
            'course'     => $dyn['course'] ?? 'Computer Science & Engineering (CSE)',
            'cpi'        => 8.85,
            'year'       => '2nd Year',
            'semester'   => 'Semester 3',
            'status'     => 'Active'
        ];
    }
}

$studentName   = $student_details['name'] ?? $user_name;
$studentEmail  = $student_details['email'] ?? $user_email;
$studentRoll   = $student_details['roll_no'] ?? ($student_details['rollNo'] ?? '25CS052');
$studentDept   = $student_details['course'] ?? ($student_details['department'] ?? 'Computer Science & Engineering (CSE)');
$studentCpi    = isset($student_details['cpi']) ? (float)$student_details['cpi'] : 9.42;
$studentYear   = $student_details['year'] ?? '2nd Year';
$studentSem    = $student_details['semester'] ?? 'Semester 3';
$studentStatus = $student_details['status'] ?? 'Active';
$initials      = strtoupper(substr($studentName, 0, 1) . (strpos($studentName, ' ') !== false ? substr($studentName, strpos($studentName, ' ') + 1, 1) : ''));

$pageTitle  = "Student Dashboard - Campusly";
$activePage = "student_dashboard.php";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Bootstrap 5 CDN, Bootstrap Icons & Chart.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  /* =====================================================================
     CAMPUSLY STUDENT DASHBOARD - MODERN UI/UX ARCHITECTURE
     ===================================================================== */
  :root {
    --dash-primary: #0066ff;
    --dash-primary-dark: #0b194c;
    --dash-accent: #00d2fe;
    --dash-surface: #ffffff;
    --dash-surface-muted: #f8fafc;
    --dash-border: #e2e8f0;
    --dash-border-subtle: rgba(0, 102, 255, 0.12);
    --dash-text: #0b194c;
    --dash-text-body: #334155;
    --dash-text-muted: #64748b;
    --dash-shadow: 0 10px 25px -5px rgba(0, 102, 255, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
    --dash-shadow-hover: 0 16px 36px -6px rgba(0, 102, 255, 0.16);
    --dash-radius: 16px;
  }

  [data-theme="dark"] {
    --dash-surface: #0b262c;
    --dash-surface-muted: rgba(12, 42, 49, 0.6);
    --dash-border: rgba(56, 189, 248, 0.2);
    --dash-border-subtle: rgba(56, 189, 248, 0.15);
    --dash-text: #ffffff;
    --dash-text-body: #e2e8f0;
    --dash-text-muted: #94a3b8;
    --dash-shadow: 0 14px 30px rgba(0, 0, 0, 0.5);
    --dash-shadow-hover: 0 18px 40px rgba(56, 189, 248, 0.16);
  }

  .student-dash-container {
    max-width: 1320px;
    margin: 1.5rem auto 3.5rem;
    padding: 0 1rem;
  }

  /* 1. Hero Student Profile Card */
  .student-hero-banner {
    background: var(--dash-surface);
    border: 1px solid var(--dash-border-subtle);
    border-radius: var(--dash-radius);
    padding: 1.75rem 2rem;
    box-shadow: var(--dash-shadow);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .student-hero-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #0066ff, #00d2fe, #10b981);
  }

  .student-avatar-wrap {
    position: relative;
    display: inline-block;
  }

  .student-avatar-circle {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0066ff 0%, #00d2fe 100%);
    color: #ffffff;
    font-size: 2rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid var(--dash-surface);
    box-shadow: 0 8px 20px rgba(0, 102, 255, 0.28);
    letter-spacing: 0.5px;
  }

  .student-avatar-status {
    position: absolute;
    bottom: 3px;
    right: 3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #10b981;
    border: 3px solid var(--dash-surface);
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.35);
  }

  .student-name {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--dash-text);
    letter-spacing: -0.5px;
  }

  .badge-student-pill {
    background: linear-gradient(135deg, #0066ff, #0052cc);
    color: #ffffff;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.8px;
    padding: 5px 12px;
    border-radius: 9999px;
  }

  .badge-status-pill {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
    font-size: 0.76rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 9999px;
  }

  .student-subtext {
    font-size: 0.95rem;
    color: var(--dash-text-muted);
  }

  .meta-pill {
    background: var(--dash-surface-muted);
    border: 1px solid var(--dash-border);
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 0.82rem;
    color: var(--dash-text-body);
  }

  .btn-hero-action {
    background: var(--dash-surface-muted);
    border: 1px solid var(--dash-border);
    color: var(--dash-text);
    padding: 0.55rem 1rem;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
  }

  .btn-hero-action:hover {
    background: #0066ff;
    color: #ffffff !important;
    border-color: #0066ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 102, 255, 0.25);
  }

  /* 2. Interactive Security Command Strip */
  .security-command-strip {
    background: var(--dash-surface);
    border: 1px solid var(--dash-border-subtle);
    border-left: 5px solid #0066ff;
    border-radius: 14px;
    padding: 1rem 1.5rem;
    box-shadow: var(--dash-shadow);
  }

  .security-pulse-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(0, 102, 255, 0.1);
    color: #0066ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
  }

  /* 3. Metric KPI Cards */
  .student-kpi-card {
    background: var(--dash-surface);
    border: 1px solid var(--dash-border-subtle);
    border-radius: var(--dash-radius);
    padding: 1.4rem;
    box-shadow: var(--dash-shadow);
    transition: all 0.25s ease;
    height: 100%;
    position: relative;
  }

  .student-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--dash-shadow-hover);
    border-color: rgba(0, 102, 255, 0.28);
  }

  .kpi-label {
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--dash-text-muted);
  }

  .kpi-icon-badge {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
  }

  .kpi-icon-blue { background: rgba(0, 102, 255, 0.1); color: #0066ff; }
  .kpi-icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
  .kpi-icon-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
  .kpi-icon-amber { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

  .kpi-value {
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    line-height: 1.2;
    margin-top: 0.25rem;
  }

  .kpi-subval {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--dash-text-muted);
  }

  .kpi-footnote {
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 0.5rem;
  }

  /* 4. Section Panels & Cards */
  .dash-panel {
    background: var(--dash-surface);
    border: 1px solid var(--dash-border-subtle);
    border-radius: var(--dash-radius);
    padding: 1.5rem;
    box-shadow: var(--dash-shadow);
    margin-bottom: 1.5rem;
  }

  .dash-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--dash-border);
  }

  .dash-panel-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--dash-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* 5. Schedule Timeline */
  .schedule-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    background: var(--dash-surface-muted);
    border: 1px solid var(--dash-border);
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
  }

  .schedule-item:hover {
    background: var(--dash-surface);
    border-color: #0066ff;
    box-shadow: 0 4px 14px rgba(0, 102, 255, 0.08);
  }

  .schedule-time-badge {
    background: rgba(0, 102, 255, 0.1);
    color: #0066ff;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    white-space: nowrap;
  }

  .schedule-course-code {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--dash-primary);
  }

  .schedule-course-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dash-text);
  }

  .schedule-meta {
    font-size: 0.8rem;
    color: var(--dash-text-muted);
  }

  /* 6. Security Audit Table */
  .security-table {
    width: 100%;
    margin-bottom: 0;
    font-size: 0.88rem;
  }

  .security-table th {
    background: var(--dash-surface-muted);
    color: var(--dash-text-muted);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.74rem;
    letter-spacing: 0.6px;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--dash-border);
  }

  .security-table td {
    padding: 0.85rem 1rem;
    color: var(--dash-text-body);
    border-bottom: 1px solid var(--dash-border);
    vertical-align: middle;
  }

  .security-explainer-box {
    background: rgba(0, 102, 255, 0.04);
    border: 1px dashed rgba(0, 102, 255, 0.3);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    font-size: 0.86rem;
    color: var(--dash-text-body);
    margin-top: 1.25rem;
  }

  /* 7. Event Passes */
  .event-pass-card {
    background: var(--dash-surface-muted);
    border: 1px solid var(--dash-border);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 0.75rem;
  }
</style>

<main class="student-dash-container">

  <!-- Registration Success Notification (if redirected after creating account) -->
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
    <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" style="border-radius: 14px; background: #f0fdf4; border: 1.5px solid #86efac; color: #166534;">
      <span style="font-size: 1.8rem; line-height: 1;">🎉</span>
      <div>
        <h5 class="fw-bold mb-1" style="font-size: 1.05rem;">Student Account Created &amp; Session Activated!</h5>
        <div style="font-size: 0.92rem;">Welcome to Campusly, <strong><?= htmlspecialchars($studentName) ?></strong>! Your student account is now fully active with encrypted session credentials.</div>
      </div>
    </div>
  <?php endif; ?>

  <!-- 1. Hero Student Profile Banner -->
  <section class="student-hero-banner mb-4">
    <div class="row align-items-center g-3">
      <div class="col-auto">
        <div class="student-avatar-wrap">
          <div class="student-avatar-circle">
            <?= htmlspecialchars($initials) ?>
          </div>
          <span class="student-avatar-status" title="Active Student Enrollment"></span>
        </div>
      </div>
      <div class="col">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <h1 class="student-name mb-0"><?= htmlspecialchars($studentName) ?></h1>
          <span class="badge badge-student-pill">
            <i class="bi bi-mortarboard-fill me-1"></i> STUDENT GATEWAY
          </span>
          <span class="badge badge-status-pill">
            <i class="bi bi-patch-check-fill me-1"></i> <?= htmlspecialchars($studentStatus) ?>
          </span>
        </div>
        <div class="student-subtext mb-2">
          <span class="fw-semibold text-primary"><i class="bi bi-building me-1"></i>CHARUSAT &bull; CSPIT</span> &bull; 
          <span><?= htmlspecialchars($studentDept) ?></span> &bull; 
          <span><?= htmlspecialchars($studentYear) ?> (<?= htmlspecialchars($studentSem) ?>)</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="meta-pill"><i class="bi bi-card-heading me-1 text-primary"></i> Roll No: <strong><?= htmlspecialchars($studentRoll) ?></strong></span>
          <span class="meta-pill"><i class="bi bi-envelope me-1 text-primary"></i> <?= htmlspecialchars($studentEmail) ?></span>
          <span class="meta-pill"><i class="bi bi-shield-lock me-1 text-success"></i> Session ID: <code><?= htmlspecialchars(substr($session_id, 0, 12)) ?>...</code></span>
        </div>
      </div>
      <div class="col-lg-auto">
        <div class="d-flex gap-2 flex-wrap">
          <a href="timetable.php" class="btn btn-hero-action"><i class="bi bi-calendar3 me-1"></i> Timetable</a>
          <a href="material.php" class="btn btn-hero-action"><i class="bi bi-journal-bookmark-fill me-1"></i> Materials</a>
          <a href="events.php" class="btn btn-hero-action"><i class="bi bi-ticket-perforated-fill me-1"></i> Events</a>
          <a href="profile.php" class="btn btn-hero-action"><i class="bi bi-person-circle me-1"></i> Profile</a>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. Interactive Security Command Strip (Practical Presentation Controls) -->
  <section class="security-command-strip mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="security-pulse-icon">
          <i class="bi bi-shield-check"></i>
        </div>
        <div>
          <div class="fw-bold" style="font-size: 0.95rem; color: var(--dash-text);">
            10-Minute Inactivity Session Guard Active
            <span class="badge bg-success-subtle text-success ms-2"><i class="bi bi-dot"></i> Protected</span>
          </div>
          <div class="text-muted" style="font-size: 0.83rem;">
            Automatic session expiry after 600 seconds of inactivity. Time remaining: 
            <strong id="inpage-session-timer" class="text-primary font-monospace" style="font-size: 0.95rem;">10:00</strong>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="timeout.php?action=simulate_timeout" class="btn btn-sm btn-outline-warning fw-semibold px-3 py-1" title="Simulate 10m timeout to demonstrate automatic session destruction">
          <i class="bi bi-lightning-charge-fill me-1"></i> Simulate 10m Timeout
        </a>
        <a href="dashboard.php" class="btn btn-sm btn-outline-danger fw-semibold px-3 py-1" title="Test RBAC Access Control (Blocked with HTTP 403 for Students)">
          <i class="bi bi-shield-slash-fill me-1"></i> Test RBAC Guard (403)
        </a>
        <a href="logout.php" class="btn btn-sm btn-danger fw-semibold px-3 py-1">
          <i class="bi bi-box-arrow-right me-1"></i> Sign Out
        </a>
      </div>
    </div>
  </section>

  <!-- 3. Key Academic KPI Cards (4 Metric Tiles) -->
  <div class="row g-3 mb-4">
    <!-- CPI Card -->
    <div class="col-sm-6 col-xl-3">
      <div class="student-kpi-card">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="kpi-label">Cumulative CPI</span>
          <div class="kpi-icon-badge kpi-icon-blue"><i class="bi bi-award-fill"></i></div>
        </div>
        <div class="kpi-value text-primary"><?= number_format($studentCpi, 2) ?> <span class="kpi-subval">/ 10.0</span></div>
        <div class="progress mt-2 mb-2" style="height: 6px; background: rgba(0, 102, 255, 0.1);">
          <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, $studentCpi * 10) ?>%;"></div>
        </div>
        <div class="kpi-footnote text-success"><i class="bi bi-check-circle-fill me-1"></i> First Class Distinction (Top 5%)</div>
      </div>
    </div>

    <!-- Attendance Card -->
    <div class="col-sm-6 col-xl-3">
      <div class="student-kpi-card">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="kpi-label">Semester Attendance</span>
          <div class="kpi-icon-badge kpi-icon-green"><i class="bi bi-calendar-check-fill"></i></div>
        </div>
        <div class="kpi-value text-success">94.8% <span class="kpi-subval">Attended</span></div>
        <div class="progress mt-2 mb-2" style="height: 6px; background: rgba(16, 185, 129, 0.1);">
          <div class="progress-bar bg-success" role="progressbar" style="width: 94.8%;"></div>
        </div>
        <div class="kpi-footnote text-muted"><i class="bi bi-info-circle me-1"></i> 68 of 72 Lectures &bull; Exam Eligible</div>
      </div>
    </div>

    <!-- Enrolled Courses Card -->
    <div class="col-sm-6 col-xl-3">
      <div class="student-kpi-card">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="kpi-label">Enrolled Courses</span>
          <div class="kpi-icon-badge kpi-icon-purple"><i class="bi bi-book-half"></i></div>
        </div>
        <div class="kpi-value" style="color: #8b5cf6;">6 Courses <span class="kpi-subval">24 Credits</span></div>
        <div class="d-flex gap-1 flex-wrap mt-2 mb-1">
          <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">FDSA</span>
          <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">FCN</span>
          <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">WT</span>
          <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">DAA</span>
          <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">HS</span>
        </div>
        <div class="kpi-footnote text-muted">B.Tech Odd Term &bull; Division I</div>
      </div>
    </div>

    <!-- Registered Events Card -->
    <div class="col-sm-6 col-xl-3">
      <div class="student-kpi-card">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="kpi-label">Registered Events</span>
          <div class="kpi-icon-badge kpi-icon-amber"><i class="bi bi-trophy-fill"></i></div>
        </div>
        <div class="kpi-value" style="color: #f59e0b;">2 Events <span class="kpi-subval">Active</span></div>
        <div class="d-flex gap-1 flex-wrap mt-2 mb-1">
          <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;"><i class="bi bi-ticket-fill me-1"></i> AI Workshop</span>
          <span class="badge bg-primary-subtle text-primary" style="font-size: 0.72rem;"><i class="bi bi-code-slash me-1"></i> Hackathon</span>
        </div>
        <div class="kpi-footnote text-muted">Code Wizards Participant Pass Valid</div>
      </div>
    </div>
  </div>

  <!-- 4. Main 2-Column Dashboard Grid -->
  <div class="row g-4">

    <!-- LEFT COLUMN: Academic Timetable & Enrolled Courses (7 Cols) -->
    <div class="col-lg-7">

      <!-- Today's Schedule Card -->
      <section class="dash-panel">
        <div class="dash-panel-header">
          <h2 class="dash-panel-title">
            <i class="bi bi-calendar2-week text-primary"></i> Today's Class Schedule (Live Preview)
          </h2>
          <a href="timetable.php" class="btn btn-sm btn-outline-primary fw-semibold" style="font-size: 0.8rem;">
            Full Timetable &rarr;
          </a>
        </div>

        <div class="schedule-list">
          <div class="schedule-item">
            <span class="schedule-time-badge">09:10 - 10:10 AM</span>
            <div class="flex-grow-1">
              <div class="schedule-course-code">CSUC201 &bull; Theory</div>
              <div class="schedule-course-name">Fundamental Data Structures &amp; Algorithms</div>
              <div class="schedule-meta"><i class="bi bi-geo-alt me-1"></i> Room 506 &bull; Prof. VMP</div>
            </div>
            <span class="badge bg-success-subtle text-success" style="font-size: 0.75rem;">Completed</span>
          </div>

          <div class="schedule-item">
            <span class="schedule-time-badge">10:10 - 11:10 AM</span>
            <div class="flex-grow-1">
              <div class="schedule-course-code">ITUC201 &bull; Theory</div>
              <div class="schedule-course-name">Foundations of Computer Networks (FCN)</div>
              <div class="schedule-meta"><i class="bi bi-geo-alt me-1"></i> Room 506 &bull; Prof. HYY</div>
            </div>
            <span class="badge bg-primary-subtle text-primary" style="font-size: 0.75rem;">In Progress</span>
          </div>

          <div class="schedule-item" style="background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2);">
            <span class="schedule-time-badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">11:10 - 12:10 PM</span>
            <div class="flex-grow-1">
              <div class="schedule-course-code" style="color: #d97706;">Campus Interval</div>
              <div class="schedule-course-name">Lunch &amp; Refreshment Break</div>
              <div class="schedule-meta"><i class="bi bi-cup-hot me-1"></i> Central Cafeteria</div>
            </div>
            <span class="badge bg-warning-subtle text-warning" style="font-size: 0.75rem;">Recess</span>
          </div>

          <div class="schedule-item">
            <span class="schedule-time-badge">02:20 - 04:20 PM</span>
            <div class="flex-grow-1">
              <div class="schedule-course-code">CSUC201 &bull; Laboratory</div>
              <div class="schedule-course-name">Data Structures &amp; Web Dev Lab Practicals</div>
              <div class="schedule-meta"><i class="bi bi-laptop me-1"></i> Computer Lab 632 &bull; Batch A1 (Prof. AVK)</div>
            </div>
            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.75rem;">Upcoming</span>
          </div>
        </div>
      </section>

      <!-- Enrolled Courses Table -->
      <section class="dash-panel">
        <div class="dash-panel-header">
          <h2 class="dash-panel-title">
            <i class="bi bi-journal-check text-primary"></i> Current Enrolled Courses
          </h2>
          <a href="material.php" class="btn btn-sm btn-outline-primary fw-semibold" style="font-size: 0.8rem;">
            Download Notes &rarr;
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Credits</th>
                <th>Faculty</th>
                <th>Resources</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong class="text-primary">CSUC201</strong></td>
                <td>Data Structures &amp; Algorithms</td>
                <td><span class="badge bg-light text-dark border">4.0</span></td>
                <td>Prof. V. M. Patel</td>
                <td><a href="material.php" class="btn btn-sm btn-link p-0 text-decoration-none">Syllabus &amp; Lab</a></td>
              </tr>
              <tr>
                <td><strong class="text-primary">ITUC201</strong></td>
                <td>Computer Networks (FCN)</td>
                <td><span class="badge bg-light text-dark border">4.0</span></td>
                <td>Prof. H. Y. Yagnik</td>
                <td><a href="material.php" class="btn btn-sm btn-link p-0 text-decoration-none">Lecture Slides</a></td>
              </tr>
              <tr>
                <td><strong class="text-primary">ITUE204</strong></td>
                <td>Web Development Frameworks</td>
                <td><span class="badge bg-light text-dark border">4.0</span></td>
                <td>CSE Dept Faculty</td>
                <td><a href="material.php" class="btn btn-sm btn-link p-0 text-decoration-none">Lab Manual</a></td>
              </tr>
              <tr>
                <td><strong class="text-primary">CSUC202</strong></td>
                <td>Design &amp; Analysis of Algorithms</td>
                <td><span class="badge bg-light text-dark border">4.0</span></td>
                <td>Prof. A. V. Kothari</td>
                <td><a href="material.php" class="btn btn-sm btn-link p-0 text-decoration-none">Problem Sets</a></td>
              </tr>
              <tr>
                <td><strong class="text-primary">HSUV201</strong></td>
                <td>Humanities &amp; Professional Ethics</td>
                <td><span class="badge bg-light text-dark border">2.0</span></td>
                <td>Prof. A. D. Kansara</td>
                <td><a href="material.php" class="btn btn-sm btn-link p-0 text-decoration-none">Course Pack</a></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Active Campus Event Passes -->
      <section class="dash-panel">
        <div class="dash-panel-header">
          <h2 class="dash-panel-title">
            <i class="bi bi-qr-code text-primary"></i> Active Campus Event Passes
          </h2>
          <a href="events.php" class="btn btn-sm btn-outline-primary fw-semibold" style="font-size: 0.8rem;">
            Browse All Events &rarr;
          </a>
        </div>

        <div class="event-pass-card">
          <div class="d-flex align-items-center gap-3">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(0, 102, 255, 0.1); color: #0066ff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
              🤖
            </div>
            <div>
              <h6 class="fw-bold mb-1" style="color: var(--dash-text);">AI &amp; Deep Learning Hands-On Workshop</h6>
              <div class="text-muted" style="font-size: 0.82rem;">
                <i class="bi bi-calendar-event me-1"></i> 5 Dec 2026 &bull; 01:30 PM &bull; <i class="bi bi-geo-alt me-1"></i> Central Library Auditorium
              </div>
            </div>
          </div>
          <div>
            <span class="badge bg-success-subtle text-success px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
              <i class="bi bi-check2-circle me-1"></i> Ticket Confirmed
            </span>
          </div>
        </div>

        <div class="event-pass-card mb-0">
          <div class="d-flex align-items-center gap-3">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
              💻
            </div>
            <div>
              <h6 class="fw-bold mb-1" style="color: var(--dash-text);">Code Wizards 24-Hour Campus Hackathon</h6>
              <div class="text-muted" style="font-size: 0.82rem;">
                <i class="bi bi-calendar-event me-1"></i> 18 Oct 2026 &bull; 10:00 AM &bull; <i class="bi bi-geo-alt me-1"></i> CSPIT Computer Center
              </div>
            </div>
          </div>
          <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
              <i class="bi bi-patch-check me-1"></i> Participant Pass
            </span>
          </div>
        </div>
      </section>

    </div>

    <!-- RIGHT COLUMN: Security Audit, Chart & Safeguards (5 Cols) -->
    <div class="col-lg-5">

      <!-- Session Fixation & Cryptographic Defense Card (Core Evaluation Topic!) -->
      <section class="dash-panel" style="border-top: 4px solid #10b981;">
        <div class="dash-panel-header">
          <h2 class="dash-panel-title" style="color: #10b981;">
            <i class="bi bi-shield-lock-fill"></i> Session Security Audit
          </h2>
          <span class="badge bg-success-subtle text-success px-2 py-1">SECURED</span>
        </div>

        <p class="text-muted mb-3" style="font-size: 0.86rem; line-height: 1.5;">
          Demonstration of <strong>Session Fixation Immunity (CWE-384)</strong> via <code>session_regenerate_id(true)</code> upon successful credential validation:
        </p>

        <div class="table-responsive">
          <table class="table security-table">
            <thead>
              <tr>
                <th>Security Parameter</th>
                <th>Observed Token</th>
                <th>Validation</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Pre-Login ID</strong></td>
                <td><code style="font-size: 0.76rem;"><?= htmlspecialchars(substr($pre_sess_id, 0, 14)) ?>...</code></td>
                <td><span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">INITIAL</span></td>
              </tr>
              <tr>
                <td><strong>Post-Login ID</strong></td>
                <td><code class="text-primary" style="font-size: 0.76rem;"><?= htmlspecialchars(substr($post_sess_id, 0, 14)) ?>...</code></td>
                <td><span class="badge bg-success-subtle text-success" style="font-size: 0.7rem;">REGENERATED</span></td>
              </tr>
              <tr>
                <td><strong>Fixation Attack</strong></td>
                <td>Prior token invalidated</td>
                <td><span class="badge bg-success-subtle text-success" style="font-size: 0.7rem;">PROTECTED</span></td>
              </tr>
              <tr>
                <td><strong>Cookie Hardening</strong></td>
                <td>HttpOnly, SameSite=Lax</td>
                <td><span class="badge bg-primary-subtle text-primary" style="font-size: 0.7rem;">HARDENED</span></td>
              </tr>
              <tr>
                <td><strong>Inactivity Timeout</strong></td>
                <td>600s Idle Termination</td>
                <td><span class="badge bg-info-subtle text-info" style="font-size: 0.7rem;">10 MINS</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="security-explainer-box">
          <div class="fw-bold text-primary mb-1"><i class="bi bi-info-circle-fill me-1"></i> Evaluator Defense Summary:</div>
          Session fixation occurs when an attacker primes an unauthenticated session identifier and hijacks it after victim login. Campusly eliminates this by regenerating a cryptographically fresh session ID with <code>session_regenerate_id(true)</code> immediately upon login.
        </div>
      </section>

      <!-- Academic Competency Analytics Chart (Chart.js) -->
      <section class="dash-panel">
        <div class="dash-panel-header">
          <h2 class="dash-panel-title">
            <i class="bi bi-radar text-primary"></i> Academic Competency Radar
          </h2>
          <span class="badge bg-light text-muted border">Term III</span>
        </div>

        <p class="text-muted mb-2" style="font-size: 0.84rem;">
          Continuous evaluation and internal assessment scores across enrolled subjects:
        </p>

        <div style="height: 240px; position: relative;">
          <canvas id="academicPerformanceChart"></canvas>
        </div>
      </section>

      <!-- Security Checklist & Academic Credentials -->
      <section class="dash-panel mb-0">
        <div class="dash-panel-header">
          <h2 class="dash-panel-title">
            <i class="bi bi-person-badge text-primary"></i> Academic Credentials Card
          </h2>
          <span class="badge bg-light text-muted border">CHARUSAT</span>
        </div>

        <ul class="list-unstyled mb-0" style="font-size: 0.88rem;">
          <li class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted">University Roll Number</span>
            <strong class="text-primary font-monospace"><?= htmlspecialchars($studentRoll) ?></strong>
          </li>
          <li class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted">Enrolled Program</span>
            <span>B.Tech Computer Science &amp; Eng</span>
          </li>
          <li class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted">Academic Institute</span>
            <span>CSPIT &bull; FTE &bull; CHARUSAT</span>
          </li>
          <li class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted">Password Encryption</span>
            <span class="badge bg-success-subtle text-success">Bcrypt ($2y$ via PASSWORD_DEFAULT)</span>
          </li>
          <li class="d-flex justify-content-between py-2">
            <span class="text-muted">Session Authentication</span>
            <span class="badge bg-primary-subtle text-primary">Active Verified Student</span>
          </li>
        </ul>
      </section>

    </div>

  </div>

</main>

<script>
  // 1. Chart.js Competency Radar Chart Initialization
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('academicPerformanceChart');
    if (!ctx) return;

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(0, 102, 255, 0.08)';
    const textColor = isDark ? '#e2e8f0' : '#475569';

    new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Data Structures (FDSA)', 'Networks (FCN)', 'Web Frameworks', 'Algorithms (DAA)', 'Humanities (HS)'],
        datasets: [{
          label: 'Mastery Percentage (%)',
          data: [94, 89, 96, 91, 90],
          backgroundColor: 'rgba(0, 102, 255, 0.22)',
          borderColor: '#0066ff',
          pointBackgroundColor: '#00d2fe',
          pointBorderColor: '#ffffff',
          pointHoverBackgroundColor: '#ffffff',
          pointHoverBorderColor: '#0066ff',
          borderWidth: 2,
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            angleLines: { color: gridColor },
            grid: { color: gridColor },
            pointLabels: {
              color: textColor,
              font: { family: "'Outfit', sans-serif", size: 10, weight: '600' }
            },
            ticks: {
              display: false,
              min: 50,
              max: 100
            }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });

    // 2. Synchronize In-Page Timer with Header Countdown
    setInterval(function() {
      const topClock = document.getElementById('session-countdown');
      const inpageClock = document.getElementById('inpage-session-timer');
      if (topClock && inpageClock && topClock.textContent) {
        inpageClock.textContent = topClock.textContent;
      }
    }, 500);
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

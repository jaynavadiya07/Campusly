<?php
/**
 * Campusly Unified Header (includes/header.php)
 * Handles session validation, navigation bar, and top-right 10-minute session timeout widget.
 */
require_once __DIR__ . '/../auth.php';

$isLoggedIn = is_logged_in();
$userName   = $_SESSION['user_name'] ?? 'Student';
$userRole   = $_SESSION['role'] ?? 'student';
$remainingSecs = get_session_time_remaining();
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Campusly Portal') ?></title>
  <meta name="description" content="CHARUSAT Campusly - Academic Portal with 10-Minute Secure Session Timeout">
  <link rel="icon" type="image/png" href="images/favicon.png">
  <script>
    (function() {
      const urlParams = new URLSearchParams(window.location.search);
      const urlTheme = urlParams.get('theme');
      if (urlTheme === 'dark' || urlTheme === 'light') {
        document.documentElement.setAttribute('data-theme', urlTheme);
      } else {
        const savedTheme = localStorage.getItem('campusly_theme');
        if (savedTheme === 'dark') {
          document.documentElement.setAttribute('data-theme', 'dark');
        } else {
          document.documentElement.setAttribute('data-theme', 'light');
        }
      }
    })();
  </script>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/live-bg.js" defer></script>
  <script src="js/intro.js" defer></script>
  <style>
    /* Top-Right Session Timer inside Corner Controls */
    .top-corner-controls .top-right-session-timer {
      position: static;
      top: auto;
      right: auto;
    }
    .top-right-session-timer {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: var(--panel-bg);
      padding: 6px 14px;
      border-radius: 9999px;
      border: 1.5px solid #0066ff;
      box-shadow: 0 4px 14px rgba(0, 102, 255, 0.18);
      cursor: pointer;
      user-select: none;
      transition: all 0.25s ease;
    }
    .top-right-session-timer:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 102, 255, 0.28);
      border-color: #0052cc;
    }
    .top-right-session-timer .timer-icon {
      font-size: 0.95rem;
      line-height: 1;
    }
    .top-right-session-timer .timer-countdown-clock {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-main);
      letter-spacing: 0.5px;
      line-height: 1;
    }
    /* Warning state: 1 - 3 mins remaining */
    .top-right-session-timer.timer-warning {
      border-color: #f59e0b;
      box-shadow: 0 4px 20px rgba(245, 158, 11, 0.35);
    }
    .top-right-session-timer.timer-warning .timer-pulse-dot {
      background-color: #f59e0b;
    }
    .top-right-session-timer.timer-warning .timer-countdown-clock {
      color: #b45309;
      background: #fef3c7;
      border-color: #fde68a;
    }
    /* Critical state: < 1 min remaining */
    .top-right-session-timer.timer-danger {
      border-color: #ef4444;
      box-shadow: 0 4px 20px rgba(239, 68, 68, 0.45);
      animation: dangerFlash 1s infinite alternate;
    }
    @keyframes dangerFlash {
      from { transform: scale(1); }
      to { transform: scale(1.03); }
    }
    .top-right-session-timer.timer-danger .timer-pulse-dot {
      background-color: #ef4444;
    }
    .top-right-session-timer.timer-danger .timer-countdown-clock {
      color: #991b1b;
      background: #fee2e2;
      border-color: #fca5a5;
    }
    .top-right-session-timer .btn-refresh-timer {
      background: #f1f5f9;
      color: #0b194c;
      border: 1px solid #cbd5e1;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 4px 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s ease;
    }
    .top-right-session-timer .btn-refresh-timer:hover {
      background: #0066ff;
      color: #fff;
      border-color: #0066ff;
    }
    /* Timeout Warning Modal */
    .session-warning-modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(11, 25, 76, 0.6);
      backdrop-filter: blur(4px);
      z-index: 100000;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .session-warning-modal {
      background: #ffffff;
      padding: 2rem;
      border-radius: 16px;
      max-width: 440px;
      width: 90%;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      border: 2px solid #ef4444;
      text-align: center;
    }
    @media (max-width: 768px) {
      .top-right-session-timer {
        top: auto;
        bottom: 12px;
        right: 12px;
        padding: 5px 12px;
      }
      .top-right-session-timer .timer-title {
        display: none;
      }
    }
  </style>
</head>
<body>

  <!-- Cinematic Campusly Starting Intro Preloader (Inspired by fenilfinava.vercel.app) -->
  <div id="campusly-intro-preloader" class="campusly-intro" aria-label="Campusly Portal Intro">
    <div class="campusly-intro__orb"></div>
    <div class="campusly-intro__content">
      <div class="campusly-intro__logo-wrap">
        <img src="images/campusly-logo.png" alt="Campusly Logo" class="campusly-intro__logo">
      </div>
      <div class="campusly-intro__brand">
        <span class="campusly-intro__brand-campus">Campus</span><span class="campusly-intro__brand-ly">ly</span>
      </div>
      <div class="campusly-intro__line-track">
        <div class="campusly-intro__line-fill"></div>
      </div>
      <div class="campusly-intro__slogan">LEARN &bull; GROW &bull; BELONG</div>
      <div class="campusly-intro__subtext">CHARUSAT &bull; CSPIT ACADEMIC GATEWAY</div>
    </div>
    <div class="campusly-intro__skip-hint">Click anywhere to skip &rarr;</div>
  </div>

  <!-- Live Animated Background Canvas & Floating Aurora Mesh -->
  <div id="live-bg-container" class="live-bg-container" aria-hidden="true">
    <div class="live-bg-orb live-bg-orb-1"></div>
    <div class="live-bg-orb live-bg-orb-2"></div>
    <div class="live-bg-orb live-bg-orb-3"></div>
    <canvas id="live-bg-canvas" class="live-bg-canvas"></canvas>
  </div>

  <!-- Skip to Content Link -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Top-Right Floating Controls (Session Timer + Single Round Theme Toggle Button) -->
  <div class="top-corner-controls">
    <?php if ($isLoggedIn): ?>
      <!-- Sleek Top-Right Session Timer -->
      <div id="top-session-timer" class="top-right-session-timer" onclick="extendSession()" title="Session Timeout (10m) — Click to refresh" aria-label="Session Timeout (10m)">
        <span class="timer-icon">⏱️</span>
        <span class="timer-label" style="display:none;">Session Timeout (10m)</span>
        <span id="session-countdown" class="timer-countdown-clock">10:00</span>
      </div>
    <?php endif; ?>

    <!-- 🌙 / ☀️ Single Round Shape Theme Button from Top Right Corner (Theme #092328) -->
    <button type="button" id="theme-toggle-btn" class="theme-toggle-round-btn" onclick="toggleTheme()" aria-label="Switch Dark (#092328) / Light Theme" title="Toggle Dark/Light Theme">
      <span class="icon-sun" aria-hidden="true">☀️</span>
      <span class="icon-moon" aria-hidden="true">🌙</span>
    </button>
  </div>

  <script>
    function toggleTheme() {
      const htmlEl = document.documentElement;
      const currentTheme = htmlEl.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      htmlEl.setAttribute('data-theme', newTheme);
      try {
        localStorage.setItem('campusly_theme', newTheme);
      } catch(e) {}
    }
  </script>

  <?php if ($isLoggedIn): ?>

    <!-- Non-intrusive Expiring Warning Modal -->
    <div id="session-warning-modal" class="session-warning-modal-backdrop">
      <div class="session-warning-modal">
        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⏳</div>
        <h3 style="color: var(--text-main); margin-bottom: 0.5rem; font-weight: 800;">Session Expiring Soon</h3>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem;">
          Due to 10 minutes of inactivity, your session will expire in <strong id="modal-countdown" style="color: #ef4444; font-size: 1.1rem;">60s</strong>.
        </p>
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
          <button type="button" onclick="extendSession()" class="btn btn-primary" style="padding: 0.6rem 1.25rem;">Stay Logged In (+10m)</button>
          <a href="logout.php" class="btn btn-secondary" style="padding: 0.6rem 1rem;">Log Out</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <header>
    <div class="header-container">
      <a href="index.php" class="logo-link" aria-label="Campusly Home">
        <img src="images/campusly-logo.png" alt="Campusly Logo" class="brand-logo-img">
        <span class="brand-text"><span class="brand-campus">Campus</span><span class="brand-ly">ly</span></span>
      </a>
      <nav class="primary-nav" aria-label="Main Navigation">
        <ul>
          <li><a href="index.php" class="<?= $activePage === 'index.php' ? 'active-page' : '' ?>">Home</a></li>
          <?php if ($isLoggedIn): ?>
            <?php if ($userRole === 'admin'): ?>
              <!-- Admin Dynamic Role Navigation (RBAC) -->
              <li><a href="dashboard.php" class="admin-nav-link <?= in_array($activePage, ['dashboard.php', 'admin_dashboard.php']) ? 'active-page' : '' ?>">Admin Dashboard</a></li>
              <li><a href="students.php" class="<?= $activePage === 'students.php' ? 'active-page' : '' ?>">Students (CRUD)</a></li>
              <li><a href="events.php" class="<?= $activePage === 'events.php' ? 'active-page' : '' ?>">Campus Events</a></li>
              <li><a href="dashboard.php#audit-logs" class="<?= $activePage === 'audit_logs' ? 'active-page' : '' ?>">Audit Logs</a></li>
              <li><a href="about.php" class="<?= $activePage === 'about.php' ? 'active-page' : '' ?>">About</a></li>
              <li class="nav-logout-item"><a href="logout.php" class="nav-logout-link"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg><span>Log Out</span></a></li>
            <?php else: ?>
              <!-- Student Dynamic Role Navigation (RBAC) -->
              <li><a href="student_dashboard.php" class="<?= in_array($activePage, ['student_dashboard.php', 'dashboard.php']) ? 'active-page' : '' ?>">Student Dashboard</a></li>
              <li><a href="timetable.php" class="<?= $activePage === 'timetable.php' ? 'active-page' : '' ?>">Timetable</a></li>
              <li><a href="material.php" class="<?= $activePage === 'material.php' ? 'active-page' : '' ?>">Material</a></li>
              <li><a href="events.php" class="<?= $activePage === 'events.php' ? 'active-page' : '' ?>">Events</a></li>
              <li><a href="profile.php" class="<?= $activePage === 'profile.php' ? 'active-page' : '' ?>">Profile</a></li>
              <li><a href="about.php" class="<?= $activePage === 'about.php' ? 'active-page' : '' ?>">About</a></li>
              <li class="nav-logout-item"><a href="logout.php" class="nav-logout-link"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg><span>Log Out</span></a></li>
            <?php endif; ?>
          <?php else: ?>
            <li><a href="about.php" class="<?= $activePage === 'about.php' ? 'active-page' : '' ?>">About</a></li>
            <li><a href="login.php" class="<?= $activePage === 'login.php' ? 'active-page' : '' ?>">Sign In / Register</a></li>
            <li><a href="contact.php" class="<?= $activePage === 'contact.php' ? 'active-page' : '' ?>">Contact</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

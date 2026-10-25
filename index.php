<?php
/**
 * Campusly Home Page (index.php)
 */

$pageTitle = "Campusly - Learn • Grow • Belong";
$activePage = "index.php";

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li aria-current="page">Home</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <section class="hero-banner-panel" aria-labelledby="welcome-heading">
      <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
        <img src="images/campusly-logo.png" alt="Campusly Logo" style="width: 56px; height: 56px; object-fit: contain; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 102, 255, 0.2);">
        <div>
          <h1 id="welcome-heading" style="margin: 0; font-size: 2.2rem; line-height: 1.2;">
            <span class="hero-brand-charusat">CHARUSAT</span> <span class="brand-text"><span class="brand-campus">Campus</span><span class="brand-ly">ly</span></span>
          </h1>
          <div class="hero-subtitle">LEARN • GROW • BELONG</div>
        </div>
      </div>
      
      <p class="hero-desc">The next-generation academic portal for the <strong>Chandubhai S. Patel Institute of Technology (CSPIT)</strong> at <strong>Charotar University of Science and Technology</strong>. Empowering students with centralized academic resources, live schedules, interactive events, and role-based portal services.</p>
      
      <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <?php if (is_logged_in()): ?>
          <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="admin_dashboard.php" class="btn btn-primary">Go to Admin Control Center</a>
            <a href="events.php" class="btn btn-secondary">Explore Events</a>
          <?php else: ?>
            <a href="dashboard.php" class="btn btn-primary">Go to Student Dashboard</a>
            <a href="timetable.php" class="btn btn-secondary">Check Timetable</a>
            <a href="events.php" class="btn btn-secondary">Explore Events</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php?role=student" class="btn btn-primary">🎓 Student Sign In</a>
          <a href="login.php?role=admin" class="btn btn-secondary">🛡️ Administrator Sign In</a>
          <a href="about.php" class="btn btn-secondary">About Campusly</a>
        <?php endif; ?>
      </div>
    </section>

    <div class="grid-cols-3">
      <article class="feature-card feat-academic" aria-labelledby="feat-academic">
        <h3 id="feat-academic">Academic Excellence</h3>
        <p>CSPIT offers world-class undergraduate and postgraduate engineering courses accredited by NBA, designed to align with international professional standards.</p>
      </article>

      <article class="feature-card feat-events" aria-labelledby="feat-events">
        <h3 id="feat-events">Campus Events & Tech Fests</h3>
        <p>Discover and participate in university workshops, Cognizance annual tech fest, Spoural sports championships, and student-led innovation clubs.</p>
      </article>

      <article class="feature-card feat-rbac" aria-labelledby="feat-rbac">
        <h3 id="feat-rbac">Role-Based Access</h3>
        <p>Dynamic dashboard routing separates student and administrative services with cryptographically verified session safeguards.</p>
      </article>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

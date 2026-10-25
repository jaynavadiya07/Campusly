<?php
/**
 * Campusly Study Material (material.php)
 */

$pageTitle = "Study Material - Campusly";
$activePage = "material.php";

require_once __DIR__ . '/auth.php';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
require_login(['student']);
require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li><a href="index.php">Home</a></li>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li aria-current="page">Material</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <section class="panel" aria-labelledby="material-heading">
      <h1 id="material-heading">Study Materials & Resources</h1>
      <p>Select a subject below to download reference books, syllabus copies, lab sheets, and class slides.</p>

      <form style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;" role="search">
        <div style="flex: 1; min-width: 250px;">
          <input type="text" id="search-input" placeholder="Search by course code, keyword, or professor..." aria-label="Search study material files">
        </div>
        <button type="button" class="btn btn-primary">Filter Resources</button>
      </form>

      <div class="grid-cols-2">
        <!-- Web Development Frameworks resources -->
        <article class="panel" aria-labelledby="wt-material">
          <h2 id="wt-material" style="border: none; padding: 0; margin-bottom: 0.5rem;">Web Development Frameworks (ITUE204)</h2>
          <p style="font-size: 0.9rem; margin-bottom: 1rem; color: #64748b;">Instructor: CSE Department Faculty</p>
          <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
            <li><a href="#" style="color: var(--primary); text-decoration: none;">📄 Course Syllabus (PDF, 220KB)</a></li>
            <li><a href="#" style="color: var(--primary); text-decoration: none;">📄 Unit 1: Introduction to Framework Architectures (PDF, 1.4MB)</a></li>
            <li><a href="#" style="color: var(--primary); text-decoration: none;">📄 Academic Lab Manual: Setup Guidelines (PDF, 850KB)</a></li>
          </ul>
        </article>

        <!-- Data Structures & Algorithms resources -->
        <article class="panel" aria-labelledby="daa-material">
          <h2 id="daa-material" style="border: none; padding: 0; margin-bottom: 0.5rem;">Data Structures & Algorithms (CSUC201)</h2>
          <p style="font-size: 0.9rem; margin-bottom: 1rem; color: #64748b;">Instructor: CSE Department Faculty</p>
          <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
            <li><a href="#" style="color: var(--primary); text-decoration: none;">📄 Course Syllabus & Reference Books (PDF, 180KB)</a></li>
            <li><a href="#" style="color: var(--primary); text-decoration: none;">📄 Lecture Notes: Binary Search Trees (PDF, 2.1MB)</a></li>
            <li><a href="#" style="color: var(--primary); text-decoration: none;">📄 Problem Set 1: Time Complexity (PDF, 320KB)</a></li>
          </ul>
        </article>
      </div>
    </section>

    <div class="next-step-panel">
      <div class="next-step-content">
        <h3>Discover Upcoming Campus Events</h3>
        <p>Register for tech fests, chapter workshops, and cultural celebrations.</p>
      </div>
      <div>
        <a href="events.php" class="btn btn-primary">Explore Campus Events</a>
      </div>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

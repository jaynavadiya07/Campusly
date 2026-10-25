<?php
/**
 * Campusly About Page (about.php)
 */

$pageTitle = "About Us - Campusly";
$activePage = "about.php";

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li><a href="index.php">Home</a></li>
      <li aria-current="page">About</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
      <img src="images/campusly-logo.png" alt="Campusly Logo" style="width: 50px; height: 50px; object-fit: contain; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 102, 255, 0.15);">
      <div>
        <h1 id="about-heading" style="margin: 0;">About CHARUSAT & <span class="brand-text"><span class="brand-campus">Campus</span><span class="brand-ly">ly</span></span></h1>
        <div style="color: #0066ff; font-weight: 600; font-size: 0.9rem;">Learn • Grow • Belong</div>
      </div>
    </div>
    <p>The centralized academic nerve center for <strong>Charotar University of Science and Technology (CHARUSAT)</strong>. Empowering the university community with digital solutions, academic resources, and accessibility-compliant architecture.</p>

    <!-- Key Facts & Accreditations -->
    <div class="grid-cols-3" style="margin-bottom: 2rem;">
      <article class="panel" style="margin-bottom: 0; text-align: center; border-color: var(--primary);">
        <h3 style="color: var(--primary); font-size: 1.4rem;">NAAC "A+" Grade</h3>
        <p style="font-size: 0.9rem; margin-bottom: 0;">Accredited with premium institutional standards representing national academic quality.</p>
      </article>
      <article class="panel" style="margin-bottom: 0; text-align: center; border-color: var(--success);">
        <h3 style="color: var(--success); font-size: 1.4rem;">Top 3 in Gujarat</h3>
        <p style="font-size: 0.9rem; margin-bottom: 0;">Ranked consistently among the state's peak universities by GSIRF evaluations.</p>
      </article>
      <article class="panel" style="margin-bottom: 0; text-align: center; border-color: var(--accent);">
        <h3 style="color: var(--accent); font-size: 1.4rem;">125-Acre Campus</h3>
        <p style="font-size: 0.9rem; margin-bottom: 0;">State-of-the-art green campus in Changa, housing over 10,000 active students.</p>
      </article>
    </div>

    <div class="grid-cols-2" style="margin-bottom: 2rem;">
      <section class="panel" aria-labelledby="history-heading" style="margin-bottom: 0;">
        <h2 id="history-heading">Academic Legacy & Infrastructure</h2>
        <p>CHARUSAT has built a legacy of academic excellence since its inception. The campus features world-class computer centers, NBA-accredited course branches, and high-speed campus networks designed to provide industry-aligned engineering training.</p>
      </section>

      <section class="panel" aria-labelledby="chapters-heading" style="margin-bottom: 0;">
        <h2 id="chapters-heading">Research & Tech Innovations</h2>
        <p>CHARUSAT drives high-impact innovations, hosting a dedicated <strong>Space Technology Centre</strong> and running student projects like the development of functional solar cars and F3 racing models.</p>
      </section>

      <section class="panel" aria-labelledby="placements-heading" style="margin-bottom: 0;">
        <h2 id="placements-heading">Career Development & Global Placements</h2>
        <p>Spearheaded by the centralized <strong>Career Development and Placement Cell (CDPC)</strong>, CHARUSAT prepares students through industry bootcamps, competitive exam coaching (GATE/GRE/IELTS), and high-volume recruitment drives engaging over 350+ leading MNCs like TCS, L&amp;T, Infosys, and Amazon.</p>
      </section>

      <section class="panel" aria-labelledby="incubation-heading" style="margin-bottom: 0;">
        <h2 id="incubation-heading">Startup Incubation & Entrepreneurship</h2>
        <p>Through the <strong>CHARUSAT Innovative Ventures Foundation (CIVF)</strong> and <strong>Startup &amp; Innovation Centre (CSIC)</strong>, students are supported with dedicated pre-incubation maker spaces, patent/IP filing grants, and direct seed funding to transform prototype concepts into viable market startups.</p>
      </section>
    </div>

    <section class="panel" aria-labelledby="mission-heading">
      <h2 id="mission-heading">Our Mission & Security Standards</h2>
      <p>We aim to consolidate university resources into a unified digital portal equipped with 10-minute session inactivity safeguards, role-based access control, and cryptographically verified authentication under the <strong>Campusly</strong> initiative.</p>
    </section>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

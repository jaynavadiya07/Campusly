<?php
/**
 * Campusly Contact & Support Page (contact.php)
 */

$pageTitle = "Contact Support - Campusly";
$activePage = "contact.php";

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

  <!-- Breadcrumbs -->
  <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
      <li><a href="index.php">Home</a></li>
      <li aria-current="page">Contact</li>
    </ol>
  </nav>

  <!-- Main Content Area -->
  <main id="main-content">
    <h1 id="contact-title">Contact & Student Support Desk</h1>
    <p>Have questions about your enrollment, class schedule, or portal access? Send us a ticket.</p>

    <div class="grid-cols-2">
      <!-- Contact Info Panel -->
      <section class="panel" aria-labelledby="office-hours-heading">
        <h2 id="office-hours-heading">Office Information</h2>
        <p>Contact CHARUSAT administration or student support desks.</p>
        
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
          <li>
            <strong>📍 Location:</strong>
            <p style="margin-bottom: 0;">CHARUSAT Campus, Off Nadiad-Petlad Highway, Changa - 388421, District Anand, Gujarat, India</p>
          </li>
          <li>
            <strong>📧 Email Support:</strong>
            <p style="margin-bottom: 0;"><a href="mailto:info@charusat.ac.in" style="color: var(--primary);">info@charusat.ac.in</a> | <a href="mailto:studentservices@charusat.ac.in" style="color: var(--primary);">studentservices@charusat.ac.in</a></p>
          </li>
          <li>
            <strong>📞 Telephone Helpline:</strong>
            <p style="margin-bottom: 0;">+91 2697 265011 / +91 2697 265021</p>
          </li>
          <li>
            <strong>🕒 Office Hours:</strong>
            <p style="margin-bottom: 0;">Monday - Saturday: 09:00 AM to 04:30 PM</p>
          </li>
        </ul>
      </section>

      <!-- Message Ticket Form -->
      <section class="panel" aria-labelledby="form-ticket-heading">
        <h2 id="form-ticket-heading">Submit a Support Ticket</h2>
        
        <form onsubmit="event.preventDefault(); alert('Your ticket has been submitted to CSPIT Support!'); this.reset();">
          <div class="form-group">
            <label for="ticket-name">Your Full Name</label>
            <input type="text" id="ticket-name" name="ticket-name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label for="ticket-email">Your Email Address</label>
            <input type="email" id="ticket-email" name="ticket-email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label for="ticket-category">Category</label>
            <select id="ticket-category" name="ticket-category" required>
              <option value="technical">Technical Glitch / Bug</option>
              <option value="timetable">Timetable Discrepancy</option>
              <option value="material">Missing Study Material</option>
              <option value="auth">Session / Authentication Issue</option>
              <option value="other">Other Support Query</option>
            </select>
          </div>

          <div class="form-group">
            <label for="ticket-message">Message Details</label>
            <textarea id="ticket-message" name="ticket-message" rows="5" required placeholder="Describe your query or request..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary">Submit Support Ticket</button>
        </form>
      </section>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

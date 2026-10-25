  <!-- Footer -->
  <footer class="campusly-footer">
    <div class="footer-container">
      <div class="footer-brand-wrap">
        <a href="index.php" class="footer-brand-badge" title="Campusly Portal">
          <img src="images/campusly-logo.png" alt="Campusly Logo" class="footer-brand-logo">
          <div class="footer-brand-title">
            <span class="footer-brand-campus">Campus</span><span class="footer-brand-gradient">ly</span>
            <span class="footer-brand-sub">Portal</span>
          </div>
        </a>
        <p class="footer-tagline">Learn • Grow • Belong &mdash; CHARUSAT CSPIT Academic Gateway</p>
      </div>

      <nav class="footer-actions-nav" aria-label="Portal Quick Links">
        <a href="about.php" class="footer-nav-pill">
          <svg class="footer-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span>About Us</span>
        </a>
        <a href="contact.php" class="footer-nav-pill footer-nav-pill-highlight">
          <svg class="footer-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="10" r="1"></circle>
            <path d="M12 13v-1"></path>
          </svg>
          <span>Help</span>
        </a>
      </nav>
    </div>

    <div class="footer-bottom-bar">
      <div class="footer-bottom-content">
        <p class="footer-copyright">&copy; <?= date('Y') ?> Campusly Portal. All rights reserved.</p>
        <div class="footer-status-pill">
          <span class="status-indicator-dot"></span>
          <span>10-Minute Secure Session Guard</span>
        </div>
      </div>
    </div>
  </footer>

  <?php if (!empty($_SESSION['user_id'])): ?>
  <script>
    /**
     * CAMPUSLY ZERO-LAG, ZERO-DRIFT PRECISION SESSION TIMEOUT ENGINE
     * Uses wall-clock epoch timestamping, multi-tab storage sync, and graceful redirection.
     */
    (function() {
      const DURATION_SECONDS = 600; // 10 minutes
      const initialRemaining = <?= (int)get_session_time_remaining() ?>;
      
      const timerWidget = document.getElementById('top-session-timer');
      const clockEl = document.getElementById('session-countdown');
      const warningModal = document.getElementById('session-warning-modal');
      const modalCountdown = document.getElementById('modal-countdown');
      const refreshBtn = document.getElementById('btn-extend-timer');

      let hasTimedOut = false;
      const STORAGE_KEY = 'campusly_session_expire_at';

      // Always anchor against exact epoch timestamp to prevent tab-throttling drift
      let targetEpoch = Date.now() + (initialRemaining * 1000);
      try {
        localStorage.setItem(STORAGE_KEY, targetEpoch.toString());
      } catch (e) {}

      // Cross-tab synchronization
      window.addEventListener('storage', function(e) {
        if (e.key === STORAGE_KEY && e.newValue) {
          const syncedTarget = parseInt(e.newValue, 10);
          if (!isNaN(syncedTarget)) {
            targetEpoch = syncedTarget;
            renderTick();
          }
        }
      });

      function renderTick() {
        if (hasTimedOut || !clockEl || !timerWidget) return;

        const now = Date.now();
        const diffMs = targetEpoch - now;
        const remainingSeconds = Math.max(0, Math.ceil(diffMs / 1000));

        // Format MM:SS with leading zeroes
        const m = Math.floor(remainingSeconds / 60);
        const s = remainingSeconds % 60;
        clockEl.textContent = String(m).padStart(2, '0') + ":" + String(s).padStart(2, '0');

        if (modalCountdown) {
          modalCountdown.textContent = remainingSeconds + 's';
        }

        // Dynamic State Transitions
        if (remainingSeconds <= 0) {
          hasTimedOut = true;
          clockEl.textContent = "00:00";
          timerWidget.className = "top-right-session-timer timer-danger";
          if (warningModal) warningModal.style.display = 'none';

          // Graceful redirect without blocking alerts
          window.location.href = "login.php?msg=timeout";
          return;
        }

        if (remainingSeconds <= 60) {
          timerWidget.className = "top-right-session-timer timer-danger";
          if (warningModal && warningModal.style.display !== 'flex') {
            warningModal.style.display = 'flex';
          }
        } else if (remainingSeconds <= 180) {
          timerWidget.className = "top-right-session-timer timer-warning";
          if (warningModal) warningModal.style.display = 'none';
        } else {
          timerWidget.className = "top-right-session-timer";
          if (warningModal) warningModal.style.display = 'none';
        }
      }

      // Initial tick & reliable 500ms interval (immune to off-by-one tick drift)
      renderTick();
      const intervalId = setInterval(renderTick, 500);

      // Extend Session function
      window.extendSession = function() {
        if (hasTimedOut) return;
        
        // Optimistic UI update
        if (refreshBtn) {
          refreshBtn.textContent = '↻ Resetting...';
          refreshBtn.disabled = true;
        }
        
        fetch('auth_ping.php', { cache: 'no-store' })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') {
              const secs = data.remaining || DURATION_SECONDS;
              targetEpoch = Date.now() + (secs * 1000);
              try {
                localStorage.setItem(STORAGE_KEY, targetEpoch.toString());
              } catch (e) {}
              if (warningModal) warningModal.style.display = 'none';
              renderTick();
            } else {
              hasTimedOut = true;
              window.location.href = "login.php?msg=timeout";
            }
          })
          .catch(() => {
            targetEpoch = Date.now() + (DURATION_SECONDS * 1000);
            try {
              localStorage.setItem(STORAGE_KEY, targetEpoch.toString());
            } catch (e) {}
            if (warningModal) warningModal.style.display = 'none';
            renderTick();
          })
          .finally(() => {
            if (refreshBtn) {
              refreshBtn.textContent = '↻ Reset';
              refreshBtn.disabled = false;
            }
          });
      };
    })();
  </script>
  <?php endif; ?>

</body>
</html>

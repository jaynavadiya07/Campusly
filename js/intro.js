/**
 * Campusly Cinematic Intro Preloader Engine
 * Inspired by fenilfinava.vercel.app intro animation sequence:
 * - Monogram/Logo Pop with Ambient Halo
 * - Brand Name Reveal with Tracking Animation
 * - Expanding Glowing Laser Accent Line
 * - Slogan & Institutional Tagline Fade
 * - Smooth upward slide-out reveal (Click to skip or auto-dismiss)
 */
(function() {
  'use strict';

  function initCampuslyIntro() {
    const introEl = document.getElementById('campusly-intro-preloader');
    if (!introEl) return;

    // Detect page
    const path = window.location.pathname.toLowerCase();
    const isGatewayPage = path.endsWith('index.php') || path.endsWith('login.php') || path.endsWith('/') || path === '';

    // Skip intro if requested via URL parameter
    if (window.location.search.indexOf('nointro=1') !== -1) {
      introEl.classList.add('intro-dismissed');
      return;
    }

    // Check if user has already seen it in this session (allow replay on index and login)
    const hasSeenIntro = sessionStorage.getItem('campusly_intro_played') === '1';

    // On non-gateway pages, if already seen, hide immediately without animating
    if (!isGatewayPage && hasSeenIntro) {
      introEl.classList.add('intro-dismissed');
      return;
    }

    let isDismissed = false;

    function dismissIntro() {
      if (isDismissed) return;
      isDismissed = true;

      // Start graceful slide-up curtain
      introEl.classList.add('intro-hiding');
      sessionStorage.setItem('campusly_intro_played', '1');

      setTimeout(function() {
        introEl.classList.add('intro-dismissed');
        introEl.setAttribute('aria-hidden', 'true');
      }, 700);
    }

    // Auto-dismiss after full animation plays (~2.2s)
    const autoTimer = setTimeout(dismissIntro, 2200);

    // Interactive skip: Click/Tap anywhere or press Escape to skip immediately
    introEl.addEventListener('click', function() {
      clearTimeout(autoTimer);
      dismissIntro();
    });

    window.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.code === 'Escape') {
        clearTimeout(autoTimer);
        dismissIntro();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCampuslyIntro);
  } else {
    initCampuslyIntro();
  }
})();

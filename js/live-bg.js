/**
 * Campusly High-Performance Live Animated Background Engine
 * Features:
 * - Fluid celestial particle network with glowing nodes and distance-based constellation webs
 * - Real-time theme sync (Light azure / Dark electric cyan & aquamarine #092328)
 * - Interactive cursor gravitational response
 * - Zero CPU footprint when tab is inactive (visibility API paused)
 * - Retina/HiDPI display scaling
 */
(function() {
  'use strict';

  // Respect user preference for reduced motion
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  function initLiveBackground() {
    const canvas = document.getElementById('live-bg-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let width = 0;
    let height = 0;
    let dpr = window.devicePixelRatio || 1;
    let animationFrameId = null;
    let isRunning = false;

    // Mouse tracking
    const mouse = {
      x: null,
      y: null,
      radius: 140,
      active: false,
      idleTimer: null
    };

    // Color palettes
    const paletteLight = {
      particles: [
        'rgba(0, 102, 255, 0.45)',
        'rgba(0, 180, 255, 0.40)',
        'rgba(14, 165, 233, 0.35)',
        'rgba(99, 102, 241, 0.30)'
      ],
      lineColor: 'rgba(0, 102, 255,',
      mouseLineColor: 'rgba(0, 180, 255,',
      glow: 'rgba(0, 102, 255, 0.35)'
    };

    const paletteDark = {
      particles: [
        'rgba(56, 189, 248, 0.65)',
        'rgba(0, 210, 254, 0.70)',
        'rgba(45, 212, 191, 0.55)',
        'rgba(129, 140, 248, 0.50)'
      ],
      lineColor: 'rgba(56, 189, 248,',
      mouseLineColor: 'rgba(0, 210, 254,',
      glow: 'rgba(56, 189, 248, 0.65)'
    };

    function isDarkTheme() {
      return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    // Particle representation
    class Particle {
      constructor(w, h) {
        this.reset(w, h, true);
      }

      reset(w, h, randomInit = false) {
        this.x = randomInit ? Math.random() * w : (Math.random() > 0.5 ? 0 : w);
        this.y = randomInit ? Math.random() * h : Math.random() * h;
        this.radius = Math.random() * 2 + 1.2; // 1.2 to 3.2px
        this.baseRadius = this.radius;
        this.vx = (Math.random() - 0.5) * 0.65;
        this.vy = (Math.random() - 0.5) * 0.65;
        this.pulseAngle = Math.random() * Math.PI * 2;
        this.pulseSpeed = 0.02 + Math.random() * 0.02;
        this.colorIndex = Math.floor(Math.random() * 4);
      }

      update(w, h) {
        this.pulseAngle += this.pulseSpeed;
        this.radius = this.baseRadius + Math.sin(this.pulseAngle) * 0.5;

        // Interactive mouse interaction
        if (mouse.active && mouse.x !== null && mouse.y !== null) {
          const dx = mouse.x - this.x;
          const dy = mouse.y - this.y;
          const dist = Math.hypot(dx, dy);

          if (dist < mouse.radius) {
            const force = (1 - dist / mouse.radius) * 0.7;
            this.x -= (dx / dist) * force;
            this.y -= (dy / dist) * force;
          }
        }

        this.x += this.vx;
        this.y += this.vy;

        // Bounce gently at screen edges
        if (this.x < 0) { this.x = 0; this.vx *= -1; }
        else if (this.x > w) { this.x = w; this.vx *= -1; }
        if (this.y < 0) { this.y = 0; this.vy *= -1; }
        else if (this.y > h) { this.y = h; this.vy *= -1; }
      }

      draw(palette) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(this.x, this.y, Math.max(0.5, this.radius), 0, Math.PI * 2);
        ctx.fillStyle = palette.particles[this.colorIndex];
        ctx.shadowColor = palette.glow;
        ctx.shadowBlur = 6;
        ctx.fill();
        ctx.restore();
      }
    }

    let particles = [];

    function setupCanvas() {
      dpr = Math.min(window.devicePixelRatio || 1, 2); // Cap at 2 for performance
      width = window.innerWidth;
      height = window.innerHeight;

      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';

      ctx.setTransform(1, 0, 0, 1, 0, 0); // Reset transform
      ctx.scale(dpr, dpr);

      // Density calculation: ~35 on mobile, ~55 on desktop
      const targetCount = Math.max(24, Math.min(60, Math.floor((width * height) / 28000)));

      if (particles.length === 0) {
        for (let i = 0; i < targetCount; i++) {
          particles.push(new Particle(width, height));
        }
      } else if (particles.length < targetCount) {
        while (particles.length < targetCount) {
          particles.push(new Particle(width, height));
        }
      } else {
        particles = particles.slice(0, targetCount);
      }
    }

    // Connect close particles with delicate glowing constellation strands
    function drawConnections(palette) {
      const maxDist = width < 768 ? 85 : 115;
      const len = particles.length;

      for (let i = 0; i < len; i++) {
        const p1 = particles[i];

        // Particle to particle connection
        for (let j = i + 1; j < len; j++) {
          const p2 = particles[j];
          const dx = p1.x - p2.x;
          const dy = p1.y - p2.y;
          const dist = Math.hypot(dx, dy);

          if (dist < maxDist) {
            const alpha = (1 - dist / maxDist) * 0.22;
            ctx.beginPath();
            ctx.moveTo(p1.x, p1.y);
            ctx.lineTo(p2.x, p2.y);
            ctx.strokeStyle = palette.lineColor + alpha.toFixed(3) + ')';
            ctx.lineWidth = 1;
            ctx.stroke();
          }
        }

        // Particle to mouse cursor connection
        if (mouse.active && mouse.x !== null && mouse.y !== null) {
          const mdx = p1.x - mouse.x;
          const mdy = p1.y - mouse.y;
          const mdist = Math.hypot(mdx, mdy);

          if (mdist < mouse.radius) {
            const mAlpha = (1 - mdist / mouse.radius) * 0.35;
            ctx.beginPath();
            ctx.moveTo(p1.x, p1.y);
            ctx.lineTo(mouse.x, mouse.y);
            ctx.strokeStyle = palette.mouseLineColor + mAlpha.toFixed(3) + ')';
            ctx.lineWidth = 1.2;
            ctx.stroke();
          }
        }
      }
    }

    // Main render loop
    function render() {
      if (!isRunning) return;

      const palette = isDarkTheme() ? paletteDark : paletteLight;

      ctx.clearRect(0, 0, width, height);

      // 1. Draw connecting web
      drawConnections(palette);

      // 2. Update and draw particles
      for (let i = 0; i < particles.length; i++) {
        particles[i].update(width, height);
        particles[i].draw(palette);
      }

      animationFrameId = requestAnimationFrame(render);
    }

    function start() {
      if (!isRunning) {
        isRunning = true;
        render();
      }
    }

    function stop() {
      isRunning = false;
      if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
      }
    }

    // Window events
    let resizeTimer = null;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setupCanvas, 150);
    }, { passive: true });

    // Mouse movement
    window.addEventListener('mousemove', function(e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      mouse.active = true;

      clearTimeout(mouse.idleTimer);
      mouse.idleTimer = setTimeout(function() {
        mouse.active = false;
      }, 2500);
    }, { passive: true });

    window.addEventListener('mouseleave', function() {
      mouse.active = false;
    }, { passive: true });

    // Touch support for mobile/tablets
    window.addEventListener('touchmove', function(e) {
      if (e.touches && e.touches[0]) {
        mouse.x = e.touches[0].clientX;
        mouse.y = e.touches[0].clientY;
        mouse.active = true;

        clearTimeout(mouse.idleTimer);
        mouse.idleTimer = setTimeout(function() {
          mouse.active = false;
        }, 2000);
      }
    }, { passive: true });

    // Pause when tab is not active to consume 0% resources
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        stop();
      } else {
        start();
      }
    });

    // Initialize and run
    setupCanvas();
    start();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLiveBackground);
  } else {
    initLiveBackground();
  }
})();

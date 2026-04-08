/**
 * SwiftBite — Global UI Script
 * Handles: page-load progress bar, mobile nav, scroll reveal,
 *          back-to-top button, category tab switching.
 */

/* ── Page-load progress bar ─────────────────────────────── */
(function () {
  const bar = document.createElement('div');
  bar.id = 'sb-progress';
  document.body.prepend(bar);

  let width = 0;
  let interval = null;

  function startProgress() {
    width = 0;
    bar.style.opacity = '1';
    bar.classList.remove('done');
    interval = setInterval(() => {
      // Slows down as it nears 90%
      if (width < 90) {
        width += (90 - width) * 0.08;
        bar.style.width = width + '%';
      }
    }, 80);
  }

  function finishProgress() {
    clearInterval(interval);
    bar.style.width = '100%';
    setTimeout(() => bar.classList.add('done'), 250);
  }

  // Start on navigation away
  document.addEventListener('click', function (e) {
    const a = e.target.closest('a');
    if (a && a.href && !a.target && !a.href.startsWith('#') &&
        !a.href.startsWith('javascript') &&
        a.origin === location.origin) {
      startProgress();
    }
  });

  // Finish when page is fully loaded
  window.addEventListener('load', finishProgress);
  // Also finish immediately if DOM already loaded
  if (document.readyState === 'complete') finishProgress();
})();

/* ── Back-to-top button ─────────────────────────────────── */
(function () {
  const btn = document.createElement('button');
  btn.id = 'sb-back-top';
  btn.title = 'Back to top';
  btn.textContent = '↑';
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  }, { passive: true });

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();

/* ── Main DOMContentLoaded ──────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Mobile nav toggle
  const mobileToggle = document.querySelector('[data-mobile-toggle]');
  const navLinks     = document.querySelector('.nav-links');

  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => {
      navLinks.classList.toggle('show');
    });
    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!mobileToggle.contains(e.target) && !navLinks.contains(e.target)) {
        navLinks.classList.remove('show');
      }
    });
  }

  // Category tab switching (home page)
  const catCards = document.querySelectorAll('.cat-card');
  catCards.forEach((card) => {
    card.addEventListener('click', () => {
      catCards.forEach((c) => c.classList.remove('active'));
      card.classList.add('active');
    });
  });

  // Scroll reveal — observe food cards, steps, testimonials, etc.
  const revealItems = document.querySelectorAll(
    '.food-card, .testi-card, .step, .rest-home-card, [data-reveal]'
  );

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          // Stagger siblings in the same grid/container
          const siblings = [...(entry.target.parentElement?.children || [])];
          const idx = siblings.indexOf(entry.target);
          entry.target.style.transitionDelay = (idx * 60) + 'ms';
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    revealItems.forEach((el) => {
      el.classList.add('reveal-on-scroll');
      observer.observe(el);
    });
  } else {
    // Fallback: just show everything
    revealItems.forEach((el) => el.classList.add('revealed'));
  }
});

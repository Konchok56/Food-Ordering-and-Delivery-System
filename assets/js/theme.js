/**
 * SwiftBite – Theme Toggle (Light / Dark)
 * Applied immediately to <html> to prevent flash of wrong theme.
 */
(function () {
  const KEY  = 'sb-theme';
  const html = document.documentElement;

  // Apply saved preference RIGHT AWAY (before paint)
  const saved = localStorage.getItem(KEY) || 'light';
  html.setAttribute('data-theme', saved);

  // Wire toggle button once DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    const btn      = document.getElementById('theme-toggle');
    if (!btn) return;

    const iconLight = btn.querySelector('.theme-icon-light');
    const iconDark  = btn.querySelector('.theme-icon-dark');

    function syncBtn(theme) {
      btn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
      btn.setAttribute('aria-label', btn.title);
      btn.setAttribute('data-active-theme', theme);
      // Show moon in light mode (click to go dark), sun in dark mode (click to go light)
      if (iconLight) iconLight.style.display = theme === 'dark' ? 'none'   : 'inline';
      if (iconDark)  iconDark.style.display  = theme === 'dark' ? 'inline' : 'none';
    }

    syncBtn(saved);

    btn.addEventListener('click', function () {
      const current = html.getAttribute('data-theme') || 'light';
      const next    = current === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem(KEY, next);
      syncBtn(next);

      // Ripple animation
      btn.classList.add('theme-toggling');
      setTimeout(() => btn.classList.remove('theme-toggling'), 400);
    });
  });
})();

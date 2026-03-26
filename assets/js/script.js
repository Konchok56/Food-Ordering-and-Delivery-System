document.addEventListener('DOMContentLoaded', () => {
  const mobileToggle = document.querySelector('[data-mobile-toggle]');
  const navLinks = document.querySelector('.nav-links');
  const catCards = document.querySelectorAll('.cat-card');
  const revealItems = document.querySelectorAll('.reveal-on-scroll, .food-card, .testi-card, .step');

  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => {
      navLinks.classList.toggle('show');
    });
  }

  catCards.forEach((card) => {
    card.addEventListener('click', () => {
      catCards.forEach((item) => item.classList.remove('active'));
      card.classList.add('active');
    });
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  revealItems.forEach((el) => {
    el.classList.add('reveal-on-scroll');
    observer.observe(el);
  });
});

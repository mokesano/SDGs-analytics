// Main entry point for Wizdam Sikola
import './utils.js';

// Theme Toggle
function initThemeToggle() {
  const toggle = document.getElementById('dark-mode-toggle');
  const html = document.documentElement;
  
  // Check saved preference or system preference
  const savedTheme = localStorage.getItem('theme');
  const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  
  if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
    html.classList.add('dark');
  }
  
  if (toggle) {
    toggle.addEventListener('click', () => {
      html.classList.toggle('dark');
      localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
    });
  }
}

// Scroll Reveal Animation
function initScrollReveal() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
      }
    });
  }, { threshold: 0.1 });
  
  document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  initThemeToggle();
  initScrollReveal();
});

// Export for use in other modules
export { initThemeToggle, initScrollReveal };

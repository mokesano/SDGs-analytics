/**
 * scroll-reveal.js — Intersection Observer animations + interactive effects
 * Wizdam AI-scola SDG Analytics
 */
(function () {
  'use strict';

  // ── Scroll Reveal (Intersection Observer) ─────────────────────────────
  const revealSelectors = '.reveal, .reveal-left, .reveal-right, .reveal-scale';

  const revealObserver = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
  );

  function initReveal() {
    document.querySelectorAll(revealSelectors).forEach(function (el) {
      // skip if already active (e.g. above the fold on load)
      if (!el.classList.contains('active')) {
        revealObserver.observe(el);
      }
    });
  }

  // ── Magic Card: mouse tracking for gradient border ─────────────────────
  function initMagicCards() {
    document.querySelectorAll('.magic-card').forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        var rect = card.getBoundingClientRect();
        card.style.setProperty('--mouse-x', (e.clientX - rect.left) + 'px');
        card.style.setProperty('--mouse-y', (e.clientY - rect.top) + 'px');
      });
    });
  }

  // ── Dotted BG: mouse tracking for highlight dots ───────────────────────
  function initDottedBg() {
    document.querySelectorAll('.dotted-bg').forEach(function (el) {
      el.addEventListener('mousemove', function (e) {
        var rect = el.getBoundingClientRect();
        el.style.setProperty('--mouse-x', (e.clientX - rect.left) + 'px');
        el.style.setProperty('--mouse-y', (e.clientY - rect.top) + 'px');
      });
      el.addEventListener('mouseleave', function () {
        el.style.setProperty('--mouse-x', '-1000px');
        el.style.setProperty('--mouse-y', '-1000px');
      });
    });
  }

  // ── Scroll: back-to-top + navbar scrolled state ─────────────────────────
  function initScrollEffects() {
    var backToTop = document.getElementById('backToTop') || document.getElementById('back-to-top');
    var navbar    = document.querySelector('.navbar');
    var lastST    = 0;

    window.addEventListener('scroll', function () {
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

      // Back to top button
      if (backToTop) {
        if (scrollTop > 300) {
          backToTop.classList.add('show');
        } else {
          backToTop.classList.remove('show');
        }
      }

      // Navbar scroll state
      if (navbar) {
        if (scrollTop > 50) {
          navbar.classList.add('scrolled');
        } else {
          navbar.classList.remove('scrolled');
        }

        if (scrollTop > lastST && scrollTop > 200) {
          navbar.classList.add('scrolled-down');
        } else {
          navbar.classList.remove('scrolled-down');
        }
      }

      lastST = scrollTop;
    }, { passive: true });
  }

  // ── Page Loader hide ───────────────────────────────────────────────────
  function initPageLoader() {
    window.addEventListener('load', function () {
      var loader = document.getElementById('page-loader');
      if (loader) {
        setTimeout(function () {
          loader.classList.add('hidden');
          setTimeout(function () { loader.style.display = 'none'; }, 300);
        }, 400);
      }
    });
  }

  // ── FAQ Accordion ──────────────────────────────────────────────────────
  function initFaqAccordion() {
    document.querySelectorAll('.faq-question').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var answer   = btn.nextElementSibling;
        var isOpen   = btn.classList.contains('active');

        // Close all open
        document.querySelectorAll('.faq-question.active').forEach(function (openBtn) {
          openBtn.classList.remove('active');
          var ans = openBtn.nextElementSibling;
          if (ans) ans.classList.remove('open');
        });

        // Toggle current
        if (!isOpen && answer) {
          btn.classList.add('active');
          answer.classList.add('open');
        }
      });
    });
  }

  // ── Confidence Bars Animation ──────────────────────────────────────────
  function animateConfidenceBars() {
    document.querySelectorAll('.confidence-fill').forEach(function (fill, i) {
      var target = fill.getAttribute('data-width') || fill.style.width;
      if (target) {
        fill.style.width = '0%';
        setTimeout(function () { fill.style.width = target; }, 150 + i * 60);
      }
    });
  }

  // ── Staggered children ────────────────────────────────────────────────
  function initStagger() {
    document.querySelectorAll('[data-stagger]').forEach(function (parent) {
      var delay = parseInt(parent.getAttribute('data-stagger')) || 100;
      Array.from(parent.children).forEach(function (child, i) {
        child.style.transitionDelay = (i * delay) + 'ms';
        child.classList.add('reveal');
        revealObserver.observe(child);
      });
    });
  }

  // ── Bootstrap all ─────────────────────────────────────────────────────
  function init() {
    initReveal();
    initMagicCards();
    initDottedBg();
    initScrollEffects();
    initPageLoader();
    initFaqAccordion();
    initStagger();

    // Run confidence bar animation after a short delay
    setTimeout(animateConfidenceBars, 500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Re-init when new content is dynamically added
  window.scrollRevealInit = function () {
    initReveal();
    initMagicCards();
    initDottedBg();
    setTimeout(animateConfidenceBars, 200);
  };
})();

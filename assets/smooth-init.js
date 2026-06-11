document.addEventListener('DOMContentLoaded', function () {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReducedMotion) {
    return;
  }

  if (typeof SmoothScroll !== 'undefined') {
    try {
      new SmoothScroll('a[href*="#"]', { speed: 800 });
    } catch (e) {
      console && console.warn && console.warn('SmoothScroll init failed:', e);
    }
  }
});

document.addEventListener('DOMContentLoaded', function () {
  if (typeof SmoothScroll !== 'undefined') {
    try {
      new SmoothScroll('a[href*="#"]', { speed: 800 });
    } catch (e) {
      // fail silently
      console && console.warn && console.warn('SmoothScroll init failed:', e);
    }
  }
});

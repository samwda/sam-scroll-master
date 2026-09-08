/**
 * Sam Scroll Master — frontend init
 */
document.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  if (typeof SmoothScroll === 'undefined') {
    return;
  }

  // Only same-page anchors (fix: bare "#" and cross-page links excluded).
  var links = document.querySelectorAll('a[href*="#"]');
  var marked = 0;

  Array.prototype.forEach.call(links, function (a) {
    var href = a.getAttribute('href');
    if (!href) return;

    var isSamePage = false;

    if (href.charAt(0) === '#') {
      isSamePage = href.length > 1; // ignore bare "#"
    } else if (href.indexOf('#') !== -1) {
      try {
        var url = new URL(a.href, window.location.href);
        isSamePage = (url.pathname === window.location.pathname && url.search === window.location.search);
      } catch (e) {
        isSamePage = false;
      }
    }

    if (isSamePage) {
      a.setAttribute('data-samsm-scroll', '');
      marked++;
    }
  });

  if (marked) {
    try {
      new SmoothScroll('a[data-samsm-scroll]', { speed: 800 });
    } catch (e) {
      if (window.console && console.warn) {
        console.warn('SmoothScroll init failed:', e);
      }
    }
  }
});

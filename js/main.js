(function () {
  'use strict';

  var grid = document.querySelector('.photos');
  if (!grid) return;

  var tiles = Array.from(grid.querySelectorAll('.mikey-image'));

  // MASONRY GRID
  // Every <img> carries width/height attributes (see index.php), so the
  // browser already knows how tall each tile will be. That lets Masonry lay
  // out the grid immediately instead of waiting for every image to download.
  if (window.Masonry) {
    grid.classList.add('is-masonry');

    var masonry = new Masonry(grid, {
      itemSelector: '.mikey-image',
      percentPosition: true
    });

    // An image PHP couldn't measure only gets its real height once it loads.
    grid.querySelectorAll('img:not([width])').forEach(function (img) {
      img.addEventListener('load', function () { masonry.layout(); }, { once: true });
    });
  }

  // RISE INTO VIEW
  // Each image waits below the fold and slides up as it scrolls into view.
  // Images that arrive together cascade in reading order, a beat apart.
  var STAGGER = 90;   // ms between images that arrive together
  var MAX_STEPS = 5;  // never make anyone wait longer than this many beats

  function show(tile, delay, instant) {
    tile.style.setProperty('--delay', delay + 'ms');
    tile.classList.toggle('is-instant', !!instant);
    tile.classList.add('is-in');
  }

  // Hold the animation until the image has actually loaded, so nothing ever
  // slides up as an empty box.
  function reveal(tile, delay) {
    var img = tile.querySelector('img');
    var go = function () { show(tile, delay); };

    if (img.complete) {
      go();
    } else {
      img.addEventListener('load', go, { once: true });
      img.addEventListener('error', go, { once: true });
    }
  }

  if (!('IntersectionObserver' in window)) {
    tiles.forEach(function (tile) { show(tile, 0, true); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    var arriving = [];

    entries.forEach(function (entry) {
      var top = entry.rootBounds ? entry.rootBounds.top : 0;
      var box = entry.boundingClientRect;

      if (entry.isIntersecting && box.top >= top) {
        arriving.push(entry);
      } else if (box.top < top) {
        // Already scrolled past (a reload partway down the page, or scrolling
        // back up): show it in place rather than rising against the scroll.
        observer.unobserve(entry.target);
        show(entry.target, 0, true);
      }
    });

    arriving
      .sort(function (a, b) {
        return (a.boundingClientRect.top - b.boundingClientRect.top) ||
               (a.boundingClientRect.left - b.boundingClientRect.left);
      })
      .forEach(function (entry, i) {
        observer.unobserve(entry.target);
        reveal(entry.target, Math.min(i, MAX_STEPS) * STAGGER);
      });
  }, { rootMargin: '0px 0px -6% 0px' });

  tiles.forEach(function (tile) { observer.observe(tile); });
})();

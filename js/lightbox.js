(function () {
  'use strict';

  // LIGHTBOX
  // Click an image and it grows out of the grid to fill the window. Step
  // through the gallery with the arrow keys, the on-screen arrows, or a swipe.
  // Close with Esc, the close button, a click outside the image, or a swipe
  // down; the image shrinks back into its spot. Without JavaScript (or
  // <dialog> support) each link simply opens the image file.

  var dialog = document.querySelector('.lightbox');
  var grid = document.querySelector('.photos');
  if (!dialog || !grid || typeof dialog.showModal !== 'function') return;

  var links = Array.from(grid.querySelectorAll('.mikey-link'));
  var scrim = dialog.querySelector('.lightbox-scrim');
  var stage = dialog.querySelector('.lightbox-stage');
  var image = dialog.querySelector('.lightbox-image');
  var position = dialog.querySelector('.lightbox-position');
  var status = dialog.querySelector('.lightbox-status');
  var controls = Array.from(dialog.querySelectorAll('.lightbox-button, .lightbox-count'));
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  var EASE_OUT = 'cubic-bezier(0.16, 1, 0.3, 1)';
  var MAX_UPSCALE = 2;  // never blow a small image up past twice its real size
  var SLIDE = 48;       // px an image travels when stepping to the next one

  var current = 0;
  var savedScroll = 0;
  var busy = false;     // an open, close, or step animation is running
  var pending = null;   // the one request that arrived while busy

  // SIZING

  function sizeOf(link) {
    var thumb = link.querySelector('img');
    return {
      width: thumb.naturalWidth || Number(thumb.getAttribute('width')) || 1,
      height: thumb.naturalHeight || Number(thumb.getAttribute('height')) || 1
    };
  }

  // Fit the image inside the stage, keeping its shape. Sizing it up front
  // (instead of waiting for it to load) keeps the animations exact.
  function fit() {
    var box = stage.getBoundingClientRect();
    var size = sizeOf(links[current]);
    var scale = Math.min(box.width / size.width, box.height / size.height, MAX_UPSCALE);

    image.style.width = Math.round(size.width * scale) + 'px';
    image.style.height = Math.round(size.height * scale) + 'px';
  }

  function show(index) {
    links[current].parentNode.classList.remove('is-viewing');
    current = (index + links.length) % links.length;

    var link = links[current];
    var alt = link.querySelector('img').alt;

    link.parentNode.classList.add('is-viewing');
    image.src = link.href;
    image.alt = alt;
    fit();
    position.textContent = current + 1;
    status.textContent = alt + ', ' + (current + 1) + ' of ' + links.length;

    // Fetch the neighbors now so stepping to them is instant.
    [current - 1, current + 1].forEach(function (i) {
      new Image().src = links[(i + links.length) % links.length].href;
    });
  }

  // ANIMATION HELPERS

  // The transform that shrinks and moves box `to` so it sits exactly on box
  // `from`. Both boxes hold the same image, so they share a shape.
  function flip(from, to) {
    return 'translate(' + (from.left - to.left) + 'px, ' + (from.top - to.top) + 'px) ' +
           'scale(' + (from.width / to.width) + ')';
  }

  function fade(element, from, to, options) {
    return element.animate({ opacity: [from, to] }, Object.assign({ easing: 'ease-out', fill: 'both' }, options));
  }

  function done(animations) {
    return Promise.all(animations.map(function (a) { return a.finished; })).catch(function () {});
  }

  function ready(img) {
    var timeout = new Promise(function (resolve) { setTimeout(resolve, 400); });
    return Promise.race([img.decode().catch(function () {}), timeout]);
  }

  function inView(link) {
    var box = link.getBoundingClientRect();
    return link.parentNode.classList.contains('is-in') && box.bottom > 0 && box.top < window.innerHeight;
  }

  // Run an action now, or once the current animation finishes. Only the
  // latest request is kept, except that a pending close is never dropped.
  function request(action) {
    if (!busy) return action();
    if (pending !== close) pending = action;
  }

  function idle() {
    busy = false;
    var next = pending;
    pending = null;
    if (next) next();
  }

  // OPEN, STEP, CLOSE

  function open(index) {
    var from = links[index].getBoundingClientRect();

    busy = true;
    savedScroll = window.scrollY;
    dialog.showModal();
    document.documentElement.classList.add('has-lightbox');
    show(index);

    var animations = [fade(scrim, 0, 1, { duration: 300 })];
    controls.forEach(function (el) {
      animations.push(fade(el, 0, 1, { duration: 250, delay: 150 }));
    });

    if (reducedMotion.matches) {
      animations.push(fade(image, 0, 1, { duration: 200 }));
    } else {
      animations.push(image.animate(
        { transform: [flip(from, image.getBoundingClientRect()), 'none'] },
        { duration: 550, easing: EASE_OUT }
      ));
    }

    done(animations).then(idle);
  }

  function step(direction) {
    busy = true;

    var distance = reducedMotion.matches ? 0 : SLIDE * direction;
    var out = image.animate(
      { opacity: [1, 0], transform: ['none', 'translateX(' + -distance + 'px)'] },
      { duration: 140, easing: 'ease-in', fill: 'forwards' }
    );

    out.finished
      .then(function () {
        show(current + direction);
        return ready(image);
      })
      .then(function () {
        out.cancel();
        return done([image.animate(
          { opacity: [0, 1], transform: ['translateX(' + distance + 'px)', 'none'] },
          { duration: 280, easing: EASE_OUT }
        )]);
      })
      .then(idle);
  }

  function previous() { step(-1); }
  function next() { step(1); }

  function close() {
    busy = true;

    var link = links[current];
    var animations = [fade(scrim, 1, 0, { duration: 260 })];
    controls.forEach(function (el) {
      animations.push(fade(el, 1, 0, { duration: 150 }));
    });

    if (!reducedMotion.matches && inView(link)) {
      animations.push(image.animate(
        { transform: ['none', flip(link.getBoundingClientRect(), image.getBoundingClientRect())] },
        { duration: 380, easing: EASE_OUT, fill: 'forwards' }
      ));
    } else {
      animations.push(fade(image, 1, 0, { duration: 180 }));
    }

    done(animations).then(function () {
      dialog.close();
      cleanup();
    });
  }

  // Put the page back the way it was. Also runs if the browser closes the
  // dialog on its own.
  function cleanup() {
    var root = document.documentElement;
    if (!root.classList.contains('has-lightbox')) return;

    var link = links[current];

    [scrim, image].concat(controls).forEach(function (el) {
      el.getAnimations().forEach(function (a) { a.cancel(); });
    });
    root.classList.remove('has-lightbox');
    link.parentNode.classList.remove('is-viewing');
    window.scrollTo(0, savedScroll);

    // Hand focus back to the image you ended on, scrolling to it if you
    // stepped somewhere off screen.
    if (!inView(link)) link.parentNode.scrollIntoView({ block: 'center' });
    link.focus({ preventScroll: true });

    busy = false;
    pending = null;
  }

  // EVENTS

  grid.addEventListener('click', function (event) {
    var link = event.target.closest('.mikey-link');
    if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return; // let "open in new tab" clicks through
    }
    event.preventDefault();
    open(links.indexOf(link));
  });

  dialog.querySelector('.lightbox-close').addEventListener('click', function () { request(close); });
  dialog.querySelector('.lightbox-prev').addEventListener('click', function () { request(previous); });
  dialog.querySelector('.lightbox-next').addEventListener('click', function () { request(next); });
  scrim.addEventListener('click', function () { request(close); });

  dialog.addEventListener('cancel', function (event) { // Esc
    event.preventDefault();
    request(close);
  });
  dialog.addEventListener('close', cleanup);

  dialog.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      request(previous);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      request(next);
    }
  });

  // Swipe left or right to step, down to close. Ignored while pinch-zoomed.
  var touch = null;

  dialog.addEventListener('touchstart', function (event) {
    touch = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
  }, { passive: true });

  dialog.addEventListener('touchend', function (event) {
    if (!touch || (window.visualViewport && window.visualViewport.scale > 1.01)) return;

    var dx = event.changedTouches[0].clientX - touch.x;
    var dy = event.changedTouches[0].clientY - touch.y;
    touch = null;

    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
      request(dx < 0 ? next : previous);
    } else if (dy > 80 && dy > Math.abs(dx) * 1.5) {
      request(close);
    }
  });

  window.addEventListener('resize', function () {
    if (dialog.open) fit();
  });
})();

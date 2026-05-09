(function () {
  var strip = document.querySelector('.partners-strip');
  if (!strip) return;

  var setEl = strip.querySelector('.partners-strip-set');
  if (!setEl) return;

  var setWidth = setEl.offsetWidth;
  var position = 0;
  var dragging = false;
  var moved = false;
  var suppressClick = false;
  var startX = 0;
  var startPos = 0;
  var speed = 0.3;

  function getClientX(e) {
    return e.touches ? e.touches[0].clientX : e.clientX;
  }

  function tick() {
    if (!dragging) {
      position -= speed;
      if (position < -setWidth) position += setWidth;
      if (position > 0) position -= setWidth;
    }
    strip.style.transform = 'translateX(' + position + 'px)';
    requestAnimationFrame(tick);
  }

  function onDown(e) {
    dragging = true;
    moved = false;
    startX = getClientX(e);
    startPos = position;
    strip.style.cursor = 'grabbing';
  }

  function onMove(e) {
    if (!dragging) return;
    var x = getClientX(e);
    if (Math.abs(x - startX) > 6) moved = true;
    position = startPos + (x - startX);
  }

  function onUp() {
    if (!dragging) return;
    suppressClick = moved;
    if (suppressClick) {
      window.setTimeout(function () {
        suppressClick = false;
      }, 120);
    }
    dragging = false;
    strip.style.cursor = 'grab';
    while (position < -setWidth) position += setWidth;
    while (position > 0) position -= setWidth;
  }

  function onResize() {
    setWidth = setEl.offsetWidth;
  }

  strip.style.cursor = 'grab';
  strip.style.willChange = 'transform';

  strip.addEventListener('dragstart', function (e) {
    e.preventDefault();
  });

  strip.addEventListener('mousedown', onDown);
  strip.addEventListener('touchstart', onDown, { passive: true });
  strip.addEventListener('click', function (e) {
    if (!suppressClick) return;
    e.preventDefault();
    e.stopPropagation();
  }, true);
  window.addEventListener('mousemove', onMove);
  window.addEventListener('touchmove', onMove, { passive: true });
  window.addEventListener('mouseup', onUp);
  window.addEventListener('touchend', onUp);
  window.addEventListener('resize', onResize);

  if (setWidth > 0) requestAnimationFrame(tick);
  else {
    window.addEventListener('load', function () {
      setWidth = setEl.offsetWidth;
      requestAnimationFrame(tick);
    });
  }
})();

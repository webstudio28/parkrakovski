(function () {
  function attachStripDrag(strip) {
    var dragging = false;
    var moved = false;
    var startX = 0;
    var startScroll = 0;

    function onDown(e) {
      if (e.button !== 0) return;
      dragging = true;
      moved = false;
      startX = e.clientX;
      startScroll = strip.scrollLeft;
    }

    function onMove(e) {
      if (!dragging) return;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 4) {
        if (!moved) {
          moved = true;
          strip.classList.add("is-dragging");
          strip.style.cursor = "grabbing";
        }
        strip.scrollLeft = startScroll - dx;
      }
    }

    function onUp() {
      if (!dragging) return;
      dragging = false;
      strip.classList.remove("is-dragging");
      strip.style.cursor = "grab";
      if (moved) {
        window.__gallerySuppressClick = true;
        window.setTimeout(function () {
          window.__gallerySuppressClick = false;
        }, 200);
      }
      moved = false;
    }

    strip.style.cursor = "grab";
    strip.addEventListener("dragstart", function (e) {
      e.preventDefault();
    });
    strip.addEventListener("mousedown", onDown);
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", onUp);

    strip.addEventListener(
      "click",
      function (e) {
        if (!window.__gallerySuppressClick) return;
        e.preventDefault();
        e.stopPropagation();
      },
      true
    );
  }

  document.querySelectorAll("[data-gallery-wrap]").forEach(function (root) {
    var strip = root.querySelector("[data-gallery-scroller]");
    var prevBtn = root.querySelector("[data-gallery-prev]");
    var nextBtn = root.querySelector("[data-gallery-next]");
    if (!strip) return;

    function stepWidth() {
      var card = strip.querySelector(".gallery-card");
      if (!card) return strip.clientWidth * 0.88;
      var cs = getComputedStyle(strip);
      var gap = parseFloat(cs.columnGap || cs.gap || "0") || 0;
      return card.getBoundingClientRect().width + gap;
    }

    function updateArrows() {
      var max = strip.scrollWidth - strip.clientWidth;
      var atStart = strip.scrollLeft <= 2;
      var atEnd = strip.scrollLeft >= max - 2;
      if (prevBtn) prevBtn.toggleAttribute("disabled", atStart);
      if (nextBtn) nextBtn.toggleAttribute("disabled", atEnd || max <= 0);
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        strip.scrollBy({ left: -stepWidth(), behavior: "smooth" });
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        strip.scrollBy({ left: stepWidth(), behavior: "smooth" });
      });
    }

    strip.addEventListener("scroll", updateArrows, { passive: true });
    window.addEventListener("resize", updateArrows);
    updateArrows();

    attachStripDrag(strip);
  });
})();

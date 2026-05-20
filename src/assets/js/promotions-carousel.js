(function () {
  /**
   * Partners-strip style drag: down on strip, move/end on window,
   * drives horizontal scroll (finite list, no loop).
   */
  function attachStripDrag(strip) {
    var dragging = false;
    var startX = 0;
    var startScroll = 0;

    function onDown(e) {
      if (e.button !== 0) return;
      dragging = true;
      startX = e.clientX;
      startScroll = strip.scrollLeft;
      strip.classList.add("is-dragging");
      strip.style.cursor = "grabbing";
    }

    function onMove(e) {
      if (!dragging) return;
      strip.scrollLeft = startScroll - (e.clientX - startX);
    }

    function onUp() {
      if (!dragging) return;
      dragging = false;
      strip.classList.remove("is-dragging");
      strip.style.cursor = "grab";
    }

    strip.style.cursor = "grab";
    strip.addEventListener("dragstart", function (e) {
      e.preventDefault();
    });
    strip.addEventListener("mousedown", onDown);
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", onUp);
  }

  document.querySelectorAll("[data-promotions-wrap]").forEach(function (root) {
    var strip = root.querySelector("[data-promotions-scroller]");
    var prevBtn = root.querySelector("[data-promotions-prev]");
    var nextBtn = root.querySelector("[data-promotions-next]");
    if (!strip) return;

    function stepWidth() {
      var card = strip.querySelector(".promotion-card");
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

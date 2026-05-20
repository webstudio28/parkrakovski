(function () {
  function enableHorizontalDrag(viewport) {
    if (!viewport) return;
    var isDown = false;
    var startX = 0;
    var startScroll = 0;
    var moved = false;
    var DRAG_THRESHOLD = 4;

    viewport.addEventListener("pointerdown", function (e) {
      if (e.pointerType === "touch") return;
      isDown = true;
      moved = false;
      startX = e.clientX;
      startScroll = viewport.scrollLeft;
      viewport.classList.add("is-dragging");
      try {
        viewport.setPointerCapture(e.pointerId);
      } catch (err) {}
    });

    viewport.addEventListener("pointermove", function (e) {
      if (!isDown) return;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > DRAG_THRESHOLD) moved = true;
      viewport.scrollLeft = startScroll - dx;
    });

    function endDrag(e) {
      if (!isDown) return;
      isDown = false;
      viewport.classList.remove("is-dragging");
      if (e && e.pointerId != null) {
        try {
          viewport.releasePointerCapture(e.pointerId);
        } catch (err) {}
      }
    }

    viewport.addEventListener("pointerup", endDrag);
    viewport.addEventListener("pointercancel", endDrag);
    viewport.addEventListener("pointerleave", endDrag);

    viewport.addEventListener(
      "click",
      function (e) {
        if (moved) {
          e.preventDefault();
          e.stopPropagation();
          moved = false;
        }
      },
      true,
    );

    viewport.addEventListener("dragstart", function (e) {
      e.preventDefault();
    });
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

    if (window.matchMedia("(max-width: 767px)").matches) {
      enableHorizontalDrag(strip);
    }
  });
})();

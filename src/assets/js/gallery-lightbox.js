(function () {
  function initGalleryLightbox() {
    var modal = document.getElementById("gallery-lightbox");
    if (!modal) return;

    var modalImage = modal.querySelector("[data-gallery-lightbox-image]");
    var modalStage = modal.querySelector("[data-gallery-lightbox-stage]");
    var closeBtns = modal.querySelectorAll("[data-gallery-lightbox-close]");
    var prevBtn = modal.querySelector("[data-gallery-lightbox-prev]");
    var nextBtn = modal.querySelector("[data-gallery-lightbox-next]");

    var activeItems = [];
    var activeIndex = 0;

    function renderImage() {
      if (!activeItems.length || !modalImage) return;
      var source = activeItems[activeIndex];
      modalImage.src = source.currentSrc || source.src || "";
      modalImage.alt = source.alt || "";
    }

    function openLightbox(items, index) {
      if (!items.length) return;
      activeItems = items;
      activeIndex = Math.max(0, Math.min(index, items.length - 1));
      renderImage();
      modal.hidden = false;
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
    }

    function closeLightbox() {
      if (modalImage) {
        modalImage.src = "";
        modalImage.alt = "";
      }
      modal.hidden = true;
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      activeItems = [];
    }

    function nextImage() {
      if (!activeItems.length) return;
      activeIndex = (activeIndex + 1) % activeItems.length;
      renderImage();
    }

    function prevImage() {
      if (!activeItems.length) return;
      activeIndex = (activeIndex - 1 + activeItems.length) % activeItems.length;
      renderImage();
    }

    document.addEventListener("click", function (e) {
      if (window.__gallerySuppressClick) return;

      var trigger = e.target.closest("[data-gallery-lightbox-trigger]");
      if (!trigger) return;

      var wrap = trigger.closest("[data-gallery-wrap]");
      if (!wrap) return;

      var items = Array.prototype.slice.call(
        wrap.querySelectorAll("[data-gallery-lightbox-item]")
      );
      if (!items.length) return;

      var idx = Number(trigger.getAttribute("data-gallery-lightbox-index"));
      if (!isFinite(idx)) {
        idx = items.indexOf(e.target.closest("[data-gallery-lightbox-item]"));
      }
      if (idx < 0) idx = 0;

      e.preventDefault();
      openLightbox(items, idx);
    });

    closeBtns.forEach(function (btn) {
      btn.addEventListener("click", closeLightbox);
    });
    if (prevBtn) prevBtn.addEventListener("click", prevImage);
    if (nextBtn) nextBtn.addEventListener("click", nextImage);

    var swipeStartX = null;
    if (modalStage) {
      modalStage.addEventListener(
        "touchstart",
        function (e) {
          if (e.touches && e.touches.length) swipeStartX = e.touches[0].clientX;
        },
        { passive: true }
      );
      modalStage.addEventListener("touchend", function (e) {
        if (swipeStartX === null || !e.changedTouches || !e.changedTouches.length) return;
        var dx = e.changedTouches[0].clientX - swipeStartX;
        swipeStartX = null;
        if (Math.abs(dx) < 40) return;
        if (dx < 0) nextImage();
        else prevImage();
      });
    }

    document.addEventListener("keydown", function (e) {
      if (modal.hidden) return;
      if (e.key === "Escape") closeLightbox();
      if (e.key === "ArrowRight") nextImage();
      if (e.key === "ArrowLeft") prevImage();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGalleryLightbox);
  } else {
    initGalleryLightbox();
  }
})();

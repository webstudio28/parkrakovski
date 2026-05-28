(function () {
  var section = document.getElementById("hero-section");
  var v = document.getElementById("hero-video");
  var loader = document.getElementById("hero-loader");
  if (!section || !v) return;

  var MIN_MS = 1000;
  var MAX_MS = 12000;
  var startedAt = Date.now();
  var minElapsed = false;
  var videoReady = false;
  var dismissed = false;

  function prepare() {
    v.muted = true;
    v.defaultMuted = true;
    v.setAttribute("muted", "");
    try {
      v.playsInline = true;
    } catch (_) {}
    v.setAttribute("playsinline", "");
    v.setAttribute("webkit-playsinline", "");
  }

  function tryPlay() {
    prepare();
    var p = v.play();
    if (p && typeof p.catch === "function") {
      p.catch(function () {});
    }
  }

  function markVideoReady() {
    if (v.readyState >= 3) videoReady = true;
    tryDismiss();
  }

  function tryDismiss() {
    if (dismissed) return;
    if (!minElapsed || !videoReady) return;
    dismissed = true;
    section.classList.add("hero-is-ready");
    if (loader) {
      loader.classList.add("is-hidden");
      loader.setAttribute("aria-hidden", "true");
    }
    tryPlay();
  }

  function onError() {
    videoReady = true;
    tryDismiss();
  }

  v.addEventListener("error", onError);
  v.addEventListener("canplaythrough", markVideoReady, { once: true });
  v.addEventListener("canplay", markVideoReady);
  v.addEventListener("loadeddata", markVideoReady);

  window.setTimeout(function () {
    minElapsed = true;
    tryDismiss();
  }, MIN_MS);

  window.setTimeout(function () {
    videoReady = true;
    tryDismiss();
  }, MAX_MS);

  function init() {
    prepare();
    tryPlay();
    if (v.readyState >= 3) markVideoReady();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  document.addEventListener(
    "pointerdown",
    function () {
      tryPlay();
    },
    true,
  );

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) tryPlay();
  });
})();

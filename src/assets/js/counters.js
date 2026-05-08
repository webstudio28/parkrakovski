(function () {
  var counters = document.querySelectorAll(".js-counter");
  if (!counters.length) return;

  function format(n) {
    return Math.round(n).toLocaleString("bg-BG");
  }

  function animate(el) {
    var target = parseInt(el.getAttribute("data-counter-target"), 10) || 0;
    var duration = 1400;
    var start = performance.now();

    function step(now) {
      var t = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - t, 3);
      el.textContent = format(target * eased);
      if (t < 1) requestAnimationFrame(step);
      else el.textContent = format(target);
    }

    requestAnimationFrame(step);
  }

  if (!("IntersectionObserver" in window)) {
    counters.forEach(animate);
    return;
  }

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animate(entry.target);
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.35 });

  counters.forEach(function (c) {
    io.observe(c);
  });
})();

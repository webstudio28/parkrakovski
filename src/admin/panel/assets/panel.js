(function () {
  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }
  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function getCsrf() {
    var el = qs('input[name="csrf"]');
    return el ? el.value : "";
  }

  function serializeForm(form) {
    if (typeof window.__pkSyncRichEditors === "function") {
      window.__pkSyncRichEditors();
    }
    var fields = qsa("input, textarea, select", form).filter(function (el) {
      if (!el.name) return false;
      var t = (el.type || "").toLowerCase();
      if (t === "submit" || t === "button" || t === "file") return false;
      return true;
    });
    fields.sort(function (a, b) {
      if (a.name !== b.name) return a.name < b.name ? -1 : 1;
      return 0;
    });
    return fields
      .map(function (el) {
        var t = (el.type || "").toLowerCase();
        if (t === "checkbox" || t === "radio") {
          return el.name + "=" + (el.checked ? "1" : "0");
        }
        return el.name + "=" + el.value;
      })
      .join("\n");
  }

  function updateDirtyState(form) {
    if (!form || form._pkDirtyBusy) return;
    var saveBtn = qs("[data-pk-save]", form);
    if (!saveBtn || !form._pkSnapshot) return;
    form._pkDirtyBusy = true;
    try {
      var dirty = serializeForm(form) !== form._pkSnapshot;
      saveBtn.disabled = !dirty;
    } finally {
      form._pkDirtyBusy = false;
    }
  }

  window.__pkUpdateDirtyState = updateDirtyState;

  function initDirtyForms() {
    qsa("form[data-pk-dirty-form]").forEach(function (form) {
      form._pkSnapshot = serializeForm(form);
      updateDirtyState(form);

      form.addEventListener("input", function (e) {
        if (e.target.matches("[data-pk-rich-source]")) return;
        updateDirtyState(form);
      });
      form.addEventListener("change", function () {
        updateDirtyState(form);
      });
    });
  }

  function markDirtyFromEvent(e) {
    var form = e.target && e.target.closest ? e.target.closest("form[data-pk-dirty-form]") : null;
    if (form) updateDirtyState(form);
  }

  function uploadFile(file, prefix, onDone) {
    var fd = new FormData();
    fd.append("file", file);
    fd.append("csrf", getCsrf());
    fd.append("prefix", prefix || "upload");

    fetch("./upload.php", { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        onDone(data);
      })
      .catch(function () {
        onDone({ ok: false, error: "Грешка при качване." });
      });
  }

  document.addEventListener("change", function (e) {
    var input = e.target;
    if (!input.matches("[data-pk-upload]")) return;

    var file = input.files && input.files[0];
    if (!file) return;

    var wrap = input.closest("[data-pk-media]");
    if (!wrap) return;

    var pathInput = qs("[data-pk-media-path]", wrap);
    var preview = qs("[data-pk-media-preview]", wrap);
    var prefix = input.getAttribute("data-pk-upload") || "upload";
    var status = qs("[data-pk-upload-status]", wrap);

    if (status) status.textContent = "Качване…";

    uploadFile(file, prefix, function (res) {
      if (!res.ok) {
        if (status) status.textContent = res.error || "Грешка.";
        return;
      }
      if (pathInput) pathInput.value = res.path;
      if (preview) {
        preview.src = res.path;
        preview.hidden = false;
      }
      if (status) status.textContent = res.path;
      input.value = "";
      markDirtyFromEvent({ target: pathInput || wrap });
    });
  });

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-pk-remove-repeater]");
    if (!btn) return;
    var item = btn.closest("[data-pk-repeater-item]");
    if (item) {
      item.remove();
      markDirtyFromEvent(e);
    }
  });

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-pk-add-repeater]");
    if (!btn) return;

    var tpl = qs(btn.getAttribute("data-target-template"));
    var list = qs(btn.getAttribute("data-target-list"));
    if (!tpl || !list) return;

    var html = tpl.innerHTML.replace(/__INDEX__/g, String(Date.now()));
    var wrap = document.createElement("div");
    wrap.innerHTML = html.trim();
    var node = wrap.firstElementChild;
    if (node) {
      list.appendChild(node);
      markDirtyFromEvent(e);
    }
  });

  var slugSource = qs("[data-pk-slug-source]");
  var slugTarget = qs("[data-pk-slug-target]");
  if (slugSource && slugTarget && !slugTarget.value) {
    slugSource.addEventListener("blur", function () {
      if (slugTarget.value) return;
      var v = slugSource.value
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
      slugTarget.value = v;
      markDirtyFromEvent({ target: slugTarget });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDirtyForms);
  } else {
    initDirtyForms();
  }
})();

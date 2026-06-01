(function () {
  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }
  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function markFormDirty(wrap) {
    var form = wrap.closest("form[data-pk-dirty-form]");
    if (form && typeof window.__pkUpdateDirtyState === "function") {
      window.__pkUpdateDirtyState(form);
    }
  }

  function richPlainLength(html) {
    var div = document.createElement("div");
    div.innerHTML = html || "";
    var text = (div.textContent || div.innerText || "").replace(/\s+/g, " ").trim();
    return text.length;
  }

  function updateRichCount(wrap) {
    var max = parseInt(wrap.getAttribute("data-pk-rich-max") || "0", 10);
    var counter = qs("[data-pk-rich-count]", wrap);
    if (!max || !counter) return;
    var editor = qs("[data-pk-rich-editor]", wrap);
    var len = editor ? richPlainLength(editor.innerHTML) : 0;
    counter.textContent = len + " / " + max;
    counter.classList.toggle("pk-rich__count--limit", len >= max);
  }

  function enforceRichMax(wrap) {
    var max = parseInt(wrap.getAttribute("data-pk-rich-max") || "0", 10);
    var editor = qs("[data-pk-rich-editor]", wrap);
    var source = qs("[data-pk-rich-source]", wrap);
    if (!max || !editor) return;
    while (richPlainLength(editor.innerHTML) > max) {
      var text = (editor.textContent || "").replace(/\s+/g, " ");
      editor.textContent = text.slice(0, max);
    }
    if (source) source.value = editor.innerHTML.trim();
    updateRichCount(wrap);
  }

  function syncRichField(wrap, silent) {
    var editor = qs("[data-pk-rich-editor]", wrap);
    var source = qs("[data-pk-rich-source]", wrap);
    if (!editor || !source) return;
    if (!silent) {
      enforceRichMax(wrap);
    }
    var next = editor.innerHTML.trim();
    if (source.value === next) {
      if (!silent) updateRichCount(wrap);
      return;
    }
    source.value = next;
    if (!silent) updateRichCount(wrap);
    if (!silent) {
      markFormDirty(wrap);
    }
  }

  function syncAllRich(silent) {
    qsa("[data-pk-rich]").forEach(function (wrap) {
      syncRichField(wrap, !!silent);
    });
  }

  function closeIconPickers(keepWrap) {
    qsa("[data-pk-icon-picker]").forEach(function (picker) {
      var owner = picker.closest("[data-pk-rich]");
      if (keepWrap && owner === keepWrap) return;
      picker.hidden = true;
      if (owner) {
        var btn = qs("[data-pk-icon-toggle]", owner);
        if (btn) btn.setAttribute("aria-expanded", "false");
      }
    });
  }

  function insertIcon(wrap, className) {
    var editor = qs("[data-pk-rich-editor]", wrap);
    if (!editor) return;
    editor.focus();
    var icon = '<i class="' + className + '" aria-hidden="true"></i>&nbsp;';
    if (document.queryCommandSupported && document.queryCommandSupported("insertHTML")) {
      document.execCommand("insertHTML", false, icon);
    } else {
      editor.insertAdjacentHTML("beforeend", icon);
    }
    syncRichField(wrap, false);
    closeIconPickers(null);
  }

  function bootstrapRichWrap(wrap) {
    var editor = qs("[data-pk-rich-editor]", wrap);
    var source = qs("[data-pk-rich-source]", wrap);
    if (!editor || !source || wrap.getAttribute("data-pk-rich-ready") === "1") return;
    if (source.value && !editor.innerHTML.trim()) {
      editor.innerHTML = source.value;
    }
    syncRichField(wrap, true);
    updateRichCount(wrap);
    wrap.setAttribute("data-pk-rich-ready", "1");
  }

  function initRichEditors() {
    qsa("[data-pk-rich]").forEach(bootstrapRichWrap);

    document.addEventListener(
      "input",
      function (e) {
        if (!e.target.matches("[data-pk-rich-editor]")) return;
        var wrap = e.target.closest("[data-pk-rich]");
        if (wrap) syncRichField(wrap, false);
      },
      true,
    );

    document.addEventListener(
      "blur",
      function (e) {
        if (!e.target.matches("[data-pk-rich-editor]")) return;
        var wrap = e.target.closest("[data-pk-rich]");
        if (wrap) syncRichField(wrap, false);
      },
      true,
    );

    document.addEventListener("mousedown", function (e) {
      if (e.target.closest("[data-pk-icon-picker]") || e.target.closest("[data-pk-icon-toggle]")) return;
      if (e.target.closest("[data-pk-cmd]")) return;
      if (!e.target.closest("[data-pk-rich]")) {
        closeIconPickers(null);
      }
    });

    document.addEventListener("click", function (e) {
      var cmdBtn = e.target.closest("[data-pk-cmd]");
      if (cmdBtn) {
        e.preventDefault();
        var wrap = cmdBtn.closest("[data-pk-rich]");
        var editor = wrap ? qs("[data-pk-rich-editor]", wrap) : null;
        if (!editor) return;
        editor.focus();
        var cmd = cmdBtn.getAttribute("data-pk-cmd");
        if (cmd === "bold") document.execCommand("bold", false, null);
        else if (cmd === "italic") document.execCommand("italic", false, null);
        else if (cmd === "underline") document.execCommand("underline", false, null);
        syncRichField(wrap, false);
        return;
      }

      var iconToggle = e.target.closest("[data-pk-icon-toggle]");
      if (iconToggle) {
        e.preventDefault();
        var richWrap = iconToggle.closest("[data-pk-rich]");
        var picker = richWrap ? qs("[data-pk-icon-picker]", richWrap) : null;
        if (!picker) return;
        var open = picker.hidden;
        closeIconPickers(richWrap);
        picker.hidden = !open;
        iconToggle.setAttribute("aria-expanded", open ? "true" : "false");
        return;
      }

      var iconBtn = e.target.closest("[data-pk-icon]");
      if (iconBtn) {
        e.preventDefault();
        var rich = iconBtn.closest("[data-pk-rich]");
        if (rich) insertIcon(rich, iconBtn.getAttribute("data-pk-icon"));
      }
    });

    if (typeof MutationObserver !== "undefined") {
      qsa("form[data-pk-dirty-form]").forEach(function (form) {
        var mo = new MutationObserver(function (mutations) {
          var needsBootstrap = false;
          for (var i = 0; i < mutations.length; i++) {
            var m = mutations[i];
            if (m.type !== "childList" || !m.addedNodes.length) continue;
            for (var j = 0; j < m.addedNodes.length; j++) {
              var node = m.addedNodes[j];
              if (node.nodeType !== 1) continue;
              if (
                (node.matches && node.matches("[data-pk-rich]")) ||
                (node.querySelector && node.querySelector("[data-pk-rich]"))
              ) {
                needsBootstrap = true;
                break;
              }
            }
            if (needsBootstrap) break;
          }
          if (!needsBootstrap) return;
          qsa("[data-pk-rich]", form).forEach(function (wrap) {
            if (wrap.getAttribute("data-pk-rich-ready") !== "1") {
              bootstrapRichWrap(wrap);
            }
          });
        });
        mo.observe(form, { childList: true, subtree: true });
      });
    }

    document.addEventListener(
      "submit",
      function (e) {
        if (e.target && e.target.matches("form[data-pk-dirty-form]")) {
          qsa("[data-pk-rich]", e.target).forEach(function (wrap) {
            enforceRichMax(wrap);
            syncRichField(wrap, true);
          });
        }
      },
      true,
    );
  }

  window.__pkSyncRichEditors = function () {
    syncAllRich(true);
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRichEditors);
  } else {
    initRichEditors();
  }
})();

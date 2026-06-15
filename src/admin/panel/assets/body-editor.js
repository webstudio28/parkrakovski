(function () {
  "use strict";

  /* ── Toolbar tooltips (Bulgarian labels) ── */
  var TOOLBAR_LABELS = {
    "ql-bold":       "Удебелен (Ctrl+B)",
    "ql-italic":     "Курсив (Ctrl+I)",
    "ql-underline":  "Подчертан (Ctrl+U)",
    "ql-strike":     "Зачертан",
    "ql-blockquote": "Цитат",
    "ql-link":       "Добави линк",
    "ql-image":      "Вмъкни снимка в текста",
    "ql-header":     "Тип блок (параграф / заглавие)",
    "ql-list-ordered": "Номериран списък",
    "ql-list-bullet":  "Точков списък",
  };

  function addToolbarTooltips(toolbarEl) {
    if (!toolbarEl) return;
    ["ql-bold", "ql-italic", "ql-underline", "ql-strike",
      "ql-blockquote", "ql-link", "ql-image"].forEach(function (cls) {
      var btn = toolbarEl.querySelector("button." + cls);
      if (btn) btn.setAttribute("title", TOOLBAR_LABELS[cls] || "");
    });
    toolbarEl.querySelectorAll("button.ql-list").forEach(function (btn) {
      var val = btn.getAttribute("value");
      var key = "ql-list-" + val;
      if (TOOLBAR_LABELS[key]) btn.setAttribute("title", TOOLBAR_LABELS[key]);
    });
    var picker = toolbarEl.querySelector(".ql-header");
    if (picker) picker.setAttribute("title", TOOLBAR_LABELS["ql-header"]);
  }

  /* ── Image orientation detection ── */
  function classifyImage(img) {
    function apply() {
      if (img.naturalWidth > 0 && img.naturalHeight > 0) {
        img.dataset.orientation = img.naturalHeight > img.naturalWidth ? "portrait" : "landscape";
      }
    }
    if (img.complete && img.naturalWidth > 0) {
      apply();
    } else {
      img.addEventListener("load", apply, { once: true });
    }
  }

  function classifyAllImages(root) {
    root.querySelectorAll("img").forEach(classifyImage);
  }

  /* ── Floating delete button (lives OUTSIDE Quill DOM to avoid breaking it) ── */
  function initImageDeleteOverlay(editorShell, editorRoot, quillInstance) {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.setAttribute("aria-label", "Изтрий снимка");
    btn.title = "Изтрий снимка";
    btn.style.cssText = [
      "position:absolute",
      "z-index:20",
      "width:1.6rem",
      "height:1.6rem",
      "padding:0",
      "border:none",
      "border-radius:50%",
      "background:rgba(15,23,42,0.75)",
      "color:#fff",
      "font-size:1.1rem",
      "line-height:1",
      "cursor:pointer",
      "display:none",
      "align-items:center",
      "justify-content:center",
      "box-shadow:0 2px 8px rgba(0,0,0,0.45)",
      "transition:background 0.15s",
      "pointer-events:auto",
    ].join(";");
    btn.innerHTML = "&times;";
    editorShell.style.position = "relative";
    editorShell.appendChild(btn);

    var activeImg = null;
    var hideTimer = null;

    function showBtn(img) {
      if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
      activeImg = img;
      var shellRect = editorShell.getBoundingClientRect();
      var imgRect   = img.getBoundingClientRect();
      btn.style.top  = (imgRect.top  - shellRect.top  + 6) + "px";
      btn.style.right = (shellRect.right - imgRect.right + 6) + "px";
      btn.style.left = "auto";
      btn.style.display = "inline-flex";
    }

    function scheduleHide() {
      hideTimer = setTimeout(function () {
        btn.style.display = "none";
        activeImg = null;
      }, 120);
    }

    editorRoot.addEventListener("mouseover", function (e) {
      var img = e.target.closest ? e.target.closest("img") : (e.target.tagName === "IMG" ? e.target : null);
      if (img && editorRoot.contains(img)) showBtn(img);
    });
    editorRoot.addEventListener("mouseout", function (e) {
      if (e.target.tagName === "IMG") scheduleHide();
    });
    btn.addEventListener("mouseenter", function () {
      if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
      btn.style.background = "rgba(220,38,38,0.9)";
    });
    btn.addEventListener("mouseleave", function () {
      btn.style.background = "rgba(15,23,42,0.75)";
      scheduleHide();
    });

    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!activeImg) return;
      /* Find the Quill blot index for the image and delete it */
      var blot = window.Quill.find(activeImg);
      if (blot) {
        var index = quillInstance.getIndex(blot);
        quillInstance.deleteText(index, 1, window.Quill.sources.USER);
      } else {
        /* Fallback: remove directly and re-sync */
        if (activeImg.parentNode) activeImg.parentNode.removeChild(activeImg);
        quillInstance.update();
        quillInstance.emit("text-change");
      }
      btn.style.display = "none";
      activeImg = null;
    });
  }

  /* ── Upload helper (self-contained) ── */
  function doUpload(file, prefix, callback) {
    var rules = window.__pkUploadRules || {};
    var maxBytes = rules.maxBytes || 800 * 1024;
    var maxKb    = rules.maxKb || 800;
    var allowedExts  = rules.extensions || ["jpg", "jpeg", "png", "webp"];
    var allowedMimes = rules.mimes || ["image/jpeg", "image/png", "image/x-png", "image/webp"];

    if (!file || !file.size) { callback({ ok: false, error: "Липсва файл." }); return; }

    var ext = (file.name || "").split(".").pop().toLowerCase();
    if (ext === "jpeg") ext = "jpg";
    if (allowedExts.indexOf(ext) === -1) {
      callback({ ok: false, error: rules.formatError || "Невалиден формат. Позволени са JPG, PNG и WebP." });
      return;
    }
    if (file.type && allowedMimes.indexOf(file.type) === -1) {
      callback({ ok: false, error: rules.formatError || "Невалиден формат. Позволени са JPG, PNG и WebP." });
      return;
    }
    if (file.size > maxBytes) {
      callback({ ok: false, error: "Снимката е твърде голяма (" + Math.ceil(file.size / 1024) + " KB). Максимумът е " + maxKb + " KB." });
      return;
    }

    var csrf = "";
    var csrfEl = document.querySelector('input[name="csrf"]');
    if (csrfEl) csrf = csrfEl.value;

    var fd = new FormData();
    fd.append("file", file);
    fd.append("csrf", csrf);
    fd.append("prefix", prefix || "news");

    fetch("./upload.php", { method: "POST", body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) { callback(data); })
      .catch(function () { callback({ ok: false, error: "Грешка при качване. Опитайте отново." }); });
  }

  /* ── Initialize one body editor ── */
  function initBodyEditor(wrap) {
    if (wrap.getAttribute("data-pk-body-ready") === "1") return;
    if (!window.Quill) return;
    wrap.setAttribute("data-pk-body-ready", "1");

    var sourceId = wrap.getAttribute("data-pk-body-source");
    var modalId  = wrap.getAttribute("data-pk-body-modal");
    var field    = wrap.closest(".pk-field") || wrap.parentElement;
    var countEl  = field ? field.querySelector("[data-pk-body-count]") : null;

    var sourceEl = sourceId ? document.getElementById(sourceId) : null;
    var modalEl  = modalId  ? document.getElementById(modalId)  : null;
    var editorDiv = wrap.querySelector("[data-pk-body-editor-div]");

    if (!editorDiv || !sourceEl) return;

    var savedRange       = null;
    var pendingOrientation = null;
    var previewObjectUrl = null;

    /* ── Quill init ── */
    var quill = new window.Quill(editorDiv, {
      theme: "snow",
      placeholder: "Напишете съдържание на новината...",
      modules: {
        toolbar: {
          container: [
            [{ header: [2, 3, 4, false] }],
            ["bold", "italic", "underline", "strike"],
            [{ list: "ordered" }, { list: "bullet" }],
            ["blockquote", "link"],
            ["image"],
          ],
          handlers: {
            image: function () {
              savedRange = quill.getSelection();
              if (modalEl) {
                modalEl.hidden = false;
                var fi = modalEl.querySelector("[data-pk-body-img-file]");
                var st = modalEl.querySelector("[data-pk-body-img-status]");
                var pr = modalEl.querySelector("[data-pk-body-img-preview]");
                if (fi) fi.value = "";
                if (st) { st.textContent = ""; st.classList.remove("pk-body-img-modal__status--err"); }
                if (pr) { pr.hidden = true; pr.src = ""; }
                pendingOrientation = null;
                revokePreview();
              }
            },
          },
        },
      },
    });

    /* Add Bulgarian tooltips to the Quill toolbar */
    var toolbarEl = wrap.querySelector(".ql-toolbar");
    addToolbarTooltips(toolbarEl);

    /* ── Load initial content ── */
    var initialHtml = sourceEl.value.trim();
    if (initialHtml) {
      quill.clipboard.dangerouslyPasteHTML(initialHtml);
    }

    /* Classify images after initial load */
    var editorRoot = wrap.querySelector(".ql-editor");
    var editorShell = wrap.querySelector(".pk-body-editor-shell");
    setTimeout(function () {
      classifyAllImages(wrap);
    }, 0);

    /* Floating delete overlay (never touches Quill DOM) */
    if (editorShell && editorRoot) {
      initImageDeleteOverlay(editorShell, editorRoot, quill);
    }

    /* Watch for new images — classify orientation only */
    if (typeof MutationObserver !== "undefined" && editorRoot) {
      var imgObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            if (node.tagName === "IMG") classifyImage(node);
            else node.querySelectorAll("img").forEach(classifyImage);
          });
        });
      });
      imgObserver.observe(editorRoot, { childList: true, subtree: true });
    }

    /* ── Sync helpers ── */
    function syncToSource() {
      var html = quill.root.innerHTML;
      if (html === "<p><br></p>" || html === "<p></p>") html = "";
      sourceEl.value = html;
    }

    function updateWordCount() {
      if (!countEl) return;
      var text  = quill.getText().trim();
      var words = text ? text.split(/\s+/).filter(Boolean).length : 0;
      countEl.textContent = words + (words === 1 ? " дума" : " думи");
    }

    quill.on("text-change", function () {
      syncToSource();
      updateWordCount();
      var form = wrap.closest("form[data-pk-dirty-form]");
      if (form && typeof window.__pkUpdateDirtyState === "function") {
        window.__pkUpdateDirtyState(form);
      }
    });

    var form = sourceEl.closest("form");
    if (form) {
      form.addEventListener("submit", function () { syncToSource(); }, true);
    }

    updateWordCount();

    /* ── Image modal ── */
    function revokePreview() {
      if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
      }
    }

    function closeModal() {
      if (!modalEl) return;
      modalEl.hidden = true;
      var fi  = modalEl.querySelector("[data-pk-body-img-file]");
      var st  = modalEl.querySelector("[data-pk-body-img-status]");
      var btn = modalEl.querySelector("[data-pk-body-img-btn]");
      var lbl = modalEl.querySelector("[data-pk-body-img-btn-label]");
      var pr  = modalEl.querySelector("[data-pk-body-img-preview]");
      if (fi)  fi.value = "";
      if (st)  { st.textContent = ""; st.classList.remove("pk-body-img-modal__status--err"); }
      if (btn) btn.disabled = false;
      if (lbl) lbl.textContent = "Избери и качи снимка";
      if (pr)  { pr.hidden = true; pr.src = ""; }
      revokePreview();
    }

    if (modalEl) {
      /* Close on backdrop click or × button */
      modalEl.addEventListener("click", function (e) {
        if (e.target.closest("[data-pk-body-modal-close]")) closeModal();
      });
      document.addEventListener("keydown", function (e) {
        if (!modalEl.hidden && e.key === "Escape") closeModal();
      });

      var fileInput = modalEl.querySelector("[data-pk-body-img-file]");
      if (fileInput) {
        fileInput.addEventListener("change", function () {
          var file = fileInput.files && fileInput.files[0];
          if (!file) return;
          fileInput.value = "";

          var statusEl = modalEl.querySelector("[data-pk-body-img-status]");
          var btnEl    = modalEl.querySelector("[data-pk-body-img-btn]");
          var btnLbl   = modalEl.querySelector("[data-pk-body-img-btn-label]");
          var previewEl = modalEl.querySelector("[data-pk-body-img-preview]");

          /* Show local preview in the modal while uploading */
          revokePreview();
          previewObjectUrl = URL.createObjectURL(file);
          if (previewEl) {
            previewEl.src = previewObjectUrl;
            previewEl.hidden = false;
            previewEl.onload = function () {
              pendingOrientation = previewEl.naturalHeight > previewEl.naturalWidth
                ? "portrait" : "landscape";
            };
          }

          if (statusEl) { statusEl.textContent = "Качване…"; statusEl.classList.remove("pk-body-img-modal__status--err"); }
          if (btnEl)    btnEl.disabled = true;
          if (btnLbl)   btnLbl.textContent = "Качване…";

          doUpload(file, "news", function (res) {
            if (btnEl)  btnEl.disabled = false;
            if (btnLbl) btnLbl.textContent = "Избери и качи снимка";

            if (!res.ok) {
              if (statusEl) {
                statusEl.textContent = res.error || "Грешка при качване.";
                statusEl.classList.add("pk-body-img-modal__status--err");
              }
              return;
            }

            if (statusEl) statusEl.textContent = "";

            /* Insert real server path — no blob URLs in Quill */
            var insertIdx = savedRange ? savedRange.index : quill.getLength();
            quill.insertEmbed(insertIdx, "image", res.path, window.Quill.sources.USER);
            quill.setSelection(insertIdx + 1, 0, window.Quill.sources.SILENT);

            /* Apply orientation to freshly inserted image */
            var orientation = pendingOrientation || "landscape";
            setTimeout(function () {
              wrap.querySelectorAll(".ql-editor img").forEach(function (img) {
                var src = img.getAttribute("src") || "";
                if (!img.dataset.orientation && (src === res.path || src.endsWith(res.path))) {
                  img.dataset.orientation = orientation;
                }
              });
              pendingOrientation = null;
            }, 50);

            closeModal();
          });
        });
      }
    }

    /* Expose quill instance on the wrap element for external sync */
    wrap._pkQuill = quill;
    return quill;
  }

  /* ── Boot all body editors on page ── */
  function initAll() {
    document.querySelectorAll("[data-pk-body-wrap]").forEach(initBodyEditor);
  }

  /* ── Sync all body editor textareas (called by preview button + dirty tracking) ── */
  function syncAllBodyEditors() {
    document.querySelectorAll("[data-pk-body-wrap][data-pk-body-ready='1']").forEach(function (wrap) {
      var q = wrap._pkQuill;
      var sourceId = wrap.getAttribute("data-pk-body-source");
      var sourceEl = sourceId ? document.getElementById(sourceId) : null;
      if (!q || !sourceEl) return;
      var html = q.root.innerHTML;
      if (html === "<p><br></p>" || html === "<p></p>") html = "";
      sourceEl.value = html;
    });
  }
  window.__pkSyncBodyEditors = syncAllBodyEditors;

  /* ── Patch global sync so dirty-form tracking includes body editors ── */
  var _origSync = window.__pkSyncRichEditors;
  window.__pkSyncRichEditors = function () {
    if (typeof _origSync === "function") _origSync();
    syncAllBodyEditors();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();

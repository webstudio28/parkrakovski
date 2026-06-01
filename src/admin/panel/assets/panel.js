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

  function domSort(a, b) {
    if (a === b) return 0;
    if (!a.compareDocumentPosition || !a.isConnected || !b.isConnected) return 0;
    var pos = a.compareDocumentPosition(b);
    if (pos & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
    if (pos & Node.DOCUMENT_POSITION_PRECEDING) return 1;
    return 0;
  }

  function serializeForm(form) {
    if (!form._pkSerializing) {
      form._pkSerializing = true;
      try {
        if (typeof window.__pkSyncRichEditors === "function") {
          window.__pkSyncRichEditors();
        }
      } finally {
        form._pkSerializing = false;
      }
    }
    var fields = qsa("input, textarea, select", form).filter(function (el) {
      if (!el.name) return false;
      if (el.closest("template")) return false;
      var t = (el.type || "").toLowerCase();
      if (t === "submit" || t === "button" || t === "file") return false;
      return true;
    });
    fields.sort(function (a, b) {
      if (a.name !== b.name) return a.name < b.name ? -1 : 1;
      return domSort(a, b);
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

      form.addEventListener("submit", function () {
        qsa("input[type=time][disabled]", form).forEach(function (el) {
          el.disabled = false;
        });
      });

      form.addEventListener("input", function (e) {
        if (e.target.matches("[data-pk-rich-source]")) return;
        updateDirtyState(form);
      });
      form.addEventListener("change", function () {
        updateDirtyState(form);
      });
    });
  }

  function notifyFormDirty(fromEl) {
    var el = fromEl;
    if (fromEl && fromEl.target) {
      el = fromEl.target.nodeType === 1 ? fromEl.target : fromEl.target.parentElement;
    }
    if (!el) return;
    var form =
      el.nodeName === "FORM" && el.matches("[data-pk-dirty-form]")
        ? el
        : el.closest
          ? el.closest("form[data-pk-dirty-form]")
          : null;
    if (form) updateDirtyState(form);
  }

  function markDirtyFromEvent(e) {
    notifyFormDirty(e);
  }

  function updateGalleryEmpty(gallery) {
    var grid = qs("[data-pk-gallery-grid]", gallery);
    var empty = qs("[data-pk-gallery-empty]", gallery);
    if (!empty) return;
    var count = grid ? qsa("[data-pk-gallery-item]", grid).length : 0;
    empty.hidden = count > 0;
  }

  function galleryTemplate(gallery) {
    return (
      qs("[data-pk-gallery-item-template]", gallery) ||
      (gallery.parentElement ? qs("[data-pk-gallery-item-template]", gallery.parentElement) : null)
    );
  }

  function createGalleryItem(gallery, path, previewUrl) {
    var tpl = galleryTemplate(gallery);
    var grid = qs("[data-pk-gallery-grid]", gallery);
    if (!tpl || !grid) return null;

    var wrap = document.createElement("div");
    wrap.innerHTML = tpl.innerHTML.trim();
    var item = wrap.firstElementChild;
    if (!item) return null;

    var img = qs(".pk-gallery__thumb", item);
    var input = qs('input[name="gallery_image[]"]', item);
    if (img) {
      var src = previewUrl || path;
      if (src) {
        img.src = src;
        img.hidden = false;
      } else {
        img.removeAttribute("src");
        img.hidden = true;
      }
    }
    if (input) input.value = path;
    grid.appendChild(item);
    updateGalleryEmpty(gallery);
    return item;
  }

  function revokeGalleryPreview(item) {
    if (!item || !item._pkPreviewUrl) return;
    URL.revokeObjectURL(item._pkPreviewUrl);
    item._pkPreviewUrl = null;
  }

  function applyGalleryUploadPath(item, path) {
    if (!item) return;
    var img = qs(".pk-gallery__thumb", item);
    var input = qs('input[name="gallery_image[]"]', item);
    if (input) input.value = path;
    if (!img) {
      item.classList.remove("pk-gallery__item--loading");
      return;
    }

    var probe = new Image();
    probe.onload = function () {
      revokeGalleryPreview(item);
      img.src = path;
      img.hidden = false;
      item.classList.remove("pk-gallery__item--loading");
    };
    probe.onerror = function () {
      if (item._pkPreviewUrl) {
        img.src = item._pkPreviewUrl;
        img.hidden = false;
      }
      item.classList.remove("pk-gallery__item--loading");
    };
    probe.src = path;
  }

  function applyMediaUploadPath(wrap, path) {
    var pathInput = qs("[data-pk-media-path]", wrap);
    var preview = qs("[data-pk-media-preview]", wrap);
    var btnText = qs("[data-pk-upload-btn-text]", wrap);
    var status = qs("[data-pk-upload-status]", wrap);
    if (pathInput) pathInput.value = path;
    if (btnText) btnText.textContent = "Смени снимка";
    if (status) status.hidden = true;
    if (!preview) return;

    var probe = new Image();
    probe.onload = function () {
      revokeGalleryPreview(wrap);
      preview.src = path;
      preview.hidden = false;
    };
    probe.onerror = function () {
      if (wrap._pkPreviewUrl) {
        preview.src = wrap._pkPreviewUrl;
        preview.hidden = false;
      }
    };
    probe.src = path;
  }

  function scheduleDirtyCheck(form) {
    if (!form) return;
    if (form._pkDirtyTimer) {
      clearTimeout(form._pkDirtyTimer);
    }
    form._pkDirtyTimer = setTimeout(function () {
      form._pkDirtyTimer = null;
      updateDirtyState(form);
    }, 0);
  }

  function watchListDirty(listEl) {
    if (!listEl || typeof MutationObserver === "undefined") return;
    var form = listEl.closest ? listEl.closest("form[data-pk-dirty-form]") : null;
    if (!form) return;
    var mo = new MutationObserver(function () {
      updateRepeaterCap(listEl);
      scheduleDirtyCheck(form);
    });
    mo.observe(listEl, { childList: true });
  }

  function watchGalleryDirty(gallery) {
    var grid = qs("[data-pk-gallery-grid]", gallery);
    if (!grid) return;
    watchListDirty(grid);
  }

  function repeaterListCount(list) {
    return qsa("[data-pk-repeater-item]", list).length;
  }

  function updateRepeaterCap(list) {
    if (!list) return;
    var max = parseInt(list.getAttribute("data-pk-max") || "0", 10);
    if (!max) return;
    var listId = list.id;
    var btn = listId
      ? qs('[data-pk-add-repeater][data-target-list="#' + listId + '"]')
      : null;
    if (!btn) return;
    var atCap = repeaterListCount(list) >= max;
    btn.disabled = atCap;
  }

  function initRepeaterLists() {
    qsa("[data-pk-repeater-list]").forEach(function (list) {
      watchListDirty(list);
      updateRepeaterCap(list);
    });
  }

  function initShopGalleries() {
    qsa("[data-pk-gallery]").forEach(function (gallery) {
      var addBtn = qs("[data-pk-gallery-add]", gallery);
      var fileInput = qs("[data-pk-gallery-input]", gallery);
      if (!addBtn || !fileInput) return;

      watchGalleryDirty(gallery);

      addBtn.addEventListener("click", function () {
        fileInput.click();
      });

      fileInput.addEventListener("change", function () {
        var files = fileInput.files ? Array.prototype.slice.call(fileInput.files) : [];
        fileInput.value = "";
        if (!files.length) return;

        var prefix = fileInput.getAttribute("data-pk-upload-prefix") || "shop-gallery";

        files.forEach(function (file) {
          var previewUrl = URL.createObjectURL(file);
          var item = createGalleryItem(gallery, "", previewUrl);
          if (item) {
            item._pkPreviewUrl = previewUrl;
            item.classList.add("pk-gallery__item--loading");
          } else {
            URL.revokeObjectURL(previewUrl);
          }

          uploadFile(file, prefix, function (res) {
            if (!res.ok) {
              if (item) {
                revokeGalleryPreview(item);
                item.remove();
              }
              updateGalleryEmpty(gallery);
              return;
            }
            if (!item) {
              item = createGalleryItem(gallery, res.path);
              if (item) item.classList.remove("pk-gallery__item--loading");
            } else {
              applyGalleryUploadPath(item, res.path);
            }
            notifyFormDirty(gallery);
          });
        });
      });

      gallery.addEventListener("click", function (e) {
        var removeBtn = e.target.closest("[data-pk-gallery-remove]");
        if (!removeBtn) return;
        e.preventDefault();
        var item = removeBtn.closest("[data-pk-gallery-item]");
        if (item) {
          revokeGalleryPreview(item);
          item.remove();
          updateGalleryEmpty(gallery);
          notifyFormDirty(gallery);
        }
      });

      updateGalleryEmpty(gallery);
    });
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

    var prefix = input.getAttribute("data-pk-upload") || "upload";
    var status = qs("[data-pk-upload-status]", wrap);
    var preview = qs("[data-pk-media-preview]", wrap);
    var previewUrl = URL.createObjectURL(file);
    wrap._pkPreviewUrl = previewUrl;
    if (preview) {
      preview.src = previewUrl;
      preview.hidden = false;
    }

    if (status) {
      status.hidden = false;
      status.textContent = "Качване…";
    }

    uploadFile(file, prefix, function (res) {
      input.value = "";
      if (!res.ok) {
        revokeGalleryPreview(wrap);
        if (status) {
          status.hidden = false;
          status.textContent = res.error || "Грешка.";
        }
        return;
      }
      if (status) status.hidden = true;
      applyMediaUploadPath(wrap, res.path);
      markDirtyFromEvent({ target: wrap });
    });
  });

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-pk-remove-repeater]");
    if (!btn) return;
    e.preventDefault();
    var item = btn.closest("[data-pk-repeater-item]");
    if (!item) return;
    var form = btn.closest("form[data-pk-dirty-form]");
    var list = item.parentElement;
    item.remove();
    if (list) updateRepeaterCap(list);
    if (form) updateDirtyState(form);
    else if (list) notifyFormDirty(list);
  });

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-pk-add-repeater]");
    if (!btn) return;

    var tpl = qs(btn.getAttribute("data-target-template"));
    var list = qs(btn.getAttribute("data-target-list"));
    if (!tpl || !list) return;

    var max = parseInt(btn.getAttribute("data-pk-max") || list.getAttribute("data-pk-max") || "0", 10);
    if (max && repeaterListCount(list) >= max) return;

    var html = tpl.innerHTML.replace(/__INDEX__/g, String(Date.now()));
    var wrap = document.createElement("div");
    wrap.innerHTML = html.trim();
    var node = wrap.firstElementChild;
    if (node) {
      list.appendChild(node);
      updateRepeaterCap(list);
      notifyFormDirty(list);
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

  function initHoursRows() {
    qsa("[data-pk-hours-row]").forEach(function (row) {
      var closed = qs("[data-pk-hours-closed]", row);
      var times = qs("[data-pk-hours-times]", row);
      if (!closed || !times) return;
      function sync() {
        var off = closed.checked;
        qsa("input[type=time]", times).forEach(function (input) {
          input.disabled = off;
          if (off) input.value = "";
        });
        markDirtyFromEvent({ target: closed });
      }
      closed.addEventListener("change", sync);
      sync();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initDirtyForms();
      initHoursRows();
      initShopGalleries();
      initRepeaterLists();
    });
  } else {
    initDirtyForms();
    initHoursRows();
    initShopGalleries();
    initRepeaterLists();
  }
})();

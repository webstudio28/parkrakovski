<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/rich-text.php";
require_once __DIR__ . "/_inc/panel-data.php";

require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit("Методът не е позволен.");
}

verify_csrf($_POST["csrf"] ?? null);

$title   = trim(panel_post_string("title")) ?: "Без заглавие";
$date    = trim(panel_post_string("date"))  ?: "";
$image   = trim(panel_post_string("image")) ?: "";
$excerpt = panel_post_rich_html("excerpt");
$body    = panel_sanitize_body_html(panel_post_string("body"), true);

/* ── Brand colours from site config ── */
$cfg = panel_config();
$siteCfg = panel_load_json_file(panel_file_key("site"));
$colors = $siteCfg["ok"] ? ($siteCfg["data"]["colors"] ?? []) : [];
$colorBg     = (string)($colors["background"] ?? "#FFFFFF");
$colorBlue   = (string)($colors["blue"]       ?? "#003E8D");
$colorTeal   = (string)($colors["teal"]       ?? "#006484");
$colorYellow = (string)($colors["yellow"]     ?? "#B5961D");

/* ── Asset base path (relative to this file's location in _site/admin/panel/) ── */
$stylesUrl = "../../assets/css/styles.css";
$stylesPath = __DIR__ . "/../../assets/css/styles.css";
if (is_file($stylesPath)) {
  $stylesUrl .= "?v=" . (string)filemtime($stylesPath);
}
$bgUrl     = "../../assets/images/main-bg.jpg";

?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Преглед: <?php echo html($title); ?></title>
  <link rel="stylesheet" href="<?php echo html($stylesUrl); ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    :root {
      --color-bg: <?php echo html($colorBg); ?>;
      --color-brand-blue: <?php echo html($colorBlue); ?>;
      --color-brand-teal: <?php echo html($colorTeal); ?>;
      --color-brand-yellow: <?php echo html($colorYellow); ?>;
    }

    /* Preview banner */
    .pk-preview-bar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 9999;
      background: #b5961d;
      color: #0f172a;
      font-family: system-ui, -apple-system, sans-serif;
      font-size: 0.82rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.5rem 1.25rem;
      gap: 1rem;
      box-shadow: 0 2px 12px rgba(0,0,0,0.25);
    }
    .pk-preview-bar__label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .pk-preview-bar__close {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.3rem 0.75rem;
      border-radius: 0.4rem;
      background: rgba(15,23,42,0.18);
      border: 1px solid rgba(15,23,42,0.28);
      color: #0f172a;
      font: inherit;
      font-size: 0.78rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.15s;
    }
    .pk-preview-bar__close:hover {
      background: rgba(15,23,42,0.3);
    }

    /* Push content below fixed bar */
    body { padding-top: 2.5rem; }
  </style>
</head>
<body class="relative min-h-dvh flex flex-col text-slate-900">

  <!-- Preview bar -->
  <div class="pk-preview-bar" role="banner">
    <span class="pk-preview-bar__label">
      <i class="fa-solid fa-eye" aria-hidden="true"></i>
      Преглед — промените не са запазени
    </span>
    <a href="javascript:window.close()" class="pk-preview-bar__close">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      Затвори
    </a>
  </div>

  <!-- Page background (mirrors base.njk) -->
  <div aria-hidden="true" class="pointer-events-none fixed inset-0 z-0">
    <img
      src="<?php echo html($bgUrl); ?>"
      alt=""
      class="absolute inset-0 h-full w-full object-cover opacity-30"
    />
    <div class="absolute opacity-5 inset-0 bg-[linear-gradient(90deg,rgba(0,62,141,0.78)_0%,rgba(0,100,132,0.58)_56%,rgba(181,150,29,0.42)_100%)]"></div>
  </div>

  <div class="relative z-10 flex-1">
    <main>
      <section class="relative pt-12 sm:pt-14 pb-16 sm:pb-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">

          <!-- Breadcrumb -->
          <nav class="mb-10 flex items-center gap-2 text-sm text-slate-500" aria-label="Навигация">
            <span class="transition text-slate-500">Начало</span>
            <span class="text-slate-300">/</span>
            <span class="transition text-slate-500">Новини</span>
            <span class="text-slate-300">/</span>
            <span class="font-medium text-slate-900 truncate max-w-[200px] sm:max-w-xs"><?php echo html($title); ?></span>
          </nav>

          <div class="relative grid grid-cols-1 gap-6 sm:gap-8 lg:gap-10 items-start">
            <div>

              <!-- Title + date -->
              <header class="mb-5 sm:mb-6">
                <div class="flex items-center gap-3 mb-3 sm:mb-4">
                  <span class="h-px w-8 bg-brand-yellow"></span>
                  <?php if ($date): ?>
                  <time class="text-[10px] sm:text-xs font-semibold tracking-[0.32em] uppercase text-brand-yellow"><?php echo html($date); ?></time>
                  <?php endif; ?>
                </div>
                <h1 class="text-2xl sm:text-4xl lg:text-[2.75rem] font-extrabold tracking-tight text-slate-900 leading-tight"><?php echo html($title); ?></h1>
              </header>

              <!-- Hero image -->
              <?php if ($image): ?>
              <div class="relative overflow-hidden rounded-[2rem] sm:rounded-[2.5rem] shadow-2xl shadow-slate-900/15 ring-1 ring-white/40 aspect-[16/9] max-w-2xl">
                <img
                  src="<?php echo html("../../" . ltrim($image, "/")); ?>"
                  alt="<?php echo html($title); ?>"
                  class="absolute inset-0 h-full w-full object-cover"
                />
              </div>
              <?php endif; ?>

              <!-- Excerpt -->
              <?php if ($excerpt): ?>
              <div class="mt-6 sm:mt-8 flex items-start gap-4 rounded-[1.5rem] bg-white/92 backdrop-blur-sm p-6 sm:p-8 shadow-lg shadow-slate-900/10 ring-1 ring-slate-200/60">
                <span class="mt-1 shrink-0 block h-px w-10 bg-brand-yellow"></span>
                <p class="rich-content text-base sm:text-lg text-slate-700 leading-relaxed italic"><?php echo $excerpt; ?></p>
              </div>
              <?php endif; ?>

              <!-- Body -->
              <?php if ($body): ?>
              <div class="mt-5 rounded-[1.75rem] sm:rounded-[2rem] bg-white shadow-xl shadow-slate-900/10 p-7 sm:p-10">
                <div class="rich-content prose prose-slate max-w-none prose-p:leading-relaxed prose-p:text-slate-700 prose-headings:font-extrabold prose-a:text-brand-teal prose-a:no-underline hover:prose-a:underline prose-blockquote:border-brand-yellow prose-blockquote:text-slate-500 prose-strong:text-slate-900" data-rich-body>
                  <?php echo $body; ?>
                </div>
              </div>
              <?php endif; ?>

            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
  (function () {
    function classify(img) {
      function apply() {
        if (img.naturalWidth > 0 && img.naturalHeight > 0) {
          img.dataset.orientation = img.naturalHeight > img.naturalWidth ? "portrait" : "landscape";
        }
      }
      if (img.complete && img.naturalWidth > 0) { apply(); }
      else { img.addEventListener("load", apply, { once: true }); }
    }
    function run() {
      document.querySelectorAll("[data-rich-body] img").forEach(classify);
    }
    if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", run); }
    else { run(); }
  })();
  </script>

</body>
</html>

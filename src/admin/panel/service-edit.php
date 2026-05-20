<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";
require_once __DIR__ . "/_inc/forms.php";

require_login();

$path = panel_file_key("services");
$loaded = panel_load_json_file($path);
if (!$loaded["ok"]) {
  redirect("/services.php");
}

$items = $loaded["data"]["items"] ?? [];
$editSlug = (string)($_GET["slug"] ?? "");
$isNew = $editSlug === "";

$svc = ["slug" => "", "title" => "", "summary" => "", "body" => ""];
if (!$isNew) {
  foreach ($items as $item) {
    if (($item["slug"] ?? "") === $editSlug) {
      $svc = array_merge($svc, $item);
      break;
    }
  }
}

$sha = $loaded["sha"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);
  $originalSlug = panel_post_string("original_slug");
  $newSlug = panel_slugify(panel_post_string("slug") ?: panel_post_string("title"));
  $entry = [
    "slug" => $newSlug,
    "title" => panel_post_string("title"),
    "summary" => panel_post_string("summary"),
    "body" => panel_post_string("body"),
  ];

  $updated = [];
  $replaced = false;
  foreach ($items as $item) {
    if (!$isNew && ($item["slug"] ?? "") === $originalSlug) {
      $updated[] = $entry;
      $replaced = true;
    } elseif (($item["slug"] ?? "") !== $newSlug || $isNew) {
      $updated[] = $item;
    }
  }
  if ($isNew) {
    $updated[] = $entry;
  }

  $save = panel_save_json_file($path, ["items" => array_values($updated)], $sha, "chore(cms): update service");
  if ($save["ok"]) {
    panel_flash_set("ok", "Записано.");
    panel_redirect_with("./service-edit.php", ["slug" => $newSlug]);
  }
  panel_flash_set("err", $save["error"] ?? "Грешка.");
  $svc = $entry;
}

panel_page_open("Услуга — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <h1 class="pk-title"><?php echo $isNew ? "Нова услуга" : "Редакция"; ?></h1>
        <a class="pk-btn pk-btn--ghost" href="./services.php">← Услуги</a>
      </div>
      <?php panel_flash_render(); ?>
      <form method="post" class="pk-card pk-card--wide" style="margin-top:1rem;" data-pk-dirty-form>
        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />
        <input type="hidden" name="original_slug" value="<?php echo html((string)($svc["slug"] ?? "")); ?>" />
        <div class="pk-section">
          <?php panel_field_text("Заглавие", "title", (string)($svc["title"] ?? "")); ?>
          <?php panel_field_text("Slug", "slug", (string)($svc["slug"] ?? "")); ?>
          <?php panel_field_textarea("Резюме", "summary", (string)($svc["summary"] ?? ""), 2); ?>
          <?php panel_field_textarea("Текст", "body", (string)($svc["body"] ?? ""), 8); ?>
        </div>
        <?php panel_save_button(); ?>
      </form>
    </div>
<?php
panel_page_close();

<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";
require_once __DIR__ . "/_inc/forms.php";

require_login();

$path = panel_file_key("news");
$loaded = panel_load_json_file($path);
if (!$loaded["ok"]) {
  panel_flash_set("err", $loaded["error"] ?? "Грешка.");
  redirect("/news.php");
}

$items = $loaded["data"]["items"] ?? [];
$editSlug = (string)($_GET["slug"] ?? "");
$isNew = $editSlug === "";

$post = [
  "slug" => "",
  "title" => "",
  "date" => "",
  "excerpt" => "",
  "body" => "",
  "image" => "",
  "url" => "",
];

if (!$isNew) {
  foreach ($items as $item) {
    if (($item["slug"] ?? "") === $editSlug) {
      $post = array_merge($post, $item);
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
    "date" => panel_post_string("date"),
    "excerpt" => panel_post_rich_html("excerpt"),
    "body" => panel_post_rich_html("body"),
    "image" => panel_post_string("image"),
    "url" => "/novini/" . $newSlug . "/",
  ];

  foreach ($items as $item) {
    if (($item["slug"] ?? "") === $newSlug && ($isNew || ($item["slug"] ?? "") !== $originalSlug)) {
      panel_flash_set("err", "Вече съществува новина с този slug.");
      panel_redirect_with("./news-edit.php", $isNew ? [] : ["slug" => $originalSlug]);
    }
  }

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

  $save = panel_save_json_file($path, ["items" => array_values($updated)], $sha, "chore(cms): update news " . $newSlug);
  if ($save["ok"]) {
    panel_flash_set("ok", "Записано.");
    panel_redirect_with("./news-edit.php", ["slug" => $newSlug]);
  }
  panel_flash_set("err", $save["error"] ?? "Грешка.");
  $post = $entry;
  $editSlug = $newSlug;
  $isNew = false;
}

$title = $isNew ? "Нова новина" : "Редакция: " . (string)($post["title"] ?? "");
panel_page_open($title . " — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <h1 class="pk-title"><?php echo html($title); ?></h1>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./news.php">← Новини</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <form method="post" class="pk-card pk-card--wide" style="margin-top:1rem;" data-pk-dirty-form>
        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />
        <input type="hidden" name="original_slug" value="<?php echo html((string)($post["slug"] ?? "")); ?>" />

        <div class="pk-section">
          <div class="pk-grid-2">
            <?php panel_field_text("Заглавие", "title", (string)($post["title"] ?? "")); ?>
            <?php panel_field_text("Дата", "date", (string)($post["date"] ?? ""), "text", "напр. 12 май 2026"); ?>
            <?php panel_field_text("Slug", "slug", (string)($post["slug"] ?? "")); ?>
          </div>
          <?php panel_field_media("Снимка", "image", (string)($post["image"] ?? ""), "news", true); ?>
          <?php panel_field_rich_text("Кратко описание", "excerpt", (string)($post["excerpt"] ?? ""), 3); ?>
          <?php panel_field_rich_text("Пълен текст", "body", (string)($post["body"] ?? ""), 12); ?>
        </div>

        <?php panel_save_button(); ?>
      </form>
    </div>
<?php
panel_page_close();

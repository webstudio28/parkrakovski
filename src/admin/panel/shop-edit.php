<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";
require_once __DIR__ . "/_inc/forms.php";
require_once __DIR__ . "/_inc/rich-text.php";

require_login();

$path = panel_file_key("shops");
$loaded = panel_load_json_file($path);
if (!$loaded["ok"]) {
  panel_flash_set("err", $loaded["error"] ?? "Грешка при зареждане.");
  redirect("/shops.php");
}

$items = $loaded["data"]["items"] ?? [];
$editSlug = (string)($_GET["slug"] ?? "");
$isNew = $editSlug === "" || $editSlug === "__new__";

$shop = [
  "slug" => "",
  "title" => "",
  "category" => "",
  "color" => "#006484",
  "url" => "",
  "logo" => "",
  "image" => "",
  "description" => "",
  "hours" => "",
  "phone" => "",
  "promotions" => [],
  "images" => [],
];

if (!$isNew) {
  foreach ($items as $item) {
    if (($item["slug"] ?? "") === $editSlug) {
      $shop = array_merge($shop, $item);
      break;
    }
  }
}

$sha = $loaded["sha"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $originalSlug = panel_post_string("original_slug");
  $newSlug = panel_slugify(panel_post_string("slug") ?: panel_post_string("title"));
  $promotions = [];
  $images = $_POST["promo_image"] ?? [];
  $alts = $_POST["promo_alt"] ?? [];
  $descs = panel_post_rich_html_list("promo_description");
  if (is_array($images)) {
    foreach ($images as $i => $img) {
      $img = trim((string)$img);
      $desc = (string)($descs[$i] ?? "");
      if ($img === "" && $desc === "") {
        continue;
      }
      $promotions[] = [
        "image" => $img,
        "alt" => trim((string)($alts[$i] ?? "")),
        "description" => $desc,
      ];
    }
  }

  $galleryImages = panel_post_path_list("gallery_image");
  $existing = $isNew ? [] : panel_find_item_by_slug($items, $originalSlug);

  $formEntry = [
    "slug" => $newSlug,
    "title" => panel_post_string("title"),
    "category" => panel_post_string("category"),
    "color" => panel_post_string("color", "#006484"),
    "url" => panel_post_string("url"),
    "logo" => panel_post_string("logo"),
    "image" => panel_post_string("image"),
    "description" => panel_post_rich_html("description"),
    "hours" => panel_post_string("hours"),
    "phone" => panel_post_string("phone"),
    "promotions" => $promotions,
    "images" => $galleryImages,
  ];
  $entry = panel_merge_entry($existing, $formEntry);

  foreach ($items as $item) {
    $slug = (string)($item["slug"] ?? "");
    if ($slug === $newSlug && ($isNew || $slug !== $originalSlug)) {
      panel_flash_set("err", "Вече съществува обект с този slug.");
      panel_redirect_with("./shop-edit.php", $isNew ? [] : ["slug" => $originalSlug]);
    }
  }

  $updated = [];
  $replaced = false;
  foreach ($items as $item) {
    $slug = (string)($item["slug"] ?? "");
    if (!$isNew && $slug === $originalSlug) {
      $updated[] = $entry;
      $replaced = true;
    } elseif ($slug !== $newSlug || $isNew) {
      $updated[] = $item;
    }
  }
  if ($isNew) {
    $updated[] = $entry;
  } elseif (!$replaced) {
    panel_flash_set("err", "Обектът не е намерен.");
    panel_redirect_with("./shops.php");
  }

  $save = panel_save_json_file($path, ["items" => array_values($updated)], $sha, "chore(cms): update shop " . $newSlug);
  if ($save["ok"]) {
    panel_flash_set("ok", "Записано успешно.");
    panel_redirect_with("./shop-edit.php", ["slug" => $newSlug]);
  }
  panel_flash_set("err", $save["error"] ?? "Грешка при запис.");
  $shop = $entry;
  $editSlug = $newSlug;
  $isNew = false;
}

$promotions = is_array($shop["promotions"] ?? null) ? $shop["promotions"] : [];
$galleryImages = [];
if (is_array($shop["images"] ?? null)) {
  foreach ($shop["images"] as $img) {
    if (is_string($img)) {
      $path = trim($img);
    } elseif (is_array($img)) {
      $path = trim((string)($img["src"] ?? $img["url"] ?? ""));
    } else {
      $path = "";
    }
    if ($path !== "") {
      $galleryImages[] = $path;
    }
  }
}
$pageTitle = $isNew ? "Нов обект" : "Редакция: " . (string)($shop["title"] ?? "");

panel_page_open($pageTitle . " — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <div class="pk-eyebrow">Ритеил парк Раковски</div>
          <h1 class="pk-title"><?php echo html($pageTitle); ?></h1>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./shops.php">← Обекти</a>
          <a class="pk-btn pk-btn--ghost" href="./logout.php">Изход</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <form method="post" class="pk-card pk-card--wide" style="margin-top:1rem;" data-pk-dirty-form>
        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />
        <input type="hidden" name="original_slug" value="<?php echo html((string)($shop["slug"] ?? "")); ?>" />

        <div class="pk-section">
          <h2 class="pk-section__title">Основни данни</h2>
          <div class="pk-grid-2">
            <?php panel_field_text("Име", "title", (string)($shop["title"] ?? "")); ?>
            <?php panel_field_text("Категория", "category", (string)($shop["category"] ?? "")); ?>
            <?php panel_field_text("Slug (URL)", "slug", (string)($shop["slug"] ?? ""), "text", "Латиница и тирета, напр. t-market"); ?>
            <?php panel_field_text("Цвят", "color", (string)($shop["color"] ?? "#006484"), "text", "#006484"); ?>
            <?php panel_field_text("Уебсайт", "url", (string)($shop["url"] ?? ""), "url"); ?>
            <?php panel_field_text("Телефон", "phone", (string)($shop["phone"] ?? "")); ?>
          </div>
          <?php panel_field_textarea("Работно време", "hours", (string)($shop["hours"] ?? ""), 2); ?>
          <?php panel_field_rich_text("Описание", "description", (string)($shop["description"] ?? ""), 5); ?>
        </div>

        <div class="pk-section">
          <h2 class="pk-section__title">Снимки</h2>
          <div class="pk-grid-2">
            <?php panel_field_media("Лого", "logo", (string)($shop["logo"] ?? ""), "shop-logo", false); ?>
            <?php panel_field_media("Основна снимка (страница)", "image", (string)($shop["image"] ?? ""), "shop-hero", true); ?>
          </div>
        </div>

        <div class="pk-section">
          <div class="pk-repeater-item__head">
            <h2 class="pk-section__title" style="margin:0;">Галерия (снимки на страницата)</h2>
            <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm" data-pk-add-repeater data-target-list="#gallery-list" data-target-template="#gallery-template">+ Добави снимка</button>
          </div>
          <p class="pk-hint" style="margin:0 0 1rem;">Каруселът на страницата на обекта. Ако е празно, се ползват снимките от промоциите.</p>
          <div class="pk-repeater" id="gallery-list">
            <?php foreach ($galleryImages as $i => $imgPath): ?>
              <?php panel_gallery_repeater_item((int)$i, $imgPath); ?>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="pk-section">
          <div class="pk-repeater-item__head">
            <h2 class="pk-section__title" style="margin:0;">Промоции</h2>
            <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm" data-pk-add-repeater data-target-list="#promo-list" data-target-template="#promo-template">+ Добави промоция</button>
          </div>
          <div class="pk-repeater" id="promo-list">
            <?php foreach ($promotions as $i => $promo): ?>
              <?php panel_promotion_repeater_item((int)$i, is_array($promo) ? $promo : []); ?>
            <?php endforeach; ?>
          </div>
        </div>

        <?php panel_save_button(); ?>
      </form>
    </div>

    <template id="gallery-template">
      <?php panel_gallery_repeater_item(0, ""); ?>
    </template>

    <template id="promo-template">
      <?php panel_promotion_repeater_item(0, []); ?>
    </template>
<?php
panel_page_close();

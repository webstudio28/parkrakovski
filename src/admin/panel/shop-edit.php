<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";
require_once __DIR__ . "/_inc/forms.php";
require_once __DIR__ . "/_inc/rich-text.php";
require_once __DIR__ . "/_inc/shop-hours.php";

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
  $newSlug = panel_slugify(panel_post_string("title"));
  if ($newSlug === "item" && $originalSlug !== "") {
    $newSlug = $originalSlug;
  }
  $promoRows = [];
  $images = $_POST["promo_image"] ?? [];
  $descs = panel_post_rich_html_list("promo_description");
  if (is_array($images)) {
    foreach ($images as $i => $img) {
      $promoRows[] = [
        "image" => trim((string)$img),
        "description" => (string)($descs[$i] ?? ""),
      ];
    }
  }

  $galleryPaths = panel_post_path_list("gallery_image");
  $existing = $isNew ? [] : panel_find_item_by_slug($items, $originalSlug);
  $existingPromotions = is_array($existing["promotions"] ?? null) ? $existing["promotions"] : [];
  $promotions = panel_promotions_cap(panel_promotions_normalize_for_storage(
    $promoRows,
    $existingPromotions,
    panel_post_string("title")
  ));
  $existingImages = is_array($existing["images"] ?? null) ? $existing["images"] : [];
  $galleryImages = panel_gallery_normalize_for_storage(
    $galleryPaths,
    $existingImages,
    panel_post_string("title")
  );

  $formEntry = [
    "slug" => $newSlug,
    "title" => panel_post_string("title"),
    "category" => panel_post_string("category"),
    "url" => panel_post_string("url"),
    "logo" => panel_post_string("logo"),
    "image" => panel_post_string("image"),
    "description" => panel_post_rich_html("description"),
    "hours" => panel_shop_hours_for_storage(panel_post_shop_hours()),
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
    panel_flash_set("ok", panel_save_success_message());
    panel_redirect_with("./shop-edit.php", ["slug" => $newSlug]);
  }
  panel_flash_set("err", $save["error"] ?? "Грешка при запис.");
  $shop = $entry;
  $editSlug = $newSlug;
  $isNew = false;
}

$promotions = panel_promotions_cap(is_array($shop["promotions"] ?? null) ? $shop["promotions"] : []);
$promoMax = panel_shop_promotions_max();
$promoAtCap = count($promotions) >= $promoMax;
$galleryPaths = [];
if (is_array($shop["images"] ?? null)) {
  foreach ($shop["images"] as $img) {
    $imgPath = panel_gallery_image_path($img);
    if ($imgPath !== "") {
      $galleryPaths[] = $imgPath;
    }
  }
}
$pageTitle = $isNew ? "Нов обект" : "Редакция: " . (string)($shop["title"] ?? "");
$hoursSchedule = panel_shop_hours_normalize($shop["hours"] ?? null);

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
            <?php panel_field_text("Уебсайт", "url", (string)($shop["url"] ?? ""), "url"); ?>
            <?php panel_field_text("Телефон", "phone", (string)($shop["phone"] ?? ""), "tel", "Показва се на страницата на обекта"); ?>
          </div>
          <?php panel_field_rich_text("Описание", "description", (string)($shop["description"] ?? ""), 5); ?>
        </div>

        <div class="pk-section">
          <h2 class="pk-section__title">Работно време</h2>
          <?php panel_field_shop_hours($hoursSchedule); ?>
        </div>

        <div class="pk-section">
          <h2 class="pk-section__title">Лого и основна снимка</h2>
          <div class="pk-grid-2">
            <?php panel_field_media("Лого", "logo", (string)($shop["logo"] ?? ""), "shop-logo", false); ?>
            <?php panel_field_media("Основна снимка (страница)", "image", (string)($shop["image"] ?? ""), "shop-hero", true); ?>
          </div>
        </div>

        <?php panel_field_shop_gallery($galleryPaths); ?>

        <div class="pk-section">
          <div class="pk-repeater-item__head">
            <h2 class="pk-section__title" style="margin:0;">Промоции</h2>
            <button
              type="button"
              class="pk-btn pk-btn--ghost pk-btn--sm"
              data-pk-add-repeater
              data-pk-max="<?php echo (int)$promoMax; ?>"
              data-target-list="#promo-list"
              data-target-template="#promo-template"
              <?php echo $promoAtCap ? "disabled" : ""; ?>
            >+ Добави промоция</button>
          </div>
          <p class="pk-hint" style="margin:0 0 1rem;">Максимум <?php echo (int)$promoMax; ?> промоции на обект.</p>
          <div class="pk-repeater pk-repeater--promos" id="promo-list" data-pk-repeater-list data-pk-max="<?php echo (int)$promoMax; ?>">
            <?php foreach ($promotions as $i => $promo): ?>
              <?php panel_promotion_repeater_item((int)$i, is_array($promo) ? $promo : []); ?>
            <?php endforeach; ?>
          </div>
        </div>

        <?php panel_save_button(); ?>
      </form>
    </div>

    <template id="promo-template">
      <?php panel_promotion_repeater_item(0, []); ?>
    </template>
<?php
panel_page_close();

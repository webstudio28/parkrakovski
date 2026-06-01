<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";
require_once __DIR__ . "/_inc/forms.php";

require_login();

$path = panel_file_key("site");
$loaded = panel_load_json_file($path);
if (!$loaded["ok"]) {
  panel_flash_set("err", $loaded["error"] ?? "Грешка.");
  redirect("/index.php");
}

$data = $loaded["data"];
$sha = $loaded["sha"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $nav = [];
  $labels = $_POST["nav_label"] ?? [];
  $urls = $_POST["nav_url"] ?? [];
  if (is_array($labels)) {
    foreach ($labels as $i => $label) {
      $label = trim((string)$label);
      $url = trim((string)($urls[$i] ?? ""));
      if ($label === "" && $url === "") {
        continue;
      }
      $nav[] = ["label" => $label, "url" => $url];
    }
  }

  $prev = is_array($loaded["data"]) ? $loaded["data"] : [];
  $built = [
    "brand" => [
      "name" => panel_post_string("brand_name"),
      "language" => panel_post_string("brand_language", "bg"),
      "tagline" => panel_post_string("brand_tagline"),
    ],
    "colors" => [
      "background" => panel_post_string("color_background", "#FFFFFF"),
      "blue" => panel_post_string("color_blue"),
      "teal" => panel_post_string("color_teal"),
      "yellow" => panel_post_string("color_yellow"),
    ],
    "seo" => [
      "siteUrl" => panel_post_string("seo_siteUrl"),
      "defaultTitle" => panel_post_string("seo_defaultTitle"),
      "defaultDescription" => panel_post_string("seo_defaultDescription"),
    ],
    "footer" => [
      "address" => panel_post_string("footer_address"),
      "phone" => panel_post_string("footer_phone"),
      "email" => panel_post_string("footer_email"),
      "social" => [
        "facebook" => panel_post_string("social_facebook"),
        "instagram" => panel_post_string("social_instagram"),
        "youtube" => panel_post_string("social_youtube"),
      ],
    ],
    "nav" => ["header" => $nav],
  ];
  $data = array_replace_recursive($prev, $built);
  $data["nav"]["header"] = $nav;

  $save = panel_save_json_file($path, $data, $sha, "chore(cms): update site settings");
  if ($save["ok"]) {
    panel_flash_set("ok", "Настройките са записани.");
    panel_redirect_with("./site.php");
  }
  panel_flash_set("err", $save["error"] ?? "Грешка.");
}

$brand = $data["brand"] ?? [];
$colors = $data["colors"] ?? [];
$seo = $data["seo"] ?? [];
$footer = $data["footer"] ?? [];
$social = $footer["social"] ?? [];
$navItems = $data["nav"]["header"] ?? [];

panel_page_open("Настройки — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <h1 class="pk-title">Настройки на сайта</h1>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./index.php">Начало</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <form method="post" class="pk-card pk-card--wide" style="margin-top:1rem;" data-pk-dirty-form>
        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />

        <div class="pk-section">
          <h2 class="pk-section__title">Марка</h2>
          <div class="pk-grid-2">
            <?php panel_field_text("Име", "brand_name", (string)($brand["name"] ?? "")); ?>
            <?php panel_field_text("Език", "brand_language", (string)($brand["language"] ?? "bg")); ?>
          </div>
          <?php panel_field_textarea("Слоган", "brand_tagline", (string)($brand["tagline"] ?? ""), 2); ?>
        </div>

        <div class="pk-section">
          <h2 class="pk-section__title">Цветове</h2>
          <div class="pk-grid-2">
            <?php panel_field_text("Фон", "color_background", (string)($colors["background"] ?? "")); ?>
            <?php panel_field_text("Синьо", "color_blue", (string)($colors["blue"] ?? "")); ?>
            <?php panel_field_text("Тюркоаз", "color_teal", (string)($colors["teal"] ?? "")); ?>
            <?php panel_field_text("Жълто", "color_yellow", (string)($colors["yellow"] ?? "")); ?>
          </div>
        </div>

        <div class="pk-section">
          <h2 class="pk-section__title">SEO</h2>
          <div class="pk-grid-2">
            <?php panel_field_text("URL на сайта", "seo_siteUrl", (string)($seo["siteUrl"] ?? "")); ?>
            <?php panel_field_text("Заглавие по подразбиране", "seo_defaultTitle", (string)($seo["defaultTitle"] ?? "")); ?>
          </div>
          <?php panel_field_textarea("Описание по подразбиране", "seo_defaultDescription", (string)($seo["defaultDescription"] ?? ""), 3); ?>
        </div>

        <div class="pk-section">
          <h2 class="pk-section__title">Футър и контакти</h2>
          <?php panel_field_textarea("Адрес", "footer_address", (string)($footer["address"] ?? ""), 2); ?>
          <div class="pk-grid-2">
            <?php panel_field_text("Телефон", "footer_phone", (string)($footer["phone"] ?? "")); ?>
            <?php panel_field_text("Имейл", "footer_email", (string)($footer["email"] ?? ""), "email"); ?>
          </div>
          <div class="pk-grid-2">
            <?php panel_field_text("Facebook", "social_facebook", (string)($social["facebook"] ?? "")); ?>
            <?php panel_field_text("Instagram", "social_instagram", (string)($social["instagram"] ?? "")); ?>
            <?php panel_field_text("YouTube", "social_youtube", (string)($social["youtube"] ?? "")); ?>
          </div>
        </div>

        <div class="pk-section">
          <div class="pk-repeater-item__head">
            <h2 class="pk-section__title" style="margin:0;">Меню</h2>
            <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm" data-pk-add-repeater data-target-list="#nav-list" data-target-template="#nav-template">+ Ред</button>
          </div>
          <div class="pk-repeater" id="nav-list">
            <?php foreach ($navItems as $i => $link): ?>
              <div class="pk-repeater-item" data-pk-repeater-item>
                <div class="pk-repeater-item__head">
                  <strong>Връзка <?php echo (int)$i + 1; ?></strong>
                  <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm pk-btn--danger" data-pk-remove-repeater>Изтрий</button>
                </div>
                <div class="pk-grid-2">
                  <?php panel_field_text("Етикет", "nav_label[]", (string)($link["label"] ?? "")); ?>
                  <?php panel_field_text("URL", "nav_url[]", (string)($link["url"] ?? "")); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php panel_save_button(); ?>
      </form>
    </div>

    <template id="nav-template">
      <div class="pk-repeater-item" data-pk-repeater-item>
        <div class="pk-repeater-item__head">
          <strong>Нова връзка</strong>
          <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm pk-btn--danger" data-pk-remove-repeater>Изтрий</button>
        </div>
        <div class="pk-grid-2">
          <?php panel_field_text("Етикет", "nav_label[]", ""); ?>
          <?php panel_field_text("URL", "nav_url[]", ""); ?>
        </div>
      </div>
    </template>
<?php
panel_page_close();

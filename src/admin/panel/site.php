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

  $prev = is_array($loaded["data"]) ? $loaded["data"] : [];
  $built = [
    "footer" => [
      "address" => panel_post_string("footer_address"),
      "phone" => panel_post_string("footer_phone"),
      "email" => panel_post_string("footer_email"),
      "hours" => panel_shop_hours_for_storage(panel_post_shop_hours()),
      "social" => [
        "facebook" => panel_post_string("social_facebook"),
        "instagram" => panel_post_string("social_instagram"),
        "youtube" => panel_post_string("social_youtube"),
      ],
    ],
  ];
  $data = array_replace_recursive($prev, $built);

  $save = panel_save_json_file($path, $data, $sha, "chore(cms): update site footer");
  if ($save["ok"]) {
    panel_flash_set("ok", panel_save_success_message());
    panel_redirect_with("./site.php");
  }
  panel_flash_set("err", $save["error"] ?? "Грешка.");
}

$footer = $data["footer"] ?? [];
$social = $footer["social"] ?? [];
$hoursSchedule = panel_shop_hours_normalize($footer["hours"] ?? null);

panel_page_open("Настройки — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <h1 class="pk-title">Контакти и футър</h1>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./index.php">Начало</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <form method="post" class="pk-card pk-card--wide" style="margin-top:1rem;" data-pk-dirty-form>
        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />

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
          <h2 class="pk-section__title">Работно време</h2>
          <?php panel_field_shop_hours($hoursSchedule); ?>
        </div>

        <?php panel_save_button(); ?>
      </form>
    </div>
<?php
panel_page_close();

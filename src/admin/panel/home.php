<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";
require_once __DIR__ . "/_inc/forms.php";

require_login();

$path = panel_file_key("home");
$loaded = panel_load_json_file($path);
if (!$loaded["ok"]) {
  panel_flash_set("err", $loaded["error"] ?? "Грешка.");
  redirect("/index.php");
}

$data = $loaded["data"];
$sha = $loaded["sha"];
$about = is_array($data["about"] ?? null) ? $data["about"] : [];
$counters = is_array($about["counters"] ?? null) ? $about["counters"] : [];
$galleryPaths = [];
if (is_array($data["gallery"] ?? null)) {
  foreach ($data["gallery"] as $img) {
    $imgPath = panel_gallery_image_path($img);
    if ($imgPath !== "") {
      $galleryPaths[] = $imgPath;
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $prev = is_array($loaded["data"]) ? $loaded["data"] : [];
  $prevAbout = is_array($prev["about"] ?? null) ? $prev["about"] : [];
  $prevCounters = is_array($prevAbout["counters"] ?? null) ? $prevAbout["counters"] : [];

  $newCounters = [];
  $counterValues = $_POST["counter_value"] ?? [];
  if (is_array($counterValues)) {
    foreach ($prevCounters as $i => $existing) {
      if (!is_array($existing)) {
        continue;
      }
      $value = (int)trim((string)($counterValues[$i] ?? "0"));
      $newCounters[] = panel_merge_entry($existing, ["value" => $value]);
    }
  }

  $data = $prev;
  $data["about"] = $prevAbout;
  $data["about"]["counters"] = $newCounters;
  $data["gallery"] = panel_post_path_list("gallery_image");

  $save = panel_save_json_file($path, $data, $sha, "chore(cms): update home page");
  if ($save["ok"]) {
    panel_flash_set("ok", panel_save_success_message());
    panel_redirect_with("./home.php");
  }
  panel_flash_set("err", $save["error"] ?? "Грешка.");
  $about = is_array($data["about"] ?? null) ? $data["about"] : [];
  $counters = $about["counters"] ?? [];
  $galleryPaths = $data["gallery"] ?? [];
}

panel_page_open("Начална страница — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <h1 class="pk-title">Начална страница</h1>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./index.php">Начало</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <form method="post" class="pk-card pk-card--wide" style="margin-top:1rem;" data-pk-dirty-form>
        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />

        <div class="pk-section">
          <h2 class="pk-section__title">Броячи</h2>
          <p class="pk-hint" style="margin:0 0 1rem;">Променете само числата. Текстовете под тях се задават в кода.</p>
          <div class="pk-repeater">
            <?php foreach ($counters as $counter): ?>
              <?php
              $counterLabel = trim((string)($counter["label"] ?? ""));
              $fieldLabel = $counterLabel !== "" ? $counterLabel : "Число";
              ?>
              <div class="pk-repeater-item">
                <?php panel_field_text($fieldLabel, "counter_value[]", (string)($counter["value"] ?? ""), "number"); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php panel_field_shop_gallery(
          $galleryPaths,
          "home-gallery",
          "Галерия на началната страница",
          "Каруселът под секцията „За нас“ на началната страница."
        ); ?>

        <?php panel_save_button(); ?>
      </form>
    </div>
<?php
panel_page_close();

<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";

require_login();

$path = panel_file_key("shops");
$loaded = panel_load_json_file($path);
$items = $loaded["ok"] ? ($loaded["data"]["items"] ?? []) : [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && panel_post_string("action") === "delete") {
  verify_csrf($_POST["csrf"] ?? null);
  $slug = panel_post_string("slug");
  $sha = panel_post_string("sha");
  $newItems = array_values(array_filter($items, static fn($s) => ($s["slug"] ?? "") !== $slug));
  $save = panel_save_json_file($path, ["items" => $newItems], $sha, "chore(cms): delete shop " . $slug);
  if ($save["ok"]) {
    panel_flash_set("ok", "Обектът е изтрит.");
    panel_redirect_with("./shops.php");
  }
  panel_flash_set("err", $save["error"] ?? "Грешка при изтриване.");
  panel_redirect_with("./shops.php");
}

$sha = $loaded["ok"] ? $loaded["sha"] : "";

panel_page_open("Обекти — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <div class="pk-eyebrow">Ритейл парк Раковски</div>
          <h1 class="pk-title">Търговски обекти</h1>
          <p class="pk-sub">Партньори, промоции и снимки</p>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./index.php">Начало</a>
          <a class="pk-btn pk-btn--ghost" href="./logout.php">Изход</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <div class="pk-toolbar">
        <div class="pk-toolbar__left"></div>
        <div class="pk-toolbar__right">
          <a class="pk-btn pk-btn--sm" href="./shop-edit.php">+ Нов обект</a>
        </div>
      </div>

      <?php if (!$items): ?>
        <div class="pk-empty">Няма добавени обекти.</div>
      <?php else: ?>
        <div class="pk-list">
          <?php foreach ($items as $shop): ?>
            <?php
              $logo = (string)($shop["logo"] ?? $shop["image"] ?? "");
              $promoCount = is_array($shop["promotions"] ?? null) ? count($shop["promotions"]) : 0;
            ?>
            <div class="pk-list-item" style="cursor:default;">
              <?php if ($logo): ?>
                <img class="pk-list-item__thumb" src="<?php echo html($logo); ?>" alt="" />
              <?php else: ?>
                <div class="pk-list-item__thumb"></div>
              <?php endif; ?>
              <div class="pk-list-item__body">
                <div class="pk-list-item__title"><?php echo html((string)($shop["title"] ?? "")); ?></div>
                <div class="pk-list-item__meta">
                  <?php echo html((string)($shop["category"] ?? "")); ?>
                  · <?php echo (int)$promoCount; ?> промоции
                </div>
              </div>
              <div class="pk-actions-inline">
                <a class="pk-btn pk-btn--ghost pk-btn--sm" href="./shop-edit.php?slug=<?php echo urlencode((string)($shop["slug"] ?? "")); ?>">Редакция</a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Изтриване на обекта?');">
                  <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="slug" value="<?php echo html((string)($shop["slug"] ?? "")); ?>" />
                  <input type="hidden" name="sha" value="<?php echo html($sha); ?>" />
                  <button type="submit" class="pk-btn pk-btn--ghost pk-btn--sm pk-btn--danger">Изтрий</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
<?php
panel_page_close();

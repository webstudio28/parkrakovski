<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";

require_login();

$path = panel_file_key("news");
$loaded = panel_load_json_file($path);
$items = $loaded["ok"] ? ($loaded["data"]["items"] ?? []) : [];
$sha = $loaded["ok"] ? $loaded["sha"] : "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && panel_post_string("action") === "delete") {
  verify_csrf($_POST["csrf"] ?? null);
  $slug = panel_post_string("slug");
  $newItems = array_values(array_filter($items, static fn($n) => ($n["slug"] ?? "") !== $slug));
  $save = panel_save_json_file($path, ["items" => $newItems], panel_post_string("sha"), "chore(cms): delete news " . $slug);
  if ($save["ok"]) {
    panel_flash_set("ok", "Новината е изтрита.");
  } else {
    panel_flash_set("err", $save["error"] ?? "Грешка.");
  }
  panel_redirect_with("./news.php");
}

panel_page_open("Новини — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <div class="pk-eyebrow">Ритейл парк Раковски</div>
          <h1 class="pk-title">Новини</h1>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./index.php">Начало</a>
          <a class="pk-btn pk-btn--ghost" href="./logout.php">Изход</a>
        </div>
      </div>

      <?php panel_flash_render(); ?>

      <div class="pk-toolbar">
        <div></div>
        <a class="pk-btn pk-btn--sm" href="./news-edit.php">+ Нова новина</a>
      </div>

      <?php if (!$items): ?>
        <div class="pk-empty">Няма новини.</div>
      <?php else: ?>
        <div class="pk-list">
          <?php foreach ($items as $post): ?>
            <div class="pk-list-item" style="cursor:default;">
              <img class="pk-list-item__thumb" src="<?php echo html((string)($post["image"] ?? "")); ?>" alt="" />
              <div class="pk-list-item__body">
                <div class="pk-list-item__title"><?php echo html((string)($post["title"] ?? "")); ?></div>
                <div class="pk-list-item__meta"><?php echo html((string)($post["date"] ?? "")); ?></div>
              </div>
              <div class="pk-actions-inline">
                <a class="pk-btn pk-btn--ghost pk-btn--sm" href="./news-edit.php?slug=<?php echo urlencode((string)($post["slug"] ?? "")); ?>">Редакция</a>
                <form method="post" onsubmit="return confirm('Изтриване?');">
                  <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="slug" value="<?php echo html((string)($post["slug"] ?? "")); ?>" />
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

<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/github.php";
require_once __DIR__ . "/_inc/ui.php";

require_login();

$cfg = panel_config();
$sitePath = $cfg["files"]["site"] ?? "src/_data/site.config.json";

$error = "";
$success = "";
$sha = "";
$jsonText = "";

// Load current file from GitHub
$fileRes = gh_get_file($sitePath);
if (!$fileRes["ok"]) {
  $error = "Грешка при зареждане: " . ($fileRes["error"] ?? "неизвестна грешка");
} else {
  $contentB64 = $fileRes["data"]["content"] ?? "";
  $sha = (string)($fileRes["data"]["sha"] ?? "");
  $raw = base64_decode(str_replace(["\n", "\r"], "", (string)$contentB64), true);
  $jsonText = $raw !== false ? $raw : "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $incoming = (string)($_POST["json"] ?? "");
  $decoded = json_decode($incoming, true);
  if (!is_array($decoded)) {
    $error = "Невалиден JSON.";
  } else {
    $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    $saveSha = (string)($_POST["sha"] ?? "");
    if (!$saveSha) {
      $error = "Липсва SHA — презаредете страницата.";
    } else {
      $msg = "chore(cms): update site settings";
      $putRes = gh_update_file($sitePath, $pretty, $saveSha, $msg);
      if (!$putRes["ok"]) {
        $error = "Грешка при запис: " . ($putRes["error"] ?? "неизвестна грешка");
      } else {
        $success = panel_save_success_message();
        $newSha = $putRes["data"]["content"]["sha"] ?? null;
        if (is_string($newSha) && $newSha) $sha = $newSha;
        $jsonText = $pretty;
      }
    }
  }
}

$decoded = json_decode($jsonText, true);
$pretty = is_array($decoded)
  ? (json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n")
  : $jsonText;

panel_page_open("Настройки — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <h1 class="pk-title">Настройки на сайта</h1>
          <p class="pk-sub"><code><?php echo html(panel_edit_hint((string)$sitePath)); ?></code></p>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./index.php">Назад</a>
          <a class="pk-btn pk-btn--ghost" href="./logout.php">Изход</a>
        </div>
      </div>

      <div class="pk-card pk-card--wide">
        <form method="post" action="">
          <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
          <input type="hidden" name="sha" value="<?php echo html((string)$sha); ?>" />
          <textarea class="pk-textarea" name="json"><?php echo html($pretty); ?></textarea>
          <button class="pk-btn" type="submit"><?php echo html(panel_save_button_label()); ?></button>

          <?php if ($error): ?>
            <div class="pk-err" role="alert"><?php echo html($error); ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="pk-ok" role="status"><?php echo html($success); ?></div>
          <?php endif; ?>
        </form>
      </div>
    </div>
<?php
panel_page_close();


<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/github.php";
require_once __DIR__ . "/_inc/ui.php";

require_login();

$cfg = panel_config();
$servicesPath = $cfg["files"]["services"] ?? "src/_data/services.json";

$error = "";
$success = "";
$sha = "";
$jsonText = "";

function normalize_services_json(string $jsonText): array {
  $data = json_decode($jsonText, true);
  if (!is_array($data)) return ["items" => []];
  if (array_key_exists("items", $data) && is_array($data["items"])) return $data;
  // Support legacy array format
  if (array_is_list($data)) return ["items" => $data];
  return ["items" => []];
}

// Load current file from GitHub
$fileRes = gh_get_file($servicesPath);
if (!$fileRes["ok"]) {
  $error = "Грешка при зареждане: " . ($fileRes["error"] ?? "неизвестна грешка");
} else {
  $contentB64 = $fileRes["data"]["content"] ?? "";
  $sha = (string)($fileRes["data"]["sha"] ?? "");
  $raw = base64_decode(str_replace(["\n", "\r"], "", (string)$contentB64), true);
  $jsonText = $raw !== false ? $raw : "";
}

// Handle save
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $incoming = (string)($_POST["json"] ?? "");
  $normalized = normalize_services_json($incoming);
  $pretty = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

  $saveSha = (string)($_POST["sha"] ?? "");
  if (!$saveSha) {
    $error = "Липсва SHA — презаредете страницата.";
  } else {
    $msg = "chore(cms): update services";
    $putRes = gh_update_file($servicesPath, $pretty, $saveSha, $msg);
    if (!$putRes["ok"]) {
      $error = "Грешка при запис: " . ($putRes["error"] ?? "неизвестна грешка");
    } else {
      $success = panel_save_success_message();
      // Refresh SHA + text
      $newSha = $putRes["data"]["content"]["sha"] ?? null;
      if (is_string($newSha) && $newSha) $sha = $newSha;
      $jsonText = $pretty;
    }
  }
}

$normalized = normalize_services_json($jsonText);
$pretty = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

panel_page_open("Услуги — админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <h1 class="pk-title">Услуги</h1>
          <p class="pk-sub"><code><?php echo html(panel_edit_hint((string)$servicesPath)); ?></code></p>
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

          <p class="pk-hint">Формат: <code>{ "items": [ ... ] }</code></p>
        </form>
      </div>
    </div>
<?php
panel_page_close();


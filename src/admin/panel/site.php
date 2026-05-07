<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/github.php";

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
  $error = "Failed to load from GitHub: " . ($fileRes["error"] ?? "Unknown error");
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
    $error = "Invalid JSON.";
  } else {
    $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    $saveSha = (string)($_POST["sha"] ?? "");
    if (!$saveSha) {
      $error = "Missing SHA (reload the page).";
    } else {
      $msg = "chore(cms): update site settings";
      $putRes = gh_update_file($sitePath, $pretty, $saveSha, $msg);
      if (!$putRes["ok"]) {
        $error = "Failed to save to GitHub: " . ($putRes["error"] ?? "Unknown error");
      } else {
        $success = "Saved. GitHub Actions will deploy shortly.";
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

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Site settings</title>
    <style>
      body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background: #0b1220; color: #e5e7eb; margin: 0; }
      .wrap { max-width: 1000px; margin: 0 auto; padding: 24px; }
      .top { display:flex; align-items:center; justify-content:space-between; gap: 12px; }
      .card { margin-top: 16px; padding: 14px; border-radius: 16px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); }
      textarea { width: 100%; min-height: 520px; resize: vertical; padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.18); background: rgba(0,0,0,0.2); color: #fff; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; line-height: 1.45; }
      button { margin-top: 10px; padding: 10px 12px; border-radius: 10px; border: 0; background: #22c55e; color: #052e16; font-weight: 800; cursor: pointer; }
      .msg { margin-top: 10px; font-size: 14px; }
      .err { color: #fecaca; }
      .ok { color: #bbf7d0; }
      .btn { display:inline-block; padding: 8px 10px; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); color: #e5e7eb; text-decoration: none; }
      code { color: #e5e7eb; }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="top">
        <div>
          <h1 style="margin:0; font-size: 20px;">Site settings</h1>
          <div style="opacity:0.8; font-size: 13px;">Edits commit to GitHub: <code><?php echo html((string)$sitePath); ?></code></div>
        </div>
        <div style="display:flex; gap: 8px;">
          <a class="btn" href="./index.php">Back</a>
          <a class="btn" href="./logout.php">Logout</a>
        </div>
      </div>

      <div class="card">
        <form method="post" action="">
          <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
          <input type="hidden" name="sha" value="<?php echo html((string)$sha); ?>" />
          <textarea name="json"><?php echo html($pretty); ?></textarea>
          <button type="submit">Save (commit to GitHub)</button>

          <?php if ($error): ?>
            <div class="msg err"><?php echo html($error); ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="msg ok"><?php echo html($success); ?></div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </body>
</html>


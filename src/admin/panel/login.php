<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";

ensure_session();

if (!empty($_SESSION["panel_logged_in"])) {
  redirect("/index.php");
}

$cfg = panel_config();
$cfgUser = (string)($cfg["username"] ?? "");
$cfgHash = trim((string)($cfg["password_hash"] ?? ""));
$hashLooksValid = panel_is_acceptable_password_hash($cfgHash);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $user = (string)($_POST["username"] ?? "");
  $pass = (string)($_POST["password"] ?? "");

  if (!$cfgUser || !$cfgHash || !$hashLooksValid) {
    $error = "Панелът не е настроен. Свържете се с администратор.";
  } elseif (hash_equals($cfgUser, $user) && password_verify($pass, $cfgHash)) {
    $_SESSION["panel_logged_in"] = true;
    redirect("/index.php");
  } else {
    $error = "Невалидно потребителско име или парола.";
  }
}

panel_page_open("Вход — админ панел");
?>
    <div class="pk-login-wrap">
      <form class="pk-card pk-form" method="post" action="">
        <div class="pk-eyebrow">Ритейл парк Раковски</div>
        <h1 class="pk-title">Админ панел</h1>

        <div class="pk-field">
          <label class="pk-label" for="username">Потребителско име</label>
          <input class="pk-input" id="username" name="username" autocomplete="username" required />
        </div>

        <div class="pk-field">
          <label class="pk-label" for="password">Парола</label>
          <input class="pk-input" id="password" name="password" type="password" autocomplete="current-password" required />
        </div>

        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <button class="pk-btn" type="submit">Вход</button>

        <?php if ($error): ?>
          <div class="pk-err" role="alert"><?php echo html($error); ?></div>
        <?php endif; ?>
      </form>
    </div>
<?php
panel_page_close();

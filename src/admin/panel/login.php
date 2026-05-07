<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";

ensure_session();

$cfg = panel_config();
$cfgUser = (string)($cfg["username"] ?? "");
$cfgHash = (string)($cfg["password_hash"] ?? "");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  verify_csrf($_POST["csrf"] ?? null);

  $user = (string)($_POST["username"] ?? "");
  $pass = (string)($_POST["password"] ?? "");

  if (!$cfgUser || !$cfgHash) {
    $error = "Panel is not configured. Create config.php on the server.";
  } elseif (hash_equals($cfgUser, $user) && password_verify($pass, $cfgHash)) {
    $_SESSION["panel_logged_in"] = true;
    redirect("/index.php");
  } else {
    $error = "Invalid credentials.";
  }
}

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login</title>
    <style>
      body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background: #0b1220; color: #e5e7eb; margin: 0; }
      .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
      .card { width: 100%; max-width: 420px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 20px; }
      label { display: block; font-size: 14px; margin-top: 12px; opacity: 0.9; }
      input { width: 100%; padding: 10px 12px; margin-top: 6px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.18); background: rgba(0,0,0,0.2); color: #fff; }
      button { margin-top: 16px; width: 100%; padding: 10px 12px; border-radius: 10px; border: 0; background: #22c55e; color: #052e16; font-weight: 700; cursor: pointer; }
      .err { margin-top: 12px; color: #fecaca; font-size: 14px; }
      .hint { margin-top: 10px; font-size: 12px; opacity: 0.75; }
      a { color: #93c5fd; }
    </style>
  </head>
  <body>
    <div class="wrap">
      <form class="card" method="post" action="">
        <h1 style="margin: 0 0 4px; font-size: 20px;">Admin panel</h1>
        <div style="opacity:0.85; font-size: 13px;">Login to edit site content.</div>

        <label>Username</label>
        <input name="username" autocomplete="username" required />

        <label>Password</label>
        <input name="password" type="password" autocomplete="current-password" required />

        <input type="hidden" name="csrf" value="<?php echo html(csrf_token()); ?>" />
        <button type="submit">Sign in</button>

        <?php if ($error): ?>
          <div class="err"><?php echo html($error); ?></div>
        <?php endif; ?>

        <div class="hint">
          If you see “not configured”, create <code>config.php</code> on the server next to this file.
        </div>
      </form>
    </div>
  </body>
</html>


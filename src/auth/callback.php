<?php

declare(strict_types=1);

session_start();

function load_config(): array {
  $cfg = [];

  $envId = getenv("GITHUB_CLIENT_ID");
  $envSecret = getenv("GITHUB_CLIENT_SECRET");
  $envScope = getenv("GITHUB_OAUTH_SCOPE");

  if ($envId) $cfg["github_client_id"] = $envId;
  if ($envSecret) $cfg["github_client_secret"] = $envSecret;
  if ($envScope) $cfg["github_scope"] = $envScope;

  $file = __DIR__ . "/config.php";
  if (file_exists($file)) {
    $fileCfg = require $file;
    if (is_array($fileCfg)) {
      $cfg = array_merge($cfg, $fileCfg);
    }
  }

  return $cfg;
}

function origin(): string {
  $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (($_SERVER["SERVER_PORT"] ?? "") === "443");
  $scheme = $https ? "https" : "http";
  $host = $_SERVER["HTTP_HOST"] ?? "localhost";
  return $scheme . "://" . $host;
}

function current_dir_url(): string {
  $scriptName = $_SERVER["SCRIPT_NAME"] ?? "";
  $dir = rtrim(str_replace("\\", "/", dirname($scriptName)), "/");
  return origin() . ($dir ? $dir : "");
}

function exchange_code_for_token(string $code, string $clientId, string $clientSecret, string $redirectUri, string $state): array {
  $ch = curl_init("https://github.com/login/oauth/access_token");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "User-Agent: decap-cms-oauth-php",
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    "client_id" => $clientId,
    "client_secret" => $clientSecret,
    "code" => $code,
    "redirect_uri" => $redirectUri,
    "state" => $state,
  ], "", "&", PHP_QUERY_RFC3986));

  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false) {
    return ["error" => "cURL error: " . $err];
  }

  $json = json_decode($raw, true);
  if (!is_array($json)) {
    return ["error" => "Invalid response from GitHub"];
  }
  if ($status >= 400) {
    return ["error" => $json["error_description"] ?? $json["error"] ?? "GitHub token exchange failed"];
  }
  return $json;
}

$cfg = load_config();
$clientId = $cfg["github_client_id"] ?? "";
$clientSecret = $cfg["github_client_secret"] ?? "";
if (!$clientId || !$clientSecret) {
  http_response_code(500);
  echo "OAuth not configured.";
  exit;
}

$code = $_GET["code"] ?? "";
$state = $_GET["state"] ?? "";
$expectedState = $_SESSION["oauth_state"] ?? "";

if (!$code) {
  http_response_code(400);
  echo "Missing code.";
  exit;
}

if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
  http_response_code(400);
  echo "Invalid state.";
  exit;
}

// One-time use
unset($_SESSION["oauth_state"]);

$redirectUri = current_dir_url() . "/callback.php";
$tokenRes = exchange_code_for_token($code, $clientId, $clientSecret, $redirectUri, $state);

if (!empty($tokenRes["error"])) {
  http_response_code(500);
  echo "OAuth error: " . htmlspecialchars((string)$tokenRes["error"], ENT_QUOTES, "UTF-8");
  exit;
}

$accessToken = (string)($tokenRes["access_token"] ?? "");
if (!$accessToken) {
  http_response_code(500);
  echo "OAuth error: missing access_token.";
  exit;
}

// Decap CMS expects this postMessage format:
// authorization:github:success:<json>
$payload = json_encode(["token" => $accessToken], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$msg = "authorization:github:success:" . $payload;

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Authorized</title>
  </head>
  <body>
    <script>
      (function () {
        var msg = <?php echo json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        if (window.opener) {
          window.opener.postMessage(msg, window.location.origin);
        }
        window.close();
      })();
    </script>
    <p>Authorized. You can close this window.</p>
  </body>
</html>


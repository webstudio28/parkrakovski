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

function json_response(int $status, array $body): void {
  http_response_code($status);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

// Decap CMS opens: {base_url}/{auth_endpoint}?provider=github
$provider = $_GET["provider"] ?? "github";
if ($provider !== "github") {
  json_response(400, ["error" => "Unsupported provider"]);
}

$cfg = load_config();
$clientId = $cfg["github_client_id"] ?? "";
$clientSecret = $cfg["github_client_secret"] ?? "";
$scope = $cfg["github_scope"] ?? "public_repo";

if (!$clientId || !$clientSecret) {
  json_response(500, [
    "error" => "OAuth not configured. Provide GITHUB_CLIENT_ID/GITHUB_CLIENT_SECRET or auth/config.php on server.",
  ]);
}

// CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION["oauth_state"] = $state;

$redirectUri = current_dir_url() . "/callback.php";

$params = http_build_query([
  "client_id" => $clientId,
  "redirect_uri" => $redirectUri,
  "scope" => $scope,
  "state" => $state,
], "", "&", PHP_QUERY_RFC3986);

header("Location: https://github.com/login/oauth/authorize?" . $params, true, 302);
exit;


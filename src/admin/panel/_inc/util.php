<?php

declare(strict_types=1);

function panel_normalize_config(array $cfg): array {
  if (isset($cfg["username"]) && is_string($cfg["username"])) {
    $cfg["username"] = trim($cfg["username"]);
  }
  if (isset($cfg["password_hash"]) && is_string($cfg["password_hash"])) {
    $cfg["password_hash"] = trim($cfg["password_hash"]);
  }
  return $cfg;
}

/**
 * bcrypt / password_hash hashes; password_get_info() can wrongly report algo 0 for some $2b$ strings.
 */
function panel_is_acceptable_password_hash(string $hash): bool {
  if ($hash === "") {
    return false;
  }

  $info = password_get_info($hash);
  if (($info["algo"] ?? 0) !== 0) {
    return true;
  }

  // bcrypt modular format (~60 chars): $2x$cost$salt+hash (53 chars base64-ish)
  return (bool) preg_match('#^\$2[a-z]+\$\d{2}\$[./A-Za-z0-9]{53}$#', $hash);
}

function panel_config(): array {
  $path = __DIR__ . "/../config.php";
  if (!file_exists($path)) {
    return [];
  }
  $cfg = require $path;
  if (!is_array($cfg)) {
    return [];
  }
  return panel_normalize_config($cfg);
}

function panel_base_path(): string {
  $scriptName = $_SERVER["SCRIPT_NAME"] ?? "";
  return rtrim(str_replace("\\", "/", dirname($scriptName)), "/");
}

function redirect(string $path): void {
  $base = panel_base_path();
  header("Location: " . ($base ? $base : "") . $path, true, 302);
  exit;
}

function html(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

function ensure_session(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
}

function require_login(): void {
  ensure_session();
  if (empty($_SESSION["panel_logged_in"])) {
    redirect("/login.php");
  }
}

function csrf_token(): string {
  ensure_session();
  if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(16));
  }
  return (string)$_SESSION["csrf"];
}

function verify_csrf(?string $token): void {
  ensure_session();
  $expected = $_SESSION["csrf"] ?? "";
  if (!$token || !$expected || !hash_equals((string)$expected, (string)$token)) {
    http_response_code(400);
    echo "Bad CSRF token.";
    exit;
  }
}


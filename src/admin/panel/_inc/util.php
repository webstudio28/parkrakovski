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

function panel_demo_local_config(): array {
  return [
    "username" => "admin",
    "password_hash" => '$2b$12$Yz.oJ7uW6GGdJqGWJ1/ZXuGzbAn8S998XEq637i9DCL2B9GTMC3pi',
    "files" => [
      "site" => "src/_data/site.config.json",
    ],
  ];
}

function panel_config_source_path(): ?string {
  $local = __DIR__ . "/../config.local.php";
  $prod = __DIR__ . "/../config.php";
  if (panel_is_local_dev() && file_exists($local)) {
    return $local;
  }
  if (file_exists($prod)) {
    return $prod;
  }
  return null;
}

function panel_config(): array {
  $path = panel_config_source_path();
  if ($path !== null) {
    $cfg = require $path;
    if (is_array($cfg)) {
      return panel_normalize_config($cfg);
    }
  }
  if (panel_is_local_dev()) {
    return panel_normalize_config(panel_demo_local_config());
  }
  return [];
}

/** Project root (parkrakovski/), not src/ */
function panel_repo_root(): string {
  $cfg = panel_config();
  if (!empty($cfg["repo_root"]) && is_string($cfg["repo_root"])) {
    return rtrim(str_replace("\\", "/", $cfg["repo_root"]), "/");
  }
  return dirname(__DIR__, 4);
}

function panel_is_local_dev(): bool {
  if (getenv("PANEL_LOCAL_DEV") === "1") {
    return true;
  }
  $host = strtolower((string)($_SERVER["HTTP_HOST"] ?? ""));
  $host = preg_replace("/:\d+$/", "", $host);
  return in_array($host, ["localhost", "127.0.0.1", "[::1]"], true);
}

function panel_save_success_message(): string {
  if (panel_is_local_dev()) {
    return "Промените са запазени. Презаредете сайта, за да ги видите.";
  }
  return "Промените са запазени. Ще бъдат отразени до няколко минути";
}

function panel_save_button_label(): string {
  return "Запази";
}

function panel_edit_hint(string $repoPath): string {
  return "";
}

/**
 * Strip technical/backend details from errors shown to editors.
 */
function panel_public_error(string $error, string $fallback = "Възникна грешка. Опитайте отново."): string {
  $error = trim($error);
  if ($error === "") {
    return $fallback;
  }

  if (
    str_contains($error, "променено")
    || str_contains($error, "Презаредете страницата")
  ) {
    return $error;
  }

  $lower = mb_strtolower($error);
  $blocked = [
    "github",
    "git ",
    "git-",
    "curl",
    "token",
    "api error",
    "api.",
    "repo",
    "commit",
    "chore(",
    "local-",
    "file not found",
    "could not",
    "invalid json",
    "src/_data",
    "src/assets",
    "on disk",
    "since load",
    " slug",
    "slug.",
  ];
  foreach ($blocked as $needle) {
    if (str_contains($lower, $needle)) {
      return $fallback;
    }
  }

  if (preg_match('/\bslug\b/i', $error)) {
    return $fallback;
  }

  if (!preg_match('/[а-яА-ЯёЁ]/u', $error)) {
    return $fallback;
  }

  return $error;
}

function panel_base_path(): string {
  $scriptName = $_SERVER["SCRIPT_NAME"] ?? "";
  return rtrim(str_replace("\\", "/", dirname($scriptName)), "/");
}

/**
 * Absolute URL path for a panel script (handles ./shop-edit.php and /news.php).
 */
function panel_url(string $path, array $query = []): string {
  $base = panel_base_path();
  $path = str_replace("\\", "/", $path);
  if (strncmp($path, "./", 2) === 0) {
    $path = substr($path, 2);
  }
  $path = ltrim($path, "/");
  $url = ($base !== "" ? $base : "") . "/" . $path;
  $url = preg_replace("#/+#", "/", $url) ?? $url;
  if ($query !== []) {
    $url .= "?" . http_build_query($query);
  }
  return $url;
}

function redirect(string $path): void {
  header("Location: " . panel_url($path), true, 302);
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
    echo "Невалиден CSRF токен.";
    exit;
  }
}


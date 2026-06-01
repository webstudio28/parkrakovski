<?php

declare(strict_types=1);

require_once __DIR__ . "/github.php";

function panel_file_key(string $key): string {
  $cfg = panel_config();
  $map = $cfg["files"] ?? [];
  $defaults = [
    "services" => "src/_data/services.json",
    "site" => "src/_data/site.config.json",
    "shops" => "src/_data/shops.json",
    "news" => "src/_data/news.json",
  ];
  return (string)($map[$key] ?? $defaults[$key] ?? "");
}

function panel_load_json_file(string $repoPath): array {
  $res = gh_get_file($repoPath);
  if (!$res["ok"]) {
    return ["ok" => false, "error" => $res["error"] ?? "Грешка при зареждане."];
  }
  $b64 = $res["data"]["content"] ?? "";
  $raw = base64_decode(str_replace(["\n", "\r"], "", (string)$b64), true);
  if ($raw === false) {
    return ["ok" => false, "error" => "Невалидни данни от файла."];
  }
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return ["ok" => false, "error" => "JSON файлът не е валиден обект."];
  }
  return [
    "ok" => true,
    "data" => $data,
    "sha" => (string)($res["data"]["sha"] ?? ""),
    "raw" => $raw,
  ];
}

function panel_save_json_file(string $repoPath, array $data, string $sha, string $commitMessage): array {
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    return ["ok" => false, "error" => "Грешка при кодиране на JSON."];
  }
  $payload = $json . "\n";
  return gh_update_file($repoPath, $payload, $sha, $commitMessage);
}

function panel_slugify(string $text): string {
  $text = trim(mb_strtolower($text, "UTF-8"));
  $map = [
    "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "zh",
    "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n",
    "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f",
    "х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sht", "ъ" => "a", "ь" => "",
    "ю" => "yu", "я" => "ya",
  ];
  $text = strtr($text, $map);
  $text = preg_replace("/[^a-z0-9]+/", "-", $text) ?? "";
  $text = trim($text, "-");
  return $text !== "" ? $text : "item";
}

function panel_post_string(string $key, string $default = ""): string {
  return trim((string)($_POST[$key] ?? $default));
}

function panel_post_bool(string $key): bool {
  return !empty($_POST[$key]);
}

/** Non-empty trimmed paths from a POST array field (e.g. gallery_image[]). */
function panel_post_path_list(string $key): array {
  $raw = $_POST[$key] ?? [];
  if (!is_array($raw)) {
    return [];
  }
  $out = [];
  foreach ($raw as $path) {
    $path = trim((string)$path);
    if ($path !== "") {
      $out[] = $path;
    }
  }
  return $out;
}

function panel_find_item_by_slug(array $items, string $slug): array {
  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }
    if ((string)($item["slug"] ?? "") === $slug) {
      return $item;
    }
  }
  return [];
}

/** Keep JSON keys not shown in the form; form values win on overlap. */
function panel_merge_entry(array $existing, array $fromForm): array {
  return array_merge($existing, $fromForm);
}

function panel_redirect_with(string $path, array $query = []): void {
  header("Location: " . panel_url($path, $query), true, 302);
  exit;
}

function panel_flash_set(string $type, string $message): void {
  ensure_session();
  $_SESSION["panel_flash"] = ["type" => $type, "message" => $message];
}

function panel_flash_get(): ?array {
  ensure_session();
  if (empty($_SESSION["panel_flash"])) {
    return null;
  }
  $flash = $_SESSION["panel_flash"];
  unset($_SESSION["panel_flash"]);
  return is_array($flash) ? $flash : null;
}

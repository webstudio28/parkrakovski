<?php

declare(strict_types=1);

require_once __DIR__ . "/github.php";

function panel_file_key(string $key): string {
  $cfg = panel_config();
  $map = $cfg["files"] ?? [];
  $defaults = [
    "site" => "src/_data/site.config.json",
    "shops" => "src/_data/shops.json",
    "news" => "src/_data/news.json",
    "home" => "src/_data/home.json",
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

function panel_site_brand_name(): string {
  static $cached = null;
  if ($cached !== null) {
    return $cached;
  }
  $cached = "Ритеил парк Раковски";
  $path = panel_file_key("site");
  $loaded = panel_load_json_file($path);
  if ($loaded["ok"]) {
    $name = trim((string)($loaded["data"]["brand"]["name"] ?? ""));
    if ($name !== "") {
      $cached = $name;
    }
  }
  return $cached;
}

/** @param string|array{src?: string, url?: string, alt?: string}|scalar|null $img */
function panel_gallery_image_path($img): string {
  if (is_string($img)) {
    return trim($img);
  }
  if (is_array($img)) {
    return trim((string)($img["src"] ?? $img["url"] ?? ""));
  }
  return "";
}

function panel_gallery_random_suffix(): string {
  $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
  $out = "";
  for ($i = 0; $i < 4; $i++) {
    $out .= $chars[random_int(0, strlen($chars) - 1)];
  }
  return $out;
}

function panel_gallery_alt_text(string $partnerName, string $siteName): string {
  return trim($partnerName) . " — " . trim($siteName) . " — " . panel_gallery_random_suffix();
}

/**
 * @param list<string> $paths
 * @param list<string|array{src?: string, alt?: string}> $existingImages
 * @return list<array{src: string, alt: string}>
 */
function panel_gallery_normalize_for_storage(array $paths, array $existingImages, string $partnerTitle): array {
  $siteName = panel_site_brand_name();
  $altByPath = [];
  foreach ($existingImages as $img) {
    $path = panel_gallery_image_path($img);
    if ($path === "") {
      continue;
    }
    if (is_array($img) && trim((string)($img["alt"] ?? "")) !== "") {
      $altByPath[$path] = trim((string)$img["alt"]);
    }
  }

  $out = [];
  foreach ($paths as $path) {
    $path = trim($path);
    if ($path === "") {
      continue;
    }
    $out[] = [
      "src" => $path,
      "alt" => $altByPath[$path] ?? panel_gallery_alt_text($partnerTitle, $siteName),
    ];
  }
  return $out;
}

/**
 * @param list<array{image: string, description: string}> $rows
 * @param list<array{image?: string, alt?: string, description?: string}> $existingPromotions
 * @return list<array{image: string, alt: string, description: string}>
 */
function panel_shop_promotions_max(): int {
  return 3;
}

function panel_shop_promo_description_max(): int {
  return 150;
}

function panel_news_default_date(): string {
  $months = [
    1 => "януари",
    2 => "февруари",
    3 => "март",
    4 => "април",
    5 => "май",
    6 => "юни",
    7 => "юли",
    8 => "август",
    9 => "септември",
    10 => "октомври",
    11 => "ноември",
    12 => "декември",
  ];
  $month = (int)date("n");
  return (int)date("j") . " " . ($months[$month] ?? "") . " " . date("Y");
}

function panel_news_permalink(string $slug): string {
  $slug = panel_slugify($slug);
  return $slug !== "" ? "/novini/" . $slug . "/" : "/novini/";
}

/** @param list<mixed> $promotions */
function panel_promotions_cap(array $promotions): array {
  return array_slice(array_values($promotions), 0, panel_shop_promotions_max());
}

function panel_promotions_normalize_for_storage(array $rows, array $existingPromotions, string $partnerTitle): array {
  if (!function_exists("panel_rich_html_truncate")) {
    require_once __DIR__ . "/rich-text.php";
  }
  $descMax = panel_shop_promo_description_max();
  $siteName = panel_site_brand_name();
  $altByImage = [];
  foreach ($existingPromotions as $promo) {
    if (!is_array($promo)) {
      continue;
    }
    $path = trim((string)($promo["image"] ?? ""));
    if ($path === "") {
      continue;
    }
    $alt = trim((string)($promo["alt"] ?? ""));
    if ($alt !== "") {
      $altByImage[$path] = $alt;
    }
  }

  $out = [];
  foreach ($rows as $row) {
    $image = trim((string)($row["image"] ?? ""));
    $description = (string)($row["description"] ?? "");
    if ($image === "" && $description === "") {
      continue;
    }
    $out[] = [
      "image" => $image,
      "alt" => $image !== "" ? ($altByImage[$image] ?? panel_gallery_alt_text($partnerTitle, $siteName)) : "",
      "description" => panel_rich_html_truncate($description, $descMax),
    ];
  }
  return $out;
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

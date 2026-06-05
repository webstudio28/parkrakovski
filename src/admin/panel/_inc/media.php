<?php

declare(strict_types=1);

require_once __DIR__ . "/panel-data.php";

function panel_uploads_repo_dir(): string {
  $cfg = panel_config();
  $dir = (string)($cfg["uploads_dir"] ?? "src/assets/images/uploads");
  return rtrim(str_replace("\\", "/", $dir), "/");
}

function panel_uploads_url_prefix(): string {
  $cfg = panel_config();
  $prefix = (string)($cfg["uploads_url_prefix"] ?? "/assets/images/uploads");
  return "/" . trim($prefix, "/");
}

function panel_uploads_abs_dir(): string {
  return panel_local_file_path(panel_uploads_repo_dir());
}

/** Eleventy output folder (PHP dev server document root). */
function panel_site_output_dir(): string {
  $cfg = panel_config();
  $dir = (string)($cfg["site_output_dir"] ?? "_site");
  return rtrim(str_replace("\\", "/", $dir), "/");
}

/** Copy upload into _site so previews work on the PHP dev server (127.0.0.1:8081). */
function panel_mirror_upload_for_local_preview(string $repoPath, string $bytes): void {
  if (!panel_is_local_dev()) {
    return;
  }
  $name = basename(str_replace("\\", "/", $repoPath));
  if ($name === "" || $name === "." || $name === "..") {
    return;
  }
  $urlRel = ltrim(panel_uploads_url_prefix(), "/") . "/" . $name;
  $siteRepoPath = panel_site_output_dir() . "/" . $urlRel;
  $absPath = panel_local_file_path($siteRepoPath);
  $dir = dirname($absPath);
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    return;
  }
  file_put_contents($absPath, $bytes);
}

function panel_upload_max_bytes(): int {
  return 800 * 1024;
}

function panel_upload_max_kb(): int {
  return 800;
}

/** @return list<string> */
function panel_upload_allowed_extensions(): array {
  return ["jpg", "jpeg", "png", "webp"];
}

function panel_upload_formats_label(): string {
  return "JPG, JPEG, PNG и WebP";
}

function panel_upload_rules_hint(): string {
  return "Позволени формати: " . panel_upload_formats_label() . ". Максимален размер: " . panel_upload_max_kb() . " KB.";
}

function panel_upload_accept_attr(): string {
  return ".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp";
}

function panel_allowed_upload_mimes(): array {
  return [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/x-png" => "png",
    "image/webp" => "webp",
  ];
}

/** Rules injected into panel.js so client validation matches the server. */
function panel_upload_client_config(): array {
  return [
    "maxBytes" => panel_upload_max_bytes(),
    "maxKb" => panel_upload_max_kb(),
    "extensions" => panel_upload_allowed_extensions(),
    "mimes" => array_keys(panel_allowed_upload_mimes()),
    "formatError" => panel_upload_format_error(),
  ];
}

function panel_upload_size_error(int $bytes): string {
  $kb = max(1, (int)ceil($bytes / 1024));
  return "Снимката е твърде голяма ({$kb} KB). Максималният размер е " . panel_upload_max_kb() . " KB.";
}

function panel_upload_format_error(): string {
  return "Невалиден формат. Позволени са само " . panel_upload_formats_label() . " снимки.";
}

function panel_upload_file_extension(string $filename): string {
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  return $ext === "jpeg" ? "jpg" : $ext;
}

function panel_upload_validate_file(array $file): ?string {
  $errorCode = (int)($file["error"] ?? UPLOAD_ERR_NO_FILE);
  if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
    return panel_upload_size_error((int)($file["size"] ?? panel_upload_max_bytes() + 1));
  }
  if ($errorCode !== UPLOAD_ERR_OK) {
    return "Грешка при качване на файла. Опитайте отново.";
  }

  $size = (int)($file["size"] ?? 0);
  if ($size <= 0) {
    return "Файлът е празен. Изберете валидна снимка.";
  }
  if ($size > panel_upload_max_bytes()) {
    return panel_upload_size_error($size);
  }

  $originalName = (string)($file["name"] ?? "");
  $ext = panel_upload_file_extension($originalName);
  if ($ext === "" || !in_array($ext, panel_upload_allowed_extensions(), true)) {
    return panel_upload_format_error();
  }

  $tmp = (string)($file["tmp_name"] ?? "");
  if ($tmp === "" || !is_uploaded_file($tmp)) {
    return "Невалиден качен файл. Опитайте отново.";
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? (string)finfo_file($finfo, $tmp) : "";
  if ($finfo) {
    finfo_close($finfo);
  }
  if (!isset(panel_allowed_upload_mimes()[$mime])) {
    return panel_upload_format_error();
  }

  return null;
}

function panel_upload_image(array $file, string $prefix = "img"): array {
  $validationError = panel_upload_validate_file($file);
  if ($validationError !== null) {
    return ["ok" => false, "error" => $validationError];
  }

  $tmp = (string)($file["tmp_name"] ?? "");

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? (string)finfo_file($finfo, $tmp) : "";
  if ($finfo) {
    finfo_close($finfo);
  }
  $ext = panel_allowed_upload_mimes()[$mime] ?? panel_upload_file_extension((string)($file["name"] ?? ""));
  $prefix = panel_slugify($prefix);
  $name = $prefix . "-" . date("Ymd-His") . "-" . bin2hex(random_bytes(4)) . "." . $ext;
  $repoPath = panel_uploads_repo_dir() . "/" . $name;
  $absPath = panel_local_file_path($repoPath);
  $dir = dirname($absPath);
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    return ["ok" => false, "error" => "Неуспешно създаване на папка за качвания."];
  }

  $bytes = file_get_contents($tmp);
  if ($bytes === false) {
    return ["ok" => false, "error" => "Неуспешно четене на файла."];
  }

  if (panel_is_local_dev()) {
    if (file_put_contents($absPath, $bytes) === false) {
      return ["ok" => false, "error" => "Грешка при качване. Опитайте отново."];
    }
    panel_mirror_upload_for_local_preview($repoPath, $bytes);
  } else {
    $put = gh_put_binary_file($repoPath, $bytes, "chore(cms): upload " . $name);
    if (!$put["ok"]) {
      return ["ok" => false, "error" => panel_public_error((string)($put["error"] ?? ""), "Грешка при качване. Опитайте отново.")];
    }
  }

  $urlPath = panel_uploads_url_prefix() . "/" . $name;
  return ["ok" => true, "path" => $urlPath, "repoPath" => $repoPath];
}

function gh_put_binary_file(string $repoPath, string $bytes, string $message): array {
  [$owner, $repo, $branch] = gh_repo_info();
  if (!$owner || !$repo) {
    return ["ok" => false, "error" => "Грешка при качване. Опитайте отново."];
  }

  $existing = gh_api("GET", "/repos/$owner/$repo/contents/" . rawurlencode($repoPath) . "?ref=" . rawurlencode($branch));
  $sha = null;
  if ($existing["ok"] && is_array($existing["data"])) {
    $sha = $existing["data"]["sha"] ?? null;
  }

  $payload = [
    "message" => $message,
    "content" => base64_encode($bytes),
    "branch" => $branch,
  ];
  if (is_string($sha) && $sha !== "") {
    $payload["sha"] = $sha;
  }

  return gh_api("PUT", "/repos/$owner/$repo/contents/" . rawurlencode($repoPath), $payload);
}

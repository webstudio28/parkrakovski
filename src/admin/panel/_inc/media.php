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

function panel_allowed_upload_mimes(): array {
  return [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
    "image/gif" => "gif",
    "image/svg+xml" => "svg",
  ];
}

function panel_upload_image(array $file, string $prefix = "img"): array {
  if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return ["ok" => false, "error" => "Грешка при качване на файла."];
  }
  $maxBytes = 8 * 1024 * 1024;
  if (($file["size"] ?? 0) > $maxBytes) {
    return ["ok" => false, "error" => "Файлът е твърде голям (макс. 8 MB)."];
  }

  $tmp = (string)($file["tmp_name"] ?? "");
  if ($tmp === "" || !is_uploaded_file($tmp)) {
    return ["ok" => false, "error" => "Невалиден качен файл."];
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? (string)finfo_file($finfo, $tmp) : "";
  if ($finfo) {
    finfo_close($finfo);
  }
  $allowed = panel_allowed_upload_mimes();
  if (!isset($allowed[$mime])) {
    return ["ok" => false, "error" => "Позволени формати: JPG, PNG, WebP, GIF, SVG."];
  }

  $ext = $allowed[$mime];
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
      return ["ok" => false, "error" => "Неуспешен запис на диска."];
    }
    panel_mirror_upload_for_local_preview($repoPath, $bytes);
  } else {
    $put = gh_put_binary_file($repoPath, $bytes, "chore(cms): upload " . $name);
    if (!$put["ok"]) {
      return ["ok" => false, "error" => $put["error"] ?? "Грешка при качване към GitHub."];
    }
  }

  $urlPath = panel_uploads_url_prefix() . "/" . $name;
  return ["ok" => true, "path" => $urlPath, "repoPath" => $repoPath];
}

function gh_put_binary_file(string $repoPath, string $bytes, string $message): array {
  [$owner, $repo, $branch] = gh_repo_info();
  if (!$owner || !$repo) {
    return ["ok" => false, "error" => "GitHub не е конфигуриран."];
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

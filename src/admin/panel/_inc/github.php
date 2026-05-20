<?php

declare(strict_types=1);

require_once __DIR__ . "/util.php";

function gh_api(string $method, string $path, ?array $body = null): array {
  $cfg = panel_config();
  $token = $cfg["github"]["token"] ?? "";
  if (!$token) {
    return ["ok" => false, "status" => 500, "error" => "GitHub token missing in panel config."];
  }

  $url = "https://api.github.com" . $path;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter([
    "Accept: application/vnd.github+json",
    "X-GitHub-Api-Version: 2022-11-28",
    "Authorization: Bearer " . $token,
    "User-Agent: parkrakovski-panel",
    "Content-Type: application/json",
  ]));

  if ($body !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }

  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false) {
    return ["ok" => false, "status" => 500, "error" => "cURL error: " . $err];
  }

  $json = json_decode($raw, true);
  if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
    return ["ok" => false, "status" => $status, "error" => "Invalid JSON from GitHub."];
  }

  if ($status >= 400) {
    $msg = is_array($json) ? ($json["message"] ?? "GitHub API error") : "GitHub API error";
    return ["ok" => false, "status" => $status, "error" => $msg, "details" => $json];
  }

  return ["ok" => true, "status" => $status, "data" => $json];
}

function gh_repo_info(): array {
  $cfg = panel_config();
  $owner = $cfg["github"]["owner"] ?? "";
  $repo = $cfg["github"]["repo"] ?? "";
  $branch = $cfg["github"]["branch"] ?? "main";
  return [$owner, $repo, $branch];
}

function panel_local_file_path(string $repoPath): string {
  $rel = str_replace(["\\", "/"], DIRECTORY_SEPARATOR, ltrim($repoPath, "/\\"));
  return panel_repo_root() . DIRECTORY_SEPARATOR . $rel;
}

function panel_local_file_sha(string $fullPath): string {
  if (!is_file($fullPath)) {
    return "local-missing";
  }
  return "local-" . md5_file($fullPath);
}

function gh_get_file(string $repoPath): array {
  if (panel_is_local_dev()) {
    $full = panel_local_file_path($repoPath);
    if (!is_file($full)) {
      return ["ok" => false, "error" => "File not found locally: " . $repoPath];
    }
    $raw = file_get_contents($full);
    if ($raw === false) {
      return ["ok" => false, "error" => "Could not read local file."];
    }
    return [
      "ok" => true,
      "status" => 200,
      "data" => [
        "content" => base64_encode($raw),
        "sha" => panel_local_file_sha($full),
      ],
    ];
  }

  [$owner, $repo, $branch] = gh_repo_info();
  if (!$owner || !$repo) {
    return ["ok" => false, "error" => "GitHub repo not configured."];
  }
  $p = rawurlencode($repoPath);
  return gh_api("GET", "/repos/$owner/$repo/contents/$p?ref=" . rawurlencode($branch));
}

function gh_update_file(string $repoPath, string $contentUtf8, string $sha, string $message): array {
  if (panel_is_local_dev()) {
    $full = panel_local_file_path($repoPath);
    $dir = dirname($full);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
      return ["ok" => false, "error" => "Could not create directory for file."];
    }
    if (is_file($full) && panel_local_file_sha($full) !== $sha) {
      return [
        "ok" => false,
        "error" => "File changed on disk since load. Reload the page and try again.",
      ];
    }
    if (file_put_contents($full, $contentUtf8) === false) {
      return ["ok" => false, "error" => "Could not write local file."];
    }
    return [
      "ok" => true,
      "status" => 200,
      "data" => ["content" => ["sha" => panel_local_file_sha($full)]],
    ];
  }

  [$owner, $repo, $branch] = gh_repo_info();
  $payload = [
    "message" => $message,
    "content" => base64_encode($contentUtf8),
    "sha" => $sha,
    "branch" => $branch,
  ];
  $p = rawurlencode($repoPath);
  return gh_api("PUT", "/repos/$owner/$repo/contents/$p", $payload);
}


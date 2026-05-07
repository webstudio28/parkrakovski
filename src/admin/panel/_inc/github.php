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

function gh_get_file(string $repoPath): array {
  [$owner, $repo, $branch] = gh_repo_info();
  if (!$owner || !$repo) {
    return ["ok" => false, "error" => "GitHub repo not configured."];
  }
  $p = rawurlencode($repoPath);
  return gh_api("GET", "/repos/$owner/$repo/contents/$p?ref=" . rawurlencode($branch));
}

function gh_update_file(string $repoPath, string $contentUtf8, string $sha, string $message): array {
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


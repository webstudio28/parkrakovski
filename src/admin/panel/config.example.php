<?php
/**
 * Copy this file to `config.php` ON THE SERVER ONLY (do not commit secrets).
 *
 * Server path: `root/parkrakovski/admin/panel/config.php`
 *
 * Local dev (http://127.0.0.1:8081): copy config.local.example.php to
 * config.local.php — login admin / 1234, saves write to src/_data/ on disk.
 */

return [
  // Login
  "username" => "admin",
  // Generate with PHP: password_hash("YOUR_PASSWORD", PASSWORD_DEFAULT)
  "password_hash" => "REPLACE_ME",

  // GitHub commit settings
  "github" => [
    "owner" => "webstudio28",
    "repo" => "parkrakovski",
    // Branch to push commits to (usually "main")
    "branch" => "main",
    // Fine-grained PAT recommended (Contents: Read & Write). Store server-only.
    "token" => "REPLACE_ME"
  ],

  // Files editable via this panel (repo paths)
  "files" => [
    "site" => "src/_data/site.config.json",
    "shops" => "src/_data/shops.json",
    "news" => "src/_data/news.json",
  ],

  "uploads_dir" => "src/assets/images/uploads",
  "uploads_url_prefix" => "/assets/images/uploads",
];


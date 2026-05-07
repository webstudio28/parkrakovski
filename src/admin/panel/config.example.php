<?php
/**
 * Copy this file to `config.php` ON THE SERVER ONLY (do not commit secrets).
 *
 * Server path (as you described): `root/parkrakovski/admin/panel/config.php`
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
    "services" => "src/_data/services.json",
    "site" => "src/_data/site.config.json"
  ]
];


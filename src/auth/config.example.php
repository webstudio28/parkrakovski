<?php
/**
 * Copy this to config.php on the SERVER ONLY (do not commit secrets).
 * On cPanel, place it under: public_html/parkrakovski/auth/config.php
 */

return [
  // Create a GitHub OAuth App and put its credentials here.
  "github_client_id" => "REPLACE_ME",
  "github_client_secret" => "REPLACE_ME",

  // Use "public_repo" if your repo is public, "repo" if private.
  "github_scope" => "public_repo",
];


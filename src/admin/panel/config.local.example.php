<?php
/**
 * Local dev only — copy to config.local.php (gitignored).
 * Login: admin / 1234
 * Saves write to src/_data/ on disk (no GitHub token needed).
 */

return [
  "username" => "admin",
  "password_hash" => '$2b$12$Yz.oJ7uW6GGdJqGWJ1/ZXuGzbAn8S998XEq637i9DCL2B9GTMC3pi',

  "files" => [
    "services" => "src/_data/services.json",
    "site" => "src/_data/site.config.json",
  ],
];

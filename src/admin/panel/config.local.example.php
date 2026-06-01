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
    "site" => "src/_data/site.config.json",
    "shops" => "src/_data/shops.json",
    "news" => "src/_data/news.json",
    "home" => "src/_data/home.json",
  ],

  "uploads_dir" => "src/assets/images/uploads",
  "uploads_url_prefix" => "/assets/images/uploads",
];

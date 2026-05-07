<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";

require_login();

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin panel</title>
    <style>
      body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background: #0b1220; color: #e5e7eb; margin: 0; }
      .wrap { max-width: 900px; margin: 0 auto; padding: 24px; }
      .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
      .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 18px; }
      .card { display: block; padding: 14px; border-radius: 16px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: inherit; text-decoration: none; }
      .card:hover { border-color: rgba(34,197,94,0.5); }
      a { color: #93c5fd; }
      .btn { display: inline-block; padding: 8px 10px; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); color: #e5e7eb; text-decoration: none; }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="top">
        <div>
          <h1 style="margin:0; font-size: 20px;">Admin panel</h1>
          <div style="opacity:0.8; font-size: 13px;">Edits commit to GitHub, then CI deploys.</div>
        </div>
        <a class="btn" href="./logout.php">Logout</a>
      </div>

      <div class="cards">
        <a class="card" href="./services.php">
          <div style="font-weight: 700;">Services</div>
          <div style="opacity:0.8; font-size: 13px; margin-top: 6px;">Edit `src/_data/services.json`</div>
        </a>
        <a class="card" href="./site.php">
          <div style="font-weight: 700;">Site settings</div>
          <div style="opacity:0.8; font-size: 13px; margin-top: 6px;">Edit `src/_data/site.config.json`</div>
        </a>
      </div>
    </div>
  </body>
</html>


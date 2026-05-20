<?php

declare(strict_types=1);

function panel_styles(): void {
  ?>
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      background: linear-gradient(160deg, #0f172a 0%, #0c1929 45%, #0a3d4a 100%);
      color: #e2e8f0;
      margin: 0;
      min-height: 100vh;
      line-height: 1.5;
    }

    a {
      color: #7dd3fc;
      text-decoration: none;
    }
    a:hover {
      color: #bae6fd;
    }

    .pk-wrap {
      max-width: 1000px;
      margin: 0 auto;
      padding: 1.5rem;
    }

    .pk-login-wrap {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 1.5rem;
    }

    .pk-card {
      width: 100%;
      max-width: 26rem;
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 1.25rem;
      padding: 1.75rem 1.5rem;
      box-shadow: 0 24px 48px rgba(2, 6, 23, 0.35);
      overflow: hidden;
    }

    .pk-card--wide {
      max-width: none;
      margin-top: 1rem;
      padding: 1.25rem;
    }

    .pk-eyebrow {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      margin-bottom: 0.35rem;
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: #b5961d;
    }

    .pk-eyebrow::before {
      content: "";
      width: 1.75rem;
      height: 1px;
      background: #b5961d;
    }

    .pk-title {
      margin: 0;
      font-size: 1.45rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #f8fafc;
    }

    .pk-sub {
      margin: 0.35rem 0 0;
      font-size: 0.88rem;
      color: #94a3b8;
    }

    .pk-form {
      margin-top: 1.35rem;
    }

    .pk-field {
      margin-top: 1rem;
    }

    .pk-field:first-of-type {
      margin-top: 0;
    }

    .pk-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      color: #cbd5e1;
      margin-bottom: 0.4rem;
    }

    .pk-input {
      display: block;
      width: 100%;
      max-width: 100%;
      margin: 0;
      padding: 0.65rem 0.85rem;
      border-radius: 0.65rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(15, 23, 42, 0.55);
      color: #f8fafc;
      font: inherit;
      line-height: 1.4;
    }

    .pk-input:focus {
      outline: 2px solid rgba(0, 100, 132, 0.65);
      outline-offset: 1px;
      border-color: #006484;
    }

    .pk-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-top: 1.25rem;
      width: 100%;
      padding: 0.7rem 1rem;
      border: 0;
      border-radius: 9999px;
      background: #006484;
      color: #fff;
      font: inherit;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      transition: filter 0.15s ease, transform 0.15s ease;
    }

    .pk-btn:hover {
      filter: brightness(1.1);
    }

    .pk-btn--ghost {
      width: auto;
      margin-top: 0;
      padding: 0.5rem 0.9rem;
      border-radius: 0.65rem;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.16);
      color: #e2e8f0;
      font-weight: 600;
      font-size: 0.85rem;
    }

    .pk-btn--ghost:hover {
      background: rgba(255, 255, 255, 0.12);
      filter: none;
    }

    .pk-err {
      margin-top: 1rem;
      padding: 0.65rem 0.85rem;
      border-radius: 0.65rem;
      background: rgba(127, 29, 29, 0.35);
      border: 1px solid rgba(248, 113, 113, 0.35);
      color: #fecaca;
      font-size: 0.88rem;
    }

    .pk-ok {
      margin-top: 1rem;
      padding: 0.65rem 0.85rem;
      border-radius: 0.65rem;
      background: rgba(6, 78, 59, 0.35);
      border: 1px solid rgba(74, 222, 128, 0.35);
      color: #bbf7d0;
      font-size: 0.88rem;
    }

    .pk-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .pk-top__actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .pk-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 0.85rem;
      margin-top: 1.25rem;
    }

    .pk-tile {
      display: block;
      padding: 1.1rem 1.15rem;
      border-radius: 1rem;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: inherit;
      text-decoration: none;
      transition: border-color 0.2s ease, transform 0.2s ease;
    }

    .pk-tile:hover {
      border-color: rgba(181, 150, 29, 0.55);
      transform: translateY(-2px);
    }

    .pk-tile__title {
      font-weight: 700;
      font-size: 1.05rem;
    }

    .pk-tile__meta {
      margin-top: 0.4rem;
      font-size: 0.8rem;
      color: #94a3b8;
    }

    .pk-textarea {
      display: block;
      width: 100%;
      max-width: 100%;
      min-height: 520px;
      resize: vertical;
      margin: 0;
      padding: 0.75rem;
      border-radius: 0.75rem;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(15, 23, 42, 0.55);
      color: #f1f5f9;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.75rem;
      line-height: 1.45;
    }

    .pk-textarea:focus {
      outline: 2px solid rgba(0, 100, 132, 0.65);
      outline-offset: 1px;
    }

    .pk-hint {
      margin-top: 0.65rem;
      font-size: 0.78rem;
      color: #64748b;
    }

    code {
      font-size: 0.85em;
      color: #cbd5e1;
    }
  </style>
  <?php
}

function panel_page_open(string $title): void {
  ?>
<!doctype html>
<html lang="bg">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo html($title); ?></title>
    <?php panel_styles(); ?>
  </head>
  <body>
  <?php
}

function panel_page_close(): void {
  ?>
  </body>
</html>
  <?php
}

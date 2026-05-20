<?php

declare(strict_types=1);

require_once __DIR__ . "/panel-data.php";

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

    .pk-btn:hover:not(:disabled) {
      filter: brightness(1.1);
    }

    .pk-btn:disabled {
      opacity: 0.42;
      cursor: not-allowed;
      filter: none;
      box-shadow: none;
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

    .pk-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-top: 1.25rem;
    }

    .pk-toolbar__left,
    .pk-toolbar__right {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      align-items: center;
    }

    .pk-btn--sm {
      width: auto;
      margin-top: 0;
      padding: 0.45rem 0.85rem;
      font-size: 0.82rem;
    }

    .pk-btn--danger {
      background: #b91c1c;
    }

    .pk-btn--danger:hover {
      filter: brightness(1.08);
    }

    .pk-section {
      margin-top: 1.25rem;
      padding: 1.15rem 1.2rem;
      border-radius: 1rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .pk-section__title {
      margin: 0 0 1rem;
      font-size: 0.95rem;
      font-weight: 700;
      color: #f1f5f9;
    }

    .pk-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.85rem;
    }

    @media (max-width: 720px) {
      .pk-grid-2 {
        grid-template-columns: 1fr;
      }
    }

    .pk-list {
      display: grid;
      gap: 0.65rem;
      margin-top: 1rem;
    }

    .pk-list-item {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      padding: 0.85rem 1rem;
      border-radius: 0.85rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      text-decoration: none;
      color: inherit;
      transition: border-color 0.15s ease, background 0.15s ease;
    }

    .pk-list-item:hover {
      border-color: rgba(181, 150, 29, 0.45);
      background: rgba(255, 255, 255, 0.08);
    }

    .pk-list-item__thumb {
      width: 3.25rem;
      height: 3.25rem;
      border-radius: 0.65rem;
      object-fit: cover;
      background: rgba(15, 23, 42, 0.6);
      flex-shrink: 0;
    }

    .pk-list-item__body {
      flex: 1;
      min-width: 0;
    }

    .pk-list-item__title {
      font-weight: 700;
      font-size: 0.95rem;
    }

    .pk-list-item__meta {
      margin-top: 0.2rem;
      font-size: 0.78rem;
      color: #94a3b8;
    }

    .pk-repeater {
      display: grid;
      gap: 0.85rem;
    }

    .pk-repeater-item {
      padding: 1rem;
      border-radius: 0.85rem;
      border: 1px dashed rgba(255, 255, 255, 0.18);
      background: rgba(15, 23, 42, 0.35);
    }

    .pk-repeater-item__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      margin-bottom: 0.75rem;
    }

    .pk-media {
      display: grid;
      gap: 0.65rem;
    }

    .pk-media__preview {
      width: 100%;
      max-width: 14rem;
      aspect-ratio: 3 / 4;
      border-radius: 0.75rem;
      object-fit: cover;
      background: rgba(15, 23, 42, 0.55);
      border: 1px solid rgba(255, 255, 255, 0.14);
    }

    .pk-media__preview--wide {
      max-width: 100%;
      aspect-ratio: 16 / 10;
    }

    .pk-media__row {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      align-items: center;
    }

    .pk-media__path {
      flex: 1;
      min-width: 12rem;
      font-size: 0.78rem;
      color: #94a3b8;
      word-break: break-all;
    }

    .pk-file {
      font-size: 0.78rem;
      color: #cbd5e1;
    }

    .pk-actions-inline {
      display: flex;
      gap: 0.35rem;
      flex-wrap: wrap;
    }

    .pk-empty {
      margin-top: 1rem;
      padding: 1.25rem;
      text-align: center;
      color: #94a3b8;
      border-radius: 0.85rem;
      border: 1px dashed rgba(255, 255, 255, 0.15);
    }
  </style>
  <?php
}

function panel_save_button(): void {
  ?>
  <button class="pk-btn" type="submit" data-pk-save disabled><?php echo html(panel_save_button_label()); ?></button>
  <?php
}

function panel_flash_render(): void {
  $flash = function_exists("panel_flash_get") ? panel_flash_get() : null;
  if (!$flash) {
    return;
  }
  $class = ($flash["type"] ?? "") === "ok" ? "pk-ok" : "pk-err";
  echo '<div class="' . html($class) . '" role="status">' . html((string)($flash["message"] ?? "")) . '</div>';
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

function panel_page_close(bool $withScripts = true): void {
  if ($withScripts) {
    echo '<script src="./assets/panel.js"></script>';
  }
  ?>
  </body>
</html>
  <?php
}

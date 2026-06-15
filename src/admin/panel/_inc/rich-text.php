<?php

declare(strict_types=1);

/** Curated Font Awesome 6 icons (familiar “phone UI” set). */
function panel_rich_icon_catalog(): array {
  return [
    ["fa-solid fa-star", "Звезда"],
    ["fa-solid fa-heart", "Сърце"],
    ["fa-solid fa-thumbs-up", "Харесвам"],
    ["fa-solid fa-check", "Отметка"],
    ["fa-solid fa-circle-info", "Инфо"],
    ["fa-solid fa-bell", "Известие"],
    ["fa-solid fa-gift", "Подарък"],
    ["fa-solid fa-tag", "Етикет"],
    ["fa-solid fa-percent", "Процент"],
    ["fa-solid fa-fire", "Горещо"],
    ["fa-solid fa-sparkles", "Ново"],
    ["fa-solid fa-clock", "Часове"],
    ["fa-solid fa-calendar-days", "Календар"],
    ["fa-solid fa-phone", "Телефон"],
    ["fa-solid fa-location-dot", "Местоположение"],
    ["fa-solid fa-truck", "Доставка"],
    ["fa-solid fa-cart-shopping", "Пазаруване"],
    ["fa-solid fa-euro-sign", "Цена"],
    ["fa-solid fa-ticket", "Билет"],
    ["fa-solid fa-sun", "Слънце"],
    ["fa-solid fa-snowflake", "Зима"],
    ["fa-solid fa-leaf", "Еко"],
    ["fa-solid fa-utensils", "Храна"],
    ["fa-solid fa-shirt", "Мода"],
    ["fa-solid fa-house", "Дом"],
    ["fa-solid fa-wifi", "Wi‑Fi"],
    ["fa-solid fa-square-parking", "Паркинг"],
    ["fa-solid fa-wheelchair", "Достъп"],
    ["fa-solid fa-baby", "Деца"],
    ["fa-solid fa-paw", "Любимци"],
    ["fa-solid fa-music", "Музика"],
    ["fa-solid fa-camera", "Снимка"],
  ];
}

function panel_sanitize_rich_html(string $html): string {
  $html = trim($html);
  if ($html === "") {
    return "";
  }

  $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', "", $html) ?? "";
  $html = str_replace(["\r\n", "\r"], "\n", $html);

  $icons = [];
  $html = preg_replace_callback(
    '#<i\s+class="(fa-(?:solid|regular)\s+fa-[a-z0-9-]+)"\s*(?:aria-hidden="true")?\s*></i>#i',
    static function (array $m) use (&$icons): string {
      $key = "%%PKICON" . count($icons) . "%%";
      $icons[$key] = '<i class="' . $m[1] . '" aria-hidden="true"></i>';
      return $key;
    },
    $html,
  ) ?? $html;

  $html = strip_tags($html, "<b><strong><i><em><u><br>");
  $html = preg_replace('#<(/?)(b|strong|i|em|u|br)\b[^>]*>#i', '<$1$2>', $html) ?? $html;
  $html = preg_replace('#<(?!/?(b|strong|i|em|u|br)\b)[^>]+>#i', "", $html) ?? $html;

  foreach ($icons as $key => $markup) {
    $html = str_replace($key, $markup, $html);
  }

  return trim($html);
}

function panel_rich_plain_text(string $html): string {
  $html = panel_sanitize_rich_html($html);
  if ($html === "") {
    return "";
  }
  $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, "UTF-8");
  $plain = preg_replace("/\s+/u", " ", $plain) ?? "";
  return trim($plain);
}

function panel_rich_plain_length(string $html): int {
  return mb_strlen(panel_rich_plain_text($html));
}

function panel_rich_html_truncate(string $html, int $maxPlainChars): string {
  if ($maxPlainChars < 1) {
    return panel_sanitize_rich_html($html);
  }
  $sanitized = panel_sanitize_rich_html($html);
  $plain = panel_rich_plain_text($sanitized);
  if (mb_strlen($plain) <= $maxPlainChars) {
    return $sanitized;
  }
  $plain = mb_substr($plain, 0, $maxPlainChars);
  $plain = rtrim($plain);
  return panel_sanitize_rich_html(htmlspecialchars($plain, ENT_QUOTES | ENT_HTML5, "UTF-8"));
}

function panel_post_rich_html(string $key, string $default = ""): string {
  return panel_sanitize_rich_html(panel_post_string($key, $default));
}

function panel_post_rich_html_list(string $key): array {
  $raw = $_POST[$key] ?? [];
  if (!is_array($raw)) {
    return [];
  }
  $out = [];
  foreach ($raw as $item) {
    $out[] = panel_sanitize_rich_html(trim((string)$item));
  }
  return $out;
}

/**
 * Full-featured body sanitizer — allows block structure, images, and links.
 * Used exclusively for the news article body field (Quill output).
 */
function panel_sanitize_body_html(string $html, bool $allowDataImages = false): string {
  $html = trim($html);
  if ($html === "") {
    return "";
  }

  $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', "", $html) ?? "";
  $html = str_replace(["\r\n", "\r"], "\n", $html);

  // Strip everything except the allowed structural + inline tags.
  $allowed = "<p><h2><h3><h4><ul><ol><li><blockquote><br><strong><b><em><i><u><s><a><img><hr>";
  $html = strip_tags($html, $allowed);

  // Strip all attributes from simple block/inline tags.
  $html = preg_replace(
    '#<(/?)(p|h2|h3|h4|ul|ol|li|blockquote|hr|b|strong|em|i|u|s|br)\b[^>]*>#i',
    '<$1$2>',
    $html,
  ) ?? $html;

  // <a> — keep only a safe href; add target/rel for external URLs.
  $html = preg_replace_callback(
    '#<a\b([^>]*)>#i',
    static function (array $m): string {
      $attrs = $m[1];
      $href = "";
      if (preg_match('#\bhref=["\']?(https?://[^\s"\'<>]+|/[^\s"\'<>]*)["\'"]?#i', $attrs, $hm)) {
        $url = $hm[1];
        $href = ' href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, "UTF-8") . '"';
        if (preg_match('#^https?://#i', $url)) {
          $href .= ' target="_blank" rel="noopener noreferrer"';
        }
      }
      return $href ? "<a{$href}>" : "";
    },
    $html,
  ) ?? $html;

  // <img> — saved content uses /assets/images/. Preview may also allow data:image URLs.
  $html = preg_replace_callback(
    '#<img\b([^>]*)/?>#i',
    static function (array $m) use ($allowDataImages): string {
      $attrs = $m[1];
      $src = "";
      $alt = "";
      if (preg_match('#\bsrc=["\']?(/assets/images/[^\s"\'<>]*)["\'"]?#i', $attrs, $sm)) {
        $src = ' src="' . htmlspecialchars($sm[1], ENT_QUOTES | ENT_HTML5, "UTF-8") . '"';
      } elseif ($allowDataImages && preg_match('#\bsrc=["\']?(data:image/(?:png|jpe?g|webp);base64,[A-Za-z0-9+/=]+)["\']?#i', $attrs, $sm)) {
        $src = ' src="' . htmlspecialchars($sm[1], ENT_QUOTES | ENT_HTML5, "UTF-8") . '"';
      }
      if (preg_match('#\balt=["\']([^"\'<>]*)["\'"]?#i', $attrs, $am)) {
        $alt = ' alt="' . htmlspecialchars($am[1], ENT_QUOTES | ENT_HTML5, "UTF-8") . '"';
      }
      return $src ? '<img' . $src . $alt . ' loading="lazy">' : "";
    },
    $html,
  ) ?? $html;

  return trim($html);
}

function panel_post_body_html(string $key, string $default = ""): string {
  return panel_sanitize_body_html(panel_post_string($key, $default));
}

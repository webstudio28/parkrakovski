<?php

declare(strict_types=1);

/** @return array<string, string> */
function panel_shop_day_labels(): array {
  return [
    "mon" => "Понеделник",
    "tue" => "Вторник",
    "wed" => "Сряда",
    "thu" => "Четвъртък",
    "fri" => "Петък",
    "sat" => "Събота",
    "sun" => "Неделя",
  ];
}

/** @return list<string> */
function panel_shop_day_keys(): array {
  return array_keys(panel_shop_day_labels());
}

/** @return array<string, array{closed: bool, open: string, close: string}> */
function panel_shop_hours_empty(): array {
  $out = [];
  foreach (panel_shop_day_keys() as $key) {
    $out[$key] = ["closed" => false, "open" => "", "close" => ""];
  }
  return $out;
}

/**
 * @param mixed $hours
 * @return array<string, array{closed: bool, open: string, close: string}>
 */
function panel_shop_hours_normalize($hours): array {
  $base = panel_shop_hours_empty();
  if (is_array($hours)) {
    foreach (panel_shop_day_keys() as $key) {
      if (!isset($hours[$key]) || !is_array($hours[$key])) {
        continue;
      }
      $day = $hours[$key];
      $base[$key] = [
        "closed" => !empty($day["closed"]),
        "open" => panel_shop_time_normalize((string)($day["open"] ?? "")),
        "close" => panel_shop_time_normalize((string)($day["close"] ?? "")),
      ];
    }
    return $base;
  }
  if (is_string($hours) && trim($hours) !== "") {
    return panel_shop_hours_from_legacy_string($hours);
  }
  return $base;
}

function panel_shop_time_normalize(string $value): string {
  $value = trim($value);
  if ($value === "") {
    return "";
  }
  if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
    $h = max(0, min(23, (int)$m[1]));
    $min = max(0, min(59, (int)$m[2]));
    return sprintf("%02d:%02d", $h, $min);
  }
  return $value;
}

/**
 * @return array<string, array{closed: bool, open: string, close: string}>
 */
function panel_shop_hours_from_legacy_string(string $raw): array {
  $schedule = panel_shop_hours_empty();
  $parts = preg_split('/\s*\|\s*/', $raw) ?: [];
  foreach ($parts as $part) {
    $part = trim($part);
    if ($part === "") {
      continue;
    }
    if (!preg_match('/^([^:]+):\s*(.+)$/u', $part, $m)) {
      continue;
    }
    $label = mb_strtolower(trim($m[1]), "UTF-8");
    $times = trim($m[2]);
    if (preg_match('/затвор/i', $times)) {
      foreach (panel_shop_days_matching_label($label) as $key) {
        $schedule[$key]["closed"] = true;
      }
      continue;
    }
    if (!preg_match('/(\d{1,2}:\d{2})\s*[–\-—]\s*(\d{1,2}:\d{2})/u', $times, $tm)) {
      continue;
    }
    $open = panel_shop_time_normalize($tm[1]);
    $close = panel_shop_time_normalize($tm[2]);
    foreach (panel_shop_days_matching_label($label) as $key) {
      $schedule[$key] = ["closed" => false, "open" => $open, "close" => $close];
    }
  }
  return $schedule;
}

/**
 * @return list<string>
 */
function panel_shop_days_matching_label(string $label): array {
  $map = [
    "пон" => ["mon"],
    "вто" => ["tue"],
    "сря" => ["wed"],
    "чет" => ["thu"],
    "пет" => ["fri"],
    "съб" => ["sat"],
    "нед" => ["sun"],
    "пон–съб" => ["mon", "tue", "wed", "thu", "fri", "sat"],
    "пон-съб" => ["mon", "tue", "wed", "thu", "fri", "sat"],
    "пон–нед" => panel_shop_day_keys(),
    "пон-нед" => panel_shop_day_keys(),
    "делнични" => ["mon", "tue", "wed", "thu", "fri"],
  ];
  foreach ($map as $needle => $keys) {
    if (strpos($label, $needle) !== false) {
      return $keys;
    }
  }
  return [];
}

/** @return array<string, array{closed: bool, open: string, close: string}> */
function panel_post_shop_hours(): array {
  $schedule = panel_shop_hours_empty();
  $open = $_POST["hours_open"] ?? [];
  $close = $_POST["hours_close"] ?? [];
  $closed = $_POST["hours_closed"] ?? [];
  if (!is_array($open)) {
    $open = [];
  }
  if (!is_array($close)) {
    $close = [];
  }
  if (!is_array($closed)) {
    $closed = [];
  }
  foreach (panel_shop_day_keys() as $key) {
    $isClosed = !empty($closed[$key]);
    $openTime = panel_shop_time_normalize((string)($open[$key] ?? ""));
    $closeTime = panel_shop_time_normalize((string)($close[$key] ?? ""));
    if ($isClosed || ($openTime === "" && $closeTime === "")) {
      $schedule[$key] = ["closed" => true, "open" => "", "close" => ""];
    } else {
      $schedule[$key] = ["closed" => false, "open" => $openTime, "close" => $closeTime];
    }
  }
  return $schedule;
}

/** Strip empty days before saving JSON. */
function panel_shop_hours_for_storage(array $schedule): array {
  $out = [];
  foreach ($schedule as $key => $day) {
    if (!is_array($day)) {
      continue;
    }
    if (!empty($day["closed"])) {
      $out[$key] = ["closed" => true];
      continue;
    }
    $open = (string)($day["open"] ?? "");
    $close = (string)($day["close"] ?? "");
    if ($open !== "" && $close !== "") {
      $out[$key] = ["open" => $open, "close" => $close];
    }
  }
  return $out;
}

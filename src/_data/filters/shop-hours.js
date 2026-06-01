const DAY_ORDER = [
  ["mon", "Пон"],
  ["tue", "Вт"],
  ["wed", "Ср"],
  ["thu", "Чет"],
  ["fri", "Пет"],
  ["sat", "Съб"],
  ["sun", "Нед"],
];

function formatShopHours(hours) {
  if (!hours) return [];

  if (typeof hours === "string") {
    const trimmed = hours.trim();
    if (!trimmed) return [];
    return trimmed
      .split("|")
      .map((part) => part.trim())
      .filter(Boolean);
  }

  if (typeof hours !== "object") return [];

  const lines = [];
  for (const [key, short] of DAY_ORDER) {
    const day = hours[key];
    if (!day || typeof day !== "object") continue;
    if (day.closed) {
      lines.push(`${short}: затворено`);
      continue;
    }
    const open = String(day.open || "").trim();
    const close = String(day.close || "").trim();
    if (open && close) {
      lines.push(`${short}: ${open} – ${close}`);
    }
  }
  return lines;
}

module.exports = { formatShopHours };

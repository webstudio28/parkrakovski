const fs = require("fs");
const path = require("path");

/** Fisher–Yates shuffle (new array). Order changes on each build. */
function shuffle(items) {
  const out = items.slice();
  for (let i = out.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [out[i], out[j]] = [out[j], out[i]];
  }
  return out;
}

function hasPromoContent(promo) {
  if (!promo || typeof promo !== "object") return false;
  const image = String(promo.image || "").trim();
  const desc = String(promo.description || "").replace(/<[^>]*>/g, "").trim();
  return image !== "" || desc !== "";
}

/**
 * All partner promotions for the homepage carousel, shuffled at build time.
 * @returns {{ shop: object, promo: object }[]}
 */
module.exports = function homePromotions() {
  const shopsPath = path.join(__dirname, "shops.json");
  const raw = fs.readFileSync(shopsPath, "utf8");
  const shops = JSON.parse(raw);
  const items = [];

  for (const shop of shops.items || []) {
    if (!shop || !Array.isArray(shop.promotions)) continue;
    for (const promo of shop.promotions) {
      if (!hasPromoContent(promo)) continue;
      items.push({ shop, promo });
    }
  }

  return shuffle(items);
};

function stripTrailingSlash(url) {
  return String(url ?? "").replace(/\/$/, "");
}

function truncateChars(value, max = 160) {
  const text = String(value ?? "").replace(/\s+/g, " ").trim();
  if (text.length <= max) {
    return text;
  }
  return text.slice(0, max - 1).trimEnd() + "…";
}

function absoluteUrl(pageUrl, siteUrl) {
  const origin = stripTrailingSlash(siteUrl);
  let path = String(pageUrl ?? "/");
  if (!path.startsWith("/")) {
    path = `/${path}`;
  }
  return `${origin}${path}`;
}

function absoluteAssetUrl(assetPath, siteUrl) {
  const path = String(assetPath ?? "").trim();
  if (path === "") {
    return "";
  }
  if (/^https?:\/\//i.test(path)) {
    return path;
  }
  return absoluteUrl(path.startsWith("/") ? path : `/${path}`, siteUrl);
}

function hasValue(value) {
  const text = String(value ?? "").trim();
  return text !== "" && text !== "#";
}

module.exports = {
  stripTrailingSlash,
  truncateChars,
  absoluteUrl,
  absoluteAssetUrl,
  hasValue,
};

const fs = require("fs");
const path = require("path");

function readJsonFile(relPath) {
  try {
    const full = path.join(__dirname, relPath);
    return JSON.parse(fs.readFileSync(full, "utf8"));
  } catch {
    return null;
  }
}

// Always read from disk — avoid `require(".json")` which Node caches and can show
// stale `nav.header` during Eleventy watch/incremental builds.
const config = readJsonFile("site.config.json") || {};

function loadJson(name) {
  return readJsonFile(name);
}

const servicesRaw = loadJson("services.json");
const services = Array.isArray(servicesRaw)
  ? servicesRaw
  : (servicesRaw?.items ?? []);

function resolveChildrenFrom(type) {
  if (type === "services") {
    return services.map((s) => ({
      label: s.title,
      url: `/services/${s.slug}/`,
    }));
  }
  return [];
}

function resolveNavHeader(header) {
  if (!Array.isArray(header)) return [];
  return header.map((item) => {
    const { childrenFrom, ...rest } = item;
    const out = { ...rest };

    if (Array.isArray(item.children) && item.children.length) {
      out.children = item.children;
      return out;
    }

    if (childrenFrom) {
      out.children = resolveChildrenFrom(childrenFrom);
      return out;
    }

    return out;
  });
}

module.exports = {
  ...config,
  currentYear: new Date().getFullYear(),
  nav: {
    ...config.nav,
    header: resolveNavHeader(config.nav?.header || []),
  },
};


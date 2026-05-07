const path = require("path");

const config = require(path.join(__dirname, "site.config.json"));

function loadJson(name) {
  try {
    // eslint-disable-next-line global-require, import/no-dynamic-require
    return require(path.join(__dirname, name));
  } catch {
    return null;
  }
}

const services = loadJson("services.json") || [];

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


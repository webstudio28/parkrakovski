module.exports = function (eleventyConfig) {
  // Passthrough copy: src/assets → _site/assets
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });
  // Favicon at site root
  eleventyConfig.addPassthroughCopy({ "src/favicon.png": "favicon.png" });
  eleventyConfig.addPassthroughCopy({ "src/.htaccess": ".htaccess" });
  // Admin area (panel, optional other tools)
  eleventyConfig.addPassthroughCopy({ "src/admin": "admin" });

  // UTF-8 BOM helps some browsers/tools when charset headers are missing
  eleventyConfig.addTransform("utf8PlainText", (content, outputPath) => {
    if (outputPath && /\.txt$/i.test(outputPath)) {
      return content.charCodeAt(0) === 0xfeff ? content : `\uFEFF${content}`;
    }
    return content;
  });

  // Handy for sitemap/footers if needed
  eleventyConfig.addGlobalData("buildDate", () =>
    new Date().toISOString().slice(0, 10),
  );

  eleventyConfig.addFilter("stripHtml", (value) =>
    String(value ?? "").replace(/<[^>]*>/g, "").replace(/\s+/g, " ").trim(),
  );

  const { formatShopHours } = require("./src/_data/filters/shop-hours.js");
  eleventyConfig.addFilter("formatShopHours", formatShopHours);

  const {
    stripTrailingSlash,
    truncateChars,
    absoluteUrl,
    absoluteAssetUrl,
    hasValue,
  } = require("./src/_data/filters/seo.js");
  eleventyConfig.addFilter("stripTrailingSlash", stripTrailingSlash);
  eleventyConfig.addFilter("truncateChars", truncateChars);
  eleventyConfig.addFilter("absoluteUrl", absoluteUrl);
  eleventyConfig.addFilter("absoluteAssetUrl", absoluteAssetUrl);
  eleventyConfig.addFilter("hasValue", hasValue);

  eleventyConfig.addCollection("sitemapEntries", (collectionApi) =>
    collectionApi.getAll().filter((item) => {
      if (!item.url) {
        return false;
      }
      if (item.data.sitemap === false) {
        return false;
      }
      if (item.data.permalink === false) {
        return false;
      }
      if (item.data.eleventyExcludeFromCollections) {
        return false;
      }
      if (item.url.startsWith("/admin")) {
        return false;
      }
      if (item.url.startsWith("/services")) {
        return false;
      }
      if (item.url.startsWith("/about")) {
        return false;
      }
      const out = String(item.outputPath || "");
      if (out.endsWith(".txt") || out.endsWith(".xml")) {
        return false;
      }
      return true;
    }),
  );

  // Dev server: smoother live reload + small typing debounce so
  // saving partial/malformed JSON doesn't immediately crash the build
  eleventyConfig.setServerOptions({
    port: 8080,
    showAllHosts: false,
    domDiff: true,
    showVersion: false,
    watch: ["./_site/assets/css/**/*.css"],
  });

  eleventyConfig.setWatchThrottleWaitTime(150);
  eleventyConfig.setQuietMode(true);

  // pathPrefix for subfolder hosting
  const pathPrefix = process.env.PATH_PREFIX || "/";

  return {
    dir: {
      input: "src",
      output: "_site",
      includes: "_includes",
      layouts: "_layouts",
      data: "_data",
    },
    pathPrefix,
    templateFormats: ["njk", "html", "md"],
    htmlTemplateEngine: "njk",
    markdownTemplateEngine: "njk",
  };
};


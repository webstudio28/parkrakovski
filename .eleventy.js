module.exports = function (eleventyConfig) {
  // Passthrough copy: src/assets → _site/assets
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });

  // Handy for sitemap/footers if needed
  eleventyConfig.addGlobalData("buildDate", () =>
    new Date().toISOString().slice(0, 10),
  );

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


module.exports = function (eleventyConfig) {
  // Passthrough copy: src/assets → _site/assets
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });
  // Favicon at site root
  eleventyConfig.addPassthroughCopy({ "src/favicon.svg": "favicon.svg" });
  // Admin area (panel, optional other tools)
  eleventyConfig.addPassthroughCopy({ "src/admin": "admin" });

  // Handy for sitemap/footers if needed
  eleventyConfig.addGlobalData("buildDate", () =>
    new Date().toISOString().slice(0, 10),
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


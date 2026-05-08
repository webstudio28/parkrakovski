/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{njk,html,md,js}"],
  theme: {
    extend: {
      colors: {
        brand: {
          bg: "var(--color-bg)",
          blue: "var(--color-brand-blue)",
          teal: "var(--color-brand-teal)",
          yellow: "var(--color-brand-yellow)"
        }
      }
    },
  },
  plugins: [],
};


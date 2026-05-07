## Base 11ty site

Minimal Eleventy starter with:

- `src/` input + `_site/` output
- Nunjucks templates (`.njk`)
- Data-driven navigation via `src/_data/site.config.json` + `src/_data/site.js`
- Tailwind CSS via CLI

### Install

```bash
npm install
```

### Dev

Runs Tailwind watch + 11ty dev server:

```bash
npm run dev
```

### Build

```bash
npm run build
```

### Deploy (cPanel via GitHub Actions)

This project deploys to hosting (not GitHub Pages) using `.github/workflows/deploy.yml`.

Required GitHub repository secrets:

- `CPANEL_HOST` (FTP/FTPS host)
- `CPANEL_USER` (FTP username)
- `CPANEL_PASS` (FTP password)

Optional secrets:

- `CPANEL_PROTOCOL` (`ftp` or `ftps`, default: `ftp`)
- `CPANEL_PORT` (default: `21`)
- `CPANEL_SERVER_DIR` (default: `parkrakovski/`)

On every push to `main`, the workflow builds `_site` with `PATH_PREFIX=/parkrakovski` and uploads it to the configured cPanel directory.


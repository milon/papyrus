---
title: build:site
---

# build:site

Build a multi-page static site: Home index, one HTML page per chapter,
sidebar navigation, light / dark mode, and Prev / Next links.

```bash
papyrus build:site
papyrus build:site -d examples/the-papyrus-handbook -e docs
```

`papyrus build` does **not** run this command — site output is opt-in.

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Parent directory for the site folder |

## Output

```text
export/<slug>-site/
  index.html
  404.html
  <chapter-slug>.html
  .nojekyll
  CNAME              # when site.cname is set
  assets/site.css
  assets/site.js
  assets/fonts/…
  assets/<banner>   # when configured
```

Point any static host (GitHub Pages, Netlify, S3, …) at that folder.
`.nojekyll` tells GitHub Pages not to run Jekyll. `CNAME` (from `site.cname`)
sets a custom domain. GitHub Pages and Netlify serve `404.html` for missing
URLs.

## Site config

Optional `site` block in `papyrus.php`:

```php
'site' => [
    'banner' => 'banner.jpg',           // under assets/; auto-detects banner.jpg / banner.png
    'repository' => 'https://github.com/you/your-book',
    'lead' => 'A one-line pitch for the home page.',
    'cname' => 'docs.example.com',      // GitHub Pages custom domain
],
```

| Key | Default | Meaning |
|-----|---------|---------|
| `banner` | `banner.jpg` then `banner.png` if present | Hero image on Home |
| `repository` | unset | Repo URL; GitHub hosts also get Packagist / Issues links when detected |
| `lead` | unset | Short pitch under the title on Home |
| `cname` | unset | Writes `CNAME` in the site root for a GitHub Pages custom domain |

## Hosting this handbook

In the Papyrus repository, pushes to `main` rebuild the handbook site and
publish it to `gh-pages` via `.github/workflows/pages.yml`. Locally:

```bash
composer build:handbook
# or just:
papyrus build:site -d examples/the-papyrus-handbook -e docs
```

## Mermaid on the site

With `mermaid.theme => auto`, each diagram embeds light and dark SVG
variants; CSS swaps them with `data-theme`. Diagrams stay within the
reading column (same max-width ladder as the HTML theme).

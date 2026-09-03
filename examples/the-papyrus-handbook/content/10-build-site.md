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

`papyrus build` does **not** run this by default — pass `--with-site` on
`build`, or call `build:site` directly.

Preview the site (including sidebar search) with PHP's built-in server:

```bash
papyrus serve
papyrus serve --build
papyrus serve --port 8080
papyrus serve -s docs/the-papyrus-handbook-site
papyrus serve -d examples/the-papyrus-handbook -e docs --build
```

By default `serve` reads `export/<slug>-site/`. Pass `--site` / `-s` to point at
another folder (for example the handbook under `docs/`) — no book project is
required in that case. `--export` / `-e` still changes the default parent
(`<export>/<slug>-site`). Pass `--build` to run `build:site` first (into that
same site directory; needs `-d` / a book root). If `site.base_path` is set on a
loaded project, the printed URL includes that prefix so `<base href>` and
`assets/search.json` resolve correctly.

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
  sitemap.xml
  robots.txt
  .nojekyll
  CNAME              # when site.cname is set
  assets/site.css
  assets/site.js
  assets/search.json
  assets/fonts/…
  assets/<banner>   # when configured
```

Point any static host (GitHub Pages, Netlify, S3, …) at that folder.
`.nojekyll` tells GitHub Pages not to run Jekyll. `CNAME` (from `site.cname`)
sets a custom domain. GitHub Pages and Netlify serve `404.html` for missing
URLs. The sidebar includes client-side search (`/` focuses the box).
`sitemap.xml` uses `https://{cname}` when `site.cname` is set, otherwise the
`site.base_path` prefix. `robots.txt` points at the sitemap when a CNAME is
configured.

## Site config

Optional `site` block in `papyrus.php`:

```php
'site' => [
    'banner' => 'banner.jpg',           // under assets/; auto-detects banner.jpg / banner.png
    'lead' => 'A one-line pitch for the home page.',
    'cname' => 'docs.example.com',      // GitHub Pages custom domain
    'base_path' => '/my-repo',          // project Pages under github.io/my-repo/; omit with cname
    'links' => [
        ['label' => 'Downloads', 'chapter' => '19-downloads.md'],
        ['label' => 'Source on GitHub', 'url' => 'https://github.com/you/your-book'],
    ],
],
```

| Key | Default | Meaning |
|-----|---------|---------|
| `banner` | `banner.jpg` then `banner.png` if present | Hero image on Home |
| `lead` | unset | Short pitch under the title on Home |
| `cname` | unset | Writes `CNAME` in the site root for a GitHub Pages custom domain |
| `base_path` | unset | URL prefix for project Pages (writes `<base href="/prefix/">`) |
| `links` | unset | Home-page links; each item needs `label` plus either `url` or `chapter` |

Nothing is inferred from chapter titles. If you want a Downloads link on Home,
add it explicitly with `['label' => 'Downloads', 'chapter' => '19-downloads.md']`.

Use `base_path` when the site is not at the domain root (for example
`https://user.github.io/my-repo/`). Leave it unset when using a custom domain
via `cname`.

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

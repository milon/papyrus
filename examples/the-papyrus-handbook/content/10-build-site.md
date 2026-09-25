---
title: build:site
---

# build:site

Build a multi-page static site: Home index, one HTML page per chapter,
sidebar navigation, light / dark mode, Prev / Next links, and an
**On this page** outline of `h2` / `h3` headings on wide screens.

```bash
papyrus build:site
papyrus build:site -d examples/the-papyrus-handbook -e docs
```

`papyrus build` does **not** run this by default — pass `--with-site` on
`build`, or call `build:site` directly.

Preview the site (including popup search) with PHP's built-in server:

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

| Option     | Short | Default           | Meaning                              |
|------------|-------|-------------------|--------------------------------------|
| `--dir`    | `-d`  | current directory | Book root                            |
| `--export` | `-e`  | `export/`         | Parent directory for the site folder |

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
URLs. Press <kbd>/</kbd> or the topbar search button for popup search
(↑/↓ moves through results, Enter opens the hit, Escape closes).
Results are ranked: title and heading matches outrank body hits, whole-word
matches beat substrings, and pretoc chapters are demoted so Welcome/copyright
noise sinks. Excerpts centre on the first matching term.
`sitemap.xml` uses `https://{cname}` when `site.cname` is set, otherwise the
`site.base_path` prefix. `robots.txt` points at the sitemap when a CNAME is
configured. Each `##` heading gets an id and a `#` permalink; search hits for
those headings open the chapter at that section.

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

| Key          | Default                                   | Meaning                                                                 |
|--------------|-------------------------------------------|-------------------------------------------------------------------------|
| `banner`     | `banner.jpg` then `banner.png` if present | Hero image on Home                                                      |
| `lead`       | unset                                     | Short pitch under the title on Home                                     |
| `cname`      | unset                                     | Writes `CNAME` in the site root for a GitHub Pages custom domain        |
| `base_path`  | unset                                     | URL prefix for project Pages (writes `<base href="/prefix/">`)          |
| `links`      | unset                                     | Home-page links; each item needs `label` plus either `url` or `chapter` |
| `nav`        | unset (filename order)                    | Grouped sidebar sections; see below                                     |
| `repository` | unset                                     | GitHub URL; used with `edit` for **Edit this page**                     |
| `edit`       | unset                                     | `{ link, path, branch }` — see Edit on GitHub below                     |
| `copy_code`  | `true`                                    | Set `false` to hide the Copy control on fences                          |
| `page_toc`   | `true`                                    | Set `false` to hide the **On this page** rail                           |
| `version`    | unset                                     | Current label for the version switcher                                  |
| `versions`   | unset                                     | Peer deploys for the version switcher (needs 2+)                        |

### Sidebar groups (`site.nav`)

```php
'site' => [
    'nav' => [
        [
            'group' => 'Getting started',
            'chapters' => ['01-install.md', '02-usage.md'],
        ],
        [
            'group' => 'Reference',
            'chapters' => ['03-api.md', '04-changelog.md'],
        ],
    ],
],
```

Chapter names match the same way as `links.chapter` (filename, stem, or path).
When `nav` is set, the sidebar shows labeled groups and Prev/Next follow that
order. Chapters not listed still appear under **More**. Omit `nav` to keep the
default natural filename order. Group headings are collapsible (chevron toggle);
open/closed state is remembered in the browser, and the group containing the
current page always starts expanded.

### On this page

Chapter pages get a sticky right-rail outline on wide viewports (Laravel-style:
list icon, left border rail, active-section indicator). When the chapter has
`##` / `###` headings, those appear as links; when it has none, the rail still
shows with the page title linking to the top. Set `site.page_toc: false` to hide
the rail entirely.

### Edit on GitHub

```php
'site' => [
    'repository' => 'https://github.com/milon/papyrus',
    'edit' => [
        'link' => true, // default false
        'path' => 'examples/the-papyrus-handbook/content',
        'branch' => 'master', // optional; default main
    ],
],
```

Each chapter page then shows **Edit this page** (pencil icon) linking to the
file on GitHub when `edit.link` is `true`. This handbook uses the settings
above. `init --preset=docs` fills `repository` / `edit` from `composer.json`
when possible (often `path` → `docs/content`, `branch` → `main`, `link` → `true`).

### Copy on code fences

Site pages wrap fenced code blocks with an icon **Copy** button (clipboard API).
Set `site.copy_code: false` to disable it.

### Version switcher

Each matrix version is its own Papyrus build (own `content/` + `base_path`).
List peers so the sidebar shows a version select:

```php
'site' => [
    'base_path' => '/barcode/v12',
    'version' => 'v12',
    'versions' => [
        ['label' => 'v12', 'path' => '/barcode/v12'],
        ['label' => 'v11', 'path' => '/barcode/v11'],
        // Or absolute: ['label' => 'v10', 'url' => 'https://docs.example.com/v10'],
    ],
],
```

| Entry key | Required              | Meaning                                                   |
|-----------|-----------------------|-----------------------------------------------------------|
| `label`   | yes                   | Shown in the select (also accepted as `version`)          |
| `path`    | one of `path` / `url` | Absolute site path prefix (same rules as `base_path`)     |
| `url`     | one of `path` / `url` | Full `https://…` override when peers live on another host |

Current build: match `site.version` to a label, else match `site.base_path` to a
`path`, else the first entry. The control appears only when `versions` has two
or more valid entries.

Switching keeps the same page filename when it exists in the other build
(e.g. `01-install.html` → `/barcode/v11/01-install.html`); otherwise it opens
that version’s home. Use the same chapter filenames across versions when you
can.

### Images and other assets

`build:site` copies everything under project `assets/` into the site’s
`assets/` folder **except** `fonts/` (fonts are published separately). Put
screenshots at `assets/examples/foo.png` and reference them from Markdown as
`assets/examples/foo.png` so they work with `site.base_path` and `<base href>`.

### Home CTA

The home page primary button prefers a chapter whose slug or title contains
“install”, “quick start”, or “getting started”; otherwise it skips a
Welcome/`00-` chapter and links to the next page. Secondary `links` stay below
the CTA (GitHub, Packagist, …). Author is shown when `author` is set on the
project.

Nothing is inferred from chapter titles for `links`. If you want a Downloads link on Home,
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

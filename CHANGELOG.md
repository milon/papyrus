# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0]

### Added

- `papyrus.phar` release artifact (Box, ~47MB gzipped) — download and run with PHP 8.2+; no Composer project required (`composer build:phar` / `PAPYRUS_PHAR_VERSION=1.5.0`)

## [1.4.0] - 2026-09-24

### Added

- Icon buttons for code **Copy** and **Edit this page**
- `site.copy_code` / `site.page_toc` (default `true`) and `site.edit.link` (default `false`) to toggle those controls
- `site.edit` nests `link` / `path` / `branch` for **Edit this page** (legacy flat `edit_*` keys still read)
- **On this page** rail always renders on chapter pages when enabled — pages without `##` / `###` show the page title as the only entry
- Unified `build:site` home (former docs layout): **Get started** CTA, optional author, `links` below; `site.mode` ignored
- Collapsible `site.nav` sidebar groups (remembered in the browser; active group stays open)
- **Copy as Markdown** and **Print this page** on chapter pages (`site.copy_markdown` / `site.print_page`, default `true`)

## [1.3.2] - 2026-09-24

### Fixed

- Site and single-file HTML: constrain Markdown images to the reading column (`max-width: 100%`) so wide screenshots no longer overflow into the **On this page** rail

## [1.3.0] - 2026-09-24

### Added

- `init --preset=docs` — documentation-site scaffold (YAML by default, starter chapters, no KDP/sample; reads nearby `composer.json` for title/links/`base_path`/edit URLs)
- `site.mode: docs` — package-style home page (Get started CTA, secondary GitHub/Packagist links; skips “Start reading”)
- `site.nav` — grouped sidebar sections; orders Prev/Next; unlisted chapters under More
- Site chapter pages: sticky **On this page** outline for `h2` / `h3` (wide screens; Laravel-style rail); `###` headings get permalink anchors too
- `import-readme` — split a `README.md` into `content/` chapters on `##` headings (`--file`, `--force`, `--dry-run`)
- `site.repository` / `site.edit` (`link`, `path`, `branch`) — **Edit this page** on chapters (`link` defaults to `false`; docs preset sets `link: true`)
- Copy button on site code fences
- Docs preset GitHub Pages workflow stub builds with `-e docs` and deploys via `actions/deploy-pages`
- `site.versions` (+ optional `site.version`) — sidebar version switcher across peer deploys (`path` or absolute `url`)
- `build:site` copies project `assets/` (except `fonts/`) into the site so example images and other files resolve

### Fixed

- Code **Copy** control no longer overlaps long fences in the narrowed article column

## [1.2.0] - 2026-09-08

### Added

- Alternate book config formats: `papyrus.yml` / `papyrus.yaml` / `papyrus.json` (auto-discovered; only one allowed)
- `init --format=php|yml|json` and `migrate-ibis --format=php|yml|json` (default `php`)

## [1.1.0] - 2026-09-04

### Added

- Wraparound paperback cover generation (`kdp:cover --wrap`, `kdp --wrap`) → `*-kdp-print-wrap.pdf` / `.png`
- Optional `kdp.print.back_cover` and `kdp.print.spine_color` (or `cover.back`)
- Site search ranking: title/heading boost, whole-word preference, pretoc demotion, match-centred excerpts

## [1.0.0] — 2026-09-03

First stable release. CLI command names, documented `papyrus.php` keys, and
default `export/` filenames are SemVer-stable for 1.x. Theme HTML/CSS and
internal PHP APIs may still change in minor releases.

### Added

- `serve` — preview a built site with PHP’s built-in server (`--host`, `--port`, `--build`, `--site`)
- Site popup search (`assets/search.json`), `sitemap.xml`, `robots.txt`, and `##` heading permalinks
- Draft chapters via front matter `draft: true` (omit unless `--include-drafts`)
- `kdp:package` / `kdp --package` — zip enabled KDP artifacts with `KDP-CHECKLIST.txt`
- Print wrap-cover size estimates (`kdp:print`, `kdp:cover --dimensions`, metadata `print.cover`)
- Stronger KDP ebook checks (empty description, cover shortest side &lt; 1600px) and `--require-epubcheck`
- Richer `kdp:metadata` (trim, bleed, presets, artifacts) and `doctor` KDP readiness checks
- `asset:publish --only=themes,css,fonts`
- `watch --with-site` / `--with-sample` (and `--include-drafts`)
- `site.links`, `site.cname`, `site.base_path`; site `404.html`
- Vendor-backed themes/CSS/fonts with optional `asset:publish` into project `assets/`
- HTML/site font embedding; improved `doctor` and `migrate-ibis`

### Upgrade from 0.x

- Empty `assets/` is fine — Papyrus uses bundled stubs until you customize.
- Existing local themes/CSS/fonts in `assets/` still win over stubs.
- `migrate-ibis` only rewrites project `assets/theme*.html` TOC markers, not vendor stubs.
- PHP **8.2+** remains the supported floor through 1.x.

## [0.5.2] — 2026-08

Patch release in the 0.5 line (see Git tags for exact notes).

## [0.5.0] — 2026-08

Site, sample PDF, and KDP-oriented export surface for Markdown books.

## Earlier

See Git tags `v0.1.0` … `v0.5.2` for pre-1.0 history.

[1.4.0]: https://github.com/milon/papyrus/releases/tag/v1.4.0
[1.3.2]: https://github.com/milon/papyrus/releases/tag/v1.3.2
[1.3.1]: https://github.com/milon/papyrus/releases/tag/v1.3.1
[1.3.0]: https://github.com/milon/papyrus/releases/tag/v1.3.0
[1.2.0]: https://github.com/milon/papyrus/releases/tag/v1.2.0
[1.1.0]: https://github.com/milon/papyrus/releases/tag/v1.1.0
[1.0.0]: https://github.com/milon/papyrus/releases/tag/v1.0.0
[0.5.2]: https://github.com/milon/papyrus/releases/tag/v0.5.2
[0.5.0]: https://github.com/milon/papyrus/releases/tag/v0.5.0

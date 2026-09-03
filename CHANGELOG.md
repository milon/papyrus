# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.0]: https://github.com/milon/papyrus/releases/tag/v1.0.0
[0.5.2]: https://github.com/milon/papyrus/releases/tag/v0.5.2
[0.5.0]: https://github.com/milon/papyrus/releases/tag/v0.5.0

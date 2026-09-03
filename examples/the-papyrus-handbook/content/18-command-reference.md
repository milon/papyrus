---
title: Command reference
---

# Command reference

## Build

| Command | Purpose | Notable options |
|---------|---------|-----------------|
| `build` | PDF themes, EPUB, HTML, enabled KDP | `-d`, `-e`, `--with-site`, `--with-sample` |
| `build:pdf` | PDF themes | `--theme`, `--parallel` |
| `build:epub` | EPUB3 | `-d`, `-e` |
| `build:html` | Single-file HTML | `-d`, `-e` |
| `build:site` | Multi-page HTML site | `-d`, `-e` |
| `build:sample` | Sample PDF from `sample.ranges` and/or `sample.chapters` | `--theme` |

## KDP

| Command | Purpose | Notable options |
|---------|---------|-----------------|
| `kdp` | All enabled KDP outputs | `-d`, `-e`, `--require-epubcheck`, `--package` |
| `kdp:ebook` | Kindle EPUB | `--require-epubcheck`; requires `kdp.ebook.enabled` |
| `kdp:print` | Print interior PDF | `--theme`; requires `kdp.print.enabled` |
| `kdp:cover` | Copy cover assets to `export/` | `--dimensions`, `--pages` |
| `kdp:metadata` | Metadata JSON sidecar | no enable flag |
| `kdp:package` | Zip KDP artifacts + checklist | requires enabled outputs + built files |

## Project tooling

| Command | Purpose | Notable options |
|---------|---------|-----------------|
| `init` | Scaffold `papyrus.php`, `content/`, and an empty `assets/` | `--force` |
| `asset:publish` | Publish bundled themes, CSS, and fonts into `assets/` | `--force`, `--only=themes,css,fonts` |
| `doctor` | Validate config, paths, Mermaid, KDP trim | `-d` |
| `sizes` | List page-size presets (+ KDP in-bounds) | (no `-d` / `-e`) |
| `lint` | Lint PHP fences in `content/` | `--fix`, `--max-width=66` |
| `watch` | Rebuild via `build` on file changes | `--interval`, `--with-site`, `--with-sample`, `--include-drafts` |
| `serve` | Preview the generated site in a browser | `--host`, `-p`/`--port`, `--build`, `-s`/`--site` |
| `migrate-ibis` | `ibis.php` → `papyrus.php`; TOC markers in local `assets/theme*.html` only | `--force` |

## Shared book options

Most book commands accept:

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root (contains `papyrus.php`) |
| `--export` | `-e` | `<book>/export` | Override export directory |
| `--include-drafts` | | off | Include chapters with `draft: true` |

Also useful globally: `-v` / `--verbose` (include third-party vendor notices),
`-q` / `--quiet`, `--no-ansi`.

## Output filenames

| Command | Path under export |
|---------|-------------------|
| `build:pdf` | `<slug>-<theme>.pdf` |
| `build:html` | `<slug>.html` |
| `build:epub` | `<slug>.epub` |
| `build:site` | `<slug>-site/` |
| `build:sample` | `sample-<slug>-<theme>.pdf` |
| `kdp:ebook` | `<slug>-kdp.epub` |
| `kdp:print` | `<slug>-kdp-print.pdf` |
| `kdp:cover` | `<slug>-kdp-ebook-cover.*`, `<slug>-kdp-print-cover-<theme>.*` |
| `kdp:metadata` | `<slug>-kdp-metadata.json` |
| `kdp:package` | `<slug>-kdp-package.zip` |

Packagist: [`milon/papyrus`](https://packagist.org/packages/milon/papyrus).
Source: [github.com/milon/papyrus](https://github.com/milon/papyrus).
Releases: [github.com/milon/papyrus/releases](https://github.com/milon/papyrus/releases).

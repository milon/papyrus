---
title: Install and project layout
---

# Install and project layout

## Requirements

**Required**

- PHP 8.2+ with extensions `dom`, `gd`, `mbstring`, `zip`, and `zlib`
- Composer

**Optional**

| Tool                                                         | Used by          | Notes                                                |
|--------------------------------------------------------------|------------------|------------------------------------------------------|
| `mmdc` (`@mermaid-js/mermaid-cli` or Homebrew `mermaid-cli`) | Mermaid diagrams | Needs Chrome/Chromium for Puppeteer                  |
| Chrome or Chromium                                           | Mermaid CLI      | Set `PUPPETEER_EXECUTABLE_PATH` if needed            |
| `epubcheck`                                                  | `kdp:ebook`      | Extra validation; skipped with a warning when absent |

### macOS (Homebrew)

```bash
brew install php composer
brew install mermaid-cli
brew install --cask google-chrome
brew install epubcheck
```

```bash
export PUPPETEER_EXECUTABLE_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
```

### npm / Linux

```bash
npm install -g @mermaid-js/mermaid-cli
# Debian/Ubuntu example:
sudo apt-get install -y chromium-browser
export PUPPETEER_EXECUTABLE_PATH="$(command -v chromium-browser || command -v google-chrome || command -v chromium)"
```

Install [epubcheck](https://github.com/w3c/epubcheck/releases) and put it on
`PATH`, or use `brew install epubcheck` on macOS.

### Check the toolchain

```bash
php -m | grep -E 'dom|gd|mbstring|zip|zlib'
mmdc --version
epubcheck --version
papyrus doctor
```

## Install per project

In your book repository:

```bash
composer require milon/papyrus
```

```bash
vendor/bin/papyrus --version
vendor/bin/papyrus init
vendor/bin/papyrus doctor
```

Wire Composer scripts (example):

```json
{
  "scripts": {
    "build": "papyrus build",
    "build:pdf": "papyrus build:pdf --theme light,dark",
    "build:epub": "papyrus build:epub",
    "build:html": "papyrus build:html",
    "build:site": "papyrus build:site",
    "build:sample": "papyrus build:sample",
    "build:kdp": "papyrus kdp",
    "doctor": "papyrus doctor"
  }
}
```

Package page: [packagist.org/packages/milon/papyrus](https://packagist.org/packages/milon/papyrus).

## Install globally

```bash
composer global require milon/papyrus
export PATH="$(composer global config bin-dir --absolute):$PATH"
papyrus list
```

## Scaffold a book

```bash
papyrus init
papyrus init -d my-book
papyrus init --format=yml
papyrus init --format=yaml
papyrus init --format=json
```

`init` creates a book config (`papyrus.php` by default, or YAML / JSON via
`--format`), `content/`, and an empty `assets/` directory.
Papyrus uses bundled themes, CSS, and fonts by default. Publish those files
into your project only when you want to customize them:

```bash
papyrus asset:publish
papyrus asset:publish --only=themes,css
```

Use `--force` / `-f` to overwrite files during `init`, or with
`asset:publish` to overwrite published assets. `--only` limits publishing to
`themes`, `css`, and/or `fonts`.

## Config without PHP (YAML or JSON)

You still need PHP installed to *run* Papyrus — but you do **not** need to
edit PHP to configure a book. If you are more comfortable with YAML or JSON,
scaffold that way:

```bash
papyrus init --format=yml    # writes papyrus.yml  (--format=yaml is the same)
papyrus init --format=json   # writes papyrus.json
```

Same keys as the PHP stub, without the `<?php return […]` wrapper. Example
YAML:

```yaml
title: My Book
subtitle: A short subtitle
author: Author Name
themes:
  - light
  - dark

document:
  size: crown-quarto
  margin_left: 27
  margin_right: 27
  margin_top: 14
  margin_bottom: 14

toc:
  h1: 0
  h2: 0
  h3: 1

mermaid:
  enabled: true
  format: svg
  theme: auto
```

Or JSON:

```json
{
  "title": "My Book",
  "subtitle": "A short subtitle",
  "author": "Author Name",
  "themes": ["light", "dark"],
  "document": {
    "size": "crown-quarto",
    "margin_left": 27,
    "margin_right": 27,
    "margin_top": 14,
    "margin_bottom": 14
  }
}
```

Rules of thumb:

- Keep **exactly one** config file in the book root (`papyrus.php`,
  `papyrus.yml` / `papyrus.yaml`, or `papyrus.json`).
- `--format=yml` and `--format=yaml` both write `papyrus.yml`.
- You can also name the file `papyrus.yaml` by hand; Papyrus loads either
  extension.
- Full option tables live in the Configuration chapter — they apply to every
  format.
- One PHP-only escape hatch: `configure_commonmark` (a callable). If you need
  that hook, use `papyrus.php`; otherwise YAML/JSON are enough.

Coming from ibis-next, the same choice exists on migrate:

```bash
papyrus migrate-ibis --format=yml\
papyrus migrate-ibis --format=json
```

## Layout

| Path                                       | Role                                               |
|--------------------------------------------|----------------------------------------------------|
| `papyrus.php` / `.yml` / `.yaml` / `.json` | Book settings (exactly one)                        |
| `content/`                                 | Markdown chapters                                  |
| `assets/`                                  | Your overrides: themes, CSS, covers, fonts, banner |
| `export/`                                  | Built artifacts                                    |
| `.papyrus/`                                | Incremental caches                                 |

Only one config file is allowed. See [Config without PHP](#config-without-php-yaml-or-json)
above if you prefer YAML or JSON. Use `papyrus.php` when you need PHP callables
such as `configure_commonmark`.

Always run from the book root, or pass `-d` / `--dir`. Override where
artifacts are written with `-e` / `--export` (default: `<book>/export`):

```bash
papyrus doctor -d /path/to/book
papyrus build --dir /path/to/book
papyrus build:site -d /path/to/book -e /path/to/out
```

Browse the Papyrus source on
[GitHub](https://github.com/milon/papyrus) if you want to follow along with
this handbook’s own project under `examples/the-papyrus-handbook/`.

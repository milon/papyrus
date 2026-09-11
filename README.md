# Papyrus

<p align="center">
  <img src="assets/papyrus-banner.jpg" alt="Papyrus — Site, PDF, EPUB, HTML, and KDP" width="100%">
</p>

PHP CLI for Markdown book projects. One `content/` tree becomes PDF, EPUB, HTML, a multi-page site, sample PDFs, and Amazon KDP deliverables.

Influenced by [ibis-next](https://github.com/Hi-Folks/ibis-next), with first-class extras for sites, Mermaid, multi-script fonts, drafts, and KDP. Book roots use `papyrus.php` (or `.yml` / `.json`), `content/`, and `assets/`. Themes, CSS, and fonts ship bundled; publish them locally only when you need to customize.

**[Handbook](https://papyrus.milon.im/)** · [Packagist](https://packagist.org/packages/milon/papyrus) · [GitHub](https://github.com/milon/papyrus)

## Features

- **Site** — `build:site` + `serve`: sidebar, ranked popup search, heading permalinks, sitemap/robots, light/dark, `404.html` (GitHub Pages / Netlify ready)
- **KDP** — Kindle EPUB, print interior, wraparound cover PDF, metadata, package zip (`kdp` / `kdp:*`)
- **PDF / EPUB / HTML** — light & dark themes, `--parallel` PDF builds, single-file HTML
- **Writing** — Mermaid at build time, draft chapters (`draft: true`), sample PDFs, multi-script fonts
- **Tooling** — `doctor`, `watch`, `lint`, `sizes`, `asset:publish`, `migrate-ibis`
- **Caches** — chapter HTML and Mermaid figures under `.papyrus/`; `-e` / `--export` for CI / `docs/`

## Handbook

The sample book lives in [`examples/the-papyrus-handbook/`](examples/the-papyrus-handbook/). Prebuilt outputs are in `docs/` and on GitHub Pages:

| Format     | Link                                                                                                    |
|------------|---------------------------------------------------------------------------------------------------------|
| Site       | [papyrus.milon.im](https://papyrus.milon.im/)                                                           |
| Downloads  | [PDF previews](https://papyrus.milon.im/19-downloads.html)                                              |
| HTML       | [docs/the-papyrus-handbook.html](docs/the-papyrus-handbook.html)                                        |
| PDF        | [light](docs/the-papyrus-handbook-light.pdf) · [dark](docs/the-papyrus-handbook-dark.pdf)               |
| Sample PDF | [light](docs/sample-the-papyrus-handbook-light.pdf) · [dark](docs/sample-the-papyrus-handbook-dark.pdf) |

```bash
composer build:handbook   # PDF, sample, HTML, and site → docs/
```

## Stability (1.x)

Stable across SemVer majors: CLI command names and common flags, documented config keys, and default `export/` filenames. Theme HTML/CSS and internal PHP APIs may change in minor releases.

## Requirements

- **PHP 8.2+** with `dom`, `gd`, `mbstring`, `zip`, `zlib`
- **Composer**

Optional (skipped with a warning when missing):

| Tool                                                                                | Used by                   |
|-------------------------------------------------------------------------------------|---------------------------|
| `mmdc` ([mermaid-cli](https://github.com/mermaid-js/mermaid-cli)) + Chrome/Chromium | Mermaid diagrams          |
| `epubcheck`                                                                         | Extra KDP EPUB validation |

```bash
# macOS example
brew install php composer mermaid-cli epubcheck
brew install --cask google-chrome
export PUPPETEER_EXECUTABLE_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

papyrus doctor
```

Linux: install Chromium or Chrome, set `PUPPETEER_EXECUTABLE_PATH`, and put [epubcheck](https://github.com/w3c/epubcheck/releases) on `PATH`. Details are in the [install chapter](https://papyrus.milon.im/02-install-and-project.html).

## Install

**Per project (recommended)**

```bash
composer require milon/papyrus
vendor/bin/papyrus init
vendor/bin/papyrus doctor
```

Optional Composer scripts: `"build": "papyrus build"`, `"build:site": "papyrus build:site"`, and so on.

```json
{
  "scripts": {
    "build": "papyrus build",
    "build:pdf": "papyrus build:pdf --theme light,dark",
    "build:epub": "papyrus build:epub",
    "build:html": "papyrus build:html",
    "build:site": "papyrus build:site",
    "build:sample": "papyrus build:sample",
    "build:kdp": "papyrus kdp"
  }
}
```

```bash
composer build
composer build:site
```

### Global CLI

```bash
composer global require milon/papyrus
export PATH="$(composer global config bin-dir --absolute):$PATH"
papyrus list
```

**From this repository**

```bash
composer install
./bin/papyrus list
```

## Quick start

```bash
papyrus init                 # or: --format=yml|yaml|json
papyrus doctor
papyrus build:site
papyrus serve               # http://127.0.0.1:8000/ — needed for site search
```

New folder: `papyrus init -d my-book` then pass `-d my-book` to other commands.

```bash
mkdir my-book && papyrus init -d my-book
papyrus doctor -d my-book
papyrus build:site -d my-book
```

Open `export/<slug>-site/index.html` in a browser, or deploy that folder as a static site.

`init` writes `papyrus.php` by default (`--format=yml` or `json` for
`papyrus.yml` / `papyrus.json`). It also creates an empty `assets/` directory.
To customize the bundled theme or fonts later:

```bash
papyrus asset:publish
papyrus asset:publish --only=themes
```

## Commands

| Command                                                                  | Description                                                                |
|--------------------------------------------------------------------------|----------------------------------------------------------------------------|
| `init`                                                                   | Scaffold config + `content/` + `assets/` (`--format=php\|yml\|yaml\|json`) |
| `asset:publish`                                                          | Copy bundled themes, CSS, fonts into `assets/` (`--only`, `--force`)       |
| `doctor`                                                                 | Validate config, assets, Mermaid, KDP readiness                            |
| `build`                                                                  | PDF / EPUB / HTML / enabled KDP (`--with-site`, `--with-sample`)           |
| `build:pdf`                                                              | PDF themes (`--theme`, `--parallel`)                                       |
| `build:epub`                                                             | EPUB3                                                                      |
| `build:html`                                                             | Single-file HTML                                                           |
| `build:site`                                                             | Multi-page site                                                            |
| `build:sample`                                                           | Sample PDF from ranges and/or chapters                                     |
| `serve`                                                                  | Local site preview (`--host`, `--port`, `--build`, `--site`)               |
| `kdp`                                                                    | All enabled KDP outputs (`--require-epubcheck`, `--package`, `--wrap`)     |
| `kdp:ebook` / `kdp:print` / `kdp:cover` / `kdp:metadata` / `kdp:package` | Individual KDP steps                                                       |
| `sizes`                                                                  | Page-size presets                                                          |
| `migrate-ibis`                                                           | `ibis.php` → Papyrus config (`--format=…`)                                 |
| `lint`                                                                   | Lint PHP fences in `content/` (`--fix`)                                    |
| `watch`                                                                  | Rebuild on change (`--with-site`, `--with-sample`, `--include-drafts`)     |

Shared flags: `-d` / `--dir`, `-e` / `--export`, `--include-drafts`. Full options: [handbook command reference](https://papyrus.milon.im/18-command-reference.html).

```php
$project = Milon\Papyrus\Config\Project::load($bookDir);
$book = $project->bookWithFigures(breakLevel: 1, exportTheme: 'html'); // drafts omitted
$withDrafts = $project->withIncludeDrafts()->bookWithFigures(breakLevel: 1, exportTheme: 'html');
```

## Development

```bash
composer test
composer lint    # Pint --test
composer format  # Pint
```

## Changelog

[CHANGELOG.md](CHANGELOG.md)

## License

MIT — [LICENSE](LICENSE).

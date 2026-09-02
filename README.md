# Papyrus

<p align="center">
  <img src="assets/papyrus-banner.jpg" alt="Papyrus — Markdown to Book (PDF, EPUB, HTML, and KDP)" width="100%">
</p>

PHP CLI for Markdown book projects — PDF, EPUB, HTML, and KDP exports.

Built from scratch with heavy influence from [ibis-next](https://github.com/Hi-Folks/ibis-next). Book projects use `papyrus.php`, `content/`, and `assets/`.

## Requirements

- PHP 8.2+
- Composer
- PHP extensions: `dom`, `gd`, `mbstring`, `zip`, `zlib` (PDF via mPDF; EPUB packaging)
- Node: `@mermaid-js/mermaid-cli` (`mmdc`) when Mermaid diagrams are enabled

## Install (development)

```bash
composer install
```

Run the CLI:

```bash
./bin/papyrus --version
./bin/papyrus list
```

## Quick start

Scaffold a new book in the current directory:

```bash
./bin/papyrus init
./bin/papyrus doctor
```

Or in a new folder:

```bash
mkdir my-book && ./bin/papyrus init -d my-book
./bin/papyrus doctor -d my-book
```

## Commands

| Command        | Description                                                           |
|----------------|-----------------------------------------------------------------------|
| `init`         | Scaffold `papyrus.php`, `content/`, `assets/`                         |
| `doctor`       | Validate config and project paths                                     |
| `build`        | Build all PDF themes, EPUB, HTML, and enabled KDP outputs             |
| `build:pdf`    | Build PDF themes (`--theme light,dark`, `--parallel` for multi-theme) |
| `build:html`   | Build single-file HTML from `assets/theme-html.html`                  |
| `build:epub`   | Build EPUB3 with CSS and embedded images                              |
| `build:sample` | Build sample PDF from `sample.ranges` page ranges                     |
| `kdp`          | Build all enabled KDP outputs (eBook, print, cover, metadata)         |
| `kdp:ebook`    | KDP-ready Kindle EPUB (`export/<slug>-kdp.epub`)                      |
| `kdp:print`    | Print interior PDF with KDP margin/bleed presets                      |
| `kdp:cover`    | Export KDP cover assets to `export/`                                  |
| `kdp:metadata` | Emit KDP metadata sidecar JSON                                        |
| `sizes`        | List KDP page-size presets                                            |
| `migrate-ibis` | Migrate `ibis.php` to `papyrus.php` and update theme TOC markers      |
| `lint`         | Lint PHP code fences in `content/` (`--fix` to auto-fix)              |
| `watch`        | Rebuild on file changes (`--interval` seconds)                        |

Convert Markdown chapters programmatically:

```php
$project = Milon\Papyrus\Config\Project::load($bookDir);
$book = $project->bookConverter()->convertDirectory($project->contentDir);
```

## Tests

```bash
composer test
composer lint   # Pint
composer format # Pint --write
```

CI runs lint, PHPUnit, and a mini-book `papyrus build` smoke on push/PR (see `.github/workflows/ci.yml`). Book repos can copy `stubs/github/workflows/book-build.yml`.

## License

MIT — see [LICENSE](LICENSE).

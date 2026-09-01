# Papyrus

<p align="center">
  <img src="assets/papyrus-banner.jpg" alt="Papyrus — Markdown books to PDF, EPUB, and HTML" width="100%">
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

| Command | Status |
|---------|--------|
| `init` | Scaffold `papyrus.php`, `content/`, `assets/` |
| `doctor` | Validate config and project paths |
| `pdf` | Build PDF themes (`--theme light,dark`, `--parallel` for multi-theme) |
| `html` | Build single-file HTML from `assets/theme-html.html` |
| `epub` | Build EPUB3 with CSS and embedded images |
| `build` | Build all PDF themes, EPUB, HTML, and enabled KDP outputs |
| `sample` | Build sample PDF from `sample.ranges` page ranges |
| `kdp` | Build all enabled KDP outputs (eBook, print, cover, metadata) |
| `kdp:ebook` | KDP-ready Kindle EPUB (`export/<slug>-kdp.epub`) |
| `kdp:print` | Print interior PDF with KDP margin/bleed presets |
| `kdp:cover` | Export KDP cover assets to `export/` |
| `kdp:metadata` | Emit KDP metadata sidecar JSON |
| `sizes` | List KDP page-size presets |
| `migrate-ibis` | Migrate `ibis.php` to `papyrus.php` and update theme TOC markers |
| `lint` | Lint PHP code fences in `content/` (`--fix` to auto-fix) |
| `watch` | Rebuild on file changes (`--interval` seconds) |
| `sort` | Not yet implemented |

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

## License

MIT — see [LICENSE](LICENSE).

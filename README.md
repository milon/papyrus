# Papyrus

<p align="center">
  <img src="assets/papyrus-banner.jpg" alt="Papyrus — Site, PDF, EPUB, HTML, and KDP" width="100%">
</p>

PHP CLI for Markdown book projects — PDF, EPUB, HTML, Hosted Site, and KDP exports.

Built from scratch with heavy influence from [ibis-next](https://github.com/Hi-Folks/ibis-next). Book projects use `papyrus.php`, `content/`, and `assets/`. By default Papyrus uses bundled themes, CSS, and fonts; publish them into your project only when you want to customize them.

## Beyond ibis-next

Same core idea — Markdown chapters to PDF, EPUB, and HTML — plus first-class extras that ibis-next does not ship:

- **Multi-page site** — `build:site` with Home, chapter sidebar, Prev/Next, light/dark mode, banner, and `404.html` (ready for GitHub Pages / Netlify)
- **Amazon KDP** — Kindle EPUB, print interior (bleed + margin presets), cover asset export, metadata JSON (`kdp` / `kdp:*`)
- **Mermaid** — `mermaid` fences rendered at build time for PDF, EPUB, HTML, and site (`theme: auto` embeds light + dark on the web)
- **Sample PDFs** — carve marketing / review PDFs from page ranges and/or whole chapters (`build:sample`)
- **Multi-script fonts** — ordered script → face routing for PDF (e.g. Bengali alongside Latin, Hindi alongside German etc.)
- **Parallel PDF themes** — `build:pdf --parallel` for light + dark in one go
- **Tooling** — `doctor`, `watch`, `serve`, PHP fence `lint`, page-size `sizes`, and `migrate-ibis` from `ibis.php`
- **Caches** — incremental chapter HTML and Mermaid figure caches under `.papyrus/`
- **Export override** — `-e` / `--export` to write artifacts outside the book tree (CI / `docs/`)

See the [handbook](https://papyrus.milon.im/) for the full option set.

## Host your book as a website

`build:site` turns the same Markdown chapters into a multi-page static site you can deploy anywhere (GitHub Pages, Netlify, S3, …):

```bash
papyrus build:site
# → export/<slug>-site/
papyrus serve
# → http://127.0.0.1:8000/  (popup search needs a real HTTP origin)
papyrus serve -s docs/the-papyrus-handbook-site
```

What you get:

- One HTML page per chapter, plus a Home index
- Chapter sidebar (collapsible on mobile)
- Light and dark mode (same palette as single-file HTML)
- Prev / Next navigation between chapters
- Shared `assets/site.css` and `assets/site.js` — no CDN required

Example — this repo’s handbook is hosted on GitHub Pages:

**[Browse The Papyrus Handbook](https://papyrus.milon.im/)**

```bash
papyrus build:site -d examples/the-papyrus-handbook -e docs
```

## Handbook

The sample book **The Papyrus Handbook** lives in [`examples/the-papyrus-handbook/`](examples/the-papyrus-handbook/). Read it online or from the prebuilt exports in `docs/`:

- [Site](https://papyrus.milon.im/) — GitHub Pages (multi-page, sidebar, light/dark mode)
- [Downloads](https://papyrus.milon.im/19-downloads.html) — full and sample PDF previews from GitHub
- [HTML](docs/the-papyrus-handbook.html) — single file, light/dark mode toggle
- [PDF (light)](docs/the-papyrus-handbook-light.pdf) · [PDF (dark)](docs/the-papyrus-handbook-dark.pdf)
- [Sample PDF (light)](docs/sample-the-papyrus-handbook-light.pdf) · [Sample PDF (dark)](docs/sample-the-papyrus-handbook-dark.pdf)

Rebuild those exports with:

```bash
composer build:handbook
```

That runs `build:pdf`, `build:sample`, `build:html`, and `build:site` with
`-d examples/the-papyrus-handbook -e docs`.

## Requirements

**Required**

- PHP 8.2+ with extensions `dom`, `gd`, `mbstring`, `zip`, and `zlib` (PDF via mPDF; EPUB packaging)
- Composer

**Optional** (features work without them; Papyrus skips or warns when missing)

| Tool | Used by | Notes |
|------|---------|--------|
| `@mermaid-js/mermaid-cli` (`mmdc`) | Mermaid diagrams | Needs a Chrome/Chromium binary for Puppeteer |
| Chrome or Chromium | Mermaid CLI | Set `PUPPETEER_EXECUTABLE_PATH` if the bundled browser is missing |
| `epubcheck` | `kdp:ebook` | Extra EPUB validation; skipped with a warning when absent |

### Install optional tooling (macOS / Homebrew)

```bash
brew install php composer
brew install mermaid-cli          # provides mmdc
brew install --cask google-chrome # Puppeteer browser for Mermaid
brew install epubcheck            # optional KDP EPUB checks (pulls OpenJDK)
```

Point Mermaid at system Chrome when needed:

```bash
export PUPPETEER_EXECUTABLE_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
```

### Install optional tooling (npm / Linux)

```bash
# Mermaid CLI (global, or use npx - Papyrus also tries `npx -y @mermaid-js/mermaid-cli`)
npm install -g @mermaid-js/mermaid-cli

# Browser for Puppeteer (pick one)
# Debian/Ubuntu:
sudo apt-get install -y chromium-browser
# or Google Chrome from Google’s .deb

export PUPPETEER_EXECUTABLE_PATH="$(command -v chromium-browser || command -v google-chrome || command -v chromium)"

# epubcheck — Homebrew on macOS, or download from
# https://github.com/w3c/epubcheck/releases and put `epubcheck` on PATH
```

Verify:

```bash
php -m | grep -E 'dom|gd|mbstring|zip|zlib'
mmdc --version
epubcheck --version
papyrus doctor
```

## Install

### Per project (recommended)

In your book repository:

```bash
composer require milon/papyrus
```

Then run via Composer’s binary path:

```bash
vendor/bin/papyrus --version
vendor/bin/papyrus init
```

Or add Composer scripts (example):

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
```

Ensure Composer’s global `bin` directory is on your `PATH` (typical locations: `~/.composer/vendor/bin` or `~/.config/composer/vendor/bin`):

```bash
export PATH="$(composer global config bin-dir --absolute):$PATH"
papyrus --version
papyrus list
```

### From this repository (development)

```bash
composer install
./bin/papyrus --version
./bin/papyrus list
```

## Quick start

Scaffold a new book in the current directory:

```bash
papyrus init
papyrus doctor
papyrus build:site
```

Or in a new folder:

```bash
mkdir my-book && papyrus init -d my-book
papyrus doctor -d my-book
papyrus build:site -d my-book
```

Open `export/<slug>-site/index.html` in a browser, or deploy that folder as a static site.

`init` creates an empty `assets/` directory. To customize the bundled theme or
fonts later:

```bash
papyrus asset:publish
papyrus asset:publish --only=themes
```

## Commands

| Command        | Description                                                           |
|----------------|-----------------------------------------------------------------------|
| `init`         | Scaffold `papyrus.php`, `content/`, and an empty `assets/`            |
| `asset:publish`| Publish bundled themes, CSS, and fonts into `assets/` (`--only`, `--force`) |
| `doctor`       | Validate config and project paths                                     |
| `build`        | Build PDF/EPUB/HTML/KDP; optional `--with-site` / `--with-sample`     |
| `build:pdf`    | Build PDF themes (`--theme light,dark`, `--parallel` for multi-theme) |
| `build:site`   | Build multi-page HTML site with chapter sidebar (light/dark mode)     |
| `serve`        | Serve the site locally with `php -S` (`--host`, `--port`, `--build`, `--site`) |
| `build:html`   | Build single-file HTML from `assets/theme-html.html` (light/dark mode) |
| `build:epub`   | Build EPUB3 with CSS and embedded images                              |
| `build:sample` | Build sample PDF from `sample.ranges` and/or `sample.chapters`        |
| `kdp`          | Build all enabled KDP outputs (eBook, print, cover, metadata)         |
| `kdp:ebook`    | KDP-ready Kindle EPUB (`export/<slug>-kdp.epub`)                      |
| `kdp:print`    | Print interior PDF with KDP margin/bleed presets                      |
| `kdp:cover`    | Export KDP cover assets to `export/`                                  |
| `kdp:metadata` | Emit KDP metadata sidecar JSON                                        |
| `sizes`        | List KDP page-size presets                                            |
| `migrate-ibis` | Migrate `ibis.php` to `papyrus.php`; update TOC markers in local themes |
| `lint`         | Lint PHP code fences in `content/` (`--fix` to auto-fix)              |
| `watch`        | Rebuild on file changes (`--interval`, `--with-site`, `--with-sample`, `--include-drafts`) |

Common options on book commands:

- `-d` / `--dir` — book root (default: current directory)
- `-e` / `--export` — override export directory (default: `<book>/export`)
- `--include-drafts` — include chapters with `draft: true` in front matter

Convert Markdown chapters programmatically:

```php
$project = Milon\Papyrus\Config\Project::load($bookDir);
$book = $project->bookWithFigures(breakLevel: 1, exportTheme: 'html'); // drafts omitted
$withDrafts = $project->withIncludeDrafts()->bookWithFigures(breakLevel: 1, exportTheme: 'html');
```

## Tests

```bash
composer test
composer lint   # Pint
composer format # Pint --write
```

## License

MIT — see [LICENSE](LICENSE).

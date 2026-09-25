---
title: CI for documentation
---

# CI for documentation

Use GitHub Actions (or similar) to run `build:site` on every push and publish the
output to GitHub Pages. You need PHP 8.2+ with `dom`, `gd`, `mbstring`, `zip`,
and `zlib` on the runner — not a PHP *application*, only the runtime.

Pick one install path:

| Project type                         | Install Papyrus with        |
|--------------------------------------|-----------------------------|
| PHP / Composer repo                  | `composer require milon/papyrus` |
| Non-PHP repo (Node, Go, Rust, docs-only, …) | Download `papyrus.phar` from [Releases](https://github.com/milon/papyrus/releases) |

Both routes call the same CLI (`build:site`, `doctor`, …). Neither the Composer
package nor the PHAR bundles Mermaid — see [Mermaid in CI](#mermaid-in-ci) if
your chapters use diagrams.

## Layout tips

- Keep a Papyrus project root with `content/`, `assets/`, and `papyrus.yml` (or
  `.php` / `.json`). For packages, `init --preset=docs -d docs` is a good start.
- Export with `-e docs` so the site lands under `docs/<slug>-site`.
- Set `site.cname` for a custom domain, or `site.base_path` (for example
  `/my-repo`) for project Pages under `username.github.io/my-repo/`.
- Enable Pages in the repo settings: **Source → GitHub Actions** when using the
  `actions/deploy-pages` flow below.

`init --preset=docs` copies a starter workflow to
`github/workflows/docs-site.yml` — move it to `.github/workflows/` and adjust
`-d` / `-e` if needed.

## Composer (PHP projects)

Install Papyrus as a normal dependency, then invoke `vendor/bin/papyrus`.

```bash
composer require milon/papyrus
# pin a version in CI: composer require milon/papyrus:^1.5
```

Example workflow (project at the repo root; export into `docs/`). Includes
Mermaid; drop the Node / Chrome / `mmdc` steps if you do not use diagrams.

```yaml
# .github/workflows/docs-site.yml
name: docs-site

on:
  push:
    branches: [main, master]
  pull_request:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: pages
  cancel-in-progress: true

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5

      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          extensions: dom, gd, mbstring, zip, zlib
          coverage: none

      - uses: actions/setup-node@v5
        with:
          node-version: "22"

      - name: Install Chromium for Mermaid
        id: setup-chrome
        uses: browser-actions/setup-chrome@v2

      - name: Install mermaid-cli
        run: npm install -g @mermaid-js/mermaid-cli

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Build documentation site
        env:
          PUPPETEER_EXECUTABLE_PATH: ${{ steps.setup-chrome.outputs.chrome-path }}
        # Papyrus root in docs/ instead:
        #   vendor/bin/papyrus build:site -d docs -e docs
        run: vendor/bin/papyrus build:site -e docs

      - name: Locate site output
        id: site
        run: echo "dir=$(ls -d docs/*-site | head -n 1)" >> "$GITHUB_OUTPUT"

      - name: Upload Pages artifact
        uses: actions/upload-pages-artifact@v3
        with:
          path: ${{ steps.site.outputs.dir }}

  deploy:
    if: github.event_name == 'push' && (github.ref == 'refs/heads/main' || github.ref == 'refs/heads/master')
    needs: build
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@v4
```

Optional: run `vendor/bin/papyrus doctor` (and `lint`) before `build:site`.

## PHAR (non-PHP projects)

No `composer.json` required. Install PHP on the runner, download the release
PHAR, and run it with `php papyrus.phar …`.

```bash
# Local or CI (pin the tag you want)
curl -fsSL -o papyrus.phar \
  https://github.com/milon/papyrus/releases/download/v1.5.0/papyrus.phar
chmod +x papyrus.phar
php papyrus.phar --version
php papyrus.phar build:site -d docs-src -e docs
```

Example workflow for a docs tree at `docs-src/` (any language monorepo). Same
Mermaid steps as Composer — the PHAR still shells out to `mmdc`.

```yaml
# .github/workflows/docs-site.yml
name: docs-site

on:
  push:
    branches: [main, master]
  pull_request:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: pages
  cancel-in-progress: true

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v5

      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          extensions: dom, gd, mbstring, zip, zlib
          coverage: none

      - uses: actions/setup-node@v5
        with:
          node-version: "22"

      - name: Install Chromium for Mermaid
        id: setup-chrome
        uses: browser-actions/setup-chrome@v2

      - name: Install mermaid-cli
        run: npm install -g @mermaid-js/mermaid-cli

      - name: Download Papyrus PHAR
        env:
          PAPYRUS_VERSION: "1.5.0" # pin; bump when you upgrade
        run: |
          curl -fsSL -o papyrus.phar \
            "https://github.com/milon/papyrus/releases/download/v${PAPYRUS_VERSION}/papyrus.phar"
          chmod +x papyrus.phar
          php papyrus.phar --version

      - name: Build documentation site
        env:
          PUPPETEER_EXECUTABLE_PATH: ${{ steps.setup-chrome.outputs.chrome-path }}
        run: php papyrus.phar build:site -d docs-src -e docs

      - name: Locate site output
        id: site
        run: echo "dir=$(ls -d docs/*-site | head -n 1)" >> "$GITHUB_OUTPUT"

      - name: Upload Pages artifact
        uses: actions/upload-pages-artifact@v3
        with:
          path: ${{ steps.site.outputs.dir }}

  deploy:
    if: github.event_name == 'push' && (github.ref == 'refs/heads/main' || github.ref == 'refs/heads/master')
    needs: build
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@v4
```

Cache the PHAR between jobs if you prefer (`actions/cache` keyed on
`PAPYRUS_VERSION`) — a single download per workflow is usually enough.

## Mermaid in CI

Papyrus renders `mermaid` fences at build time by calling `mmdc`
(`@mermaid-js/mermaid-cli`). That CLI is **not** inside the PHAR or the Composer
package — CI must provide:

1. **Node.js** — so `mmdc` / `npx` can run
2. **Chrome or Chromium** — Puppeteer needs a browser
3. **`PUPPETEER_EXECUTABLE_PATH`** — pointed at that browser binary
4. **`mmdc` on `PATH`** — or let Papyrus fall back to
   `npx -y @mermaid-js/mermaid-cli` when Node is available

Both example workflows above already include these steps. If you have no Mermaid
diagrams (or `mermaid.enabled` is `false`), omit `setup-node`, `setup-chrome`,
`npm install -g @mermaid-js/mermaid-cli`, and the `PUPPETEER_EXECUTABLE_PATH`
env on the build step.

Minimal Mermaid block to splice into any workflow:

```yaml
- uses: actions/setup-node@v5
  with:
    node-version: "22"

- name: Install Chromium for Mermaid
  id: setup-chrome
  uses: browser-actions/setup-chrome@v2

- name: Install mermaid-cli
  run: npm install -g @mermaid-js/mermaid-cli

# On the build step:
#   env:
#     PUPPETEER_EXECUTABLE_PATH: ${{ steps.setup-chrome.outputs.chrome-path }}
```

If `setup-chrome` does not set a path, fall back to a binary on `PATH`:

```bash
if [[ -z "${PUPPETEER_EXECUTABLE_PATH}" ]]; then
  for candidate in google-chrome chrome chromium chromium-browser; do
    if command -v "$candidate" >/dev/null 2>&1; then
      export PUPPETEER_EXECUTABLE_PATH="$(command -v "$candidate")"
      break
    fi
  done
fi
```

Enable Mermaid in `papyrus.php` / `papyrus.yml` (`mermaid.enabled => true`) and
confirm with `papyrus doctor` before relying on CI. Diagrams cache under
`.papyrus/cache/mermaid` — safe to leave uncached in CI, or cache that directory
keyed on content hashes if builds are slow.

This repository’s
[pages.yml](https://github.com/milon/papyrus/blob/master/.github/workflows/pages.yml)
uses the same Chrome + `PUPPETEER_EXECUTABLE_PATH` pattern for the handbook
site.

## Book builds (PDF / EPUB / KDP)

For full book pipelines (not only the docs site), copy
`stubs/github/workflows/book-build.yml` and run `composer build` (Composer
route) or `php papyrus.phar build` (PHAR route). Details for ibis migration and
programmatic use are in [Migration](17-migration-and-ci.html).

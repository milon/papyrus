---
title: Migration
---

# Migration

## Migrating from ibis-next

```bash
papyrus migrate-ibis
papyrus migrate-ibis -d /path/to/book
papyrus migrate-ibis --force
papyrus migrate-ibis --format=yml
```

| Option     | Short | Meaning                                          |
|------------|-------|--------------------------------------------------|
| `--dir`    | `-d`  | Book root                                        |
| `--force`  | `-f`  | Overwrite an existing Papyrus config file        |
| `--format` |       | `php` (default), `yml` / `yaml`, or `json`       |

This writes `papyrus.php` (or `.yml` / `.json`) from `ibis.php`. If you still have custom
`assets/theme*.html` files from ibis, it rewrites `<!-- IBIS:TOC -->` to
`<!-- PAPYRUS:TOC -->` in those **project** files only. Bundled Papyrus
themes (used when `assets/` has no theme override) already use the Papyrus
marker — `migrate-ibis` does not copy or rewrite vendor assets.

YAML/JSON migrations drop `configure_commonmark` (callables need `papyrus.php`).

After migration:

1. Remove `hi-folks/ibis-next` and any Composer patches for ibis/mPDF.
2. Point scripts at `papyrus` (`build`, `build:pdf`, `build:site`, …).
3. Decide on themes: keep your ibis HTML under `assets/` as overrides, or
   delete those copies and use Papyrus defaults (optional
   `papyrus asset:publish` later if you want local copies to edit).
4. Run `papyrus doctor` and a full `papyrus build`.

## Continuous integration

For documentation sites (`build:site` + GitHub Pages), see
[CI for documentation](18-ci-documentation.html) — Composer for PHP repos and
`papyrus.phar` for everything else.

For full book builds (PDF / EPUB / KDP), copy
`stubs/github/workflows/book-build.yml` from the Papyrus package. A typical job
installs PHP with `dom`, `gd`, `mbstring`, `zip`, `zlib`, runs `composer install`
(or downloads the PHAR), then `papyrus doctor` and `papyrus build`.

## Programmatic use

```php
use Milon\Papyrus\Config\Project;

$project = Project::load($bookDir);
$book = $project->bookConverter()->convertDirectory($project->contentDir);
```

## Checklist before release

1. `papyrus doctor` is clean
2. `papyrus build` succeeds for every theme
3. `papyrus build:site` looks right on phone and desktop (try light and dark)
4. Sample ranges still make sense after reflows (`build:sample`)
5. KDP EPUB opens in Kindle Previewer; print PDF matches trim/bleed
6. Pin a Papyrus version in the book’s `composer.json`

Report problems at
[github.com/milon/papyrus/issues](https://github.com/milon/papyrus/issues).

---
title: Migration and CI
---

# Migration and CI

## Migrating from ibis-next

```bash
papyrus migrate-ibis
papyrus migrate-ibis -d /path/to/book
papyrus migrate-ibis --force
```

| Option | Short | Meaning |
|--------|-------|---------|
| `--dir` | `-d` | Book root |
| `--force` | `-f` | Overwrite an existing `papyrus.php` |

This writes `papyrus.php` from `ibis.php` and updates theme TOC markers to
`<!-- PAPYRUS:TOC -->`. After migration:

1. Remove `hi-folks/ibis-next` and any Composer patches for ibis/mPDF.
2. Point scripts at `papyrus` (`build`, `build:pdf`, `build:site`, …).
3. Run `papyrus doctor` and a full `papyrus build`.

## Continuous integration

Copy the stub workflow from the Papyrus package:

`stubs/github/workflows/book-build.yml`

A typical book job installs PHP with `dom`, `gd`, `mbstring`, `zip`, `zlib`,
runs `composer install`, `papyrus doctor`, optional `papyrus lint`, then
`composer build`.

To publish a `build:site` output on GitHub Pages, add a job that runs
`papyrus build:site` and deploys the site directory to a `gh-pages` branch
(see [pages.yml](https://github.com/milon/papyrus/blob/master/.github/workflows/pages.yml)
in this repository for a working example, including Chromium for Mermaid).

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

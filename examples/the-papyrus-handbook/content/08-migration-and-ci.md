---
title: Migration and CI
---

# Migration and CI

## Migrating from ibis-next

```bash
papyrus migrate-ibis
papyrus migrate-ibis -d /path/to/book
```

This writes `papyrus.php` and updates theme TOC markers to
`<!-- PAPYRUS:TOC -->`. After migration:

1. Remove `hi-folks/ibis-next` and any Composer patches for ibis/mPDF.
2. Point scripts at `papyrus` (`build`, `build:pdf`, …).
3. Run `papyrus doctor` and a full `papyrus build`.

## Continuous integration

Copy the stub workflow from the Papyrus package:

`stubs/github/workflows/book-build.yml`

A typical book job installs PHP with `dom`, `gd`, `mbstring`, `zip`, `zlib`,
runs `composer install`, `papyrus doctor`, optional `papyrus lint`, then
`composer build`.

## Programmatic use

```php
use Milon\Papyrus\Config\Project;

$project = Project::load($bookDir);
$book = $project->bookConverter()->convertDirectory($project->contentDir);
```

## Checklist before release

1. `papyrus doctor` is clean
2. `papyrus build` succeeds for every theme
3. Sample ranges still make sense after reflows
4. KDP EPUB opens in Kindle Previewer; print PDF matches trim/bleed
5. HTML export readable on phone and desktop
6. Pin a Papyrus version in the book’s `composer.json`

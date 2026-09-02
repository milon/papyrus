---
title: kdp
---

# kdp

`papyrus kdp` builds every **enabled** Amazon KDP artifact in one shot:
Kindle EPUB (if enabled), print interior PDF (if enabled), cover asset
copies, and the metadata JSON sidecar.

```bash
papyrus kdp
papyrus kdp -d /path/to/book -e /path/to/out
```

Unlike `papyrus build`, this command **fails** when neither
`kdp.ebook.enabled` nor `kdp.print.enabled` is true.

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Output directory |

Print uses the **first** configured theme (same as `kdp:print` without
`--theme`).

## Enable flags

```php
'kdp' => [
    'ebook' => [
        'enabled' => true,
        'cover' => 'cover-ebook.jpg',
    ],
    'print' => [
        'enabled' => true,
        'bleed_mm' => 3,
        'margin_preset' => 'recommended', // or minimum
        'paper' => 'white',               // metadata only (e.g. cream)
    ],
    'metadata' => [
        'description' => 'Bookstore blurb…',
        'keywords' => ['papyrus', 'markdown'],
        'language' => 'en',
    ],
],
```

| Key | Default | Notes |
|-----|---------|-------|
| `ebook.enabled` | `false` | Gates ebook EPUB |
| `ebook.cover` | unset | Asset under `assets/` |
| `print.enabled` | `false` | Gates print PDF |
| `print.bleed_mm` | `3` | Added to trim and each margin |
| `print.margin_preset` | `recommended` | `recommended` or `minimum` |
| `print.paper` | `white` | Recorded in metadata only |
| `metadata.*` | see metadata chapter | Sidecar + EPUB description fallback |

## Typical artifacts

| Artifact | Filename |
|----------|----------|
| Kindle EPUB | `<slug>-kdp.epub` |
| Print PDF | `<slug>-kdp-print.pdf` |
| Ebook cover copy | `<slug>-kdp-ebook-cover.<ext>` |
| Print cover copies | `<slug>-kdp-print-cover-<theme>.<ext>` |
| Metadata | `<slug>-kdp-metadata.json` |

Confirm trim with `papyrus sizes` and Amazon’s current KDP print specs
before uploading. The next chapters document each `kdp:*` command.

---
title: kdp:metadata
---

# kdp:metadata

Emit a JSON sidecar you can keep next to KDP uploads or feed into your own
publishing scripts. Always writes — no enable flag.

```bash
papyrus kdp:metadata
papyrus kdp:metadata -d /path/to/book -e /path/to/out
```

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Output directory |

## Output

```text
export/<slug>-kdp-metadata.json
```

## Config

```php
'language' => 'en',

'kdp' => [
    'print' => [
        'paper' => 'white',
        'bleed_mm' => 3,
        'margin_preset' => 'recommended',
    ],
    'metadata' => [
        'description' => 'Bookstore blurb…',
        'keywords' => ['papyrus', 'markdown', 'ebook'],
        'language' => 'en', // optional; else project language
    ],
],
```

## JSON shape

```json
{
  "title": "…",
  "subtitle": "…",
  "author": "…",
  "language": "en",
  "description": "…",
  "keywords": ["…"],
  "ebook": {
    "enabled": true,
    "cover": "cover-ebook.jpg",
    "artifact": "<slug>-kdp.epub"
  },
  "print": {
    "enabled": true,
    "paper": "white",
    "bleed_mm": 3,
    "margin_preset": "recommended",
    "margin_preset_known": true,
    "trim": {
      "width_mm": 152.4,
      "height_mm": 228.6,
      "width_in": 6.0,
      "height_in": 9.0,
      "preset": "6x9",
      "within_kdp_bounds": true
    },
    "artifact": "<slug>-kdp-print.pdf"
  },
  "artifacts": {
    "ebook": "<slug>-kdp.epub",
    "print": "<slug>-kdp-print.pdf",
    "metadata": "<slug>-kdp-metadata.json",
    "ebook_cover": "<slug>-kdp-ebook-cover.jpg",
    "print_covers": ["<slug>-kdp-print-cover-light.jpg"]
  }
}
```

| Field | Source |
|-------|--------|
| `title` / `subtitle` / `author` | Project identity |
| `language` | `kdp.metadata.language` → `language` → `en` |
| `description` | `kdp.metadata.description` (may be empty) |
| `keywords` | `kdp.metadata.keywords` (string list) |
| `ebook.*` / `print.*` | Enable flags, paper, bleed, margin preset, trim size; optional wrap `cover` estimate when print PDF exists |
| `artifacts.*` | Expected export filenames for uploads |

When `export/<slug>-kdp-print.pdf` exists, `print.cover` includes page count,
spine width, and full wrap dimensions. Rebuild metadata after `kdp:print` to
refresh those numbers.

This file is a helper for your workflow — it is not uploaded automatically
to Amazon. Run `papyrus doctor` to check KDP readiness (covers, margin
preset, epubcheck on `PATH`). Package uploads with `papyrus kdp:package`.

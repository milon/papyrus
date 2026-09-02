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
  "print": {
    "paper": "white",
    "bleed_mm": 3,
    "margin_preset": "recommended"
  }
}
```

| Field | Source |
|-------|--------|
| `title` / `subtitle` / `author` | Project identity |
| `language` | `kdp.metadata.language` → `language` → `en` |
| `description` | `kdp.metadata.description` (may be empty) |
| `keywords` | `kdp.metadata.keywords` (string list) |
| `print.*` | Current `kdp.print` settings |

This file is a helper for your workflow — it is not uploaded automatically
to Amazon.

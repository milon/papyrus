---
title: Samples and KDP
---

# Samples and KDP

## Sample PDF

Carve page ranges from a full theme PDF:

```php
'sample' => [
    'ranges' => [
        ['from' => 1, 'to' => 4],
        ['from' => 10, 'to' => 12],
    ],
],
'sample_notice' => 'This is a sample from My Book.',
```

```bash
papyrus build:sample --theme light
```

Pages are 1-based inclusive ranges against the finished PDF.

## Amazon KDP

```php
'kdp' => [
    'ebook' => [
        'enabled' => true,
        'cover' => 'cover-ebook.jpg',
    ],
    'print' => [
        'enabled' => true,
        'bleed_mm' => 3,
        'margin_preset' => 'recommended',
        'paper' => 'white',
    ],
    'metadata' => [
        'description' => 'Bookstore blurb…',
        'keywords' => ['laravel', 'filament'],
        'language' => 'en',
    ],
],
```

```bash
papyrus kdp
papyrus kdp:ebook
papyrus kdp:print --theme light
papyrus kdp:cover
papyrus kdp:metadata
```

| Command | Typical artifact |
|---------|------------------|
| `kdp:ebook` | `export/<slug>-kdp.epub` |
| `kdp:print` | `export/<slug>-kdp-print.pdf` |
| `kdp:cover` | cover assets under `export/` |
| `kdp:metadata` | `export/<slug>-kdp-metadata.json` |

Confirm trim and margins with `papyrus sizes` and Amazon’s current KDP print
specs before uploading.

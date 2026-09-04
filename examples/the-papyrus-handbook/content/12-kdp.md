---
title: kdp
---

# kdp

`papyrus kdp` builds every **enabled** Amazon KDP artifact in one shot:
Kindle EPUB (if enabled), print interior PDF (if enabled), cover asset
copies, and the metadata JSON sidecar.

```bash
papyrus kdp
papyrus kdp --require-epubcheck
papyrus kdp --package
papyrus kdp --wrap
papyrus kdp -d /path/to/book -e /path/to/out
```

Unlike `papyrus build`, this command **fails** when neither
`kdp.ebook.enabled` nor `kdp.print.enabled` is true.

## Options

| Option                | Short | Default           | Meaning                                    |
|-----------------------|-------|-------------------|--------------------------------------------|
| `--dir`               | `-d`  | current directory | Book root                                  |
| `--export`            | `-e`  | `export/`         | Output directory                           |
| `--require-epubcheck` |       | off               | Fail ebook build if `epubcheck` is missing |
| `--package`           |       | off               | Also write `export/<slug>-kdp-package.zip` |
| `--wrap`              |       | off               | Also generate wraparound cover PDF + PNG   |

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
        'paper' => 'white',               // metadata + spine formula (e.g. cream)
        'back_cover' => 'back-cover.png', // optional wrap artwork
        'spine_color' => '#1a1a1a',       // optional wrap spine fill
    ],
    'metadata' => [
        'description' => 'Bookstore blurb…',
        'keywords' => ['papyrus', 'markdown'],
        'language' => 'en',
    ],
],
```

| Key                   | Default              | Notes                               |
|-----------------------|----------------------|-------------------------------------|
| `ebook.enabled`       | `false`              | Gates ebook EPUB                    |
| `ebook.cover`         | unset                | Asset under `assets/`               |
| `print.enabled`       | `false`              | Gates print PDF                     |
| `print.bleed_mm`      | `3`                  | Added to trim and each margin       |
| `print.margin_preset` | `recommended`        | `recommended` or `minimum`          |
| `print.paper`         | `white`              | Spine formula + metadata (`cream` allowed) |
| `print.back_cover`    | unset                | Optional back panel for `--wrap`           |
| `print.spine_color`   | sample front cover   | Hex fill for spine / generated back        |
| `metadata.*`          | see metadata chapter | Sidecar + EPUB description fallback        |

## Typical artifacts

| Artifact           | Filename                                                        |
|--------------------|-----------------------------------------------------------------|
| Kindle EPUB        | `<slug>-kdp.epub`                                               |
| Print PDF          | `<slug>-kdp-print.pdf`                                          |
| Ebook cover copy   | `<slug>-kdp-ebook-cover.<ext>`                                  |
| Print cover copies | `<slug>-kdp-print-cover-<theme>.<ext>`                          |
| Wrap cover         | `<slug>-kdp-print-wrap.pdf` (+ `.png` preview via `--wrap`)     |
| Metadata           | `<slug>-kdp-metadata.json`                                      |
| Package zip        | `<slug>-kdp-package.zip` (via `kdp:package` or `kdp --package`) |

After `kdp:print`, Papyrus prints a wrap-cover size estimate (spine + full
bleed dimensions). Confirm trim with `papyrus sizes` and Amazon’s current
KDP print specs before uploading.

## Package for upload

After the artifacts exist:

```bash
papyrus kdp:package
# or fold it into the all-in-one run:
papyrus kdp --package
```

That writes `export/<slug>-kdp-package.zip` containing the enabled ebook /
print / cover / metadata files plus a `KDP-CHECKLIST.txt` reminder. It does
not talk to Amazon — upload the zip contents through KDP’s web UI.

The next chapters document each `kdp:*` command.

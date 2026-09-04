---
title: kdp:cover
---

# kdp:cover

Copy configured cover images from `assets/` into `export/` with KDP-oriented
filenames. No enable flag — safe to run anytime.

```bash
papyrus kdp:cover
papyrus kdp:cover -d /path/to/book -e /path/to/out
papyrus kdp:cover --dimensions --pages=220
papyrus kdp:cover --wrap --pages=220
papyrus kdp:cover --wrap --theme=light
```

## Options

| Option         | Short | Default           | Meaning                                             |
|----------------|-------|-------------------|-----------------------------------------------------|
| `--dir`        | `-d`  | current directory | Book root                                           |
| `--export`     | `-e`  | `export/`         | Output directory                                    |
| `--dimensions` |       | off               | Print wrap-cover size estimate (spine + full bleed) |
| `--wrap`       |       | off               | Generate wraparound cover PDF + PNG preview         |
| `--pages`      |       | from print PDF    | Page count for `--dimensions` / `--wrap`            |
| `--theme`      |       | first theme       | Front-cover theme used by `--wrap`                  |

## Sources and outputs

| Source                              | Export name                                            |
|-------------------------------------|--------------------------------------------------------|
| `kdp.ebook.cover` → `assets/<file>` | `<slug>-kdp-ebook-cover.<ext>`                         |
| `cover[<theme>]` or `cover.image`   | `<slug>-kdp-print-cover-<theme>.<ext>` (one per theme) |

Missing source files are skipped (no error). If nothing is copied, the
command still succeeds with a comment.

## Example

```php
'cover' => [
    'image' => 'cover.png',
    'light' => 'cover-light.png',
],
'kdp' => [
    'ebook' => [
        'cover' => 'cover-ebook.jpg',
    ],
],
```

With themes `light` and `dark`, a typical export folder might contain:

```text
export/my-book-kdp-ebook-cover.jpg
export/my-book-kdp-print-cover-light.png
export/my-book-kdp-print-cover-dark.png   # if dark cover resolves
```

Papyrus copies files as-is for ebook/front covers. With `--wrap` it also
composes a **paperback wraparound** PDF (back | spine | front, plus bleed)
using Amazon’s spine formula and your front cover image:

```bash
papyrus kdp:cover --wrap
papyrus kdp:cover --wrap --pages=220
```

Outputs:

```text
export/<slug>-kdp-print-wrap.pdf   # upload to KDP as the cover
export/<slug>-kdp-print-wrap.png   # preview
```

Optional config:

```php
'cover' => [
    'image' => 'cover.png',
    'back' => 'back-cover.png',   // optional; otherwise a text back panel is drawn
],
'kdp' => [
    'print' => [
        'paper' => 'white',       // or cream — affects spine width
        'back_cover' => 'back-cover.png', // preferred over cover.back
        'spine_color' => '#1a1a1a',       // optional; default samples the front cover
    ],
],
```

Without `--pages`, Papyrus counts pages in `export/<slug>-kdp-print.pdf`
(build `kdp:print` first). Spine text is drawn only when Amazon allows it
(≥ 79 pages). Always verify with Amazon’s cover calculator before upload.

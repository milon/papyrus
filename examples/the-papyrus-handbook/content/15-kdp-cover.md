---
title: kdp:cover
---

# kdp:cover

Copy configured cover images from `assets/` into `export/` with KDP-oriented
filenames. No enable flag — safe to run anytime.

```bash
papyrus kdp:cover
papyrus kdp:cover -d /path/to/book -e /path/to/out
```

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Output directory |

## Sources and outputs

| Source | Export name |
|--------|-------------|
| `kdp.ebook.cover` → `assets/<file>` | `<slug>-kdp-ebook-cover.<ext>` |
| `cover[<theme>]` or `cover.image` | `<slug>-kdp-print-cover-<theme>.<ext>` (one per theme) |

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

Papyrus copies files as-is — it does not generate a full wraparound print
cover. Use Amazon’s cover calculator for spine width and bleed on the
print jacket.

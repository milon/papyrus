---
title: kdp:print
---

# kdp:print

Build a **print interior** PDF sized for Amazon KDP: no cover page, KDP
margin presets, and bleed applied around the project trim.

```bash
papyrus kdp:print
papyrus kdp:print --theme light
papyrus kdp:print -t dark -e /path/to/out
```

Requires `kdp.print.enabled => true`.

## Options

| Option     | Short | Default                | Meaning                       |
|------------|-------|------------------------|-------------------------------|
| `--dir`    | `-d`  | current directory      | Book root                     |
| `--export` | `-e`  | `export/`              | Output directory              |
| `--theme`  | `-t`  | first configured theme | PDF theme for interior layout |

## Output

```text
export/<slug>-kdp-print.pdf
```

## Print config

```php
'kdp' => [
    'print' => [
        'enabled' => true,
        'bleed_mm' => 3,
        'margin_preset' => 'recommended', // or 'minimum'
        'paper' => 'white',               // cream, etc. — metadata only
    ],
],
```

### Bleed

Bleed expands the page and margins:

- Page width / height each increase by `2 × bleed_mm`
- Every margin increases by `bleed_mm`

Default `bleed_mm` is `3`.

### Margin presets (mm, before bleed)

| Preset        | Left  | Right | Top   | Bottom |
|---------------|-------|-------|-------|--------|
| `recommended` | 12.7  | 9.525 | 12.7  | 12.7   |
| `minimum`     | 9.525 | 6.35  | 9.525 | 9.525  |

Unknown preset names fall back to `recommended`.

### Paper

`print.paper` is written into the KDP metadata sidecar and used for wrap-cover
spine estimates (`white` or `cream`). It does not change PDF colour or stock —
choose white vs cream in the KDP dashboard to match.

After a successful build, Papyrus prints a wrap-cover estimate from the
interior page count (Amazon’s published inches-per-page formula + 0.125"
bleed). Use `kdp:cover --dimensions` to recalculate later.

## Trim size

Uses the same `document.size` / `document.format` as your regular PDFs.
`papyrus doctor` warns when trim falls outside KDP bounds
(width 101.6–215.9 mm, height 152.4–279.4 mm). List presets with
`papyrus sizes`.

## Cover

The print PDF **omits** the cover page. Export wraparound / ebook cover
files with `kdp:cover`.

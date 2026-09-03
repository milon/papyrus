---
title: build:sample
---

# build:sample

Build a **sample** PDF for marketing downloads or review copies. Select content
with page ranges, whole chapters by filename, or both.

```bash
papyrus build:sample
papyrus build:sample --theme light
papyrus build:sample -t light,dark
```

`papyrus build` does **not** run this by default — pass `--with-sample`, or
call `build:sample` directly.

## Options

| Option     | Short | Default               | Meaning                     |
|------------|-------|-----------------------|-----------------------------|
| `--dir`    | `-d`  | current directory     | Book root                   |
| `--export` | `-e`  | `export/`             | Output directory            |
| `--theme`  | `-t`  | all configured themes | Comma-separated theme names |

## Config

```php
'sample' => [
    'ranges' => [
        ['from' => 1, 'to' => 4],
        ['from' => 10, 'to' => 12],
    ],
    'chapters' => [
        '01-introduction.md',
        '04-writing-content', // stem or basename also works
    ],
    // Optional notice text (also accepted as sample.notice / sample.text):
    // 'notice' => 'This is a sample…',
],

'sample_notice' => 'This is a sample from My Book.',
```

| Key               | Meaning                                                                                                                                                                                 |
|-------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `sample.ranges`   | List of `{from, to}` **1-based inclusive** page ranges against the finished PDF                                                                                                         |
| `sample.chapters` | Chapter files to include in full (config order). Match by source path, basename (`01-intro.md`), or stem (`01-intro`). Rendered as body pages only — no extra cover, title page, or TOC |

| `sample_notice` | Extra notice page (preferred) |
| `sample.notice` / `sample.text` | Fallbacks if `sample_notice` is empty |

Invalid ranges are dropped. Unknown chapter names fail the build.
`build:sample` requires at least one of `ranges` or `chapters`.

When both are set, page-range slices from the full theme PDF come first, then
chapter pages (in config order).

## Output

```text
export/sample-<slug>-<theme>.pdf
```

When a notice is set, it is appended after the selected content.

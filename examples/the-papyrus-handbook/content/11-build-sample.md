---
title: build:sample
---

# build:sample

Carve page ranges from a finished theme PDF into a **sample** PDF — useful
for marketing downloads or review copies.

```bash
papyrus build:sample
papyrus build:sample --theme light
papyrus build:sample -t light,dark
```

`papyrus build` does **not** run this command.

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Output directory |
| `--theme` | `-t` | all configured themes | Comma-separated theme names |

## Config

```php
'sample' => [
    'ranges' => [
        ['from' => 1, 'to' => 4],
        ['from' => 10, 'to' => 12],
    ],
    // Optional notice text (also accepted as sample.notice / sample.text):
    // 'notice' => 'This is a sample…',
],

'sample_notice' => 'This is a sample from My Book.',
```

| Key | Meaning |
|-----|---------|
| `sample.ranges` | List of `{from, to}` **1-based inclusive** page ranges against the finished PDF |
| `sample_notice` | Prefixed notice page (preferred) |
| `sample.notice` / `sample.text` | Fallbacks if `sample_notice` is empty |

Invalid ranges are dropped. `build:sample` requires at least one valid range
or it fails.

## Output

```text
export/sample-<slug>-<theme>.pdf
```

Papyrus builds the full theme PDF first, then extracts the configured
pages. When a notice is set, it appears as the first page of the sample.

---
title: kdp:ebook
---

# kdp:ebook

Build a Kindle-oriented EPUB for Amazon KDP upload.

```bash
papyrus kdp:ebook
papyrus kdp:ebook -d /path/to/book -e /path/to/out
```

Requires `kdp.ebook.enabled => true`.

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Output directory |

## Output

```text
export/<slug>-kdp.epub
```

Papyrus runs an internal KDP-oriented check and, when available on `PATH`,
`epubcheck` (install with `brew install epubcheck`, or from
[w3c/epubcheck](https://github.com/w3c/epubcheck/releases)).

## Cover

```php
'kdp' => [
    'ebook' => [
        'enabled' => true,
        'cover' => 'cover-ebook.jpg', // under assets/
    ],
],
```

If `kdp.ebook.cover` is unset, Papyrus falls back to the light theme cover
(`cover.light` or `cover.image`).

## Metadata

Description falls back to `"{title} - {author}"` when
`kdp.metadata.description` is empty. Language uses
`kdp.metadata.language` when set, otherwise project `language` (default
`en`).

## Versus build:epub

| | `build:epub` | `kdp:ebook` |
|--|--------------|-------------|
| Filename | `<slug>.epub` | `<slug>-kdp.epub` |
| Enable flag | always | `kdp.ebook.enabled` |
| Validation | packaging only | KDP checks + optional epubcheck |
| Cover preference | theme cover | `kdp.ebook.cover` first |

---
title: build:pdf
---

# build:pdf

Build one PDF per theme (or a subset). Each theme needs
`assets/theme-{name}.html` and an entry in `themes`.

```bash
papyrus build:pdf
papyrus build:pdf --theme light
papyrus build:pdf --theme light,dark --parallel
papyrus build:pdf -d /path/to/book -e /path/to/out
```

## Options

| Option       | Short | Default               | Meaning                     |
|--------------|-------|-----------------------|-----------------------------|
| `--dir`      | `-d`  | current directory     | Book root                   |
| `--export`   | `-e`  | `export/`             | Output directory            |
| `--theme`    | `-t`  | all configured themes | Comma-separated theme names |
| `--parallel` | `-p`  | off                   | One process per theme       |

## Output

```text
export/<slug>-<theme>.pdf
```

Example for this handbook: `the-papyrus-handbook-light.pdf`.

## Config that affects PDF

| Key                                 | Role                                  |
|-------------------------------------|---------------------------------------|
| `themes`                            | Which theme names to build by default |
| `document.size` / `document.format` | Trim size                             |
| `document.margin_*`                 | Margins in millimetres                |
| `cover`                             | Cover image(s) under `assets/`        |
| `header.style`                      | Running header CSS                    |
| `toc`                               | Which heading levels enter the TOC    |
| `break_level`                       | Auto page breaks before headings      |
| `fonts`                             | Faces and script routing              |
| `mermaid`                           | Diagram rendering                     |

List trim presets with `papyrus sizes`. Custom trim:

```php
'document' => [
    'format' => [152.4, 228.6], // width_mm, height_mm
],
```

## Parallel builds

`--parallel` / `-p` spawns one PHP process per theme. Useful when
`themes` has both `light` and `dark`. Sequential is the default (and what
`papyrus build` uses).

## Covers

```php
'cover' => [
    'image' => 'cover.png',       // fallback for every theme
    'light' => 'cover-light.png', // optional per-theme
    'dark' => 'cover-dark.png',
    // 'width' / 'height' — mm; default to page size
],
```

Paths are relative to `assets/`. Missing per-theme covers fall back to
`cover.image`.

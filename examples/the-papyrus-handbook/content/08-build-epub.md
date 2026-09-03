---
title: build:epub
---

# build:epub

Build an EPUB3 package from the same Markdown chapters as PDF and HTML.

```bash
papyrus build:epub
papyrus build:epub -d /path/to/book
papyrus build:epub -e /path/to/out
```

## Options

| Option     | Short | Default           | Meaning          |
|------------|-------|-------------------|------------------|
| `--dir`    | `-d`  | current directory | Book root        |
| `--export` | `-e`  | `export/`         | Output directory |

## Output

```text
export/<slug>.epub
```

## What goes in the package

- Chapter HTML derived from `content/`
- `assets/style.css` and optional `highlight.codeblock.min.css`
- Embedded images referenced from chapters
- Cover from `cover` (theme fallback → `cover.image`)
- Metadata from `title`, `subtitle`, `author`, and `language`

## Config that affects EPUB

| Key                           | Role                                       |
|-------------------------------|--------------------------------------------|
| `title`, `subtitle`, `author` | Package identity                           |
| `language`                    | EPUB language (default `en`)               |
| `cover`                       | Cover image under `assets/`                |
| `mermaid`                     | Pre-rendered figures                       |
| `fonts`                       | Faces are PDF-oriented; EPUB relies on CSS |

Prefer simple `pre` / `code` rules in `assets/style.css` for e-ink readers.

## Kindle upload

For Amazon KDP’s Kindle pipeline, prefer `kdp:ebook` (validates and names
the file for upload). `build:epub` is the general-purpose store / archive
build.

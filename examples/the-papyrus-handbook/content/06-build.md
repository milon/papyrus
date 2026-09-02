---
title: build
---

# build

`papyrus build` is the all-in-one export command. From the book root (or with
`-d`), it runs every configured PDF theme, then EPUB, then single-file HTML,
then every **enabled** KDP output.

```bash
papyrus build
papyrus build -d /path/to/book
papyrus build -d examples/the-papyrus-handbook -e docs
```

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root (must contain `papyrus.php`) |
| `--export` | `-e` | `export_dir` from config (`export`) | Where artifacts are written |

Global Symfony options also apply (`-v` / `--verbose`, `-q`, `--no-ansi`, …).
With `-v`, Papyrus prints third-party notices from mPDF and the EPUB zipper.

## What it builds

| Step | Always? | Output |
|------|---------|--------|
| PDF (each `themes` entry) | yes | `export/<slug>-<theme>.pdf` |
| EPUB | yes | `export/<slug>.epub` |
| HTML | yes | `export/<slug>.html` |
| KDP ebook / print / covers / metadata | only if `kdp.ebook.enabled` or `kdp.print.enabled` | see KDP chapters |

Slug comes from `title` (lowercased, non-alphanumeric → `-`).

## What it does *not* build

These need their own commands:

- `build:site` — multi-page static site
- `build:sample` — sample PDF from page ranges

`watch` also invokes `build` only (not site or sample).

## Failures

Each step is independent. If one format fails, Papyrus continues with the
others and exits non-zero if any step failed. When no KDP outputs are
enabled, the KDP block is skipped silently (unlike `papyrus kdp`, which
errors).

## Validate first

```bash
papyrus doctor
papyrus build
```

## Watch mode

Rebuild on changes to `papyrus.php`, `content/`, and `assets/`:

```bash
papyrus watch
papyrus watch --interval 3
```

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--interval` | `-i` | `2` | Poll interval in seconds (minimum `1`) |
| `--dir` / `--export` | `-d` / `-e` | same as `build` | Passed through to each rebuild |

## Caching

Chapter HTML caches under `.papyrus/cache/markdown`. Mermaid figures use
`.papyrus/cache/mermaid`. Delete `.papyrus/` for a cold rebuild.

## Related chapters

The next chapters cover each `build:*` command and every KDP command in turn.

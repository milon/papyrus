---
title: build
---

# build

`papyrus build` is the all-in-one export command. From the book root (or with
`-d`), it runs every configured PDF theme, then EPUB, then single-file HTML,
then every **enabled** KDP output. Site and sample builds are opt-in.

```bash
papyrus build
papyrus build --with-site --with-sample
papyrus build -d /path/to/book
papyrus build -d examples/the-papyrus-handbook -e docs --with-site --with-sample
```

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root (must contain `papyrus.php`) |
| `--export` | `-e` | `export_dir` from config (`export`) | Where artifacts are written |
| `--with-site` | | off | Also run `build:site` |
| `--with-sample` | | off | Also run `build:sample` for every theme |
| `--include-drafts` | | off | Include chapters with `draft: true` |

Global Symfony options also apply (`-v` / `--verbose`, `-q`, `--no-ansi`, …).
With `-v`, Papyrus prints third-party notices from mPDF and the EPUB zipper.

## What it builds

| Step | Always? | Output |
|------|---------|--------|
| PDF (each `themes` entry) | yes | `export/<slug>-<theme>.pdf` |
| EPUB | yes | `export/<slug>.epub` |
| HTML | yes | `export/<slug>.html` |
| KDP ebook / print / covers / metadata | only if `kdp.ebook.enabled` or `kdp.print.enabled` | see KDP chapters |
| Site | only with `--with-site` | `export/<slug>-site/` |
| Sample PDF | only with `--with-sample` | `export/sample-<slug>-<theme>.pdf` |

Slug comes from `title` (lowercased, non-alphanumeric → `-`).

`watch` invokes `build` on each change. Pass `--with-site` and/or
`--with-sample` to include those outputs in every rebuild. Pass
`--include-drafts` to keep `draft: true` chapters in the rebuild.

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
papyrus watch --with-site --with-sample
```

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--interval` | `-i` | `2` | Poll interval in seconds (minimum `1`) |
| `--dir` / `--export` | `-d` / `-e` | same as `build` | Passed through to each rebuild |
| `--with-site` | | off | Also run `build:site` |
| `--with-sample` | | off | Also run `build:sample` |

## Caching

Chapter HTML caches under `.papyrus/cache/markdown`. Mermaid figures use
`.papyrus/cache/mermaid`. Delete `.papyrus/` for a cold rebuild.

## Related chapters

The next chapters cover each `build:*` command and every KDP command in turn.

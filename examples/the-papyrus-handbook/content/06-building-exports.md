---
title: Building exports
---

# Building exports

Validate first, then build.

```bash
papyrus doctor
papyrus build
```

`build` runs every configured PDF theme, then EPUB, HTML, and any enabled KDP
outputs.

## Individual formats

```bash
papyrus build:pdf
papyrus build:pdf --theme light
papyrus build:pdf --theme light,dark --parallel
papyrus build:epub
papyrus build:html
papyrus build:sample
```

| Command | Output (slug from title) |
|---------|--------------------------|
| `build:pdf` | `export/<slug>-<theme>.pdf` |
| `build:epub` | `export/<slug>.epub` |
| `build:html` | `export/<slug>.html` |
| `build:sample` | `export/sample-<slug>-<theme>.pdf` |

`--parallel` / `-p` builds each PDF theme in its own process.

## Watch mode

```bash
papyrus watch
papyrus watch --interval 3
```

## Caching

Chapter HTML caches under `.papyrus/cache/markdown`. Mermaid figures use
`.papyrus/cache/mermaid`. Delete `.papyrus/` for a cold rebuild.

## Vendor noise

mPDF and the EPUB zipper sometimes emit PHP warnings. Papyrus collects
third-party notices and only prints them with `-v` / `--verbose`.

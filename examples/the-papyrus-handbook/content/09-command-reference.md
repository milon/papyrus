---
title: Command reference
---

# Command reference

| Command | Purpose |
|---------|---------|
| `init` | Scaffold `papyrus.php`, `content/`, `assets/` |
| `doctor` | Validate config, paths, and optional Mermaid |
| `build` | All PDF themes, EPUB, HTML, enabled KDP |
| `build:pdf` | PDF themes (`--theme`, `--parallel`) |
| `build:epub` | EPUB3 |
| `build:html` | Single-file HTML |
| `build:sample` | Sample PDF from `sample.ranges` |
| `kdp` | All enabled KDP outputs |
| `kdp:ebook` | Kindle EPUB |
| `kdp:print` | Print interior PDF |
| `kdp:cover` | Cover assets under `export/` |
| `kdp:metadata` | Metadata JSON sidecar |
| `sizes` | List page-size presets |
| `migrate-ibis` | `ibis.php` → `papyrus.php` |
| `lint` | Lint PHP fences (`--fix`) |
| `watch` | Rebuild on changes (`--interval`) |

Common options:

- `-d` / `--dir` — book root (default: current directory)
- `-e` / `--export` — override export directory (default: `<book>/export`)
- `-v` / `--verbose` — include third-party vendor notices

Packagist: `milon/papyrus`. Source:
[github.com/milon/papyrus](https://github.com/milon/papyrus).

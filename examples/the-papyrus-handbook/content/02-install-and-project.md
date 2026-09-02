---
title: Install and project layout
---

# Install and project layout

## Requirements

- PHP 8.2+ with extensions `dom`, `gd`, `mbstring`, `zip`, and `zlib`
- Composer
- Optional: Node.js and `@mermaid-js/mermaid-cli` (`mmdc`) when Mermaid is enabled

## Install per project

In your book repository:

```bash
composer require milon/papyrus
```

```bash
vendor/bin/papyrus --version
vendor/bin/papyrus init
vendor/bin/papyrus doctor
```

Wire Composer scripts (example):

```json
{
  "scripts": {
    "build": "papyrus build",
    "build:pdf": "papyrus build:pdf --theme light,dark",
    "build:epub": "papyrus build:epub",
    "build:html": "papyrus build:html",
    "build:site": "papyrus build:site",
    "build:sample": "papyrus build:sample",
    "build:kdp": "papyrus kdp",
    "doctor": "papyrus doctor"
  }
}
```

Package page: [packagist.org/packages/milon/papyrus](https://packagist.org/packages/milon/papyrus).

## Install globally

```bash
composer global require milon/papyrus
export PATH="$(composer global config bin-dir --absolute):$PATH"
papyrus list
```

## Scaffold a book

```bash
papyrus init
papyrus init -d my-book
```

`init` creates `papyrus.php`, `content/`, and `assets/`. Use `--force` / `-f`
to overwrite.

## Layout

| Path | Role |
|------|------|
| `papyrus.php` | Book settings |
| `content/` | Markdown chapters |
| `assets/` | Themes, CSS, covers, fonts |
| `export/` | Built artifacts |
| `.papyrus/` | Incremental caches |

Always run from the book root, or pass `-d` / `--dir`. Override where
artifacts are written with `-e` / `--export` (default: `<book>/export`):

```bash
papyrus doctor -d /path/to/book
papyrus build --dir /path/to/book
papyrus build:site -d /path/to/book -e /path/to/out
```

Browse the Papyrus source on
[GitHub](https://github.com/milon/papyrus) if you want to follow along with
this handbook’s own project under `examples/the-papyrus-handbook/`.

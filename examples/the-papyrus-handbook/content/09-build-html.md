---
title: build:html
---

# build:html

Build a **single-file** HTML book with light / dark mode.

```bash
papyrus build:html
papyrus build:html -d /path/to/book -e docs
```

## Options

| Option | Short | Default | Meaning |
|--------|-------|---------|---------|
| `--dir` | `-d` | current directory | Book root |
| `--export` | `-e` | `export/` | Output directory |

## Output

```text
export/<slug>.html
```

Requires `assets/theme-html.html`. Placeholders in the theme:

| Placeholder | Source |
|-------------|--------|
| `{{$title}}` | `title` |
| `{{$subtitle}}` | `subtitle` |
| `{{$author}}` | `author` |
| `{{$body}}` | Rendered chapters |

The default stub theme mirrors the light PDF palette, with a moon/sun
toggle that switches to the dark PDF colours. The reading column follows
Tailwind screen widths (640 → 1536px).

`@font-face` URLs that point at `../assets/fonts/` are rewritten at build
time so `-e docs` (or any export override) still finds the book’s fonts.

## Config that affects HTML

| Key | Role |
|-----|------|
| `title`, `subtitle`, `author` | Document chrome |
| `mermaid` | Figures; `theme => auto` embeds light + dark SVGs |
| `fonts` | Loaded via the HTML theme’s `@font-face` rules |

## Site vs HTML

| Command | Result |
|---------|--------|
| `build:html` | One self-contained `.html` file |
| `build:site` | Multi-page folder with sidebar, Home, shared CSS/JS |

Use `build:html` for emailing or archiving a single document; use
`build:site` for hosting.

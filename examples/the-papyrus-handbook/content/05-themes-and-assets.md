---
title: Themes and assets
---

# Themes and assets

Everything visual lives under `assets/`. New projects from `papyrus init` ship
a Filament Playbook–inspired theme: Linux Libertine for body and headings,
0xProto for code, github-gist highlight colors, and notice / tip / caution
asides, plus matching font files under `assets/fonts/`.

## PDF themes

For each name in `themes`, provide `assets/theme-{name}.html`. Split the theme
on the TOC marker:

```html
<!-- PAPYRUS:TOC -->
```

Papyrus writes pretoc chapters, then the TOC, then body chapters around that
marker. A legacy `<!-- IBIS:TOC -->` marker is still recognized after
`migrate-ibis`.

Typical theme responsibilities: `@page` / typography, code blocks, callout
colors, title page, and running header styles (often paired with
`header.style` in config).

## HTML theme

`assets/theme-html.html` is a full HTML document with placeholders
`{{$title}}`, `{{$subtitle}}`, `{{$author}}`, and `{{$body}}`. The default
template mirrors the light PDF theme (same typefaces, colors, code blocks,
and callouts), with a fixed moon/sun icon toggle that switches to the dark
PDF palette. The reading column follows Tailwind screen widths (640 → 1536px).
`@font-face` rules in the theme use `../assets/fonts/`; at build time Papyrus
rewrites those URLs relative to the HTML output directory (so `-e docs` still
finds the book’s fonts).

```bash
papyrus build:html
papyrus build:site
```

`build:site` writes `export/<slug>-site/` with one HTML page per chapter, a
Home index, shared CSS/JS (fonts copied into the site), a collapsible chapter
sidebar, and the same light/dark toggle as the single-file HTML export. That
folder is what this handbook deploys to GitHub Pages.

## EPUB styles

`assets/style.css` (and optional `highlight.codeblock.min.css`) ship inside
the EPUB. Prefer simple `pre`/`code` rules for e-ink readers.

## Covers and fonts

Place cover images and font files under `assets/` (fonts usually in
`assets/fonts/`). Reference them from `cover` and `fonts.faces` in
`papyrus.php`. This handbook’s cover is `assets/cover.jpg`; the wide banner
used on the Welcome page is published from the Papyrus repo at
[assets/papyrus-banner.jpg](https://github.com/milon/papyrus/blob/master/assets/papyrus-banner.jpg).

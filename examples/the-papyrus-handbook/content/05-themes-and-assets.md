---
title: Themes and assets
---

# Themes and assets

Everything visual lives under `assets/`. New projects from `papyrus init` ship
the same PDF theme used by *The Filament Playbook* (Libre Libertine body,
Times headings, 0xProto code, github-gist highlight colors, notice/tip/caution
asides) plus matching font files under `assets/fonts/`.

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
PDF palette. The reading column is `52em` wide (a bit wider than the PDF
measure). `@font-face` rules in the theme use `../assets/fonts/`; at build
time Papyrus rewrites those URLs relative to the HTML output directory (so
`-e docs` still finds the book’s fonts).

```bash
papyrus build:html
```

## EPUB styles

`assets/style.css` (and optional `highlight.codeblock.min.css`) ship inside
the EPUB. Prefer simple `pre`/`code` rules for e-ink readers.

## Covers and fonts

Place cover images and font files under `assets/` (fonts usually in
`assets/fonts/`). Reference them from `cover` and `fonts.faces` in
`papyrus.php`.

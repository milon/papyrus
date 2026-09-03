---
title: Themes and assets
---

# Themes and assets

Everything visual can live under `assets/`, but it does not have to. New
projects from `papyrus init` start with an empty `assets/` directory and use
the bundled Papyrus defaults: Linux Libertine for body and headings, 0xProto
for code, github-gist highlight colors, notice / tip / caution asides, and
matching font files.

Publish the bundled assets into your project when you want to edit them:

```bash
papyrus asset:publish
```

## PDF themes

For each name in `themes`, Papyrus looks for `assets/theme-{name}.html` first,
then falls back to the bundled copy. Split the theme on the TOC marker:

```html
<!-- PAPYRUS:TOC -->
```

Papyrus writes pretoc chapters, then the TOC, then body chapters around that
marker. A legacy `<!-- IBIS:TOC -->` marker is still accepted. `migrate-ibis`
rewrites that marker only in **your** `assets/theme*.html` files, not in the
bundled defaults.

Typical theme responsibilities: `@page` / typography, code blocks, callout
colors, title page, and running header styles (often paired with
`header.style` in config).

## HTML theme

`theme-html.html` is a full HTML document with placeholders
`{{$title}}`, `{{$subtitle}}`, `{{$author}}`, and `{{$body}}`. The default
template mirrors the light PDF theme (same typefaces, colors, code blocks,
and callouts), with a fixed moon/sun icon toggle that switches to the dark
PDF palette. The reading column follows Tailwind screen widths (640 → 1536px).
`@font-face` rules in the theme use `../assets/fonts/`. For `build:html`,
Papyrus embeds those font files as base64 data URIs so the export is
self-contained. For `build:site`, fonts are copied into the site
`assets/fonts/` folder.

```bash
papyrus build:html
papyrus build:site
```

`build:site` writes `export/<slug>-site/` with one HTML page per chapter, a
Home index, shared CSS/JS (fonts copied into the site), a collapsible chapter
sidebar, and the same light/dark toggle as the single-file HTML export. That
folder is what this handbook deploys to GitHub Pages.

## EPUB styles

`style.css` (and optional `highlight.codeblock.min.css`) are loaded from your
project `assets/` first, then from Papyrus’s bundled defaults, and ship inside
the EPUB. Prefer simple `pre`/`code` rules for e-ink readers.

## Covers and fonts

Place cover images and font files under `assets/` (fonts usually in
`assets/fonts/`). Reference them from `cover` and `fonts.faces` in
`papyrus.php`.

This handbook keeps only book-specific assets in `assets/` — `cover.jpg` for
the PDF cover and `banner.jpg` for the site home page. Themes, CSS, and fonts
come from Papyrus’s bundled defaults; run `papyrus asset:publish` when you want
local copies to customize. The wide banner image in the Papyrus repo README lives
at [assets/papyrus-banner.jpg](https://github.com/milon/papyrus/blob/master/assets/papyrus-banner.jpg).
